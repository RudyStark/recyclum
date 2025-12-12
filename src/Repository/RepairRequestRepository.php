<?php

namespace App\Repository;

use App\Entity\RepairRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RepairRequest>
 */
class RepairRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RepairRequest::class);
    }

    /**
     * Trouve les demandes en attente
     */
    public function findPending(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les demandes urgentes
     */
    public function findUrgent(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.urgency = :urgency')
            ->setParameter('urgency', true)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques par statut
     */
    public function countByStatus(): array
    {
        $results = $this->createQueryBuilder('r')
            ->select('r.status, COUNT(r.id) as count')
            ->groupBy('r.status')
            ->getQuery()
            ->getResult();

        $stats = [
            'pending' => 0,
            'contacted' => 0,
            'scheduled' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];

        foreach ($results as $result) {
            $stats[$result['status']] = (int) $result['count'];
        }

        return $stats;
    }

    /**
     * Compte les réparations actives (en cours de traitement)
     */
    public function countActiveRepairs(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status IN (:statuses)')
            ->setParameter('statuses', ['pending', 'contacted', 'scheduled'])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les réparations en attente de traitement
     */
    public function countPendingRepairs(): int
    {
        return $this->count(['status' => 'pending']);
    }

    /**
     * Compte les réparations urgentes non traitées
     */
    public function countUrgentPending(): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.urgency = :urgency')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('urgency', true)
            ->setParameter('statuses', ['pending', 'contacted'])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les dernières demandes de réparation
     */
    public function getRecentRepairs(int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques des réparations par statut (pour graphique)
     */
    public function getRepairsByStatus(): array
    {
        $results = $this->createQueryBuilder('r')
            ->select('r.status, COUNT(r.id) as count')
            ->groupBy('r.status')
            ->getQuery()
            ->getResult();

        $stats = [
            'pending' => 0,
            'contacted' => 0,
            'scheduled' => 0,
            'completed' => 0,
            'cancelled' => 0,
        ];

        foreach ($results as $result) {
            $stats[$result['status']] = (int) $result['count'];
        }

        return $stats;
    }
}
