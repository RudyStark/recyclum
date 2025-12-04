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

#[Route('/api')]
class BuybackRequestController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BuybackCalculator $calculator,
        private EmailService $emailService
    ) {}

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
                'category', 'brand', 'purchaseYear', 'functionalState',
                'aestheticState', 'firstName', 'lastName', 'email',
                'phone', 'address', 'zipCode', 'city', 'paymentMethod'
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
                if (empty($data['iban']) || empty($data['accountHolder'])) {
                    return $this->json([
                        'success' => false,
                        'error' => 'IBAN et titulaire du compte requis pour un virement'
                    ], 400);
                }
            }

            // Validation photos (minimum 2)
            if (empty($data['photos']) || count($data['photos']) < 2) {
                return $this->json([
                    'success' => false,
                    'error' => 'Minimum 2 photos requises'
                ], 400);
            }

            // Calcul de l'estimation
            $estimation = $this->calculator->calculate($data);

            // Créer l'entité
            $buybackRequest = new BuybackRequest();
            $buybackRequest->setCategory($data['category']);
            $buybackRequest->setBrand($data['brand']);
            $buybackRequest->setModel($data['model'] ?? null);
            $buybackRequest->setPurchaseYear($data['purchaseYear']);
            $buybackRequest->setHasInvoice($data['hasInvoice'] ?? false);
            $buybackRequest->setFunctionalState($data['functionalState']);
            $buybackRequest->setAestheticState($data['aestheticState']);
            $buybackRequest->setHasAllAccessories($data['hasAllAccessories'] ?? true);
            $buybackRequest->setAdditionalComments($data['additionalComments'] ?? null);
            $buybackRequest->setPhotos($data['photos']);
            $buybackRequest->setFirstName($data['firstName']);
            $buybackRequest->setLastName($data['lastName']);
            $buybackRequest->setEmail($data['email']);
            $buybackRequest->setPhone($data['phone']);
            $buybackRequest->setAddress($data['address']);
            $buybackRequest->setZipCode($data['zipCode']);
            $buybackRequest->setCity($data['city']);
            $buybackRequest->setFloor($data['floor'] ?? null);
            $buybackRequest->setHasElevator($data['hasElevator'] ?? false);
            $buybackRequest->setPaymentMethod($data['paymentMethod']);
            $buybackRequest->setIban($data['iban'] ?? null);
            $buybackRequest->setAccountHolder($data['accountHolder'] ?? null);
            $buybackRequest->setTimeSlots($data['timeSlots'] ?? []);
            $buybackRequest->setEstimatedPriceMin($estimation['min']);
            $buybackRequest->setEstimatedPriceMax($estimation['max']);
            $buybackRequest->setCalculationDetails($estimation['details']);

            if (!empty($data['preferredDate'])) {
                try {
                    $buybackRequest->setPreferredDate(new \DateTime($data['preferredDate']));
                } catch (\Exception $e) {
                    // Date invalide, on ignore
                }
            }

            // Sauvegarder
            $this->entityManager->persist($buybackRequest);
            $this->entityManager->flush();

            // TODO: Créer les méthodes dans EmailService pour les buybacks
            // $this->emailService->sendBuybackRequestClientConfirmation($buybackRequest);
            // $this->emailService->sendBuybackRequestAdminNotification($buybackRequest);

            return $this->json([
                'success' => true,
                'message' => 'Demande de rachat enregistrée avec succès',
                'request_id' => $buybackRequest->getId(),
                'estimation' => [
                    'min' => $estimation['min'],
                    'max' => $estimation['max'],
                    'details' => $estimation['details']
                ]
            ], 201);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Une erreur est survenue: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/buyback-estimate', name: 'api_buyback_estimate', methods: ['POST'])]
    public function estimate(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json(['success' => false, 'error' => 'Données invalides'], 400);
            }

            // Calcul de l'estimation sans sauvegarder
            $estimation = $this->calculator->calculate($data);

            return $this->json([
                'success' => true,
                'estimation' => [
                    'min' => $estimation['min'],
                    'max' => $estimation['max'],
                    'details' => $estimation['details']
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur lors du calcul: ' . $e->getMessage()
            ], 500);
        }
    }
}
