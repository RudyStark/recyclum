<?php

namespace App\Controller;

use App\Entity\BuybackAppointment;
use App\Entity\BuybackRequest;
use App\Repository\BuybackAppointmentRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/rachat')]
class BuybackAppointmentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BuybackAppointmentRepository $appointmentRepository,
        private EmailService $emailService
    ) {}

    /**
     * Page de sélection de RDV (accessible via token)
     */
    #[Route('/rdv/{token}', name: 'buyback_appointment_calendar')]
    public function calendar(string $token): Response
    {
        $buybackRequest = $this->entityManager
            ->getRepository(BuybackRequest::class)
            ->findOneBy(['appointmentToken' => $token]);

        if (!$buybackRequest) {
            throw $this->createNotFoundException('Lien invalide ou expiré');
        }

        // Vérifier que la demande est bien validée
        if ($buybackRequest->getStatus() !== 'validated') {
            return $this->render('buyback_appointment/already_scheduled.html.twig', [
                'request' => $buybackRequest,
            ]);
        }

        // Vérifier que c'est bien un gros électroménager
        if (!$buybackRequest->needsHomePickup()) {
            return $this->render('buyback_appointment/no_pickup_needed.html.twig', [
                'request' => $buybackRequest,
            ]);
        }

        // Récupérer les créneaux occupés du mois en cours
        $now = new \DateTimeImmutable();
        $blockedSlots = $this->appointmentRepository->getBookedSlotsForMonth(
            (int)$now->format('Y'),
            (int)$now->format('n')
        );

        return $this->render('buyback_appointment/calendar.html.twig', [
            'request' => $buybackRequest,
            'token' => $token,
            'blockedSlots' => json_encode($blockedSlots),
        ]);
    }

    /**
     * Récupération des créneaux occupés pour un mois (AJAX)
     */
    #[Route('/rdv/{token}/blocked-slots', name: 'buyback_appointment_blocked_slots', methods: ['GET'])]
    public function getBlockedSlots(string $token, Request $request): JsonResponse
    {
        $buybackRequest = $this->entityManager
            ->getRepository(BuybackRequest::class)
            ->findOneBy(['appointmentToken' => $token]);

        if (!$buybackRequest) {
            return $this->json(['error' => 'Token invalide'], 404);
        }

        $year = $request->query->getInt('year', (int)date('Y'));
        $month = $request->query->getInt('month', (int)date('n'));

        $blockedSlots = $this->appointmentRepository->getBookedSlotsForMonth($year, $month);

        return $this->json([
            'success' => true,
            'blocked_slots' => $blockedSlots,
        ]);
    }

    /**
     * Confirmation du RDV (AJAX)
     */
    #[Route('/rdv/{token}/confirm', name: 'buyback_appointment_confirm', methods: ['POST'])]
    public function confirmAppointment(string $token, Request $request): JsonResponse
    {
        try {
            $buybackRequest = $this->entityManager
                ->getRepository(BuybackRequest::class)
                ->findOneBy(['appointmentToken' => $token]);

            if (!$buybackRequest) {
                return $this->json(['error' => 'Token invalide'], 404);
            }

            // Vérifier le statut
            if ($buybackRequest->getStatus() !== 'validated') {
                return $this->json(['error' => 'Cette demande ne peut plus être modifiée'], 400);
            }

            $data = json_decode($request->getContent(), true);

            if (!isset($data['date']) || !isset($data['time'])) {
                return $this->json(['error' => 'Date et heure requises'], 400);
            }

            $appointmentDate = new \DateTimeImmutable($data['date']);
            $appointmentTime = $data['time'];

            // Vérifier que la date n'est pas dans le passé
            $today = new \DateTimeImmutable('today');
            if ($appointmentDate < $today) {
                return $this->json(['error' => 'Impossible de réserver une date passée'], 400);
            }

            // Vérifier que le créneau est disponible
            if (!$this->appointmentRepository->isSlotAvailable($appointmentDate, $appointmentTime)) {
                return $this->json(['error' => 'Ce créneau n\'est plus disponible'], 400);
            }

            // Créer le RDV
            $appointment = new BuybackAppointment();
            $appointment->setBuybackRequest($buybackRequest);
            $appointment->setAppointmentDate($appointmentDate);
            $appointment->setAppointmentTime($appointmentTime);
            $appointment->setStatus('scheduled');

            $this->entityManager->persist($appointment);

            // Mettre à jour le statut de la demande
            $buybackRequest->setStatus('appointment_scheduled');

            $this->entityManager->flush();

            // Envoyer l'email de confirmation
            try {
                $this->emailService->sendBuybackAppointmentConfirmation($buybackRequest, $appointment);
            } catch (\Exception $e) {
                error_log('Erreur envoi email confirmation RDV: ' . $e->getMessage());
            }

            return $this->json([
                'success' => true,
                'appointment' => [
                    'date' => $appointment->getFormattedDate(),
                    'time' => $appointment->getAppointmentTime(),
                ],
                'message' => 'Rendez-vous confirmé avec succès',
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue: ' . $e->getMessage()
            ], 500);
        }
    }
}
