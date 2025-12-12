<?php

namespace App\Controller\Api;

use App\Entity\BuybackRequest;
use App\Service\BuybackCalculator;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/api')]
class BuybackRequestController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BuybackCalculator $calculator,
        private EmailService $emailService,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    /**
     * 🔍 Recherche de modèles pour l'autocomplete
     */
    #[Route('/buyback/search-models', name: 'api_buyback_search_models', methods: ['GET'])]
    public function searchModels(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $category = $request->query->get('category');
        $brand = $request->query->get('brand');

        if (strlen($query) < 2) {
            return $this->json([
                'results' => [],
                'message' => 'Tapez au moins 2 caractères',
            ]);
        }

        try {
            $results = $this->calculator->searchModels($query, $category, $brand);

            return $this->json([
                'success' => true,
                'results' => $results,
                'count' => count($results),
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 📝 Création d'une demande de rachat
     */
    #[Route('/buyback-requests', name: 'api_buyback_request_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json(['success' => false, 'error' => 'Données invalides'], 400);
            }

            // Validation des champs obligatoires
            $requiredFields = [
                'category', 'brand', 'purchaseYear', 'functionalCondition',
                'aestheticCondition', 'firstName', 'lastName', 'email',
                'phone', 'address', 'postalCode', 'city', 'paymentMethod'
            ];

            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return $this->json([
                        'success' => false,
                        'error' => "Le champ $field est obligatoire"
                    ], 400);
                }
            }

            // Validation email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->json(['success' => false, 'error' => 'Email invalide'], 400);
            }

            // Validation IBAN si virement
            if ($data['paymentMethod'] === 'virement') {
                if (empty($data['iban'])) {
                    return $this->json([
                        'success' => false,
                        'error' => 'IBAN requis pour un virement'
                    ], 400);
                }

                $iban = str_replace(' ', '', strtoupper($data['iban']));
                if (!preg_match('/^FR[0-9]{25}$/', $iban)) {
                    return $this->json([
                        'success' => false,
                        'error' => 'Format IBAN invalide (doit commencer par FR et contenir 27 caractères)'
                    ], 400);
                }
            }

            // Validation photos (minimum 2)
            $photoCount = 0;
            if (!empty($data['photo1'])) $photoCount++;
            if (!empty($data['photo2'])) $photoCount++;
            if (!empty($data['photo3'])) $photoCount++;

            if ($photoCount < 2) {
                return $this->json([
                    'success' => false,
                    'error' => 'Minimum 2 photos requises'
                ], 400);
            }

            // Calcul de l'estimation
            $estimatedPrice = $this->calculator->calculatePrice($data);

            // Créer l'entité
            $buybackRequest = new BuybackRequest();
            $buybackRequest->setCategory($data['category']);
            $buybackRequest->setBrand($data['brand']);
            $buybackRequest->setModel($data['model'] ?? null);
            $buybackRequest->setSerialNumber($data['serialNumber'] ?? null);
            $buybackRequest->setPurchaseYear($data['purchaseYear']);
            $buybackRequest->setHasInvoice($data['hasInvoice'] ?? false);
            $buybackRequest->setFunctionalCondition($data['functionalCondition']);
            $buybackRequest->setAestheticCondition($data['aestheticCondition']);
            $buybackRequest->setHasAllAccessories($data['hasAllAccessories'] ?? true);
            $buybackRequest->setDefectsDescription($data['defectsDescription'] ?? null);
            $buybackRequest->setPhoto1($data['photo1'] ?? null);
            $buybackRequest->setPhoto2($data['photo2'] ?? null);
            $buybackRequest->setPhoto3($data['photo3'] ?? null);
            $buybackRequest->setFirstName($data['firstName']);
            $buybackRequest->setLastName($data['lastName']);
            $buybackRequest->setEmail($data['email']);
            $buybackRequest->setPhone($data['phone']);
            $buybackRequest->setAddress($data['address']);
            $buybackRequest->setPostalCode($data['postalCode']);
            $buybackRequest->setCity($data['city']);
            $buybackRequest->setPaymentMethod($data['paymentMethod']);
            $buybackRequest->setIban($data['iban'] ?? null);
            $buybackRequest->setEstimatedPrice($estimatedPrice);
            $buybackRequest->setStatus('pending');

            // Sauvegarder
            $this->entityManager->persist($buybackRequest);
            $this->entityManager->flush();

            // ✅ ENVOI DES EMAILS
            try {
                // Email au client
                $this->emailService->sendBuybackRequestClientConfirmation($buybackRequest);

                // Email à l'admin avec lien vers la demande
                $viewUrl = $this->urlGenerator->generate(
                    'admin_buyback_request_show',
                    ['id' => $buybackRequest->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                $this->emailService->sendBuybackRequestAdminNotification($buybackRequest, $viewUrl);

            } catch (\Exception $e) {
                // Log l'erreur mais ne bloque pas la création
                // Le système de notification dashboard prendra le relais
                error_log('Erreur envoi email rachat: ' . $e->getMessage());
            }

            return $this->json([
                'success' => true,
                'message' => 'Demande de rachat enregistrée avec succès',
                'request_id' => $buybackRequest->getId(),
                'estimated_price' => $estimatedPrice,
                'formatted_price' => number_format($estimatedPrice, 0, ',', ' ') . ' €',
            ], 201);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Une erreur est survenue: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 💰 Estimation rapide sans sauvegarde
     */
    #[Route('/buyback-estimate', name: 'api_buyback_estimate', methods: ['POST'])]
    public function estimate(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json(['success' => false, 'error' => 'Données invalides'], 400);
            }

            $estimatedPrice = $this->calculator->calculatePrice($data);

            return $this->json([
                'success' => true,
                'estimated_price' => $estimatedPrice,
                'formatted_price' => number_format($estimatedPrice, 0, ',', ' ') . ' €',
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors du calcul: ' . $e->getMessage()
            ], 500);
        }
    }
}
