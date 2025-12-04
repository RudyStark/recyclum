<?php

namespace App\Entity;

use App\Repository\RepairAppointmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RepairAppointmentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class RepairAppointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'appointment', targetEntity: RepairRequest::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?RepairRequest $repairRequest = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $appointmentDate = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $appointmentTime = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $confirmedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getRepairRequest(): ?RepairRequest
    {
        return $this->repairRequest;
    }

    public function setRepairRequest(?RepairRequest $repairRequest): static
    {
        $this->repairRequest = $repairRequest;
        return $this;
    }

    public function getAppointmentDate(): ?\DateTimeInterface
    {
        return $this->appointmentDate;
    }

    public function setAppointmentDate(\DateTimeInterface $appointmentDate): static
    {
        $this->appointmentDate = $appointmentDate;
        return $this;
    }

    public function getAppointmentTime(): ?\DateTimeInterface
    {
        return $this->appointmentTime;
    }

    public function setAppointmentTime(\DateTimeInterface $appointmentTime): static
    {
        $this->appointmentTime = $appointmentTime;
        return $this;
    }

    public function getConfirmedAt(): ?\DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function setConfirmedAt(\DateTimeImmutable $confirmedAt): static
    {
        $this->confirmedAt = $confirmedAt;
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

    public function getFullDateTime(): string
    {
        return sprintf(
            '%s à %s',
            $this->appointmentDate->format('d/m/Y'),
            $this->appointmentTime->format('H:i')
        );
    }

    public function getDateFormatted(): string
    {
        $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        $months = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

        $dayName = $days[(int)$this->appointmentDate->format('w')];
        $day = $this->appointmentDate->format('d');
        $month = $months[(int)$this->appointmentDate->format('n')];
        $year = $this->appointmentDate->format('Y');

        return sprintf('%s %s %s %s', $dayName, $day, $month, $year);
    }
}
