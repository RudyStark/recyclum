<?php

namespace App\Controller\Admin;

use App\Entity\RepairRequest;
use App\Entity\RepairAppointment;
use App\Service\EmailService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class RepairRequestCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $em,
        private AdminUrlGenerator $adminUrlGenerator,
        private EmailService $emailService,
    ) {}

    public static function getEntityFqcn(): string
    {
        return RepairRequest::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Demande de réparation')
            ->setEntityLabelInPlural('Demandes de réparation')
            ->setPageTitle('index', 'Demandes de réparation')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::DELETE);
    }

    #[Route('/admin/repair-request', name: 'admin_repair_request_index')]
    public function repairRequestIndex(): Response
    {
        $requests = $this->em->getRepository(RepairRequest::class)
            ->findBy([], ['createdAt' => 'DESC']);

        // Stats
        $stats = [
            'total' => count($requests),
            'pending' => count(array_filter($requests, fn($r) => $r->getStatus() === 'pending')),
            'urgent' => count(array_filter($requests, fn($r) => $r->isUrgency())),
            'completed' => count(array_filter($requests, fn($r) => $r->getStatus() === 'completed')),
        ];

        return $this->render('admin/repair_request/repair_request_index.html.twig', [
            'requests' => $requests,
            'stats' => $stats,
        ]);
    }

    #[Route('/admin/repair-request/detail', name: 'admin_repair_request_show')]
    public function repairRequestShow(Request $request): Response
    {
        $id = $request->query->get('id');

        if (!$id) {
            throw $this->createNotFoundException('ID manquant');
        }

        $repairRequest = $this->em->getRepository(RepairRequest::class)->find($id);

        if (!$repairRequest) {
            throw $this->createNotFoundException('Demande non trouvée');
        }

        return $this->render('admin/repair_request/repair_request_show.html.twig', [
            'request' => $repairRequest,
        ]);
    }

    #[Route('/admin/repair-request/update-status', name: 'admin_repair_request_update_status', methods: ['POST'])]
    public function updateStatus(Request $httpRequest): Response
    {
        $data = json_decode($httpRequest->getContent(), true);
        $id = $data['id'] ?? null;
        $newStatus = $data['status'] ?? null;

        if (!$id) {
            return $this->json(['error' => 'ID manquant'], 400);
        }

        $repairRequest = $this->em->getRepository(RepairRequest::class)->find($id);

        if (!$repairRequest) {
            return $this->json(['error' => 'Demande non trouvée'], 404);
        }

        if (!in_array($newStatus, ['pending', 'contacted', 'scheduled', 'completed', 'cancelled'])) {
            return $this->json(['error' => 'Statut invalide'], 400);
        }

        $repairRequest->setStatus($newStatus);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'status' => $newStatus,
            'statusLabel' => $repairRequest->getStatusLabel()
        ]);
    }

    #[Route('/admin/repair-request/send-response', name: 'admin_repair_request_send_response', methods: ['POST'])]
    public function sendResponse(Request $httpRequest): Response
    {
        $data = json_decode($httpRequest->getContent(), true);
        $id = $data['id'] ?? null;
        $responseType = $data['type'] ?? null;
        $customMessage = $data['message'] ?? '';

        if (!$id) {
            return $this->json(['error' => 'ID manquant'], 400);
        }

        $repairRequest = $this->em->getRepository(RepairRequest::class)->find($id);

        if (!$repairRequest) {
            return $this->json(['error' => 'Demande non trouvée'], 404);
        }

        try {
            if ($responseType === 'accept') {
                // Génère le token de confirmation si pas déjà fait
                if (!$repairRequest->getConfirmationToken()) {
                    $repairRequest->generateConfirmationToken();
                }

                $repairRequest->setStatus('contacted');
                $this->em->flush();

                // Envoie l'email d'acceptation via Brevo
                $this->emailService->sendRepairAcceptanceEmail($repairRequest, $customMessage);

            } else {
                $repairRequest->setStatus('cancelled');
                $this->em->flush();

                // Envoie l'email de refus via Brevo
                $this->emailService->sendRepairRejectionEmail($repairRequest, $customMessage);
            }

            return $this->json([
                'success' => true,
                'message' => 'Email envoyé avec succès à ' . $repairRequest->getEmail()
            ]);

        } catch (\Exception $e) {
            error_log('Erreur envoi email Brevo: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/admin/repair-appointments/calendar', name: 'admin_repair_appointments_calendar')]
    public function appointmentsCalendar(): Response
    {
        $appointments = $this->em->getRepository(RepairAppointment::class)
            ->findUpcomingAppointments();

        // Groupe les RDV par date
        $appointmentsByDate = [];
        foreach ($appointments as $appointment) {
            $dateKey = $appointment->getAppointmentDate()->format('Y-m-d');
            if (!isset($appointmentsByDate[$dateKey])) {
                $appointmentsByDate[$dateKey] = [];
            }
            $appointmentsByDate[$dateKey][] = $appointment;
        }

        // Stats
        $today = new \DateTime('today');
        $thisWeek = (clone $today)->modify('+7 days');
        $thisMonth = (clone $today)->modify('+30 days');

        $stats = [
            'total' => count($appointments),
            'today' => count(array_filter($appointments, fn($a) => $a->getAppointmentDate()->format('Y-m-d') === $today->format('Y-m-d'))),
            'week' => count(array_filter($appointments, fn($a) => $a->getAppointmentDate() <= $thisWeek)),
            'month' => count(array_filter($appointments, fn($a) => $a->getAppointmentDate() <= $thisMonth)),
        ];

        return $this->render('admin/repair_request/appointments_calendar.html.twig', [
            'appointments' => $appointments,
            'appointmentsByDate' => $appointmentsByDate,
            'stats' => $stats,
        ]);
    }
}
