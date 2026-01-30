<?php

namespace App\Enum;

enum ContactStatus: string
{
    case NEW = 'new';
    case READ = 'read';
    case IN_PROGRESS = 'in_progress';
    case ANSWERED = 'answered';
    case CLOSED = 'closed';

    public function getLabel(): string
    {
        return match($this) {
            self::NEW => 'Nouveau',
            self::READ => 'Lu',
            self::IN_PROGRESS => 'En cours',
            self::ANSWERED => 'Répondu',
            self::CLOSED => 'Fermé',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::NEW => '#ef4444',
            self::READ => '#f59e0b',
            self::IN_PROGRESS => '#3b82f6',
            self::ANSWERED => '#16C669',
            self::CLOSED => '#6b7280',
        };
    }

    public function getBadgeClass(): string
    {
        return match($this) {
            self::NEW => 'badge-danger',
            self::READ => 'badge-warning',
            self::IN_PROGRESS => 'badge-info',
            self::ANSWERED => 'badge-success',
            self::CLOSED => 'badge-secondary',
        };
    }

    public function isOpen(): bool
    {
        return match($this) {
            self::NEW, self::READ, self::IN_PROGRESS => true,
            default => false,
        };
    }

    public static function getChoices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->getLabel()] = $case->value;
        }
        return $choices;
    }
}
