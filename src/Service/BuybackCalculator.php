<?php

namespace App\Service;

class BuybackCalculator
{
    // ============================================
    // PRIX DE BASE PAR CATÉGORIE ET MARQUE
    // ============================================

    private const BASE_PRICES = [
        'lave-linge' => [
            'premium' => 450,  // Miele, Bosch, Siemens
            'standard' => 225, // Samsung, LG, Whirlpool, Electrolux, AEG
            'entry' => 115,    // Candy, Beko, Indesit, Brandt, Faure
            'brands' => [
                'premium' => ['miele', 'bosch', 'siemens'],
                'standard' => ['samsung', 'lg', 'whirlpool', 'electrolux', 'aeg'],
                'entry' => ['candy', 'beko', 'indesit', 'brandt', 'faure'],
            ]
        ],
        'refrigerateur' => [
            'premium' => 600,
            'standard' => 325,
            'entry' => 150,
            'brands' => [
                'premium' => ['miele', 'liebherr', 'smeg'],
                'standard' => ['samsung', 'lg', 'bosch', 'siemens', 'whirlpool'],
                'entry' => ['beko', 'candy', 'indesit', 'haier'],
            ]
        ],
        'four' => [
            'premium' => 400,
            'standard' => 225,
            'entry' => 115,
            'brands' => [
                'premium' => ['siemens', 'miele', 'neff', 'smeg'],
                'standard' => ['bosch', 'electrolux', 'whirlpool', 'hotpoint'],
                'entry' => ['beko', 'candy', 'indesit', 'brandt'],
            ]
        ],
        'lave-vaisselle' => [
            'premium' => 350,
            'standard' => 185,
            'entry' => 95,
            'brands' => [
                'premium' => ['miele', 'bosch', 'siemens'],
                'standard' => ['samsung', 'whirlpool', 'electrolux', 'aeg'],
                'entry' => ['beko', 'candy', 'indesit', 'brandt'],
            ]
        ],
        'seche-linge' => [
            'premium' => 425,
            'standard' => 225,
            'entry' => 115,
            'brands' => [
                'premium' => ['miele', 'bosch', 'siemens'],
                'standard' => ['samsung', 'lg', 'whirlpool', 'electrolux'],
                'entry' => ['beko', 'candy', 'indesit'],
            ]
        ],
        'cuisiniere' => [
            'premium' => 650,
            'standard' => 300,
            'entry' => 150,
            'brands' => [
                'premium' => ['smeg', 'lacanche', 'piano'],
                'standard' => ['bosch', 'electrolux', 'whirlpool', 'sauter'],
                'entry' => ['beko', 'indesit', 'brandt'],
            ]
        ],
        'micro-ondes' => [
            'premium' => 115,
            'standard' => 60,
            'entry' => 30,
            'brands' => [
                'premium' => ['miele', 'siemens'],
                'standard' => ['samsung', 'lg', 'whirlpool', 'panasonic'],
                'entry' => ['candy', 'severin', 'continental edison'],
            ]
        ],
        'cave-a-vin' => [
            'premium' => 500,
            'standard' => 225,
            'entry' => 115,
            'brands' => [
                'premium' => ['liebherr', 'la sommeliere', 'avintage'],
                'standard' => ['samsung', 'haier', 'candy'],
                'entry' => ['klarstein', 'continental edison'],
            ]
        ],
        'hotte' => [
            'premium' => 250,
            'standard' => 125,
            'entry' => 60,
            'brands' => [
                'premium' => ['siemens', 'miele', 'neff'],
                'standard' => ['bosch', 'electrolux', 'whirlpool'],
                'entry' => ['beko', 'candy', 'indesit'],
            ]
        ],
        'petit-electromenager' => [
            'premium' => 100,
            'standard' => 50,
            'entry' => 25,
            'brands' => [
                'premium' => ['kitchenaid', 'magimix', 'smeg'],
                'standard' => ['moulinex', 'philips', 'tefal', 'delonghi'],
                'entry' => ['russell hobbs', 'tristar', 'continental edison'],
            ]
        ],
    ];

