<?php

namespace App\Service;

use App\Entity\ContactAttachment;
use App\Entity\ContactMessage;
use App\Entity\ContactReply;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class AttachmentService
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
    ];

    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB
    private const MAX_FILES_PER_MESSAGE = 5;

    public function __construct(
        private SluggerInterface $slugger,
        private string $uploadDirectory,
    ) {
    }

    /**
     * Handle file uploads for a contact message (initial message)
     * @param UploadedFile[] $files
     * @return ContactAttachment[]
     */
    public function handleUploadsForMessage(array $files, ContactMessage $message): array
    {
        $attachments = [];

        $files = array_slice($files, 0, self::MAX_FILES_PER_MESSAGE);

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                continue;
            }

            $attachment = $this->processUpload($file);
            if ($attachment) {
                $attachment->setContactMessage($message);
                $message->addAttachment($attachment);
                $attachments[] = $attachment;
            }
        }

        return $attachments;
    }

    /**
     * Handle file uploads for a contact reply
     * @param UploadedFile[] $files
     * @return ContactAttachment[]
     */
    public function handleUploadsForReply(array $files, ContactReply $reply): array
    {
        $attachments = [];

        $files = array_slice($files, 0, self::MAX_FILES_PER_MESSAGE);

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                continue;
            }

            $attachment = $this->processUpload($file);
            if ($attachment) {
                $attachment->setContactReply($reply);
                $reply->addAttachment($attachment);
                $attachments[] = $attachment;
            }
        }

        return $attachments;
    }

    /**
     * Process a single file upload
     */
    private function processUpload(UploadedFile $file): ?ContactAttachment
    {
        // Validate file
        if (!$this->validateFile($file)) {
            return null;
        }

        // Capture file metadata BEFORE moving (file won't exist after move)
        $originalFilename = $file->getClientOriginalName();
        $mimeType = $file->getClientMimeType();
        $fileSize = $file->getSize();

        // Generate unique filename
        $safeFilename = $this->slugger->slug(pathinfo($originalFilename, PATHINFO_FILENAME));
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        // Ensure upload directory exists
        $this->ensureUploadDirectoryExists();

        try {
            $file->move($this->uploadDirectory, $newFilename);
        } catch (FileException $e) {
            return null;
        }

        // Create attachment entity with captured metadata
        $attachment = new ContactAttachment();
        $attachment->setOriginalFilename($originalFilename);
        $attachment->setStoredFilename($newFilename);
        $attachment->setMimeType($mimeType);
        $attachment->setFileSize($fileSize);

        return $attachment;
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(UploadedFile $file): bool
    {
        // Check MIME type
        if (!in_array($file->getClientMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            return false;
        }

        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return false;
        }

        return true;
    }

    /**
     * Ensure upload directory exists
     */
    private function ensureUploadDirectoryExists(): void
    {
        if (!is_dir($this->uploadDirectory)) {
            mkdir($this->uploadDirectory, 0755, true);
        }
    }

    /**
     * Delete attachment file from filesystem
     */
    public function deleteAttachmentFile(ContactAttachment $attachment): bool
    {
        $filePath = $this->uploadDirectory . '/' . $attachment->getStoredFilename();

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    /**
     * Get allowed MIME types
     */
    public static function getAllowedMimeTypes(): array
    {
        return self::ALLOWED_MIME_TYPES;
    }

    /**
     * Get max file size in bytes
     */
    public static function getMaxFileSize(): int
    {
        return self::MAX_FILE_SIZE;
    }

    /**
     * Get max files per message
     */
    public static function getMaxFilesPerMessage(): int
    {
        return self::MAX_FILES_PER_MESSAGE;
    }

    /**
     * Get allowed extensions as string for HTML accept attribute
     */
    public static function getAllowedExtensions(): string
    {
        return '.jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt';
    }
}
