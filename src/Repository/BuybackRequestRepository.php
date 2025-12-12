<?php

namespace App\Repository;

use App\Entity\BuybackRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BuybackRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BuybackRequest::class);
    }

    public function save(BuybackRequest $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(BuybackRequest $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Compte les demandes en attente (pending)
     */
    public function countPending(): int
    {
        return $this->count(['status' => 'pending']);
    }

    /**
     * Récupère les dernières demandes en attente
     */
    public function getRecentPending(int $limit = 5): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('b.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les demandes nécessitant une action (pending)
     */
    public function countAwaitingAction(): int
    {
        return $this->count(['status' => 'pending']);
    }

    /**
     * Calcule le montant total à payer aux clients (collected mais pas paid)
     */
    public function getTotalToPay(): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('SUM(b.finalPrice)')
            ->where('b.status = :status')
            ->setParameter('status', 'collected')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    /**
     * Calcule le montant total payé ce mois
     */
    public function getTotalPaidThisMonth(): int
    {
        $startOfMonth = new \DateTimeImmutable('first day of this month 00:00:00');
        $endOfMonth = new \DateTimeImmutable('last day of this month 23:59:59');

        return (int) $this->createQueryBuilder('b')
            ->select('SUM(b.finalPrice)')
            ->where('b.status = :status')
            ->andWhere('b.updatedAt BETWEEN :start AND :end')
            ->setParameter('status', 'paid')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    /**
     * Calcule le nombre de paiements effectués ce mois
     */
    public function countPaidThisMonth(): int
    {
        $startOfMonth = new \DateTimeImmutable('first day of this month 00:00:00');
        $endOfMonth = new \DateTimeImmutable('last day of this month 23:59:59');

        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.status = :status')
            ->andWhere('b.updatedAt BETWEEN :start AND :end')
            ->setParameter('status', 'paid')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calcule le taux de validation (demandes acceptées / total demandes)
     */
    public function getValidationRate(): float
    {
        $total = $this->count([]);

        if ($total === 0) {
            return 0.0;
        }

        $validated = $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.status IN (:statuses)')
            ->setParameter('statuses', ['validated', 'appointment_scheduled', 'awaiting_collection', 'collected', 'paid'])
            ->getQuery()
            ->getSingleScalarResult();

        return round(($validated / $total) * 100, 1);
    }

    /**
     * Compte les RDV prévus cette semaine
     */
    public function countAppointmentsThisWeek(): int
    {
        // Cette méthode nécessite une jointure avec BuybackAppointment
        // Pour l'instant, on compte juste les demandes en statut appointment_scheduled
        return $this->count(['status' => 'appointment_scheduled']);
    }
}
