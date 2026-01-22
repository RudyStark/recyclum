<?php

namespace App\Entity;

use App\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
#[ORM\Table(name: 'address')]
#[ORM\HasLifecycleCallbacks]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255)]
    private ?string $street = null;

    #[Assert\Length(max: 255)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $streetComplement = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 10)]
    #[Assert\Regex(pattern: '/^\d{5}$/', message: 'Le code postal doit contenir 5 chiffres')]
    #[ORM\Column(length: 10)]
    private ?string $postalCode = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private ?string $city = null;

    #[ORM\Column(length: 2, options: ['default' => 'FR'])]
    private string $country = 'FR';

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^0[1-9]\d{8}$/', message: 'Numéro de téléphone invalide (format: 0XXXXXXXXX)')]
    #[ORM\Column(length: 20)]
    private ?string $phone = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $deliveryInstructions = null;

    #[ORM\ManyToOne(inversedBy: 'addresses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isDefaultBilling = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $isDefaultShipping = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt ??= new \DateTimeImmutable();
        $this->updatedAt ??= new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf(
            '%s %s, %s, %s %s',
            $this->firstName ?? '',
            $this->lastName ?? '',
            $this->street ?? '',
            $this->postalCode ?? '',
            $this->city ?? ''
        );
    }

    /**
     * Retourne l'adresse complète formatée
     */
    public function getFullAddress(): string
    {
        $parts = [
            $this->street,
            $this->streetComplement,
            $this->postalCode . ' ' . $this->city,
        ];

        return implode(', ', array_filter($parts));
    }

    // Getters & Setters

    public function getId(): ?int
    {
        return $this->id;
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

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): static
    {
        $this->street = $street;
        return $this;
    }

    public function getStreetComplement(): ?string
    {
        return $this->streetComplement;
    }

    public function setStreetComplement(?string $streetComplement): static
    {
        $this->streetComplement = $streetComplement;
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

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;
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

    public function getDeliveryInstructions(): ?string
    {
        return $this->deliveryInstructions;
    }

    public function setDeliveryInstructions(?string $deliveryInstructions): static
    {
        $this->deliveryInstructions = $deliveryInstructions;
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

    public function isDefaultBilling(): bool
    {
        return $this->isDefaultBilling;
    }

    public function setIsDefaultBilling(bool $isDefaultBilling): static
    {
        $this->isDefaultBilling = $isDefaultBilling;
        return $this;
    }

    public function isDefaultShipping(): bool
    {
        return $this->isDefaultShipping;
    }

    public function setIsDefaultShipping(bool $isDefaultShipping): static
    {
        $this->isDefaultShipping = $isDefaultShipping;
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
}
