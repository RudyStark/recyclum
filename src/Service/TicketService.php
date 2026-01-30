<?php

namespace App\Service;

use App\Entity\ContactMessage;
use App\Entity\ContactReply;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Enum\ContactStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TicketService
{
    public function __construct(
        private EntityManagerInterface $em,
        private AttachmentService $attachmentService,
        private EmailService $emailService,
    ) {}

    /**
     * Valide le contenu d'une réponse
     *
     * @return string|null Message d'erreur ou null si valide
     */
    public function validateReplyContent(string $content): ?string
    {
        $content = trim($content);

        if (empty($content)) {
            return 'Le message ne peut pas être vide.';
        }

        if (strlen($content) < 5) {
            return 'Le message doit contenir au moins 5 caractères.';
        }

        return null;
    }

    /**
     * Ajoute une réponse client à un ticket
     *
     * @param ContactMessage $ticket Le ticket
     * @param string $content Le contenu de la réponse
     * @param array<UploadedFile> $uploadedFiles Les fichiers joints
     * @return ContactReply La réponse créée
     */
    public function addClientReply(
        ContactMessage $ticket,
        string $content,
        array $uploadedFiles = []
    ): ContactReply {
        $reply = new ContactReply();
        $reply->setContactMessage($ticket);
        $reply->setContent(trim($content));
        $reply->setIsAdminReply(false);

        $ticket->addReply($reply);

        // Gérer les pièces jointes
        if (!empty($uploadedFiles)) {
            $this->attachmentService->handleUploadsForReply($uploadedFiles, $reply);
        }

        // Remettre le ticket en attente si nécessaire
        $this->updateStatusAfterClientReply($ticket);

        $this->em->persist($reply);
        $this->em->flush();

        return $reply;
    }

    /**
     * Ajoute une réponse admin à un ticket
     *
     * @param ContactMessage $ticket Le ticket
     * @param string $content Le contenu de la réponse
     * @param string|null $adminName Nom de l'admin
     * @param array<UploadedFile> $uploadedFiles Les fichiers joints
     * @param bool $closeTicket Fermer le ticket après la réponse
     * @return ContactReply La réponse créée
     */
    public function addAdminReply(
        ContactMessage $ticket,
        string $content,
        ?string $adminName = null,
        array $uploadedFiles = [],
        bool $closeTicket = false
    ): ContactReply {
        $reply = new ContactReply();
        $reply->setContactMessage($ticket);
        $reply->setContent(trim($content));
        $reply->setIsAdminReply(true);

        if ($adminName) {
            $reply->setRepliedBy($adminName);
        }

        $ticket->addReply($reply);

        // Gérer les pièces jointes
        if (!empty($uploadedFiles)) {
            $this->attachmentService->handleUploadsForReply($uploadedFiles, $reply);
        }

        // Mettre à jour le statut
        if ($closeTicket) {
            $ticket->setStatus(ContactStatus::CLOSED);
            $ticket->setClosedAt(new \DateTimeImmutable());
        } else {
            $ticket->setStatus(ContactStatus::ANSWERED);
        }

        $this->em->persist($reply);
        $this->em->flush();

        return $reply;
    }

    /**
     * Met à jour le statut après une réponse client
     */
    private function updateStatusAfterClientReply(ContactMessage $ticket): void
    {
        $status = $ticket->getStatus();

        if ($status === ContactStatus::ANSWERED || $status === ContactStatus::READ) {
            $ticket->setStatus(ContactStatus::IN_PROGRESS);
        }
    }

    /**
     * Envoie une notification admin pour une nouvelle réponse client
     */
    public function notifyAdminOfClientReply(
        ContactMessage $ticket,
        ContactReply $reply,
        string $adminViewUrl
    ): void {
        try {
            $this->emailService->sendClientReplyNotificationToAdmin($ticket, $reply, $adminViewUrl);
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas bloquer
        }
    }

    /**
     * Calcule le statut de garantie pour un produit commandé
     *
     * @return array{status: string, expirationDate: \DateTimeImmutable|null, isValid: bool}
     */
    public function calculateWarrantyStatus(OrderItem $item): array
    {
        $product = $item->getProduct();
        $order = $item->getOrder();

        if (!$product || !$order) {
            return [
                'status' => 'N/A',
                'expirationDate' => null,
                'isValid' => false
            ];
        }

        $warrantyMonths = $product->getWarrantyMonths();
        $orderDate = $order->getCreatedAt();

        if (!$warrantyMonths || !$orderDate) {
            return [
                'status' => 'N/A',
                'expirationDate' => null,
                'isValid' => false
            ];
        }

        $expirationDate = $orderDate->modify("+{$warrantyMonths} months");
        $isValid = $expirationDate > new \DateTimeImmutable();

        return [
            'status' => $isValid ? 'Valide' : 'Expirée',
            'expirationDate' => $expirationDate,
            'isValid' => $isValid
        ];
    }

    /**
     * Vérifie si un ticket appartient à un utilisateur
     */
    public function ticketBelongsToUser(ContactMessage $ticket, User $user): bool
    {
        $ticketUser = $ticket->getUser();
        return $ticketUser !== null && $ticketUser->getId() === $user->getId();
    }

    /**
     * Vérifie si un ticket peut recevoir des réponses
     */
    public function canReply(ContactMessage $ticket): bool
    {
        return !$ticket->isClosed();
    }
}
