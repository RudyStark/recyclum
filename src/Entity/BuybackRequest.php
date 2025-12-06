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
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    // === INFORMATIONS APPAREIL ===
    #[ORM\Column(length: 100)]
    private ?string $category = null;

    #[ORM\Column(length: 100)]
    private ?string $brand = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $model = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $serialNumber = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $purchaseYear = null;

    #[ORM\Column]
    private ?bool $hasInvoice = false;

    // === ÉTAT DE L'APPAREIL ===
    #[ORM\Column(length: 50)]
    private ?string $functionalCondition = null; // perfect, working, minor_issues, major_issues, not_working

    #[ORM\Column(length: 50)]
    private ?string $aestheticCondition = null; // excellent, good, fair, poor, very_poor

    #[ORM\Column]
    private ?bool $hasAllAccessories = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $defectsDescription = null;

    // === PHOTOS ===
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo2 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo3 = null;

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
    private ?string $postalCode = null;

    #[ORM\Column(length: 100)]
    private ?string $city = null;

    // === PAIEMENT ===
    #[ORM\Column(length: 20)]
    private ?string $paymentMethod = null; // virement, cheque, especes

    #[ORM\Column(length: 34, nullable: true)]
    private ?string $iban = null;

    // === ESTIMATION ===
    #[ORM\Column(nullable: true)]
    private ?int $estimatedPrice = null;

    // === STATUT ===
    #[ORM\Column(length: 50)]
    private ?string $status = 'pending'; // pending, validated, collected, paid, cancelled

    #[ORM\Column(nullable: true)]
    private ?int $finalPrice = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

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

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function setSerialNumber(?string $serialNumber): static
    {
        $this->serialNumber = $serialNumber;
        return $this;
    }

    public function getPurchaseYear(): ?int
    {
        return $this->purchaseYear;
    }

    public function setPurchaseYear(int $purchaseYear): static
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

    public function getFunctionalCondition(): ?string
    {
        return $this->functionalCondition;
    }

    public function setFunctionalCondition(string $functionalCondition): static
    {
        $this->functionalCondition = $functionalCondition;
        return $this;
    }

    public function getFunctionalConditionLabel(): string
    {
        return match($this->functionalCondition) {
            'perfect' => 'Parfait état',
            'working' => 'Fonctionne bien',
            'minor_issues' => 'Petits problèmes',
            'major_issues' => 'Gros problèmes',
            'not_working' => 'Ne fonctionne pas',
            default => 'Non spécifié'
        };
    }

    public function getAestheticCondition(): ?string
    {
        return $this->aestheticCondition;
    }

    public function setAestheticCondition(string $aestheticCondition): static
    {
        $this->aestheticCondition = $aestheticCondition;
        return $this;
    }

    public function getAestheticConditionLabel(): string
    {
        return match($this->aestheticCondition) {
            'excellent' => 'Comme neuf',
            'good' => 'Bon état',
            'fair' => 'État correct',
            'poor' => 'Usagé',
            'very_poor' => 'Très usagé',
            default => 'Non spécifié'
        };
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

    public function getDefectsDescription(): ?string
    {
        return $this->defectsDescription;
    }

    public function setDefectsDescription(?string $defectsDescription): static
    {
        $this->defectsDescription = $defectsDescription;
        return $this;
    }

    public function getPhoto1(): ?string
    {
        return $this->photo1;
    }

    public function setPhoto1(?string $photo1): static
    {
        $this->photo1 = $photo1;
        return $this;
    }

    public function getPhoto2(): ?string
    {
        return $this->photo2;
    }

    public function setPhoto2(?string $photo2): static
    {
        $this->photo2 = $photo2;
        return $this;
    }

    public function getPhoto3(): ?string
    {
        return $this->photo3;
    }

    public function setPhoto3(?string $photo3): static
    {
        $this->photo3 = $photo3;
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

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;
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
        return $this->address . ', ' . $this->postalCode . ' ' . $this->city;
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

    public function getPaymentMethodLabel(): string
    {
        return match($this->paymentMethod) {
            'virement' => 'Virement bancaire',
            'cheque' => 'Chèque',
            'especes' => 'Espèces',
            default => 'Non spécifié'
        };
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

    public function getEstimatedPrice(): ?int
    {
        return $this->estimatedPrice;
    }

    public function setEstimatedPrice(?int $estimatedPrice): static
    {
        $this->estimatedPrice = $estimatedPrice;
        return $this;
    }

    public function getEstimatedPriceFormatted(): string
    {
        if (!$this->estimatedPrice) {
            return 'Non estimé';
        }
        return number_format($this->estimatedPrice, 0, ',', ' ') . ' €';
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
            'pending' => 'En attente',
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
}
