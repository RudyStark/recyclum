<?php

namespace App\Controller\Client;

use App\Entity\RepairRequest;
use App\Entity\RepairAppointment;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RepairAppointmentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private EmailService $emailService
    ) {}

    #[Route('/rdv/confirmation/{token}', name: 'app_repair_appointment')]
    public function appointment(string $token): Response
    {
        $repairRequest = $this->em->getRepository(RepairRequest::class)
            ->findOneBy(['confirmationToken' => $token]);

        if (!$repairRequest) {
            return $this->render('client/repair/invalid_token.html.twig');
        }

        // Vérifie si le token a expiré (14 jours)
        if ($repairRequest->isTokenExpired()) {
            return $this->render('client/repair/expired_token.html.twig', [
                'request' => $repairRequest
            ]);
        }

        // Si déjà confirmé
        if ($repairRequest->hasAppointment()) {
            return $this->render('client/repair/already_confirmed.html.twig', [
                'request' => $repairRequest,
                'appointment' => $repairRequest->getAppointment()
            ]);
        }

        // Récupère tous les créneaux bloqués
        $blockedSlots = $this->em->getRepository(RepairAppointment::class)
            ->getBlockedSlots();

        return $this->render('client/repair/appointment.html.twig', [
            'request' => $repairRequest,
            'blockedSlots' => json_encode($blockedSlots)
        ]);
    }

    #[Route('/rdv/confirmation/{token}/submit', name: 'app_repair_appointment_submit', methods: ['POST'])]
    public function submitAppointment(string $token, Request $request): Response
    {
        $repairRequest = $this->em->getRepository(RepairRequest::class)
            ->findOneBy(['confirmationToken' => $token]);

        if (!$repairRequest) {
            return $this->json(['error' => 'Lien invalide'], 404);
        }

        // Vérifie si le token a expiré
        if ($repairRequest->isTokenExpired()) {
            return $this->json(['error' => 'Lien expiré'], 400);
        }

        // Vérifie si déjà confirmé
        if ($repairRequest->hasAppointment()) {
            return $this->json(['error' => 'Rendez-vous déjà confirmé'], 400);
        }

        $data = json_decode($request->getContent(), true);

        try {
            $date = new \DateTime($data['date']);
            $time = \DateTime::createFromFormat('H:i', $data['time']);

            if (!$date || !$time) {
                return $this->json(['error' => 'Date ou heure invalide'], 400);
            }

            // Vérifie que le créneau est disponible
            if (!$this->em->getRepository(RepairAppointment::class)->isSlotAvailable($date, $time)) {
                return $this->json(['error' => 'Ce créneau n\'est plus disponible'], 400);
            }

            // Vérifie que la date n'est pas dans le passé
            $now = new \DateTime('today');
            if ($date < $now) {
                return $this->json(['error' => 'Vous ne pouvez pas choisir une date passée'], 400);
            }

            // Vérifie que c'est un jour ouvré (lundi à vendredi)
            $dayOfWeek = (int)$date->format('N'); // 1 = lundi, 7 = dimanche
            if ($dayOfWeek > 5) {
                return $this->json(['error' => 'Les rendez-vous ne sont possibles que du lundi au vendredi'], 400);
            }

            // Crée le RDV
            $appointment = new RepairAppointment();
            $appointment->setRepairRequest($repairRequest);
            $appointment->setAppointmentDate($date);
            $appointment->setAppointmentTime($time);
            $appointment->setConfirmedAt(new \DateTimeImmutable());

            $repairRequest->setStatus('scheduled');

            $this->em->persist($appointment);
            $this->em->flush();

            // Envoie les emails via le service EmailService
            try {
                $this->emailService->sendAppointmentConfirmationToClient($repairRequest, $appointment);

                // Construis l'URL admin pour l'email
                $viewUrl = sprintf(
                    'http://127.0.0.1:8000/admin?crudAction=repairRequestShow&crudControllerFqcn=%s&id=%d',
                    urlencode('App\\Controller\\Admin\\RepairRequestCrudController'),
                    $repairRequest->getId()
                );

                $this->emailService->sendAppointmentNotificationToAdmin($repairRequest, $appointment, $viewUrl);
            } catch (\Exception $e) {
                // Log l'erreur mais ne bloque pas la réponse
                error_log('Erreur envoi emails RDV: ' . $e->getMessage());
            }

            return $this->json([
                'success' => true,
                'message' => 'Rendez-vous confirmé avec succès',
                'appointment' => [
                    'date' => $appointment->getAppointmentDate()->format('d/m/Y'),
                    'time' => $appointment->getAppointmentTime()->format('H:i'),
                    'formatted' => $appointment->getFullDateTime()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la confirmation: ' . $e->getMessage()
            ], 500);
        }
    }
}
