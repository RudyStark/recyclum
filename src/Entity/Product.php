<?php

namespace App\Entity;

use App\Enum\EnergyLabel;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $shortDescription = null;

    #[Assert\Positive]
    #[ORM\Column]
    private ?int $price = null;

    #[ORM\Column(nullable: true, enumType: EnergyLabel::class)]
    private ?EnergyLabel $energyLabel = null;


    #[Assert\Range(min: 0, max: 6)] // Correspond a 6 mois max.
    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $warrantyMonths = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $slug = null;

    #[Assert\Range(min: 0)]
    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $stock = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $isPublished = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Brand $brand = null;

    /**
     * @var Collection<int, ProductImage>
     */
    #[ORM\OneToMany(targetEntity: ProductImage::class, mappedBy: 'product')]
    private Collection $images;

    public function __construct()
    {
        $this->images = new ArrayCollection();
    }

    public function __toString(): string
    {
        $parts = [];
        if ($this->title)   { $parts[] = $this->title; }
        if ($this->brand)   { $parts[] = $this->brand->getName(); }
        if ($this->category){ $parts[] = $this->category->getName(); }
        return $parts ? implode(' — ', $parts) : ('Produit #'.$this->id);
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt ??= $now;
        $this->updatedAt ??= $now;
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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateSlug(): void
    {
        // Ne génère que si VRAIMENT vide
        if ($this->slug === null || trim($this->slug) === '') {
            if ($this->title ?? null) {
                $slugger = new AsciiSlugger('fr');
                $this->slug = strtolower($slugger->slug($this->title)->toString());
            }
        }
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getEnergyLabel(): ?EnergyLabel
    {
        return $this->energyLabel;
    }

    public function setEnergyLabel(?EnergyLabel $energyLabel): self
    {
        $this->energyLabel = $energyLabel;

        return $this;
    }

    public function getEnergyLabelValue(): ?string
    {
        return $this->energyLabel?->value; // retourne 'A', 'B', ... ou null
    }

    public function getWarrantyMonths(): ?int
    {
        return $this->warrantyMonths;
    }

    public function setWarrantyMonths(int $warrantyMonths): static
    {
        $this->warrantyMonths = $warrantyMonths;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function isPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;

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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getBrand(): ?Brand
    {
        return $this->brand;
    }

    public function setBrand(?Brand $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * @return Collection<int, ProductImage>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(ProductImage $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setProduct($this);
        }

        return $this;
    }

    public function removeImage(ProductImage $image): static
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getProduct() === $this) {
                $image->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return ProductImage|null
     * Retourne l'image principale si définie, sinon la première
     */
    public function getMainImage(): ?ProductImage
    {
        foreach ($this->getImages() as $img) {
            if ($img->isMain()) {
                return $img;
            }
        }
        return $this->images->first() ?: null;
    }

    public function getMainImageFilename(): ?string
    {
        $img = $this->getMainImage();
        return $img ? $img->getFilename() : null;
    }

    public function getFirstImageFilename(): ?string
    {
        if ($this->images instanceof \Doctrine\Common\Collections\Collection && !$this->images->isEmpty()) {
            /** @var \App\Entity\ProductImage $img */
            $img = $this->images->first();
            return $img?->getFilename();
        }
        return null;
    }
}
