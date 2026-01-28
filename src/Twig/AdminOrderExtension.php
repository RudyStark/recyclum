<?php

namespace App\Twig;

use App\Repository\OrderRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AdminOrderExtension extends AbstractExtension
{
    public function __construct(
        private OrderRepository $orderRepository
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_orders_requiring_action_count', [$this, 'getOrdersRequiringActionCount']),
        ];
    }

    public function getOrdersRequiringActionCount(): int
    {
        return $this->orderRepository->countOrdersRequiringAction();
    }
}
