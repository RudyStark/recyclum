<?php

namespace App\Entity;

use App\Repository\ContactReplyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactReplyRepository::class)]
class ContactReply
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ContactMessage::class, inversedBy: 'replies')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ContactMessage $contactMessage = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $repliedBy = null;

    #[ORM\Column]
    private ?bool $isAdminReply = true;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, ContactAttachment>
     */
    #[ORM\OneToMany(targetEntity: ContactAttachment::class, mappedBy: 'contactReply', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $attachments;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->attachments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getRepliedBy(): ?string
    {
        return $this->repliedBy;
    }

    public function setRepliedBy(?string $repliedBy): static
    {
        $this->repliedBy = $repliedBy;
        return $this;
    }

    public function isAdminReply(): bool
    {
        return $this->isAdminReply;
    }

    public function setIsAdminReply(bool $isAdminReply): static
    {
        $this->isAdminReply = $isAdminReply;
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
     * @return Collection<int, ContactAttachment>
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(ContactAttachment $attachment): static
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setContactReply($this);
        }
        return $this;
    }

    public function removeAttachment(ContactAttachment $attachment): static
    {
        if ($this->attachments->removeElement($attachment)) {
            if ($attachment->getContactReply() === $this) {
                $attachment->setContactReply(null);
            }
        }
        return $this;
    }

    public function hasAttachments(): bool
    {
        return !$this->attachments->isEmpty();
    }
}