    // ============================================
    // COEFFICIENTS D'AJUSTEMENT
    // ============================================

    private const YEAR_COEFFICIENTS = [
        '2024-2025' => 1.00,   // Récent
        '2022-2023' => 0.85,   // 2-3 ans
        '2020-2021' => 0.70,   // 4-5 ans
        '2018-2019' => 0.55,   // 6-7 ans
        '2015-2017' => 0.40,   // 8-10 ans
        'avant-2015' => 0.25,  // Plus de 10 ans
    ];

    private const FUNCTIONAL_STATE_COEFFICIENTS = [
        'parfait' => 1.00,         // Fonctionne parfaitement
        'panne-legere' => 0.60,    // Petite panne réparable
        'hors-service' => 0.20,    // Ne fonctionne plus
        'pieces' => 0.10,          // Pour pièces détachées uniquement
    ];

    private const AESTHETIC_STATE_COEFFICIENTS = [
        'tres-bon' => 1.00,        // Comme neuf
        'bon' => 0.85,             // Traces d'usage légères
        'usage' => 0.65,           // Rayures, bosses visibles
        'tres-usage' => 0.40,      // Très abîmé
    ];

    private const INVOICE_BONUS = 1.10;           // +10% si facture
    private const INCOMPLETE_ACCESSORIES_MALUS = 0.90;  // -10% si accessoires manquants

    // ============================================
    // CALCUL PRINCIPAL
    // ============================================

    /**
     * Calcule le prix de rachat estimé
     *
     * @param array $data Données du formulaire
     * @return array ['min' => int, 'max' => int, 'details' => array]
     */
    public function calculate(array $data): array
    {
        // 1. Prix de base selon catégorie et marque
        $basePrice = $this->getBasePrice($data['category'], $data['brand']);

        // 2. Appliquer le coefficient d'année
        $yearCoefficient = self::YEAR_COEFFICIENTS[$data['purchaseYear']] ?? 0.25;

        // 3. Appliquer le coefficient d'état fonctionnel
        $functionalCoefficient = self::FUNCTIONAL_STATE_COEFFICIENTS[$data['functionalState']] ?? 1.0;

        // 4. Appliquer le coefficient d'état esthétique
        $aestheticCoefficient = self::AESTHETIC_STATE_COEFFICIENTS[$data['aestheticState']] ?? 1.0;

        // 5. Bonus facture
        $invoiceMultiplier = ($data['hasInvoice'] ?? false) ? self::INVOICE_BONUS : 1.0;

        // 6. Malus accessoires incomplets
        $accessoriesMultiplier = ($data['hasAllAccessories'] ?? true) ? 1.0 : self::INCOMPLETE_ACCESSORIES_MALUS;

        // Calcul final
        $estimatedPrice = $basePrice
            * $yearCoefficient
            * $functionalCoefficient
            * $aestheticCoefficient
            * $invoiceMultiplier
            * $accessoriesMultiplier;

        // Fourchette de prix (-10% / +10%)
        $minPrice = (int) floor($estimatedPrice * 0.90);
        $maxPrice = (int) ceil($estimatedPrice * 1.10);

        // Détails du calcul pour affichage
        $details = [
            'base_price' => $basePrice,
            'category_label' => $this->getCategoryLabel($data['category']),
            'brand' => ucfirst($data['brand']),
            'year_coefficient' => $yearCoefficient,
            'year_label' => $this->getYearLabel($data['purchaseYear']),
            'functional_coefficient' => $functionalCoefficient,
            'functional_label' => $this->getFunctionalStateLabel($data['functionalState']),
            'aesthetic_coefficient' => $aestheticCoefficient,
            'aesthetic_label' => $this->getAestheticStateLabel($data['aestheticState']),
            'has_invoice' => $data['hasInvoice'] ?? false,
            'invoice_bonus' => $invoiceMultiplier > 1.0,
            'has_all_accessories' => $data['hasAllAccessories'] ?? true,
            'accessories_malus' => $accessoriesMultiplier < 1.0,
            'estimated_price' => (int) round($estimatedPrice),
        ];

        return [
            'min' => $minPrice,
            'max' => $maxPrice,
            'details' => $details,
        ];
    }

