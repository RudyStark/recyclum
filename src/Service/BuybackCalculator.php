<?php

namespace App\Service;

use App\Repository\ApplianceModelRepository;

class BuybackCalculator
{
    private ApplianceModelRepository $modelRepository;

    public function __construct(ApplianceModelRepository $modelRepository)
    {
        $this->modelRepository = $modelRepository;
    }

    /**
     * Calcule le prix de rachat estimé
     */
    public function calculatePrice(array $data): int
    {
        // 1. Recherche du modèle exact dans la base
        $modelReference = $data['model'] ?? null;
        $category = $data['category'] ?? null;
        $brand = $data['brand'] ?? null;

        $basePrice = 0;
        $usedModel = false;

        if ($modelReference) {
            $applianceModel = $this->modelRepository->findByModelReference($modelReference);

            if ($applianceModel) {
                // ✅ MODÈLE TROUVÉ : Calcul basé sur la gamme
                $basePrice = $this->getBasePriceByTier($applianceModel->getTier(), $category);
                $usedModel = true;
            }
        }

        // Fallback : calcul par marque si modèle non trouvé
        if (!$usedModel) {
            $basePrice = $this->getBasePriceByBrand($brand, $category);
        }

        // 2. Application des coefficients
        $yearCoefficient = $this->getYearCoefficient($data['purchaseYear'] ?? date('Y'));
        $functionalCoefficient = $this->getFunctionalConditionCoefficient($data['functionalCondition'] ?? 'working');
        $aestheticCoefficient = $this->getAestheticConditionCoefficient($data['aestheticCondition'] ?? 'good');

        // 3. Calcul final
        $price = $basePrice * $yearCoefficient * $functionalCoefficient * $aestheticCoefficient;

        // 4. Bonus/Malus
        if (!empty($data['hasInvoice'])) {
            $price *= 1.10; // +10% si facture
        }

        if (empty($data['hasAllAccessories'])) {
            $price *= 0.90; // -10% si accessoires manquants
        }

        return (int) round($price);
    }

    /**
     * Prix de base selon la gamme (tier) du modèle
     */
    private function getBasePriceByTier(string $tier, string $category): int
    {
        $prices = [
            'premium' => [
                'lave-linge' => 450,
                'refrigerateur' => 650,
                'four' => 400,
                'lave-vaisselle' => 400,
                'seche-linge' => 350,
                'cuisiniere' => 500,
                'micro-ondes' => 120,
                'cave-a-vin' => 350,
                'hotte' => 200,
                'petit-electromenager' => 80,
            ],
            'standard' => [
                'lave-linge' => 250,
                'refrigerateur' => 325,
                'four' => 220,
                'lave-vaisselle' => 220,
                'seche-linge' => 200,
                'cuisiniere' => 280,
                'micro-ondes' => 70,
                'cave-a-vin' => 200,
                'hotte' => 120,
                'petit-electromenager' => 50,
            ],
            'entry' => [
                'lave-linge' => 120,
                'refrigerateur' => 150,
                'four' => 110,
                'lave-vaisselle' => 110,
                'seche-linge' => 95,
                'cuisiniere' => 130,
                'micro-ondes' => 40,
                'cave-a-vin' => 100,
                'hotte' => 60,
                'petit-electromenager' => 25,
            ],
        ];

        return $prices[$tier][$category] ?? 100;
    }

    /**
     * Prix de base selon la marque (fallback)
     */
    private function getBasePriceByBrand(string $brand, string $category): int
    {
        $brandTiers = [
            'premium' => ['miele', 'bosch', 'siemens', 'liebherr', 'smeg'],
            'standard' => ['samsung', 'lg', 'whirlpool', 'electrolux', 'aeg', 'brandt'],
            'entry' => ['beko', 'candy', 'indesit', 'hotpoint', 'haier'],
        ];

        $tier = 'standard'; // Par défaut
        $brandLower = strtolower($brand);

        foreach ($brandTiers as $t => $brands) {
            if (in_array($brandLower, $brands)) {
                $tier = $t;
                break;
            }
        }

        return $this->getBasePriceByTier($tier, $category);
    }

    /**
     * Coefficient selon l'année d'achat
     */
    private function getYearCoefficient(int $purchaseYear): float
    {
        $currentYear = (int) date('Y');
        $age = $currentYear - $purchaseYear;

        if ($age < 0) $age = 0; // Sécurité
        if ($age > 15) $age = 15; // Max 15 ans

        $coefficients = [
            0 => 1.00,  // < 1 an
            1 => 0.95,
            2 => 0.85,
            3 => 0.75,
            4 => 0.65,
            5 => 0.55,
            6 => 0.50,
            7 => 0.45,
            8 => 0.40,
            9 => 0.35,
            10 => 0.30,
            11 => 0.28,
            12 => 0.26,
            13 => 0.24,
            14 => 0.22,
            15 => 0.20,
        ];

        return $coefficients[$age] ?? 0.20;
    }

    /**
     * Coefficient selon l'état fonctionnel
     */
    private function getFunctionalConditionCoefficient(string $condition): float
    {
        return match($condition) {
            'perfect' => 1.00,      // Parfait état
            'working' => 0.85,      // Fonctionne bien
            'minor_issues' => 0.60, // Petits problèmes
            'major_issues' => 0.30, // Gros problèmes
            'not_working' => 0.10,  // Ne fonctionne pas
            default => 0.85,
        };
    }

    /**
     * Coefficient selon l'état esthétique
     */
    private function getAestheticConditionCoefficient(string $condition): float
    {
        return match($condition) {
            'excellent' => 1.00,    // Comme neuf
            'good' => 0.85,         // Bon état
            'fair' => 0.70,         // État correct
            'poor' => 0.50,         // Usagé
            'very_poor' => 0.40,    // Très usagé
            default => 0.85,
        };
    }

    /**
     * Recherche un modèle pour l'autocomplete
     */
    public function searchModels(string $query, ?string $category = null, ?string $brand = null): array
    {
        $models = $this->modelRepository->searchModels($query, $category, 10);

        $results = [];
        foreach ($models as $model) {
            // Filtrer par marque si fournie
            if ($brand && strtolower($model->getBrand()) !== strtolower($brand)) {
                continue;
            }

            $results[] = [
                'id' => $model->getId(),
                'reference' => $model->getModelReference(),
                'name' => $model->getModelName(),
                'fullName' => $model->getFullName(),
                'year' => $model->getReleaseYear(),
                'tier' => $model->getTier(),
                'tierLabel' => $model->getTierLabel(),
            ];
        }

        return $results;
    }
}
