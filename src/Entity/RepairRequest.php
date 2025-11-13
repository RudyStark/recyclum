<?php

namespace App\Entity;

use App\Repository\RepairRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RepairRequestRepository::class)]
#[ORM\HasLifecycleCallbacks]
class RepairRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $category = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $brand = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $model = null;

    #[ORM\Column(length: 255)]
    private ?string $issue = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $issueDetails = null;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 20)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $zipCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $preferredDate = null;

    #[ORM\Column(length: 20)]
    private ?string $repairLocation = 'atelier';

    #[ORM\Column]
    private ?bool $urgency = false;

    #[ORM\Column(length: 50)]
    private ?string $status = 'pending';

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

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(?string $brand): static
    {
        $this->brand = $brand;
        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): static
    {
        $this->model = $model;
        return $this;
    }

    public function getIssue(): ?string
    {
        return $this->issue;
    }

    public function setIssue(string $issue): static
    {
        $this->issue = $issue;
        return $this;
    }

    public function getIssueDetails(): ?string
    {
        return $this->issueDetails;
    }

    public function setIssueDetails(string $issueDetails): static
    {
        $this->issueDetails = $issueDetails;
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

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): static
    {
        $this->zipCode = $zipCode;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getPreferredDate(): ?\DateTimeInterface
    {
        return $this->preferredDate;
    }

    public function setPreferredDate(?\DateTimeInterface $preferredDate): static
    {
        $this->preferredDate = $preferredDate;
        return $this;
    }

    public function getRepairLocation(): ?string
    {
        return $this->repairLocation;
    }

    public function setRepairLocation(string $repairLocation): static
    {
        $this->repairLocation = $repairLocation;
        return $this;
    }

    public function isUrgency(): ?bool
    {
        return $this->urgency;
    }

    public function setUrgency(bool $urgency): static
    {
        $this->urgency = $urgency;
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

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function getFullAddress(): ?string
    {
        if (!$this->address) {
            return null;
        }

        return sprintf(
            '%s, %s %s',
            $this->address,
            $this->zipCode ?? '',
            $this->city ?? ''
        );
    }

    public function getCategoryLabel(): string
    {
        $labels = [
            'lave-linge' => 'Lave-linge',
            'refrigerateur' => 'Réfrigérateur',
            'four' => 'Four & Cuisson',
            'lave-vaisselle' => 'Lave-vaisselle',
            'petit-electromenager' => 'Petit électroménager',
            'autre' => 'Autre appareil'
        ];

        return $labels[$this->category] ?? $this->category;
    }

    public function getRepairLocationLabel(): string
    {
        return $this->repairLocation === 'atelier' ? 'En atelier' : 'À domicile';
    }

    public function getStatusLabel(): string
    {
        $labels = [
            'pending' => 'En attente',
            'contacted' => 'Contacté',
            'scheduled' => 'Planifié',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé'
        ];

        return $labels[$this->status] ?? $this->status;
    }
}