    // ============================================
    // MÉTHODES PRIVÉES
    // ============================================

    /**
     * Obtient le prix de base selon la catégorie et la marque
     */
    private function getBasePrice(string $category, string $brand): int
    {
        if (!isset(self::BASE_PRICES[$category])) {
            return 100; // Prix par défaut
        }

        $brandLower = strtolower(trim($brand));
        $categoryData = self::BASE_PRICES[$category];
        $brands = $categoryData['brands'] ?? [];

        // Vérifier dans quelle gamme se trouve la marque
        if (in_array($brandLower, $brands['premium'] ?? [])) {
            return $categoryData['premium'];
        }

        if (in_array($brandLower, $brands['standard'] ?? [])) {
            return $categoryData['standard'];
        }

        if (in_array($brandLower, $brands['entry'] ?? [])) {
            return $categoryData['entry'];
        }

        // Par défaut, retourner le prix standard
        return $categoryData['standard'] ?? 150;
    }

    private function getCategoryLabel(string $category): string
    {
        return match($category) {
            'lave-linge' => 'Lave-linge',
            'refrigerateur' => 'Réfrigérateur',
            'four' => 'Four',
            'lave-vaisselle' => 'Lave-vaisselle',
            'seche-linge' => 'Sèche-linge',
            'micro-ondes' => 'Micro-ondes',
            'cuisiniere' => 'Cuisinière',
            'cave-a-vin' => 'Cave à vin',
            'hotte' => 'Hotte',
            'petit-electromenager' => 'Petit électroménager',
            default => ucfirst($category)
        };
    }

    private function getYearLabel(string $year): string
    {
        return match($year) {
            '2024-2025' => 'Moins de 2 ans',
            '2022-2023' => '2-3 ans',
            '2020-2021' => '4-5 ans',
            '2018-2019' => '6-7 ans',
            '2015-2017' => '8-10 ans',
            'avant-2015' => 'Plus de 10 ans',
            default => 'Inconnu'
        };
    }

    private function getFunctionalStateLabel(string $state): string
    {
        return match($state) {
            'parfait' => 'Fonctionne parfaitement',
            'panne-legere' => 'Panne légère',
            'hors-service' => 'Hors service',
            'pieces' => 'Pour pièces détachées',
            default => 'Inconnu'
        };
    }

    private function getAestheticStateLabel(string $state): string
    {
        return match($state) {
            'tres-bon' => 'Très bon état (comme neuf)',
            'bon' => 'Bon état (traces légères)',
            'usage' => 'Usagé (rayures visibles)',
            'tres-usage' => 'Très usagé (très abîmé)',
            default => 'Inconnu'
        };
    }

    /**
     * Obtient la liste des marques par niveau de gamme pour une catégorie
     */
    public function getBrandsByTier(string $category): array
    {
        if (!isset(self::BASE_PRICES[$category])) {
            return [];
        }

        return [
            'premium' => array_keys(self::BASE_PRICES[$category]['premium'])[0] ?? [],
            'standard' => array_keys(self::BASE_PRICES[$category]['standard'])[0] ?? [],
            'entry' => array_keys(self::BASE_PRICES[$category]['entry'])[0] ?? [],
        ];
    }

    /**
     * Vérifie si une marque est reconnue pour une catégorie
     */
    public function isBrandRecognized(string $category, string $brand): bool
    {
        $brandLower = strtolower(trim($brand));
        $brands = $this->getBrandsByTier($category);

        return in_array($brandLower, $brands['premium'])
            || in_array($brandLower, $brands['standard'])
            || in_array($brandLower, $brands['entry']);
    }
}
