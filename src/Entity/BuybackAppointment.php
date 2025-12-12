<?php

namespace App\Entity;

use App\Repository\BuybackAppointmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BuybackAppointmentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class BuybackAppointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: BuybackRequest::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?BuybackRequest $buybackRequest = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $appointmentDate = null;

    #[ORM\Column(length: 5)]
    private ?string $appointmentTime = null;

    #[ORM\Column(length: 50)]
    private ?string $status = 'scheduled'; // scheduled, completed, cancelled

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
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

    public function getBuybackRequest(): ?BuybackRequest
    {
        return $this->buybackRequest;
    }

    public function setBuybackRequest(?BuybackRequest $buybackRequest): static
    {
        $this->buybackRequest = $buybackRequest;
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

    public function getAppointmentTime(): ?string
    {
        return $this->appointmentTime;
    }

    public function setAppointmentTime(string $appointmentTime): static
    {
        $this->appointmentTime = $appointmentTime;
        return $this;
    }

    public function getStatus(): ?string
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // === MÉTHODES HELPER ===

    /**
     * Retourne la date formatée en français
     */
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

    /**
     * Retourne la date et l'heure complète
     */
    public function getFullDateTime(): string
    {
        return $this->getFormattedDate() . ' à ' . $this->appointmentTime;
    }

    /**
     * Retourne la date au format court (ex: "Lun 25 déc")
     */
    public function getShortDate(): string
    {
        $days = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
        $months = ['jan', 'fév', 'mar', 'avr', 'mai', 'juin', 'juil', 'aoû', 'sep', 'oct', 'nov', 'déc'];

        $dayName = $days[(int)$this->appointmentDate->format('w')];
        $day = $this->appointmentDate->format('d');
        $month = $months[(int)$this->appointmentDate->format('n') - 1];

        return "$dayName $day $month";
    }
}
