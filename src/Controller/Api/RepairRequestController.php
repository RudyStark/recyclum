<?php

namespace App\Controller\Api;

use App\Entity\RepairRequest;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class RepairRequestController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmailService $emailService
    ) {}

    #[Route('/repair-requests', name: 'api_repair_request_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->json(['success' => false, 'error' => 'Données invalides'], 400);
            }

            // Validation des champs obligatoires
            $requiredFields = ['category', 'issue', 'issueDetails', 'firstName', 'lastName', 'email', 'phone', 'repairLocation'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return $this->json(['success' => false, 'error' => "Le champ $field est obligatoire"], 400);
                }
            }

            // Validation email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->json(['success' => false, 'error' => 'Email invalide'], 400);
            }

            // Validation description (min 10 caractères)
            if (strlen($data['issueDetails']) < 10) {
                return $this->json(['success' => false, 'error' => 'La description doit contenir au moins 10 caractères'], 400);
            }

            // Si domicile, vérifier l'adresse
            if ($data['repairLocation'] === 'domicile') {
                if (empty($data['address']) || empty($data['zipCode']) || empty($data['city'])) {
                    return $this->json(['success' => false, 'error' => 'Adresse complète requise pour une réparation à domicile'], 400);
                }
            }

            // Créer l'entité
            $repairRequest = new RepairRequest();
            $repairRequest->setCategory($data['category']);
            $repairRequest->setBrand($data['brand'] ?? null);
            $repairRequest->setModel($data['model'] ?? null);
            $repairRequest->setIssue($data['issue']);
            $repairRequest->setIssueDetails($data['issueDetails']);
            $repairRequest->setFirstName($data['firstName']);
            $repairRequest->setLastName($data['lastName']);
            $repairRequest->setEmail($data['email']);
            $repairRequest->setPhone($data['phone']);
            $repairRequest->setRepairLocation($data['repairLocation']);
            $repairRequest->setUrgency($data['urgency'] ?? false);

            if ($data['repairLocation'] === 'domicile') {
                $repairRequest->setAddress($data['address']);
                $repairRequest->setZipCode($data['zipCode']);
                $repairRequest->setCity($data['city']);
            }

            if (!empty($data['preferredDate'])) {
                try {
                    $repairRequest->setPreferredDate(new \DateTime($data['preferredDate']));
                } catch (\Exception $e) {
                    // Date invalide, on ignore
                }
            }

            // Sauvegarder
            $this->entityManager->persist($repairRequest);
            $this->entityManager->flush();

            // Envoyer les emails via le service unifié
            try {
                $this->emailService->sendRepairRequestClientConfirmation($repairRequest);
                $this->emailService->sendRepairRequestAdminNotification($repairRequest);
            } catch (\Exception $e) {
                // Log l'erreur mais ne bloque pas la réponse
                error_log('Erreur envoi email: ' . $e->getMessage());

                return $this->json([
                    'success' => true,
                    'message' => 'Demande enregistrée avec succès',
                    'request_id' => $repairRequest->getId(),
                    'warning' => 'Erreur lors de l\'envoi des emails de confirmation'
                ], 201);
            }

            return $this->json([
                'success' => true,
                'message' => 'Demande enregistrée avec succès',
                'request_id' => $repairRequest->getId()
            ], 201);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Une erreur est survenue: ' . $e->getMessage()
            ], 500);
        }
    }
}
