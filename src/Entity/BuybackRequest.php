<?php

namespace App\Entity;

use App\Repository\BuybackRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BuybackRequestRepository::class)]
#[ORM\HasLifecycleCallbacks]
class BuybackRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')] // ← Important pour PostgreSQL
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    // === INFORMATIONS APPAREIL ===
    #[ORM\Column(length: 100)]
    private ?string $category = null;

    #[ORM\Column(length: 100)]
    private ?string $brand = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $model = null;

    #[ORM\Column(length: 50)]
    private ?string $purchaseYear = null; // Ex: "2022-2023"

    #[ORM\Column]
    private ?bool $hasInvoice = false;

    // === ÉTAT DE L'APPAREIL ===
    #[ORM\Column(length: 50)]
    private ?string $functionalState = null; // parfait, panne-legere, hors-service, pieces

    #[ORM\Column(length: 50)]
    private ?string $aestheticState = null; // tres-bon, bon, usage, tres-usage

    #[ORM\Column]
    private ?bool $hasAllAccessories = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $additionalComments = null;

    // === PHOTOS ===
    #[ORM\Column(type: Types::JSON)]
    private array $photos = [];

    // === COORDONNÉES CLIENT ===
    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 20)]
    private ?string $phone = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(length: 10)]
    private ?string $zipCode = null;

    #[ORM\Column(length: 100)]
    private ?string $city = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $floor = null;

    #[ORM\Column]
    private ?bool $hasElevator = false;

    // === PAIEMENT ===
    #[ORM\Column(length: 20)]
    private ?string $paymentMethod = null; // virement ou especes

    #[ORM\Column(length: 34, nullable: true)]
    private ?string $iban = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $accountHolder = null;

    // === DISPONIBILITÉS ===
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $preferredDate = null;

    #[ORM\Column(type: Types::JSON)]
    private array $timeSlots = []; // ["matin", "apres-midi", "flexible"]

    // === ESTIMATION ===
    #[ORM\Column(nullable: true)]
    private ?int $estimatedPriceMin = null;

    #[ORM\Column(nullable: true)]
    private ?int $estimatedPriceMax = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $calculationDetails = null;

    // === STATUT ===
    #[ORM\Column(length: 50)]
    private ?string $status = 'pending'; // pending, validated, collected, paid, cancelled

    #[ORM\Column(nullable: true)]
    private ?int $finalPrice = null;

    // === DATES ===
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

    public function setBrand(string $brand): static
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

    public function getPurchaseYear(): ?string
    {
        return $this->purchaseYear;
    }

    public function setPurchaseYear(string $purchaseYear): static
    {
        $this->purchaseYear = $purchaseYear;
        return $this;
    }

    public function isHasInvoice(): ?bool
    {
        return $this->hasInvoice;
    }

    public function setHasInvoice(bool $hasInvoice): static
    {
        $this->hasInvoice = $hasInvoice;
        return $this;
    }

    public function getFunctionalState(): ?string
    {
        return $this->functionalState;
    }

    public function setFunctionalState(string $functionalState): static
    {
        $this->functionalState = $functionalState;
        return $this;
    }

    public function getAestheticState(): ?string
    {
        return $this->aestheticState;
    }

    public function setAestheticState(string $aestheticState): static
    {
        $this->aestheticState = $aestheticState;
        return $this;
    }

    public function isHasAllAccessories(): ?bool
    {
        return $this->hasAllAccessories;
    }

    public function setHasAllAccessories(bool $hasAllAccessories): static
    {
        $this->hasAllAccessories = $hasAllAccessories;
        return $this;
    }

    public function getAdditionalComments(): ?string
    {
        return $this->additionalComments;
    }

    public function setAdditionalComments(?string $additionalComments): static
    {
        $this->additionalComments = $additionalComments;
        return $this;
    }

    public function getPhotos(): array
    {
        return $this->photos;
    }

    public function setPhotos(array $photos): static
    {
        $this->photos = $photos;
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

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(string $zipCode): static
    {
        $this->zipCode = $zipCode;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getFullAddress(): string
    {
        $address = $this->address . ', ' . $this->zipCode . ' ' . $this->city;
        if ($this->floor) {
            $address .= ' - ' . $this->floor;
        }
        if ($this->hasElevator) {
            $address .= ' (avec ascenseur)';
        }
        return $address;
    }

    public function getFloor(): ?string
    {
        return $this->floor;
    }

    public function setFloor(?string $floor): static
    {
        $this->floor = $floor;
        return $this;
    }

    public function isHasElevator(): ?bool
    {
        return $this->hasElevator;
    }

    public function setHasElevator(bool $hasElevator): static
    {
        $this->hasElevator = $hasElevator;
        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function setIban(?string $iban): static
    {
        $this->iban = $iban;
        return $this;
    }

    public function getAccountHolder(): ?string
    {
        return $this->accountHolder;
    }

    public function setAccountHolder(?string $accountHolder): static
    {
        $this->accountHolder = $accountHolder;
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

    public function getTimeSlots(): array
    {
        return $this->timeSlots;
    }

    public function setTimeSlots(array $timeSlots): static
    {
        $this->timeSlots = $timeSlots;
        return $this;
    }

    public function getEstimatedPriceMin(): ?int
    {
        return $this->estimatedPriceMin;
    }

    public function setEstimatedPriceMin(?int $estimatedPriceMin): static
    {
        $this->estimatedPriceMin = $estimatedPriceMin;
        return $this;
    }

    public function getEstimatedPriceMax(): ?int
    {
        return $this->estimatedPriceMax;
    }

    public function setEstimatedPriceMax(?int $estimatedPriceMax): static
    {
        $this->estimatedPriceMax = $estimatedPriceMax;
        return $this;
    }

    public function getEstimatedPriceRange(): string
    {
        if (!$this->estimatedPriceMin || !$this->estimatedPriceMax) {
            return 'Non estimé';
        }
        return $this->estimatedPriceMin . '€ - ' . $this->estimatedPriceMax . '€';
    }

    public function getCalculationDetails(): ?array
    {
        return $this->calculationDetails;
    }

    public function setCalculationDetails(?array $calculationDetails): static
    {
        $this->calculationDetails = $calculationDetails;
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

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'En attente de validation',
            'validated' => 'Validée',
            'collected' => 'Collectée',
            'paid' => 'Payée',
            'cancelled' => 'Annulée',
            default => 'Inconnu'
        };
    }

    public function getFinalPrice(): ?int
    {
        return $this->finalPrice;
    }

    public function setFinalPrice(?int $finalPrice): static
    {
        $this->finalPrice = $finalPrice;
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

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCategoryLabel(): string
    {
        return match($this->category) {
            'lave-linge' => 'Lave-linge',
            'refrigerateur' => 'Réfrigérateur',
            'four' => 'Four',
            'lave-vaisselle' => 'Lave-vaisselle',
            'seche-linge' => 'Sèche-linge',
            'micro-ondes' => 'Micro-ondes',
            'cuisiniere' => 'Cuisinière',
            'cave-a-vin' => 'Cave à vin',
            'hotte' => 'Hotte',
            'petit-electromenager' => 'Petit électroménager',
            default => ucfirst($this->category)
        };
    }
}
