<?php

namespace App\Controller;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

#[Route('/mon-compte/commandes')]
#[IsGranted('ROLE_USER')]
class AccountOrderController extends AbstractController
{
    public function __construct(
        private OrderRepository $orderRepository,
    ) {
    }

    #[Route('', name: 'account_orders', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $user = $this->getUser();
        $filter = $request->query->get('filter', 'all');

        // Get orders based on filter
        $orders = match ($filter) {
            'active' => $this->orderRepository->findActiveOrdersByUser($user),
            'delivered' => $this->orderRepository->findDeliveredOrdersByUser($user),
            default => $this->orderRepository->findByUser($user),
        };

        // Count orders for each tab
        $counts = [
            'all' => $this->orderRepository->countByUser($user),
            'active' => $this->orderRepository->countActiveByUser($user),
            'delivered' => $this->orderRepository->countDeliveredByUser($user),
        ];

        return $this->render('account/orders/list.html.twig', [
            'orders' => $orders,
            'filter' => $filter,
            'counts' => $counts,
        ]);
    }

    #[Route('/{orderNumber}', name: 'account_order_show', methods: ['GET'])]
    public function show(string $orderNumber): Response
    {
        $user = $this->getUser();

        $order = $this->orderRepository->findOneBy(['orderNumber' => $orderNumber]);

        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        // Security check: Verify order belongs to current user
        if ($order->getUser() !== $user) {
            throw new AccessDeniedHttpException('Vous n\'avez pas accès à cette commande.');
        }

        // Build timeline steps for display
        $timelineSteps = $this->getTimelineSteps($order);

        return $this->render('account/orders/show.html.twig', [
            'order' => $order,
            'timelineSteps' => $timelineSteps,
        ]);
    }

    private function getTimelineSteps(Order $order): array
    {
        $status = $order->getStatus();

        $statusOrder = [
            OrderStatus::PENDING->value => 0,
            OrderStatus::PAID->value => 1,
            OrderStatus::PROCESSING->value => 2,
            OrderStatus::SHIPPED->value => 3,
            OrderStatus::DELIVERED->value => 4,
        ];

        $currentStatusValue = $statusOrder[$status->value] ?? 0;

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
