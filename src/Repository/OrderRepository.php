<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use App\Enum\OrderStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Trouve les commandes d'un utilisateur triées par date décroissante
     */
    public function findByUserOrderedByDate(User $user): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les commandes d'un utilisateur
     */
    public function findByUser(UserInterface $user): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les commandes actives d'un utilisateur (en cours)
     */
    public function findActiveOrdersByUser(UserInterface $user): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.user = :user')
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', [
                OrderStatus::PAID,
                OrderStatus::PROCESSING,
                OrderStatus::SHIPPED,
            ])
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les commandes livrées d'un utilisateur
     */
    public function findDeliveredOrdersByUser(UserInterface $user): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.user = :user')
            ->andWhere('o.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', OrderStatus::DELIVERED)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte toutes les commandes d'un utilisateur
     */
    public function countByUser(UserInterface $user): int
    {
        return $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les commandes actives d'un utilisateur
     */
    public function countActiveByUser(UserInterface $user): int
    {
        return $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.user = :user')
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', [
                OrderStatus::PAID,
                OrderStatus::PROCESSING,
                OrderStatus::SHIPPED,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les commandes livrées d'un utilisateur
     */
    public function countDeliveredByUser(UserInterface $user): int
    {
        return $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.user = :user')
            ->andWhere('o.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', OrderStatus::DELIVERED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve une commande par son numéro
     */
    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.orderNumber = :orderNumber')
            ->setParameter('orderNumber', $orderNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compte les commandes par statut
     */
    public function countByStatus(): array
    {
        return $this->createQueryBuilder('o')
            ->select('o.status, COUNT(o.id) as count')
            ->groupBy('o.status')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le chiffre d'affaires total (commandes payées/livrées)
     */
    public function getTotalRevenue(): int
    {
        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.total)')
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('statuses', [
                OrderStatus::PAID,
                OrderStatus::PROCESSING,
                OrderStatus::SHIPPED,
                OrderStatus::DELIVERED,
            ])
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    /**
     * Calcule le chiffre d'affaires du mois en cours
     */
    public function getMonthlyRevenue(): int
    {
        $startOfMonth = new \DateTimeImmutable('first day of this month midnight');

        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.total)')
            ->andWhere('o.status IN (:statuses)')
            ->andWhere('o.createdAt >= :startOfMonth')
            ->setParameter('statuses', [
                OrderStatus::PAID,
                OrderStatus::PROCESSING,
                OrderStatus::SHIPPED,
                OrderStatus::DELIVERED,
            ])
            ->setParameter('startOfMonth', $startOfMonth)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    /**
     * Récupère les commandes récentes pour le dashboard admin
     */
    public function findRecentOrders(int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.user', 'u')
            ->leftJoin('o.items', 'i')
            ->addSelect('u', 'i')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les commandes nécessitant une action (pending, paid)
     */
    public function findOrdersRequiringAction(): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.user', 'u')
            ->addSelect('u')
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('statuses', [
                OrderStatus::PENDING,
                OrderStatus::PAID,
            ])
            ->orderBy('o.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les commandes nécessitant une action
     */
    public function countOrdersRequiringAction(): int
    {
        return $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('statuses', [
                OrderStatus::PENDING,
                OrderStatus::PAID,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Statistiques des commandes par jour (derniers 30 jours)
     */
    public function getOrdersStatsByDay(int $days = 30): array
    {
        $startDate = new \DateTimeImmutable("-{$days} days midnight");

        return $this->createQueryBuilder('o')
            ->select("DATE(o.createdAt) as date, COUNT(o.id) as count, SUM(o.total) as revenue")
            ->andWhere('o.createdAt >= :startDate')
            ->setParameter('startDate', $startDate)
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
