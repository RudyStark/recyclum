<?php

namespace App\Controller\Admin;

use App\Entity\DeliveryAppointment;
use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Repository\DeliveryAppointmentRepository;
use App\Repository\OrderRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OrderCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $em,
        private OrderRepository $orderRepository,
        private DeliveryAppointmentRepository $deliveryAppointmentRepository,
        private AdminUrlGenerator $adminUrlGenerator,
        private EmailService $emailService,
        private LoggerInterface $logger,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setSearchFields(['orderNumber', 'deliveryFirstName', 'deliveryLastName', 'deliveryCity', 'customerEmail'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(20);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function index(AdminContext $context): Response
    {
        return $this->redirectToRoute('admin_order_index');
    }

    #[Route('/admin/commandes', name: 'admin_order_index', methods: ['GET'])]
    public function orderIndex(Request $request): Response
    {
        // Get filter parameters
        $status = $request->query->get('status', 'all');
        $search = $request->query->get('search', '');
        $page = $request->query->getInt('page', 1);
        $perPage = 20;

        // Build query
        $qb = $this->orderRepository->createQueryBuilder('o')
            ->leftJoin('o.user', 'u')
            ->leftJoin('o.items', 'i')
            ->addSelect('u', 'i')
            ->orderBy('o.createdAt', 'DESC');

        // Filter by status
        if ($status !== 'all') {
            $qb->andWhere('o.status = :status')
               ->setParameter('status', OrderStatus::from($status));
        }

        // Search
        if ($search) {
            $qb->andWhere('o.orderNumber LIKE :search OR o.deliveryFirstName LIKE :search OR o.deliveryLastName LIKE :search OR o.deliveryCity LIKE :search OR o.customerEmail LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Pagination
        $query = $qb->getQuery();
        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query);
        $totalItems = count($paginator);
        $totalPages = ceil($totalItems / $perPage);

        $paginator->getQuery()
            ->setFirstResult($perPage * ($page - 1))
            ->setMaxResults($perPage);

        // Get stats
        $stats = [
            'total' => $this->orderRepository->count([]),
            'pending' => $this->orderRepository->count(['status' => OrderStatus::PENDING]),
            'paid' => $this->orderRepository->count(['status' => OrderStatus::PAID]),
            'processing' => $this->orderRepository->count(['status' => OrderStatus::PROCESSING]),
            'shipped' => $this->orderRepository->count(['status' => OrderStatus::SHIPPED]),
            'delivered' => $this->orderRepository->count(['status' => OrderStatus::DELIVERED]),
            'cancelled' => $this->orderRepository->count(['status' => OrderStatus::CANCELLED]),
            'refunded' => $this->orderRepository->count(['status' => OrderStatus::REFUNDED]),
        ];

        // Revenue stats
        $stats['total_revenue'] = $this->orderRepository->getTotalRevenue();
        $stats['monthly_revenue'] = $this->orderRepository->getMonthlyRevenue();

        return $this->render('admin/order/order_index.html.twig', [
            'orders' => $paginator,
            'stats' => $stats,
            'currentStatus' => $status,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
        ]);
    }

    #[Route('/admin/commandes/{id}', name: 'admin_order_show', methods: ['GET'])]
    public function orderShow(int $id): Response
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable');
        }

        // Get timeline data
        $timeline = $this->buildOrderTimeline($order);

        return $this->render('admin/order/order_show.html.twig', [
            'order' => $order,
            'timeline' => $timeline,
        ]);
    }

    #[Route('/admin/commandes/{id}/status', name: 'admin_order_update_status', methods: ['POST'])]
    public function updateStatus(int $id, Request $request): Response
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('order_status_' . $id, $token)) {
            $this->addFlash('error', 'Token de sécurité invalide');
            return $this->redirectToRoute('admin_order_show', ['id' => $id]);
        }

        $newStatus = $request->request->get('status');
        $notes = $request->request->get('notes', '');

        try {
            $statusEnum = OrderStatus::from($newStatus);
            $oldStatus = $order->getStatus();

            // Don't do anything if status hasn't changed
            if ($oldStatus === $statusEnum) {
                return $this->redirectToRoute('admin_order_show', ['id' => $id]);
            }

            $order->setStatus($statusEnum);

            // Set timestamp based on new status
            $now = new \DateTimeImmutable();
            switch ($statusEnum) {
                case OrderStatus::PAID:
                    if (!$order->getPaidAt()) {
                        $order->setPaidAt($now);
                    }
                    break;
                case OrderStatus::SHIPPED:
                    if (!$order->getShippedAt()) {
                        $order->setShippedAt($now);
                    }
                    break;
                case OrderStatus::DELIVERED:
                    if (!$order->getDeliveredAt()) {
                        $order->setDeliveredAt($now);
                    }
                    break;
                case OrderStatus::CANCELLED:
                    if (!$order->getCancelledAt()) {
                        $order->setCancelledAt($now);
                    }
                    break;
            }

            // Add notes if provided
            if ($notes) {
                $existingNotes = $order->getNotes() ?? '';
                $timestamp = $now->format('d/m/Y H:i');
                $newNote = "[{$timestamp}] Statut: {$oldStatus->getLabel()} → {$statusEnum->getLabel()}\n{$notes}";
                $order->setNotes($existingNotes ? $existingNotes . "\n\n" . $newNote : $newNote);
            }

            $this->em->flush();

            // Special handling for PROCESSING status: generate booking token and send booking email
            if ($statusEnum === OrderStatus::PROCESSING && !$order->getDeliveryBookingToken()) {
                $order->generateDeliveryBookingToken();
                $this->em->flush();

                // Send booking email
                $emailSent = $this->sendDeliveryBookingEmail($order);
            } else {
                // Send regular status email notification to customer
                $emailSent = $this->sendOrderStatusEmail($order, $statusEnum);
            }

            if ($emailSent) {
                $this->addFlash('success', sprintf(
                    'Statut de la commande %s mis à jour: %s - Email envoyé au client',
                    $order->getOrderNumber(),
                    $statusEnum->getLabel()
                ));
            } else {
                $this->addFlash('success', sprintf(
                    'Statut de la commande %s mis à jour: %s',
                    $order->getOrderNumber(),
                    $statusEnum->getLabel()
                ));
            }

        } catch (\ValueError $e) {
            $this->addFlash('error', 'Statut invalide');
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'email de statut de commande', [
                'order_id' => $id,
                'error' => $e->getMessage()
            ]);
            $this->addFlash('warning', sprintf(
                'Statut mis à jour mais l\'email n\'a pas pu être envoyé: %s',
                $e->getMessage()
            ));
        }

        return $this->redirectToRoute('admin_order_show', ['id' => $id]);
    }

    private function sendDeliveryBookingEmail(Order $order): bool
    {
        $customerEmail = $order->getUser()?->getEmail() ?? $order->getCustomerEmail();
        $customerName = $order->getDeliveryFirstName() . ' ' . $order->getDeliveryLastName();

        $this->logger->info('sendDeliveryBookingEmail: Tentative d\'envoi', [
            'order_id' => $order->getId(),
            'customer_email' => $customerEmail,
            'has_token' => $order->getDeliveryBookingToken() !== null,
        ]);

        if (!$customerEmail) {
            $this->logger->warning('Impossible d\'envoyer l\'email de réservation: pas d\'adresse email', [
                'order_id' => $order->getId(),
                'user_email' => $order->getUser()?->getEmail(),
                'customer_email' => $order->getCustomerEmail(),
            ]);
            return false;
        }

        if (!$order->getDeliveryBookingToken()) {
            $this->logger->warning('Impossible d\'envoyer l\'email de réservation: pas de token', [
                'order_id' => $order->getId()
            ]);
            return false;
        }

        try {
            $bookingUrl = $this->urlGenerator->generate(
                'delivery_appointment_calendar',
                ['token' => $order->getDeliveryBookingToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $this->logger->info('sendDeliveryBookingEmail: URL générée', [
                'order_id' => $order->getId(),
                'booking_url' => $bookingUrl,
            ]);

            $this->emailService->sendDeliveryBookingEmail($order, $bookingUrl, $customerEmail, $customerName);

            $this->logger->info('sendDeliveryBookingEmail: Email envoyé avec succès', [
                'order_id' => $order->getId(),
                'to' => $customerEmail,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('sendDeliveryBookingEmail: Erreur lors de l\'envoi', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw to be caught by outer handler
        }
    }

    private function sendOrderStatusEmail(Order $order, OrderStatus $status): bool
    {
        // Get customer email
        $customerEmail = $order->getUser()?->getEmail() ?? $order->getCustomerEmail();
        $customerName = $order->getDeliveryFirstName() . ' ' . $order->getDeliveryLastName();

        if (!$customerEmail) {
            $this->logger->warning('Impossible d\'envoyer l\'email: pas d\'adresse email client', [
                'order_id' => $order->getId()
            ]);
            return false;
        }

        // Send appropriate email based on status
        switch ($status) {
            case OrderStatus::PROCESSING:
                // For PROCESSING, the booking email is sent separately
                return false;
            case OrderStatus::SHIPPED:
                $this->emailService->sendOrderShippedEmail($order, $customerEmail, $customerName);
                return true;
            case OrderStatus::DELIVERED:
                $this->emailService->sendOrderDeliveredEmail($order, $customerEmail, $customerName);
                return true;
            case OrderStatus::CANCELLED:
                $this->emailService->sendOrderCancelledEmail($order, $customerEmail, $customerName);
                return true;
            case OrderStatus::REFUNDED:
                $this->emailService->sendOrderRefundedEmail($order, $customerEmail, $customerName);
                return true;
            default:
                // No email for PENDING, PAID statuses
                $this->logger->info('Pas d\'email configuré pour le statut: ' . $status->value, [
                    'order_id' => $order->getId()
                ]);
                return false;
        }
    }

    #[Route('/admin/commandes/{id}/notes', name: 'admin_order_add_note', methods: ['POST'])]
    public function addNote(int $id, Request $request): Response
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('order_note_' . $id, $token)) {
            $this->addFlash('error', 'Token de sécurité invalide');
            return $this->redirectToRoute('admin_order_show', ['id' => $id]);
        }

        $note = trim($request->request->get('note', ''));

        if ($note) {
            $existingNotes = $order->getNotes() ?? '';
            $timestamp = (new \DateTimeImmutable())->format('d/m/Y H:i');
            $newNote = "[{$timestamp}] Note:\n{$note}";
            $order->setNotes($existingNotes ? $existingNotes . "\n\n" . $newNote : $newNote);

            $this->em->flush();

            $this->addFlash('success', 'Note ajoutée');
        }

        return $this->redirectToRoute('admin_order_show', ['id' => $id]);
    }

    #[Route('/admin/commandes/{id}/delivery-date', name: 'admin_order_set_delivery_date', methods: ['POST'])]
    public function setDeliveryDate(int $id, Request $request): Response
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('order_delivery_' . $id, $token)) {
            $this->addFlash('error', 'Token de sécurité invalide');
            return $this->redirectToRoute('admin_order_show', ['id' => $id]);
        }

        $dateStr = $request->request->get('delivery_date');

        if (!$dateStr) {
            $this->addFlash('error', 'Veuillez sélectionner une date');
            return $this->redirectToRoute('admin_order_show', ['id' => $id]);
        }

        try {
            $appointmentDate = new \DateTimeImmutable($dateStr);

            // Validate: not in the past
            $today = new \DateTimeImmutable('today');
            if ($appointmentDate < $today) {
                $this->addFlash('error', 'Impossible de réserver une date passée');
                return $this->redirectToRoute('admin_order_show', ['id' => $id]);
            }

            // Validate: not weekend
            $dayOfWeek = (int)$appointmentDate->format('w');
            if ($dayOfWeek === 0 || $dayOfWeek === 6) {
                $this->addFlash('error', 'Les livraisons ne sont pas disponibles le weekend');
                return $this->redirectToRoute('admin_order_show', ['id' => $id]);
            }

            // Validate: date is available
            if (!$this->deliveryAppointmentRepository->isDateAvailable($appointmentDate)) {
                $this->addFlash('error', 'Cette date n\'est plus disponible (déjà réservée)');
                return $this->redirectToRoute('admin_order_show', ['id' => $id]);
            }

            // Cancel existing appointment if any
            if ($order->hasDeliveryAppointment()) {
                $existingAppointment = $order->getDeliveryAppointment();
                $existingAppointment->setStatus(DeliveryAppointment::STATUS_CANCELLED);
            }

            // Create new appointment
            $appointment = new DeliveryAppointment();
            $appointment->setOrder($order);
            $appointment->setAppointmentDate($appointmentDate);
            $appointment->setStatus(DeliveryAppointment::STATUS_SCHEDULED);
            $appointment->setBookedBy('admin');

            $this->em->persist($appointment);
            $this->em->flush();

            // Send confirmation email to customer
            try {
                $customerEmail = $order->getUser()?->getEmail() ?? $order->getCustomerEmail();
                $customerName = $order->getDeliveryFirstName() . ' ' . $order->getDeliveryLastName();

                if ($customerEmail) {
                    $this->emailService->sendDeliveryAppointmentConfirmation($order, $appointment, $customerEmail, $customerName);
                    $this->addFlash('success', 'Date de livraison confirmée: ' . $appointment->getFormattedDate() . ' - Email envoyé au client');
                } else {
                    $this->addFlash('success', 'Date de livraison confirmée: ' . $appointment->getFormattedDate());
                }
            } catch (\Exception $e) {
                $this->logger->error('Erreur envoi email confirmation livraison: ' . $e->getMessage());
                $this->addFlash('success', 'Date de livraison confirmée: ' . $appointment->getFormattedDate());
            }

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la définition de la date de livraison: ' . $e->getMessage());
            $this->addFlash('error', 'Erreur lors de la définition de la date');
        }

        return $this->redirectToRoute('admin_order_show', ['id' => $id]);
    }

    #[Route('/admin/commandes/{id}/resend-booking-email', name: 'admin_order_resend_booking_email', methods: ['POST'])]
    public function resendBookingEmail(int $id, Request $request): Response
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('order_resend_' . $id, $token)) {
            $this->addFlash('error', 'Token de sécurité invalide');
            return $this->redirectToRoute('admin_order_show', ['id' => $id]);
        }

        // Generate token if not exists
        if (!$order->getDeliveryBookingToken()) {
            $order->generateDeliveryBookingToken();
            $this->em->flush();
        }

        // Send email
        if ($this->sendDeliveryBookingEmail($order)) {
            $this->addFlash('success', 'Email de réservation renvoyé au client');
        } else {
            $this->addFlash('error', 'Impossible d\'envoyer l\'email (vérifiez l\'adresse email du client)');
        }

        return $this->redirectToRoute('admin_order_show', ['id' => $id]);
    }

    #[Route('/admin/commandes/{id}/available-dates', name: 'admin_order_available_dates', methods: ['GET'])]
    public function getAvailableDates(int $id, Request $request): Response
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            return $this->json(['error' => 'Commande introuvable'], 404);
        }

        $year = $request->query->getInt('year', (int)date('Y'));
        $month = $request->query->getInt('month', (int)date('n'));

        $bookedDates = $this->deliveryAppointmentRepository->getBookedDatesForMonth($year, $month);

        return $this->json([
            'success' => true,
            'booked_dates' => $bookedDates,
        ]);
    }

    private function buildOrderTimeline(Order $order): array
    {
        $timeline = [];

        // Created
        $timeline[] = [
            'status' => 'created',
            'label' => 'Commande créée',
            'date' => $order->getCreatedAt(),
            'completed' => true,
            'icon' => 'bi-plus-circle',
        ];

        // Pending payment
        $timeline[] = [
            'status' => 'pending',
            'label' => 'En attente de paiement',
            'date' => $order->getCreatedAt(),
            'completed' => $order->getStatus() !== OrderStatus::PENDING,
            'current' => $order->getStatus() === OrderStatus::PENDING,
            'icon' => 'bi-hourglass-split',
        ];

        // Paid
        $isPaidOrAfter = in_array($order->getStatus(), [
            OrderStatus::PAID,
            OrderStatus::PROCESSING,
            OrderStatus::SHIPPED,
            OrderStatus::DELIVERED,
        ]);
        $timeline[] = [
            'status' => 'paid',
            'label' => 'Paiement reçu',
            'date' => $order->getPaidAt(),
            'completed' => $isPaidOrAfter,
            'current' => $order->getStatus() === OrderStatus::PAID,
            'icon' => 'bi-credit-card',
        ];

        // Processing
        $isProcessingOrAfter = in_array($order->getStatus(), [
            OrderStatus::PROCESSING,
            OrderStatus::SHIPPED,
            OrderStatus::DELIVERED,
        ]);
        $timeline[] = [
            'status' => 'processing',
            'label' => 'En préparation',
            'date' => $isProcessingOrAfter ? ($order->getPaidAt() ?? $order->getCreatedAt()) : null,
            'completed' => $isProcessingOrAfter && $order->getStatus() !== OrderStatus::PROCESSING,
            'current' => $order->getStatus() === OrderStatus::PROCESSING,
            'icon' => 'bi-box-seam',
        ];

        // Shipped
        $isShippedOrAfter = in_array($order->getStatus(), [
            OrderStatus::SHIPPED,
            OrderStatus::DELIVERED,
        ]);
        $timeline[] = [
            'status' => 'shipped',
            'label' => 'En cours de livraison',
            'date' => $order->getShippedAt(),
            'completed' => $order->getStatus() === OrderStatus::DELIVERED,
            'current' => $order->getStatus() === OrderStatus::SHIPPED,
            'icon' => 'bi-truck',
        ];

        // Delivered
        $timeline[] = [
            'status' => 'delivered',
            'label' => 'Livrée',
            'date' => $order->getDeliveredAt(),
            'completed' => $order->getStatus() === OrderStatus::DELIVERED,
            'current' => false,
            'icon' => 'bi-check-circle',
        ];

        // Handle cancelled/refunded
        if ($order->getStatus() === OrderStatus::CANCELLED) {
            $timeline[] = [
                'status' => 'cancelled',
                'label' => 'Annulée',
                'date' => $order->getCancelledAt(),
                'completed' => true,
                'current' => true,
                'icon' => 'bi-x-circle',
                'variant' => 'danger',
            ];
        }

        if ($order->getStatus() === OrderStatus::REFUNDED) {
            $timeline[] = [
                'status' => 'refunded',
                'label' => 'Remboursée',
                'date' => $order->getCancelledAt(),
                'completed' => true,
                'current' => true,
                'icon' => 'bi-arrow-counterclockwise',
                'variant' => 'warning',
            ];
        }

        return $timeline;
    }
}
