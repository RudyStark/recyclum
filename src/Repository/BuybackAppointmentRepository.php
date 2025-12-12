<?php


namespace App\Repository;

use App\Entity\BuybackAppointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BuybackAppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BuybackAppointment::class);
    }

    /**
     * Récupère les créneaux occupés pour une date donnée
     */
    public function getBookedSlotsForDate(\DateTimeImmutable $date): array
    {
        $appointments = $this->createQueryBuilder('a')
            ->where('a.appointmentDate = :date')
            ->andWhere('a.status = :status')
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('status', 'scheduled')
            ->getQuery()
            ->getResult();

        return array_map(fn($apt) => [
            'date' => $apt->getAppointmentDate()->format('Y-m-d'),
            'time' => $apt->getAppointmentTime()
        ], $appointments);
    }

    /**
     * Récupère tous les créneaux occupés pour un mois donné
     */
    public function getBookedSlotsForMonth(int $year, int $month): array
    {
        $firstDay = new \DateTimeImmutable("$year-$month-01");
        $lastDay = $firstDay->modify('last day of this month');

        $appointments = $this->createQueryBuilder('a')
            ->where('a.appointmentDate >= :start')
            ->andWhere('a.appointmentDate <= :end')
            ->andWhere('a.status = :status')
            ->setParameter('start', $firstDay->format('Y-m-d'))
            ->setParameter('end', $lastDay->format('Y-m-d'))
            ->setParameter('status', 'scheduled')
            ->getQuery()
            ->getResult();

        return array_map(fn($apt) => [
            'date' => $apt->getAppointmentDate()->format('Y-m-d'),
            'time' => $apt->getAppointmentTime()
        ], $appointments);
    }

    /**
     * Vérifie si un créneau est disponible
     */
    public function isSlotAvailable(\DateTimeImmutable $date, string $time): bool
    {
        $count = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.appointmentDate = :date')
            ->andWhere('a.appointmentTime = :time')
            ->andWhere('a.status = :status')
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('time', $time)
            ->setParameter('status', 'scheduled')
            ->getQuery()
            ->getSingleScalarResult();

        return $count == 0;
    }

    /**
     * Récupère les RDV à venir
     */
    public function getUpcomingAppointments(int $limit = 10): array
    {
        $today = new \DateTimeImmutable();

        return $this->createQueryBuilder('a')
            ->where('a.appointmentDate >= :today')
            ->andWhere('a.status = :status')
            ->setParameter('today', $today->format('Y-m-d'))
            ->setParameter('status', 'scheduled')
            ->orderBy('a.appointmentDate', 'ASC')
            ->addOrderBy('a.appointmentTime', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère le prochain RDV pour une demande de rachat
     */
    public function getNextAppointmentForRequest(int $buybackRequestId): ?BuybackAppointment
    {
        $today = new \DateTimeImmutable();

        return $this->createQueryBuilder('a')
            ->where('a.buybackRequest = :requestId')
            ->andWhere('a.appointmentDate >= :today')
            ->andWhere('a.status = :status')
            ->setParameter('requestId', $buybackRequestId)
            ->setParameter('today', $today->format('Y-m-d'))
            ->setParameter('status', 'scheduled')
            ->orderBy('a.appointmentDate', 'ASC')
            ->addOrderBy('a.appointmentTime', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
