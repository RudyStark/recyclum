<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Enum\OrderStatus;
use Doctrine\ORM\EntityManagerInterface;

class OrderService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Crée une commande depuis un panier
     */
    public function createOrderFromCart(Cart $cart, ?User $user, array $deliveryData, string $deliveryType): Order
    {
        if ($cart->isEmpty()) {
            throw new \RuntimeException('Le panier est vide');
        }

        $order = new Order();

        // Si l'utilisateur est connecté, on l'associe
        if ($user) {
            $order->setUser($user);
            $order->setCustomerEmail($user->getEmail());
        } else {
            // Pour les invités, sauvegarder l'email s'il est fourni
            if (!empty($deliveryData['email'])) {
                $order->setCustomerEmail($deliveryData['email']);
            }
        }

        $order->setStatus(OrderStatus::PENDING);

        // Copier les items du panier
        foreach ($cart->getItems() as $cartItem) {
            $orderItem = OrderItem::createFromCartItem($cartItem);
            $order->addItem($orderItem);
        }

        // Prix
        $subtotal = $cart->getTotal();
        $deliveryPrice = $this->calculateDeliveryPrice($deliveryType);
        $total = $subtotal + $deliveryPrice;

        $order->setSubtotal($subtotal);
        $order->setDeliveryPrice($deliveryPrice);
        $order->setTotal($total);

        // Adresse de livraison
        $order->setDeliveryFirstName($deliveryData['firstName']);
        $order->setDeliveryLastName($deliveryData['lastName']);
        $order->setDeliveryStreet($deliveryData['street']);
        $order->setDeliveryStreetComplement($deliveryData['streetComplement'] ?? null);
        $order->setDeliveryPostalCode($deliveryData['postalCode']);
        $order->setDeliveryCity($deliveryData['city']);
        $order->setDeliveryCountry($deliveryData['country'] ?? 'FR');
        $order->setDeliveryPhone($deliveryData['phone']);
        $order->setDeliveryInstructions($deliveryData['instructions'] ?? null);
        $order->setDeliveryType($deliveryType);

        // Token invité
        $order->setDeliveryType($deliveryType);

        // Générer un token pour les invités
        if (!$user) {
            $order->generateTrackingToken();
        }

        $this->em->persist($order);
        $this->em->flush();

        return $order;
    }

    /**
     * Calcule le prix de livraison selon le type
     */
    public function calculateDeliveryPrice(string $deliveryType): int
    {
        return match($deliveryType) {
            'simple' => 2000, // 20€
            'with_installation' => 3000, // 30€
            default => throw new \InvalidArgumentException('Type de livraison invalide'),
        };
    }

    /**
     * Marque une commande comme payée
     */
    public function markAsPaid(Order $order): void
    {
        $order->setStatus(OrderStatus::PAID);
        $order->setPaidAt(new \DateTimeImmutable());

        $this->em->flush();
    }

    /**
     * Marque une commande comme annulée
     */
    public function cancelOrder(Order $order, ?string $reason = null): void
    {
        $order->setStatus(OrderStatus::CANCELLED);
        $order->setCancelledAt(new \DateTimeImmutable());

        if ($reason) {
            $order->setNotes($order->getNotes() . "\n[Annulation] " . $reason);
        }

        $this->em->flush();
    }

    /**
     * Change le statut d'une commande
     */
    public function updateStatus(Order $order, OrderStatus $status): void
    {
        $oldStatus = $order->getStatus();
        $order->setStatus($status);

        // Mettre à jour les timestamps selon le statut
        match($status) {
            OrderStatus::PAID => $order->setPaidAt(new \DateTimeImmutable()),
            OrderStatus::SHIPPED => $order->setShippedAt(new \DateTimeImmutable()),
            OrderStatus::DELIVERED => $order->setDeliveredAt(new \DateTimeImmutable()),
            OrderStatus::CANCELLED => $order->setCancelledAt(new \DateTimeImmutable()),
            default => null
        };

        $this->em->flush();
    }

    /**
     * Vérifie si une commande peut être annulée
     */
    public function canBeCancelled(Order $order): bool
    {
        return in_array($order->getStatus(), [
            OrderStatus::PENDING,
            OrderStatus::PAID,
        ]);
    }

    /**
     * Récupère les commandes d'un utilisateur
     */
    public function getUserOrders(User $user): array
    {
        return $this->em->getRepository(Order::class)
            ->findByUserOrderedByDate($user);
    }

    /**
     * Récupère une commande par son numéro
     */
    public function getOrderByNumber(string $orderNumber): ?Order
    {
        return $this->em->getRepository(Order::class)
            ->findByOrderNumber($orderNumber);
    }

    /**
     * Vérifie que la commande appartient à l'utilisateur
     */
    public function belongsToUser(Order $order, User $user): bool
    {
        return $order->getUser()->getId() === $user->getId();
    }
}
