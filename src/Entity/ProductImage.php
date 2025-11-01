<?php

namespace App\Entity;

use App\Repository\ProductImageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductImageRepository::class)]
#[ORM\Table(name: 'product_image')]
class ProductImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

    #[ORM\Column(type:'string', length: 255)]
    private string $path;

    #[ORM\Column(type:'string', length: 255, nullable: true)]
    private ?string $alt = null;

    #[ORM\Column(type:'smallint', options: ['default' => 0])]
    private int $position = 0;

    public function getId(): ?int { return $this->id; }

    public function getProduct(): ?Product { return $this->product; }
    public function setProduct(?Product $product): self { $this->product = $product; return $this; }

    public function getPath(): string { return $this->path; }
    public function setPath(string $path): self { $this->path = $path; return $this; }

    public function getAlt(): ?string { return $this->alt; }
    public function setAlt(?string $alt): self { $this->alt = $alt; return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): self { $this->position = $position; return $this; }
}
