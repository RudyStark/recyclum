<?php

namespace App\Controller;

use App\Entity\Order;
use App\Enum\OrderStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OrderTrackingController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    #[Route('/commande/suivi/{orderNumber}/{token}', name: 'order_tracking', methods: ['GET'])]
    public function track(string $orderNumber, string $token): Response
    {
        // Find order by orderNumber AND trackingToken for security
        $order = $this->em->getRepository(Order::class)->findOneBy([
            'orderNumber' => $orderNumber,
            'trackingToken' => $token,
        ]);

        // Return 404 if order not found or token invalid
        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable ou lien invalide.');
        }

        // Calculate timeline steps status
        $timelineSteps = $this->getTimelineSteps($order);

        return $this->render('order/tracking.html.twig', [
            'order' => $order,
            'timelineSteps' => $timelineSteps,
        ]);
    }

    private function getTimelineSteps(Order $order): array
    {
        $status = $order->getStatus();

        // Define the order of statuses for comparison
        $statusOrder = [
            OrderStatus::PENDING->value => 0,
            OrderStatus::PAID->value => 1,
            OrderStatus::PROCESSING->value => 2,
            OrderStatus::SHIPPED->value => 3,
            OrderStatus::DELIVERED->value => 4,
        ];

        $currentStatusValue = $statusOrder[$status->value] ?? 0;

        // Check if order is cancelled or refunded (special states)
        $isCancelled = $status === OrderStatus::CANCELLED;
        $isRefunded = $status === OrderStatus::REFUNDED;

        return [
            [
                'key' => 'paid',
                'label' => 'Payée',
                'description' => 'Paiement validé',
                'icon' => 'credit-card',
                'completed' => $order->getPaidAt() !== null,
                'current' => $status === OrderStatus::PAID,
                'date' => $order->getPaidAt(),
            ],
            [
                'key' => 'processing',
                'label' => 'En préparation',
                'description' => 'Commande en cours de préparation',
                'icon' => 'package',
                'completed' => $currentStatusValue >= 2,
                'current' => $status === OrderStatus::PROCESSING,
                'date' => null,
            ],
            [
                'key' => 'shipped',
                'label' => 'Expédiée',
                'description' => 'En cours de livraison',
                'icon' => 'truck',
                'completed' => $order->getShippedAt() !== null,
                'current' => $status === OrderStatus::SHIPPED,
                'date' => $order->getShippedAt(),
            ],
            [
                'key' => 'delivered',
                'label' => 'Livrée',
                'description' => 'Commande livrée',
                'icon' => 'check-circle',
                'completed' => $order->getDeliveredAt() !== null,
                'current' => $status === OrderStatus::DELIVERED,
                'date' => $order->getDeliveredAt(),
            ],
        ];
    }
}
