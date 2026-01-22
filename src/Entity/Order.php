<?php

namespace App\Entity;

use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[ORM\HasLifecycleCallbacks]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    private ?string $orderNumber = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(enumType: OrderStatus::class)]
    private ?OrderStatus $status = null;

    // Prix en centimes
    #[ORM\Column]
    private ?int $subtotal = null;

    #[ORM\Column]
    private ?int $deliveryPrice = null;

    #[ORM\Column]
    private ?int $total = null;

    // Adresse de livraison (snapshot)
    #[ORM\Column(length: 100)]
    private ?string $deliveryFirstName = null;

    #[ORM\Column(length: 100)]
    private ?string $deliveryLastName = null;

    #[ORM\Column(length: 255)]
    private ?string $deliveryStreet = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deliveryStreetComplement = null;

    #[ORM\Column(length: 10)]
    private ?string $deliveryPostalCode = null;

    #[ORM\Column(length: 100)]
    private ?string $deliveryCity = null;

    #[ORM\Column(length: 2)]
    private ?string $deliveryCountry = null;

    #[ORM\Column(length: 20)]
    private ?string $deliveryPhone = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $deliveryInstructions = null;

    // Type de livraison
    #[ORM\Column(length: 50)]
    private ?string $deliveryType = null; // 'simple' ou 'with_installation'

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $shippedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deliveredAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'string', length: 32, unique: true, nullable: true)]
    private ?string $trackingToken = null;

    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    private ?string $customerEmail = null;


    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    /**
     * @var Collection<int, Payment>
     */
    #[ORM\OneToMany(targetEntity: Payment::class, mappedBy: 'order', cascade: ['persist'])]
    private Collection $payments;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->status = OrderStatus::PENDING;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if (!$this->orderNumber) {
            $this->orderNumber = $this->generateOrderNumber();
        }
    }

    private function generateOrderNumber(): string
    {
        // Format: REC-YYYYMMDD-XXXXX
        return 'REC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
    }

    public function __toString(): string
    {
        return $this->orderNumber ?? 'Commande #' . $this->id;
    }

    // Getters et Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): static
    {
        $this->orderNumber = $orderNumber;
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

    public function getStatus(): ?OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getSubtotal(): ?int
    {
        return $this->subtotal;
    }

    public function setSubtotal(int $subtotal): static
    {
        $this->subtotal = $subtotal;
        return $this;
    }

    public function getDeliveryPrice(): ?int
    {
        return $this->deliveryPrice;
    }

    public function setDeliveryPrice(int $deliveryPrice): static
    {
        $this->deliveryPrice = $deliveryPrice;
        return $this;
    }

    public function getTotal(): ?int
    {
        return $this->total;
    }

    public function setTotal(int $total): static
    {
        $this->total = $total;
        return $this;
    }

    // Méthodes formattées
    public function getSubtotalFormatted(): string
    {
        return number_format($this->subtotal / 100, 2, ',', ' ') . ' €';
    }

    public function getDeliveryPriceFormatted(): string
    {
        return number_format($this->deliveryPrice / 100, 2, ',', ' ') . ' €';
    }

    public function getTotalFormatted(): string
    {
        return number_format($this->total / 100, 2, ',', ' ') . ' €';
    }

    // Adresse de livraison
    public function getDeliveryFirstName(): ?string
    {
        return $this->deliveryFirstName;
    }

    public function setDeliveryFirstName(string $deliveryFirstName): static
    {
        $this->deliveryFirstName = $deliveryFirstName;
        return $this;
    }

    public function getDeliveryLastName(): ?string
    {
        return $this->deliveryLastName;
    }

    public function setDeliveryLastName(string $deliveryLastName): static
    {
        $this->deliveryLastName = $deliveryLastName;
        return $this;
    }

    public function getDeliveryStreet(): ?string
    {
        return $this->deliveryStreet;
    }

    public function setDeliveryStreet(string $deliveryStreet): static
    {
        $this->deliveryStreet = $deliveryStreet;
        return $this;
    }

    public function getDeliveryStreetComplement(): ?string
    {
        return $this->deliveryStreetComplement;
    }

    public function setDeliveryStreetComplement(?string $deliveryStreetComplement): static
    {
        $this->deliveryStreetComplement = $deliveryStreetComplement;
        return $this;
    }

    public function getDeliveryPostalCode(): ?string
    {
        return $this->deliveryPostalCode;
    }

    public function setDeliveryPostalCode(string $deliveryPostalCode): static
    {
        $this->deliveryPostalCode = $deliveryPostalCode;
        return $this;
    }

    public function getDeliveryCity(): ?string
    {
        return $this->deliveryCity;
    }

    public function setDeliveryCity(string $deliveryCity): static
    {
        $this->deliveryCity = $deliveryCity;
        return $this;
    }

    public function getDeliveryCountry(): ?string
    {
        return $this->deliveryCountry;
    }

    public function setDeliveryCountry(string $deliveryCountry): static
    {
        $this->deliveryCountry = $deliveryCountry;
        return $this;
    }

    public function getDeliveryPhone(): ?string
    {
        return $this->deliveryPhone;
    }

    public function setDeliveryPhone(string $deliveryPhone): static
    {
        $this->deliveryPhone = $deliveryPhone;
        return $this;
    }

    public function getDeliveryInstructions(): ?string
    {
        return $this->deliveryInstructions;
    }

    public function setDeliveryInstructions(?string $deliveryInstructions): static
    {
        $this->deliveryInstructions = $deliveryInstructions;
        return $this;
    }

    public function getDeliveryType(): ?string
    {
        return $this->deliveryType;
    }

    public function setDeliveryType(string $deliveryType): static
    {
        $this->deliveryType = $deliveryType;
        return $this;
    }

    // Adresse complète formatée
    public function getDeliveryFullAddress(): string
    {
        $parts = [
            $this->deliveryStreet,
            $this->deliveryStreetComplement,
            $this->deliveryPostalCode . ' ' . $this->deliveryCity,
        ];

        return implode(', ', array_filter($parts));
    }

    // Dates
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

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;
        return $this;
    }

    public function getShippedAt(): ?\DateTimeImmutable
    {
        return $this->shippedAt;
    }

    public function setShippedAt(?\DateTimeImmutable $shippedAt): static
    {
        $this->shippedAt = $shippedAt;
        return $this;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(?\DateTimeImmutable $deliveredAt): static
    {
        $this->deliveredAt = $deliveredAt;
        return $this;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeImmutable $cancelledAt): static
    {
        $this->cancelledAt = $cancelledAt;
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

    // Items
    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }

        return $this;
    }

    public function removeItem(OrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getOrder() === $this) {
                $item->setOrder(null);
            }
        }

        return $this;
    }

    // Payments
    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setOrder($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            if ($payment->getOrder() === $this) {
                $payment->setOrder(null);
            }
        }

        return $this;
    }

    // Méthodes business
    public function getTotalQuantity(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->getQuantity();
        }
        return $total;
    }

    public function isPaid(): bool
    {
        return $this->paidAt !== null;
    }

    public function isShipped(): bool
    {
        return $this->shippedAt !== null;
    }

    public function isDelivered(): bool
    {
        return $this->deliveredAt !== null;
    }

    public function isCancelled(): bool
    {
        return $this->cancelledAt !== null;
    }

    public function getTrackingToken(): ?string
    {
        return $this->trackingToken;
    }

    public function setTrackingToken(?string $trackingToken): self
    {
        $this->trackingToken = $trackingToken;
        return $this;
    }

    /**
     * Générer un token de tracking unique pour les invités
     */
    public function generateTrackingToken(): self
    {
        $this->trackingToken = bin2hex(random_bytes(16)); // 32 caractères
        return $this;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(?string $customerEmail): self
    {
        $this->customerEmail = $customerEmail;
        return $this;
    }
}
