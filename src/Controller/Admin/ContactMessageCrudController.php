<?php

namespace App\Controller\Admin;

use App\Entity\ContactMessage;
use App\Entity\ContactReply;
use App\Enum\ContactStatus;
use App\Enum\ContactSubject;
use App\Enum\OrderStatus;
use App\Repository\ContactMessageRepository;
use App\Repository\OrderRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ContactMessageCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ContactMessageRepository $contactMessageRepository,
        private OrderRepository $orderRepository,
        private EmailService $emailService,
        private LoggerInterface $logger
    ) {}

    public static function getEntityFqcn(): string
    {
        return ContactMessage::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Message')
            ->setEntityLabelInPlural('Messages de contact')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function index(AdminContext $context): Response
    {
        return $this->redirectToRoute('admin_contact_index');
    }

    #[Route('/admin/contacts', name: 'admin_contact_index', methods: ['GET'])]
    public function contactIndex(Request $request): Response
    {
        // Get filter parameters
        $status = $request->query->get('status', 'all');
        $subject = $request->query->get('subject', 'all');
        $search = $request->query->get('search', '');
        $page = $request->query->getInt('page', 1);
        $perPage = 20;

        // Convert filters
        $statusEnum = $status !== 'all' ? ContactStatus::tryFrom($status) : null;
        $subjectEnum = $subject !== 'all' ? ContactSubject::tryFrom($subject) : null;

        // Get filtered messages
        $messages = $this->contactMessageRepository->findByFilters(
            $statusEnum,
            $subjectEnum,
            $search ?: null,
            null,
            null,
            'createdAt',
            'DESC',
            $perPage,
            ($page - 1) * $perPage
        );

        $totalItems = $this->contactMessageRepository->countByFilters(
            $statusEnum,
            $subjectEnum,
            $search ?: null
        );

        $totalPages = ceil($totalItems / $perPage);

        // Get stats
        $stats = $this->contactMessageRepository->getStatsForDashboard();

        return $this->render('admin/contact/index.html.twig', [
            'messages' => $messages,
            'stats' => $stats,
            'currentStatus' => $status,
            'currentSubject' => $subject,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'statuses' => ContactStatus::cases(),
            'subjects' => ContactSubject::cases(),
        ]);
    }

    #[Route('/admin/contacts/{id}', name: 'admin_contact_show', methods: ['GET'])]
    public function contactShow(int $id): Response
    {
        $message = $this->contactMessageRepository->find($id);

        if (!$message) {
            throw $this->createNotFoundException('Message introuvable');
        }

        // Mark as read if new
        if ($message->getStatus() === ContactStatus::NEW) {
            $message->markAsRead();
            $this->em->flush();
        }

        return $this->render('admin/contact/show.html.twig', [
            'message' => $message,
            'orderStatuses' => OrderStatus::cases(),
            'contactStatuses' => ContactStatus::cases(),
        ]);
    }

    #[Route('/admin/contacts/{id}/reply', name: 'admin_contact_reply', methods: ['POST'])]
    public function reply(int $id, Request $request): Response
    {
        $message = $this->contactMessageRepository->find($id);

        if (!$message) {
            throw $this->createNotFoundException('Message introuvable');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('contact_reply_' . $id, $token)) {
            $this->addFlash('error', 'Token de sécurité invalide');
            return $this->redirectToRoute('admin_contact_show', ['id' => $id]);
        }

        $content = trim($request->request->get('content', ''));
        $closeTicket = $request->request->getBoolean('close_ticket');

        if (empty($content)) {
            $this->addFlash('error', 'Le contenu de la réponse ne peut pas être vide');
            return $this->redirectToRoute('admin_contact_show', ['id' => $id]);
        }

        // Create reply
        $reply = new ContactReply();
        $reply->setContactMessage($message);
        $reply->setContent($content);
        $reply->setRepliedBy('Équipe Recyclum'); // Could be dynamic if admin users have names
        $reply->setIsAdminReply(true);

        $message->addReply($reply);
        $message->setStatus(ContactStatus::ANSWERED);

        if ($closeTicket) {
            $message->markAsClosed();
        }

        $this->em->persist($reply);
        $this->em->flush();

        // Send email to client
        try {
            $ticketUrl = $this->generateUrl('contact_ticket_view', [
                'token' => $message->getReplyToken()
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $this->emailService->sendContactReplyToClient($message, $reply, $ticketUrl);
            $this->addFlash('success', 'Réponse envoyée au client' . ($closeTicket ? ' - Ticket fermé' : ''));
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi email réponse contact: ' . $e->getMessage());
            $this->addFlash('warning', 'Réponse enregistrée mais l\'email n\'a pas pu être envoyé');
        }

        return $this->redirectToRoute('admin_contact_show', ['id' => $id]);
    }

    #[Route('/admin/contacts/{id}/status', name: 'admin_contact_status', methods: ['POST'])]
    public function updateStatus(int $id, Request $request): Response
    {
        $message = $this->contactMessageRepository->find($id);

        if (!$message) {
            throw $this->createNotFoundException('Message introuvable');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('contact_status_' . $id, $token)) {
            $this->addFlash('error', 'Token de sécurité invalide');
            return $this->redirectToRoute('admin_contact_show', ['id' => $id]);
        }

        $newStatus = $request->request->get('status');

        try {
            $statusEnum = ContactStatus::from($newStatus);
            $message->setStatus($statusEnum);

            if ($statusEnum === ContactStatus::CLOSED) {
                $message->setClosedAt(new \DateTimeImmutable());
            }

            $this->em->flush();
            $this->addFlash('success', 'Statut mis à jour: ' . $statusEnum->getLabel());
        } catch (\ValueError $e) {
            $this->addFlash('error', 'Statut invalide');
        }

        return $this->redirectToRoute('admin_contact_show', ['id' => $id]);
    }

    #[Route('/admin/contacts/{id}/notes', name: 'admin_contact_notes', methods: ['POST'])]
    public function updateNotes(int $id, Request $request): Response
    {
        $message = $this->contactMessageRepository->find($id);

        if (!$message) {
            throw $this->createNotFoundException('Message introuvable');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('contact_notes_' . $id, $token)) {
            $this->addFlash('error', 'Token de sécurité invalide');
            return $this->redirectToRoute('admin_contact_show', ['id' => $id]);
        }

        $notes = trim($request->request->get('notes', ''));
        $message->setAdminNotes($notes ?: null);

        $this->em->flush();
        $this->addFlash('success', 'Notes mises à jour');

        return $this->redirectToRoute('admin_contact_show', ['id' => $id]);
    }

    #[Route('/admin/contacts/{id}/order-status', name: 'admin_contact_order_status', methods: ['POST'])]
    public function updateOrderStatus(int $id, Request $request): Response
    {
        $message = $this->contactMessageRepository->find($id);

        if (!$message || !$message->getRelatedOrder()) {
            throw $this->createNotFoundException('Message ou commande introuvable');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('order_status_from_contact_' . $id, $token)) {
            $this->addFlash('error', 'Token de sécurité invalide');
            return $this->redirectToRoute('admin_contact_show', ['id' => $id]);
        }

        $newStatus = $request->request->get('order_status');
        $order = $message->getRelatedOrder();

        try {
            $statusEnum = OrderStatus::from($newStatus);
            $order->setStatus($statusEnum);

            // Update timestamps based on status
            $now = new \DateTimeImmutable();
            switch ($statusEnum) {
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

            $this->em->flush();
            $this->addFlash('success', 'Statut de la commande ' . $order->getOrderNumber() . ' mis à jour: ' . $statusEnum->getLabel());
        } catch (\ValueError $e) {
            $this->addFlash('error', 'Statut de commande invalide');
        }

        return $this->redirectToRoute('admin_contact_show', ['id' => $id]);
    }
}
