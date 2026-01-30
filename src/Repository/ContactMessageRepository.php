<?php

namespace App\Repository;

use App\Entity\ContactMessage;
use App\Enum\ContactStatus;
use App\Enum\ContactSubject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContactMessage>
 */
class ContactMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContactMessage::class);
    }

    /**
     * Count messages by status
     */
    public function countByStatus(ContactStatus $status): int
    {
        return $this->count(['status' => $status]);
    }

    /**
     * Count new messages
     */
    public function countNew(): int
    {
        return $this->countByStatus(ContactStatus::NEW);
    }

    /**
     * Count open messages (NEW, READ, IN_PROGRESS)
     */
    public function countOpen(): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.status IN (:statuses)')
            ->setParameter('statuses', [
                ContactStatus::NEW,
                ContactStatus::READ,
                ContactStatus::IN_PROGRESS
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find messages with filters
     */
    public function findByFilters(
        ?ContactStatus $status = null,
        ?ContactSubject $subject = null,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        string $orderBy = 'createdAt',
        string $orderDir = 'DESC',
        int $limit = 50,
        int $offset = 0
    ): array {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->leftJoin('c.relatedOrder', 'o');

        if ($status !== null) {
            $qb->andWhere('c.status = :status')
               ->setParameter('status', $status);
        }

        if ($subject !== null) {
            $qb->andWhere('c.subject = :subject')
               ->setParameter('subject', $subject);
        }

        if ($search !== null && $search !== '') {
            $qb->andWhere('c.ticketNumber LIKE :search OR c.firstName LIKE :search OR c.lastName LIKE :search OR c.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($dateFrom !== null) {
            $qb->andWhere('c.createdAt >= :dateFrom')
               ->setParameter('dateFrom', new \DateTimeImmutable($dateFrom));
        }

        if ($dateTo !== null) {
            $qb->andWhere('c.createdAt <= :dateTo')
               ->setParameter('dateTo', new \DateTimeImmutable($dateTo . ' 23:59:59'));
        }

        // Order
        $validOrderBy = ['createdAt', 'updatedAt', 'status', 'subject'];
        if (!in_array($orderBy, $validOrderBy)) {
            $orderBy = 'createdAt';
        }
        $qb->orderBy('c.' . $orderBy, $orderDir === 'ASC' ? 'ASC' : 'DESC');

        $qb->setMaxResults($limit)
           ->setFirstResult($offset);

        return $qb->getQuery()->getResult();
    }

    /**
     * Count messages by filters (for pagination)
     */
    public function countByFilters(
        ?ContactStatus $status = null,
        ?ContactSubject $subject = null,
        ?string $search = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): int {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)');

        if ($status !== null) {
            $qb->andWhere('c.status = :status')
               ->setParameter('status', $status);
        }

        if ($subject !== null) {
            $qb->andWhere('c.subject = :subject')
               ->setParameter('subject', $subject);
        }

        if ($search !== null && $search !== '') {
            $qb->andWhere('c.ticketNumber LIKE :search OR c.firstName LIKE :search OR c.lastName LIKE :search OR c.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($dateFrom !== null) {
            $qb->andWhere('c.createdAt >= :dateFrom')
               ->setParameter('dateFrom', new \DateTimeImmutable($dateFrom));
        }

        if ($dateTo !== null) {
            $qb->andWhere('c.createdAt <= :dateTo')
               ->setParameter('dateTo', new \DateTimeImmutable($dateTo . ' 23:59:59'));
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get average response time in hours
     */
    public function getAverageResponseTime(): ?float
    {
        $result = $this->createQueryBuilder('c')
            ->select('AVG(TIMESTAMPDIFF(HOUR, c.createdAt, c.readAt)) as avgTime')
            ->where('c.readAt IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : null;
    }

    /**
     * Get stats for dashboard
     */
    public function getStatsForDashboard(): array
    {
        $new = $this->countByStatus(ContactStatus::NEW);
        $read = $this->countByStatus(ContactStatus::READ);
        $inProgress = $this->countByStatus(ContactStatus::IN_PROGRESS);
        $answered = $this->countByStatus(ContactStatus::ANSWERED);
        $closed = $this->countByStatus(ContactStatus::CLOSED);

        return [
            'new' => $new,
            'read' => $read,
            'in_progress' => $inProgress,
            'answered' => $answered,
            'closed' => $closed,
            'open' => $new + $read + $inProgress,
            'total' => $new + $read + $inProgress + $answered + $closed,
        ];
    }

    /**
     * Find priority messages (warranty, returns)
     */
    public function findPriorityMessages(int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.subject IN (:subjects)')
            ->andWhere('c.status != :closed')
            ->setParameter('subjects', [ContactSubject::WARRANTY, ContactSubject::RETURN])
            ->setParameter('closed', ContactStatus::CLOSED)
            ->orderBy('c.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recent messages for a user
     */
    public function findByUser(int $userId, int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recent new messages (for notifications)
     */
    public function findRecentNew(int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.status = :status')
            ->setParameter('status', ContactStatus::NEW)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count messages awaiting admin response (IN_PROGRESS status - client replied)
     */
    public function countAwaitingResponse(): int
    {
        return $this->countByStatus(ContactStatus::IN_PROGRESS);
    }

    /**
     * Find messages awaiting admin response (for notifications)
     */
    public function findAwaitingResponse(int $limit = 5): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.status = :status')
            ->setParameter('status', ContactStatus::IN_PROGRESS)
            ->orderBy('c.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count total notifications for contact (new + awaiting response)
     */
    public function countForNotifications(): int
    {
        return $this->countNew() + $this->countAwaitingResponse();
    }
}
