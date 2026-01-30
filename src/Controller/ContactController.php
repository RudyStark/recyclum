<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use App\Entity\ContactReply;
use App\Entity\User;
use App\Enum\ContactStatus;
use App\Enum\ContactSubject;
use App\Repository\ContactMessageRepository;
use App\Repository\OrderRepository;
use App\Service\AttachmentService;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/contact', name: 'contact_')]
class ContactController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ContactMessageRepository $contactMessageRepository,
        private OrderRepository $orderRepository,
        private EmailService $emailService,
        private AttachmentService $attachmentService,
    ) {
    }

    /**
     * Affiche le formulaire de contact
     */
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        // Si POST, traiter la soumission
        if ($request->isMethod('POST')) {
            return $this->handleSubmission($request, $user);
        }

        // Préparer les sujets pour le formulaire
        $subjects = [];
        foreach (ContactSubject::cases() as $subject) {
            $subjects[] = [
                'value' => $subject->value,
                'label' => $subject->getLabel(),
                'icon' => $subject->getIcon(),
                'color' => $subject->getColor(),
                'requiresOrder' => $subject->requiresOrder(),
                'requiresProduct' => $subject->requiresProduct(),
            ];
        }

        return $this->render('contact/index.html.twig', [
            'subjects' => $subjects,
            'user' => $user,
        ]);
    }

    /**
     * Traite la soumission du formulaire
     */
    private function handleSubmission(Request $request, ?User $user): Response
    {
        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('contact_form', $token)) {
            $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('contact_index');
        }

        // Récupérer les données
        $subjectValue = $request->request->get('subject');
        $firstName = trim($request->request->get('firstName', ''));
        $lastName = trim($request->request->get('lastName', ''));
        $email = trim($request->request->get('email', ''));
        $phone = trim($request->request->get('phone', ''));
        $message = trim($request->request->get('message', ''));
        $orderId = $request->request->get('orderId');
        $orderItemId = $request->request->get('orderItemId');

        // Validation basique
        $errors = [];
        if (!$subjectValue || !ContactSubject::tryFrom($subjectValue)) {
            $errors[] = 'Veuillez sélectionner un sujet.';
        }
        if (empty($firstName)) {
            $errors[] = 'Le prénom est requis.';
        }
        if (empty($lastName)) {
            $errors[] = 'Le nom est requis.';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Une adresse email valide est requise.';
        }
        if (empty($message)) {
            $errors[] = 'Le message est requis.';
        }
        if (strlen($message) < 10) {
            $errors[] = 'Le message doit contenir au moins 10 caractères.';
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
            return $this->redirectToRoute('contact_index');
        }

        // Créer le message de contact
        $subject = ContactSubject::from($subjectValue);
        $contactMessage = new ContactMessage();
        $contactMessage->setSubject($subject);
        $contactMessage->setFirstName($firstName);
        $contactMessage->setLastName($lastName);
        $contactMessage->setEmail($email);
        $contactMessage->setPhone($phone ?: null);
        $contactMessage->setMessage($message);

        // Si utilisateur connecté
        if ($user) {
            $contactMessage->setUser($user);
        }

        // Si commande liée
        if ($orderId) {
            $order = $this->orderRepository->find($orderId);
            if ($order && ($user === null || $order->getUser() === $user)) {
                $contactMessage->setRelatedOrder($order);

                // Si produit lié (pour garantie)
                if ($orderItemId) {
                    foreach ($order->getItems() as $item) {
                        if ($item->getId() === (int) $orderItemId) {
                            $contactMessage->setRelatedOrderItem($item);
                            break;
                        }
                    }
                }
            }
        }

        // Gérer les pièces jointes
        $uploadedFiles = $request->files->get('attachments', []);
        if (!empty($uploadedFiles)) {
            $this->attachmentService->handleUploadsForMessage($uploadedFiles, $contactMessage);
        }

        $this->em->persist($contactMessage);
        $this->em->flush();

        // Envoyer les emails
        try {
            // URL du ticket pour le client (magic link)
            $ticketUrl = $this->generateUrl('contact_ticket_view', [
                'token' => $contactMessage->getReplyToken()
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            // Email de confirmation au client
            $this->emailService->sendContactConfirmationToClient($contactMessage, $ticketUrl);

            // Email de notification à l'admin
            $adminViewUrl = $this->generateUrl('admin_contact_show', [
                'id' => $contactMessage->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL);
            $this->emailService->sendNewContactNotificationToAdmin($contactMessage, $adminViewUrl);
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas bloquer la soumission
        }

        return $this->redirectToRoute('contact_confirmation', [
            'ticketNumber' => $contactMessage->getTicketNumber()
        ]);
    }

    /**
     * Page de confirmation après soumission
     */
    #[Route('/confirmation/{ticketNumber}', name: 'confirmation', methods: ['GET'])]
    public function confirmation(string $ticketNumber): Response
    {
        $contactMessage = $this->contactMessageRepository->findOneBy(['ticketNumber' => $ticketNumber]);

        if (!$contactMessage) {
            throw $this->createNotFoundException('Message non trouvé.');
        }

        return $this->render('contact/confirmation.html.twig', [
            'contactMessage' => $contactMessage,
        ]);
    }

    /**
     * API: Récupère les commandes de l'utilisateur connecté
     */
    #[Route('/orders', name: 'orders', methods: ['GET'])]
    public function getOrders(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['orders' => []]);
        }

        $orders = $this->orderRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            20
        );

        $ordersData = [];
        foreach ($orders as $order) {
            $ordersData[] = [
                'id' => $order->getId(),
                'orderNumber' => $order->getOrderNumber(),
                'date' => $order->getCreatedAt()->format('d/m/Y'),
                'total' => $order->getTotalFormatted(),
                'status' => $order->getStatus()->getLabel(),
            ];
        }

        return new JsonResponse(['orders' => $ordersData]);
    }

    /**
     * API: Récupère les produits d'une commande
     */
    #[Route('/order/{id}/items', name: 'order_items', methods: ['GET'])]
    public function getOrderItems(int $id): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        $order = $this->orderRepository->find($id);

        if (!$order) {
            return new JsonResponse(['items' => []]);
        }

        // Vérifier que l'utilisateur a accès à cette commande
        if ($user && $order->getUser() !== $user) {
            return new JsonResponse(['items' => []]);
        }

        $itemsData = [];
        foreach ($order->getItems() as $item) {
            $product = $item->getProduct();
            $warrantyMonths = $product ? $product->getWarrantyMonths() : null;
            $warrantyEnd = null;
            $warrantyStatus = 'N/A';

            if ($warrantyMonths && $order->getCreatedAt()) {
                $warrantyEnd = $order->getCreatedAt()->modify("+{$warrantyMonths} months");
                $warrantyStatus = $warrantyEnd > new \DateTimeImmutable() ? 'Valide' : 'Expirée';
            }

            $itemsData[] = [
                'id' => $item->getId(),
                'productName' => $item->getProductName(),
                'quantity' => $item->getQuantity(),
                'price' => number_format($item->getUnitPrice(), 2, ',', ' ') . ' €',
                'warrantyMonths' => $warrantyMonths,
                'warrantyEnd' => $warrantyEnd ? $warrantyEnd->format('d/m/Y') : null,
                'warrantyStatus' => $warrantyStatus,
            ];
        }

        return new JsonResponse(['items' => $itemsData]);
    }

    /**
     * Vue du ticket via magic link (accessible à tous)
     */
    #[Route('/ticket/{token}', name: 'ticket_view', methods: ['GET'])]
    public function ticketView(string $token): Response
    {
        $contactMessage = $this->contactMessageRepository->findOneBy(['replyToken' => $token]);

        if (!$contactMessage) {
            throw $this->createNotFoundException('Ticket introuvable ou lien invalide.');
        }

        return $this->render('contact/ticket.html.twig', [
            'message' => $contactMessage,
            'token' => $token,
        ]);
    }

    /**
     * Répondre au ticket via magic link
     */
    #[Route('/ticket/{token}/reply', name: 'ticket_reply', methods: ['POST'])]
    public function ticketReply(string $token, Request $request): Response
    {
        $contactMessage = $this->contactMessageRepository->findOneBy(['replyToken' => $token]);

        if (!$contactMessage) {
            throw $this->createNotFoundException('Ticket introuvable ou lien invalide.');
        }

        // Vérifier le token CSRF
        $csrfToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('ticket_reply_' . $token, $csrfToken)) {
            $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('contact_ticket_view', ['token' => $token]);
        }

        // Vérifier que le ticket n'est pas fermé
        if ($contactMessage->isClosed()) {
            $this->addFlash('error', 'Ce ticket est fermé. Vous ne pouvez plus y répondre.');
            return $this->redirectToRoute('contact_ticket_view', ['token' => $token]);
        }

        $content = trim($request->request->get('content', ''));

        if (empty($content)) {
            $this->addFlash('error', 'Le message ne peut pas être vide.');
            return $this->redirectToRoute('contact_ticket_view', ['token' => $token]);
        }

        if (strlen($content) < 5) {
            $this->addFlash('error', 'Le message doit contenir au moins 5 caractères.');
            return $this->redirectToRoute('contact_ticket_view', ['token' => $token]);
        }

        // Créer la réponse client
        $reply = new ContactReply();
        $reply->setContactMessage($contactMessage);
        $reply->setContent($content);
        $reply->setIsAdminReply(false);

        $contactMessage->addReply($reply);

        // Gérer les pièces jointes
        $uploadedFiles = $request->files->get('attachments', []);
        if (!empty($uploadedFiles)) {
            $this->attachmentService->handleUploadsForReply($uploadedFiles, $reply);
        }

        // Remettre le ticket en attente si nécessaire
        if ($contactMessage->getStatus() === ContactStatus::ANSWERED || $contactMessage->getStatus() === ContactStatus::READ) {
            $contactMessage->setStatus(ContactStatus::IN_PROGRESS);
        }

        $this->em->persist($reply);
        $this->em->flush();

        // Notifier l'admin de la nouvelle réponse client
        try {
            $adminViewUrl = $this->generateUrl('admin_contact_show', [
                'id' => $contactMessage->getId()
            ], UrlGeneratorInterface::ABSOLUTE_URL);
            $this->emailService->sendClientReplyNotificationToAdmin($contactMessage, $reply, $adminViewUrl);
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas bloquer
        }

        $this->addFlash('success', 'Votre réponse a été envoyée. Notre équipe vous répondra dans les plus brefs délais.');

        return $this->redirectToRoute('contact_ticket_view', ['token' => $token]);
    }
}
