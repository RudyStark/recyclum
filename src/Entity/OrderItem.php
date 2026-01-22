<?php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
#[ORM\Table(name: 'order_item')]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    // Snapshot du produit au moment de la commande
    #[ORM\Column(length: 255)]
    private ?string $productTitle = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $productBrand = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $productCategory = null;

    #[Assert\Positive]
    #[Assert\LessThanOrEqual(10)]
    #[ORM\Column]
    private ?int $quantity = null;

    // Prix unitaire en centimes (figé)
    #[ORM\Column]
    private ?int $unitPrice = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $addedAt = null;

    public function __construct()
    {
        $this->addedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->productTitle ?? 'OrderItem #' . $this->id;
    }

    // Getters et Setters

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

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function getProductTitle(): ?string
    {
        return $this->productTitle;
    }

    public function setProductTitle(string $productTitle): static
    {
        $this->productTitle = $productTitle;
        return $this;
    }

    public function getProductBrand(): ?string
    {
        return $this->productBrand;
    }

    public function setProductBrand(?string $productBrand): static
    {
        $this->productBrand = $productBrand;
        return $this;
    }

    public function getProductCategory(): ?string
    {
        return $this->productCategory;
    }

    public function setProductCategory(?string $productCategory): static
    {
        $this->productCategory = $productCategory;
        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        if ($quantity < 1 || $quantity > 10) {
            throw new \InvalidArgumentException('Quantity must be between 1 and 10');
        }
        $this->quantity = $quantity;
        return $this;
    }

    public function getUnitPrice(): ?int
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

    // Méthodes business

    /**
     * Calcule le sous-total de la ligne (quantité × prix unitaire)
     */
    public function getSubtotal(): int
    {
        return $this->quantity * $this->unitPrice;
    }

    /**
     * Retourne le sous-total formaté
     */
    public function getSubtotalFormatted(): string
    {
        return number_format($this->getSubtotal() / 100, 2, ',', ' ') . ' €';
    }

    /**
     * Retourne le prix unitaire formaté
     */
    public function getUnitPriceFormatted(): string
    {
        return number_format($this->unitPrice / 100, 2, ',', ' ') . ' €';
    }

    /**
     * Crée un OrderItem depuis un CartItem
     */
    public static function createFromCartItem(CartItem $cartItem): self
    {
        $orderItem = new self();
        $product = $cartItem->getProduct();

        $orderItem->setProduct($product);
        $orderItem->setProductTitle($product->getTitle());
        $orderItem->setProductBrand($product->getBrand()?->getName());
        $orderItem->setProductCategory($product->getCategory()?->getName());
        $orderItem->setQuantity($cartItem->getQuantity());
        $orderItem->setUnitPrice($cartItem->getUnitPrice());

        return $orderItem;
    }
}
