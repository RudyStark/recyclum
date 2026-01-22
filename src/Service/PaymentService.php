<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Payment;
use App\Enum\PaymentStatus;
use Doctrine\ORM\EntityManagerInterface;

class PaymentService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Crée un paiement pour une commande
     */
    public function createPayment(
        Order $order,
        string $method,
        string $transactionId,
        ?string $paymentIntentId = null
    ): Payment {
        $payment = new Payment();
        $payment->setOrder($order);
        $payment->setMethod($method);
        $payment->setAmount($order->getTotal());
        $payment->setTransactionId($transactionId);
        $payment->setPaymentIntentId($paymentIntentId);
        $payment->setStatus(PaymentStatus::PENDING);

        $this->em->persist($payment);
        $this->em->flush();

        return $payment;
    }

    /**
     * Marque un paiement comme complété
     */
    public function markAsCompleted(Payment $payment): void
    {
        $payment->markAsCompleted();
        $this->em->flush();
    }

    /**
     * Marque un paiement comme échoué
     */
    public function markAsFailed(Payment $payment, string $reason): void
    {
        $payment->markAsFailed($reason);
        $this->em->flush();
    }

    /**
     * Récupère un paiement par son transaction ID
     */
    public function findByTransactionId(string $transactionId): ?Payment
    {
        return $this->em->getRepository(Payment::class)
            ->findByTransactionId($transactionId);
    }

    /**
     * Vérifie si une commande a un paiement valide
     */
    public function hasValidPayment(Order $order): bool
    {
        foreach ($order->getPayments() as $payment) {
            if ($payment->isCompleted()) {
                return true;
            }
        }
        return false;
    }
}
