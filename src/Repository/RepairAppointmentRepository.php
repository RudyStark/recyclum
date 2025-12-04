<?php

namespace App\Repository;

use App\Entity\RepairAppointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RepairAppointment>
 */
class RepairAppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RepairAppointment::class);
    }

    /**
     * Trouve tous les créneaux réservés à partir d'aujourd'hui
     */
    public function findUpcomingAppointments(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.appointmentDate >= :today')
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('a.appointmentDate', 'ASC')
            ->addOrderBy('a.appointmentTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un créneau est disponible
     */
    public function isSlotAvailable(\DateTimeInterface $date, \DateTimeInterface $time): bool
    {
        $count = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.appointmentDate = :date')
            ->andWhere('a.appointmentTime = :time')
            ->setParameter('date', $date)
            ->setParameter('time', $time)
            ->getQuery()
            ->getSingleScalarResult();

        return $count === 0;
    }

    /**
     * Récupère tous les créneaux occupés sous forme de tableau
     */
    public function getBlockedSlots(): array
    {
        $appointments = $this->findUpcomingAppointments();
        $blocked = [];

        foreach ($appointments as $apt) {
            $blocked[] = [
                'date' => $apt->getAppointmentDate()->format('Y-m-d'),
                'time' => $apt->getAppointmentTime()->format('H:i')
            ];
        }

        return $blocked;
    }

    /**
     * Trouve les RDV d'un jour donné
     */
    public function findByDate(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.appointmentDate = :date')
            ->setParameter('date', $date)
            ->orderBy('a.appointmentTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
