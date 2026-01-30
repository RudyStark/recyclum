<?php

namespace App\Twig;

use App\Repository\ContactMessageRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AdminContactExtension extends AbstractExtension
{
    public function __construct(
        private ContactMessageRepository $contactMessageRepository
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_new_contact_messages_count', [$this, 'getNewContactMessagesCount']),
        ];
    }

    public function getNewContactMessagesCount(): int
    {
        return $this->contactMessageRepository->countNew();
    }
}
