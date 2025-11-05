<?php

namespace App\Entity;

use App\Repository\ProductImageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: ProductImageRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class ProductImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // relation vers Product (cascade en BDD si le produit est supprimé)
    #[ORM\ManyToOne(inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

    /**
     * Fichier d’upload géré par Vich
     * - mapping: 'product_images' à configurer dans vich_uploader.yaml
     */
    #[Vich\UploadableField(mapping: 'product_images', fileNameProperty: 'filename', size: 'size', mimeType: 'mimeType')]
    #[Assert\File(
        maxSize: '8M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        mimeTypesMessage: 'Formats acceptés : JPEG, PNG, WEBP, GIF (max 8 Mo).'
    )]
    private ?File $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filename = null;

    #[ORM\Column(nullable: true)]
    private ?int $size = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mimeType = null;

    // Texte alternatif pour l’accessibilité et le SEO (utilisé dans le Twig)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $alt = null;

    // Tri des images
    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    // Image principale ?
    #[ORM\Column(options: ['default' => false])]
    private bool $isMain = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    /** Met à jour updatedAt quand un nouveau fichier est uploadé (Vich en a besoin) */
    public function setImageFile(?File $file = null): void
    {
        $this->imageFile = $file;
        if ($file !== null) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if (!isset($this->createdAt)) {
            $this->createdAt = new \DateTimeImmutable();
        }
        if ($this->updatedAt === null) {
            $this->updatedAt = $this->createdAt;
        }
    }

    // -------- Getters / Setters --------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;
        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): self
    {
        $this->filename = $filename;
        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): self
    {
        $this->size = $size;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getAlt(): ?string
    {
        return $this->alt;
    }

    public function setAlt(?string $alt): self
    {
        $this->alt = $alt;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function isMain(): bool
    {
        return $this->isMain;
    }

    public function setIsMain(bool $isMain): self
    {
        $this->isMain = $isMain;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function __toString(): string
    {
        return $this->filename ?? 'image';
    }
}
