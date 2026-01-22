<?php

namespace App\Entity;

use App\Repository\CartItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CartItemRepository::class)]
#[ORM\Table(name: 'cart_item')]
#[ORM\HasLifecycleCallbacks]
class CartItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Cart $cart = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[Assert\Positive]
    #[Assert\LessThanOrEqual(value: 10, message: 'Quantité maximale : 10 par produit')]
    #[ORM\Column]
    private int $quantity = 1;

    /**
     * Prix unitaire au moment de l'ajout (en centimes)
     * Stocké pour figer le prix en cas de changement
     */
    #[ORM\Column]
    private int $unitPrice = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $addedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->addedAt ??= new \DateTimeImmutable();

        // Figer le prix du produit au moment de l'ajout
        if ($this->product && $this->unitPrice === 0) {
            $this->unitPrice = $this->product->getPrice();
        }
    }

    /**
     * Calcule le sous-total de la ligne (quantité × prix unitaire)
     */
    public function getSubtotal(): int
    {
        return $this->quantity * $this->unitPrice;
    }

    /**
     * Retourne le sous-total formaté en euros
     */
    public function getSubtotalFormatted(): string
    {
        return number_format($this->getSubtotal() / 100, 2, ',', ' ') . ' €';
    }

    /**
     * Retourne le prix unitaire formaté en euros
     */
    public function getUnitPriceFormatted(): string
    {
        return number_format($this->unitPrice / 100, 2, ',', ' ') . ' €';
    }

    // Getters & Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(?Cart $cart): static
    {
        $this->cart = $cart;
        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        // Mettre à jour le prix unitaire si le produit change
        if ($product && $this->unitPrice === 0) {
            $this->unitPrice = $product->getPrice();
        }

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = max(1, min(10, $quantity)); // Entre 1 et 10
        return $this;
    }

    public function getUnitPrice(): int
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(int $unitPrice): static
    {
        $this->unitPrice = $unitPrice;
        return $this;
    }

    public function getAddedAt(): ?\DateTimeImmutable
    {
        return $this->addedAt;
    }

    public function setAddedAt(\DateTimeImmutable $addedAt): static
    {
        $this->addedAt = $addedAt;
        return $this;
    }
}
