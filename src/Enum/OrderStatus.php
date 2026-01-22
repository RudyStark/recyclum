<?php

namespace App\Enum;

enum OrderStatus: string
{
    case PENDING = 'pending';           // En attente de paiement
    case PAID = 'paid';                 // Payée
    case PROCESSING = 'processing';     // En préparation
    case SHIPPED = 'shipped';           // Expédiée
    case DELIVERED = 'delivered';       // Livrée
    case CANCELLED = 'cancelled';       // Annulée
    case REFUNDED = 'refunded';         // Remboursée

    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::PAID => 'Payée',
            self::PROCESSING => 'En préparation',
            self::SHIPPED => 'Expédiée',
            self::DELIVERED => 'Livrée',
            self::CANCELLED => 'Annulée',
            self::REFUNDED => 'Remboursée',
        };
    }

    public function getBadgeClass(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::PAID => 'info',
            self::PROCESSING => 'primary',
            self::SHIPPED => 'secondary',
            self::DELIVERED => 'success',
            self::CANCELLED => 'danger',
            self::REFUNDED => 'dark',
        };
    }
}
