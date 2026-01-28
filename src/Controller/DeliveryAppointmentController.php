<?php

namespace App\Controller;

use App\Entity\DeliveryAppointment;
use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Repository\DeliveryAppointmentRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/livraison')]
class DeliveryAppointmentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DeliveryAppointmentRepository $appointmentRepository,
        private EmailService $emailService,
        private LoggerInterface $logger
    ) {}

    /**
     * Page de sélection de la date de livraison (accessible via token)
     */
    #[Route('/rdv/{token}', name: 'delivery_appointment_calendar')]
    public function calendar(string $token): Response
    {
        $order = $this->entityManager
            ->getRepository(Order::class)
            ->findOneBy(['deliveryBookingToken' => $token]);

        if (!$order) {
            throw $this->createNotFoundException('Lien invalide ou expiré');
        }

        // Vérifier que la commande est bien en statut PROCESSING
        if ($order->getStatus() !== OrderStatus::PROCESSING) {
            // Si déjà expédiée ou livrée, afficher page de confirmation
            if ($order->hasDeliveryAppointment()) {
                return $this->render('delivery_appointment/already_scheduled.html.twig', [
                    'order' => $order,
                ]);
            }

            return $this->render('delivery_appointment/not_available.html.twig', [
                'order' => $order,
            ]);
        }

        // Si un RDV est déjà planifié
        if ($order->hasDeliveryAppointment()) {
            return $this->render('delivery_appointment/already_scheduled.html.twig', [
                'order' => $order,
            ]);
        }

        // Récupérer les dates disponibles
        $availableDates = $this->appointmentRepository->getAvailableDates($order->getPaidAt());

        // Récupérer les dates bloquées pour le calendrier (pour affichage)
        $now = new \DateTimeImmutable();
        $blockedDates = $this->appointmentRepository->getBookedDatesForMonth(
            (int)$now->format('Y'),
            (int)$now->format('n')
        );

        // Calculate minDate: payment date + 2 days, but at least today + 2 days
        $baseDate = $order->getPaidAt() ?? new \DateTimeImmutable();
        $minDateFromPayment = $baseDate->modify('+2 days');
        $todayPlusTwo = (new \DateTimeImmutable('today'))->modify('+2 days');
        $minDate = $minDateFromPayment > $todayPlusTwo ? $minDateFromPayment : $todayPlusTwo;

        return $this->render('delivery_appointment/calendar.html.twig', [
            'order' => $order,
            'token' => $token,
            'availableDates' => json_encode($availableDates),
            'blockedDates' => json_encode($blockedDates),
            'minDate' => $minDate->format('Y-m-d'),
        ]);
    }

    /**
     * Récupération des dates bloquées pour un mois (AJAX)
     */
    #[Route('/rdv/{token}/blocked-dates', name: 'delivery_appointment_blocked_dates', methods: ['GET'])]
    public function getBlockedDates(string $token, Request $request): JsonResponse
    {
        $order = $this->entityManager
            ->getRepository(Order::class)
            ->findOneBy(['deliveryBookingToken' => $token]);

        if (!$order) {
            return $this->json(['error' => 'Token invalide'], 404);
        }

        $year = $request->query->getInt('year', (int)date('Y'));
        $month = $request->query->getInt('month', (int)date('n'));

        $blockedDates = $this->appointmentRepository->getBookedDatesForMonth($year, $month);

        return $this->json([
            'success' => true,
            'blocked_dates' => $blockedDates,
        ]);
    }

    /**
     * Confirmation de la date de livraison (AJAX)
     */
    #[Route('/rdv/{token}/confirm', name: 'delivery_appointment_confirm', methods: ['POST'])]
    public function confirmAppointment(string $token, Request $request): JsonResponse
    {
        try {
            $order = $this->entityManager
                ->getRepository(Order::class)
                ->findOneBy(['deliveryBookingToken' => $token]);

            if (!$order) {
                return $this->json(['error' => 'Token invalide'], 404);
            }

            // Vérifier le statut
            if ($order->getStatus() !== OrderStatus::PROCESSING) {
                return $this->json(['error' => 'Cette commande ne peut plus être modifiée'], 400);
            }

            // Vérifier qu'il n'y a pas déjà un RDV
            if ($order->hasDeliveryAppointment()) {
                return $this->json(['error' => 'Un rendez-vous est déjà planifié pour cette commande'], 400);
            }

            $data = json_decode($request->getContent(), true);

            if (!isset($data['date'])) {
                return $this->json(['error' => 'Date requise'], 400);
            }

            $appointmentDate = new \DateTimeImmutable($data['date']);

            // Vérifier que la date n'est pas dans le passé
            $today = new \DateTimeImmutable('today');
            if ($appointmentDate < $today) {
                return $this->json(['error' => 'Impossible de réserver une date passée'], 400);
            }

            // Vérifier la date minimum (paiement + 2 jours) - compare dates only, ignoring time
            $baseDate = $order->getPaidAt() ?? $today;
            $minDateFromPayment = new \DateTimeImmutable($baseDate->format('Y-m-d') . ' +2 days');
            $todayPlusTwo = new \DateTimeImmutable($today->format('Y-m-d') . ' +2 days');
            $minDate = $minDateFromPayment > $todayPlusTwo ? $minDateFromPayment : $todayPlusTwo;

            // Compare dates only (both are at midnight)
            $appointmentDateOnly = new \DateTimeImmutable($appointmentDate->format('Y-m-d'));
            $minDateOnly = new \DateTimeImmutable($minDate->format('Y-m-d'));

            if ($appointmentDateOnly < $minDateOnly) {
                return $this->json(['error' => 'La date minimum est ' . $minDate->format('d/m/Y')], 400);
            }

            // Vérifier que la date est disponible (pas de weekend, pas déjà réservée)
            $dayOfWeek = (int)$appointmentDate->format('w');
            if ($dayOfWeek === 0 || $dayOfWeek === 6) {
                return $this->json(['error' => 'Les livraisons ne sont pas disponibles le weekend'], 400);
            }

            if (!$this->appointmentRepository->isDateAvailable($appointmentDate)) {
                return $this->json(['error' => 'Cette date n\'est plus disponible'], 400);
            }

            // Créer le RDV
            $appointment = new DeliveryAppointment();
            $appointment->setOrder($order);
            $appointment->setAppointmentDate($appointmentDate);
            $appointment->setStatus(DeliveryAppointment::STATUS_SCHEDULED);
            $appointment->setBookedBy('customer');

            $this->entityManager->persist($appointment);
            $this->entityManager->flush();

            // Envoyer l'email de confirmation
            try {
                $customerEmail = $order->getUser()?->getEmail() ?? $order->getCustomerEmail();
                $customerName = $order->getDeliveryFirstName() . ' ' . $order->getDeliveryLastName();

                if ($customerEmail) {
                    $this->emailService->sendDeliveryAppointmentConfirmation($order, $appointment, $customerEmail, $customerName);
                }
            } catch (\Exception $e) {
                $this->logger->error('Erreur envoi email confirmation livraison: ' . $e->getMessage());
            }

            return $this->json([
                'success' => true,
                'appointment' => [
                    'date' => $appointment->getFormattedDate(),
                ],
                'message' => 'Date de livraison confirmée avec succès',
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur confirmation RDV livraison: ' . $e->getMessage());
            return $this->json([
                'error' => 'Une erreur est survenue'
            ], 500);
        }
    }

    /**
     * Page de succès après réservation
     */
    #[Route('/rdv/{token}/confirmation', name: 'delivery_appointment_success')]
    public function success(string $token): Response
    {
        $order = $this->entityManager
            ->getRepository(Order::class)
            ->findOneBy(['deliveryBookingToken' => $token]);

        if (!$order || !$order->hasDeliveryAppointment()) {
            throw $this->createNotFoundException('Page introuvable');
        }

        return $this->render('delivery_appointment/success.html.twig', [
            'order' => $order,
        ]);
    }
}
