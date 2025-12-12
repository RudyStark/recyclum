<?php

namespace App\Twig;

use App\Service\NotificationService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NotificationExtension extends AbstractExtension
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_notifications', [$this, 'getNotifications']),
            new TwigFunction('get_notification_count', [$this, 'getNotificationCount']),
        ];
    }

    public function getNotifications(): array
    {
        return $this->notificationService->getUnreadNotifications();
    }

    public function getNotificationCount(): int
    {
        return $this->notificationService->getUnreadCount();
    }
}
