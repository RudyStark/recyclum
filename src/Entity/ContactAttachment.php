<?php

namespace App\Entity;

use App\Repository\ContactAttachmentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactAttachmentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ContactAttachment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'attachments')]
    #[ORM\JoinColumn(nullable: true)]
    private ?ContactReply $contactReply = null;

    #[ORM\ManyToOne(inversedBy: 'attachments')]
    #[ORM\JoinColumn(nullable: true)]
    private ?ContactMessage $contactMessage = null;

    #[ORM\Column(length: 255)]
    private ?string $originalFilename = null;

    #[ORM\Column(length: 255)]
    private ?string $storedFilename = null;

    #[ORM\Column(length: 100)]
    private ?string $mimeType = null;

    #[ORM\Column]
    private ?int $fileSize = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContactReply(): ?ContactReply
    {
        return $this->contactReply;
    }

    public function setContactReply(?ContactReply $contactReply): static
    {
        $this->contactReply = $contactReply;

        return $this;
    }

    public function getContactMessage(): ?ContactMessage
    {
        return $this->contactMessage;
    }

    public function setContactMessage(?ContactMessage $contactMessage): static
    {
        $this->contactMessage = $contactMessage;

        return $this;
    }

    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): static
    {
        $this->originalFilename = $originalFilename;

        return $this;
    }

    public function getStoredFilename(): ?string
    {
        return $this->storedFilename;
    }

    public function setStoredFilename(string $storedFilename): static
    {
        $this->storedFilename = $storedFilename;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): static
    {
        $this->fileSize = $fileSize;

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

    /**
     * Check if this attachment is an image
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mimeType ?? '', 'image/');
    }

    /**
     * Check if this attachment is a PDF
     */
    public function isPdf(): bool
    {
        return $this->mimeType === 'application/pdf';
    }

    /**
     * Get human-readable file size
     */
    public function getHumanFileSize(): string
    {
        $bytes = $this->fileSize;
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get file extension from original filename
     */
    public function getExtension(): string
    {
        return strtolower(pathinfo($this->originalFilename ?? '', PATHINFO_EXTENSION));
    }

    /**
     * Get the web path for this attachment
     */
    public function getWebPath(): string
    {
        return '/uploads/contact/' . $this->storedFilename;
    }
}
