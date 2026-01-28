<?php

namespace App\Entity;

use App\Repository\DeliveryAppointmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeliveryAppointmentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class DeliveryAppointment
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'deliveryAppointment', targetEntity: Order::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Order $order = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $appointmentDate = null;

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_SCHEDULED;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $bookedBy = null; // 'customer' or 'admin'

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // === GETTERS & SETTERS ===

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;
        return $this;
    }

    public function getAppointmentDate(): ?\DateTimeImmutable
    {
        return $this->appointmentDate;
    }

    public function setAppointmentDate(\DateTimeImmutable $appointmentDate): static
    {
        $this->appointmentDate = $appointmentDate;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getBookedBy(): ?string
    {
        return $this->bookedBy;
    }

    public function setBookedBy(?string $bookedBy): static
    {
        $this->bookedBy = $bookedBy;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // === HELPER METHODS ===

    public function getFormattedDate(): string
    {
        $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        $months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

        $dayName = $days[(int)$this->appointmentDate->format('w')];
        $day = $this->appointmentDate->format('d');
        $month = $months[(int)$this->appointmentDate->format('n') - 1];
        $year = $this->appointmentDate->format('Y');

        return "$dayName $day $month $year";
    }

    public function getShortDate(): string
    {
        $days = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
        $months = ['jan', 'fév', 'mar', 'avr', 'mai', 'juin', 'juil', 'aoû', 'sep', 'oct', 'nov', 'déc'];

        $dayName = $days[(int)$this->appointmentDate->format('w')];
        $day = $this->appointmentDate->format('d');
        $month = $months[(int)$this->appointmentDate->format('n') - 1];

        return "$dayName $day $month";
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_SCHEDULED => 'Planifiée',
            self::STATUS_COMPLETED => 'Effectuée',
            self::STATUS_CANCELLED => 'Annulée',
            default => 'Inconnu',
        };
    }

    public function getBookedByLabel(): string
    {
        return match($this->bookedBy) {
            'customer' => 'Client',
            'admin' => 'Service client',
            default => '-',
        };
    }
}
