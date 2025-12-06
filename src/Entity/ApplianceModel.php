<?php

namespace App\Entity;

use App\Repository\ApplianceModelRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplianceModelRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_category', columns: ['category'])]
#[ORM\Index(name: 'idx_brand', columns: ['brand'])]
#[ORM\Index(name: 'idx_model_reference', columns: ['model_reference'])]
class ApplianceModel
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $category = null;

    #[ORM\Column(length: 100)]
    private ?string $brand = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $modelReference = null;

    #[ORM\Column(length: 255)]
    private ?string $modelName = null;

    #[ORM\Column]
    private ?int $releaseYear = null;

    #[ORM\Column(length: 50)]
    private ?string $tier = null; // premium, standard, entry

    #[ORM\Column]
    private ?bool $isActive = true;

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

    // Getters et Setters
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

    public function getModelReference(): ?string
    {
        return $this->modelReference;
    }

    public function setModelReference(string $modelReference): static
    {
        $this->modelReference = strtoupper($modelReference);
        return $this;
    }

    public function getModelName(): ?string
    {
        return $this->modelName;
    }

    public function setModelName(string $modelName): static
    {
        $this->modelName = $modelName;
        return $this;
    }

    public function getReleaseYear(): ?int
    {
        return $this->releaseYear;
    }

    public function setReleaseYear(int $releaseYear): static
    {
        $this->releaseYear = $releaseYear;
        return $this;
    }

    public function getTier(): ?string
    {
        return $this->tier;
    }

    public function setTier(string $tier): static
    {
        $this->tier = $tier;
        return $this;
    }

    public function getTierLabel(): string
    {
        return match($this->tier) {
            'premium' => 'Premium',
            'standard' => 'Standard',
            'entry' => 'Entrée de gamme',
            default => ucfirst($this->tier)
        };
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
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

    public function getFullName(): string
    {
        return sprintf('%s %s - %s', ucfirst($this->brand), $this->modelReference, $this->modelName);
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }
}
