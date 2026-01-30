<?php

namespace App\Enum;

enum ContactSubject: string
{
    case ORDER = 'order';
    case WARRANTY = 'warranty';
    case DELIVERY = 'delivery';
    case RETURN = 'return';
    case TECHNICAL = 'technical';
    case COMMERCIAL = 'commercial';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match($this) {
            self::ORDER => 'Question sur une commande',
            self::WARRANTY => 'Garantie / SAV',
            self::DELIVERY => 'Livraison',
            self::RETURN => 'Retour / Échange',
            self::TECHNICAL => 'Question technique',
            self::COMMERCIAL => 'Devis / Question commerciale',
            self::OTHER => 'Autre',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::ORDER => 'bi-bag-check',
            self::WARRANTY => 'bi-shield-check',
            self::DELIVERY => 'bi-truck',
            self::RETURN => 'bi-arrow-repeat',
            self::TECHNICAL => 'bi-tools',
            self::COMMERCIAL => 'bi-briefcase',
            self::OTHER => 'bi-chat-dots',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::ORDER => '#3b82f6',
            self::WARRANTY => '#ef4444',
            self::DELIVERY => '#8b5cf6',
            self::RETURN => '#f59e0b',
            self::TECHNICAL => '#6b7280',
            self::COMMERCIAL => '#16C669',
            self::OTHER => '#64748b',
        };
    }

    public function isPriority(): bool
    {
        return match($this) {
            self::WARRANTY, self::RETURN => true,
            default => false,
        };
    }

    public function requiresOrder(): bool
    {
        return match($this) {
            self::ORDER, self::WARRANTY, self::DELIVERY, self::RETURN => true,
            default => false,
        };
    }

    public function requiresProduct(): bool
    {
        return match($this) {
            self::WARRANTY, self::TECHNICAL => true,
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
