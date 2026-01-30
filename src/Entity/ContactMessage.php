<?php

namespace App\Entity;

use App\Enum\ContactStatus;
use App\Enum\ContactSubject;
use App\Repository\ContactMessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactMessageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ContactMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $ticketNumber = null;

    #[ORM\Column(length: 30, enumType: ContactSubject::class)]
    private ?ContactSubject $subject = null;

    #[ORM\Column(length: 30, enumType: ContactStatus::class)]
    private ContactStatus $status = ContactStatus::NEW;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Order::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Order $relatedOrder = null;

    #[ORM\ManyToOne(targetEntity: OrderItem::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?OrderItem $relatedOrderItem = null;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminNotes = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $closedAt = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $replyToken = null;

    /**
     * @var Collection<int, ContactReply>
     */
    #[ORM\OneToMany(targetEntity: ContactReply::class, mappedBy: 'contactMessage', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $replies;

    /**
     * @var Collection<int, ContactAttachment>
     */
    #[ORM\OneToMany(targetEntity: ContactAttachment::class, mappedBy: 'contactMessage', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $attachments;

    public function __construct()
    {
        $this->replies = new ArrayCollection();
        $this->attachments = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->generateTicketNumber();
        $this->generateReplyToken();
    }

    public function generateTicketNumber(): void
    {
        $this->ticketNumber = 'TKT-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    public function generateReplyToken(): void
    {
        $this->replyToken = bin2hex(random_bytes(32));
    }

    public function getReplyToken(): ?string
    {
        return $this->replyToken;
    }

    public function setReplyToken(string $replyToken): static
    {
        $this->replyToken = $replyToken;
        return $this;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTicketNumber(): ?string
    {
        return $this->ticketNumber;
    }

    public function setTicketNumber(string $ticketNumber): static
    {
        $this->ticketNumber = $ticketNumber;
        return $this;
    }

    public function getSubject(): ?ContactSubject
    {
        return $this->subject;
    }

    public function setSubject(ContactSubject $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getStatus(): ContactStatus
    {
        return $this->status;
    }

    public function setStatus(ContactStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getRelatedOrder(): ?Order
    {
        return $this->relatedOrder;
    }

    public function setRelatedOrder(?Order $relatedOrder): static
    {
        $this->relatedOrder = $relatedOrder;
        return $this;
    }

    public function getRelatedOrderItem(): ?OrderItem
    {
        return $this->relatedOrderItem;
    }

    public function setRelatedOrderItem(?OrderItem $relatedOrderItem): static
    {
        $this->relatedOrderItem = $relatedOrderItem;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getAdminNotes(): ?string
    {
        return $this->adminNotes;
    }

    public function setAdminNotes(?string $adminNotes): static
    {
        $this->adminNotes = $adminNotes;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function setReadAt(?\DateTimeImmutable $readAt): static
    {
        $this->readAt = $readAt;
        return $this;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function setClosedAt(?\DateTimeImmutable $closedAt): static
    {
        $this->closedAt = $closedAt;
        return $this;
    }

    /**
     * @return Collection<int, ContactReply>
     */
    public function getReplies(): Collection
    {
        return $this->replies;
    }

    public function addReply(ContactReply $reply): static
    {
        if (!$this->replies->contains($reply)) {
            $this->replies->add($reply);
            $reply->setContactMessage($this);
        }
        return $this;
    }

    public function removeReply(ContactReply $reply): static
    {
        if ($this->replies->removeElement($reply)) {
            if ($reply->getContactMessage() === $this) {
                $reply->setContactMessage(null);
            }
        }
        return $this;
    }

    // Helper methods

    public function isNew(): bool
    {
        return $this->status === ContactStatus::NEW;
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    public function isClosed(): bool
    {
        return $this->status === ContactStatus::CLOSED;
    }

    public function isPriority(): bool
    {
        return $this->subject?->isPriority() ?? false;
    }

    public function markAsRead(): void
    {
        if ($this->status === ContactStatus::NEW) {
            $this->status = ContactStatus::READ;
            $this->readAt = new \DateTimeImmutable();
        }
    }

    public function markAsClosed(): void
    {
        $this->status = ContactStatus::CLOSED;
        $this->closedAt = new \DateTimeImmutable();
    }

    public function hasReplies(): bool
    {
        return !$this->replies->isEmpty();
    }

    public function getLastReply(): ?ContactReply
    {
        if ($this->replies->isEmpty()) {
            return null;
        }
        return $this->replies->last();
    }

    public function getRepliesCount(): int
    {
        return $this->replies->count();
    }

    /**
     * Calculate warranty expiration date if this is a warranty request
     */
    public function getWarrantyExpirationDate(): ?\DateTimeImmutable
    {
        if (!$this->relatedOrderItem || !$this->relatedOrder) {
            return null;
        }

        $product = $this->relatedOrderItem->getProduct();
        if (!$product) {
            return null;
        }

        $warrantyMonths = $product->getWarrantyMonths();
        if (!$warrantyMonths) {
            return null;
        }

        $purchaseDate = $this->relatedOrder->getCreatedAt();
        return $purchaseDate->modify("+{$warrantyMonths} months");
    }

    /**
     * Check if warranty is still valid
     */
    public function isWarrantyValid(): bool
    {
        $expirationDate = $this->getWarrantyExpirationDate();
        if (!$expirationDate) {
            return false;
        }

        return $expirationDate > new \DateTimeImmutable();
    }

    /**
     * Get warranty status label
     */
    public function getWarrantyStatusLabel(): string
    {
        if (!$this->relatedOrderItem) {
            return 'N/A';
        }

        if ($this->isWarrantyValid()) {
            $expirationDate = $this->getWarrantyExpirationDate();
            return 'Valide jusqu\'au ' . $expirationDate->format('d/m/Y');
        }

        return 'Expirée';
    }

    public function isMember(): bool
    {
        return $this->user !== null;
    }

    /**
     * @return Collection<int, ContactAttachment>
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(ContactAttachment $attachment): static
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setContactMessage($this);
        }
        return $this;
    }

    public function removeAttachment(ContactAttachment $attachment): static
    {
        if ($this->attachments->removeElement($attachment)) {
            if ($attachment->getContactMessage() === $this) {
                $attachment->setContactMessage(null);
            }
        }
        return $this;
    }

    public function hasAttachments(): bool
    {
        return !$this->attachments->isEmpty();
    }

    /**
     * Get all attachments including from replies
     */
    public function getAllAttachments(): array
    {
        $allAttachments = $this->attachments->toArray();

        foreach ($this->replies as $reply) {
            foreach ($reply->getAttachments() as $attachment) {
                $allAttachments[] = $attachment;
            }
        }

        return $allAttachments;
    }
}
