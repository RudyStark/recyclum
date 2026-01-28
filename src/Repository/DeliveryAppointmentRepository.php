<?php

namespace App\Repository;

use App\Entity\DeliveryAppointment;
use App\Entity\BuybackAppointment;
use App\Entity\RepairAppointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for DeliveryAppointment with cross-service availability checking.
 * All services (Buyback, Repair, Delivery) share the same transport team,
 * so we must check availability across all of them.
 */
class DeliveryAppointmentRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, DeliveryAppointment::class);
        $this->entityManager = $entityManager;
    }

    /**
     * Get all booked dates for a given month across ALL services
     * Returns dates that are already booked (1 appointment per day max for transport)
     */
    public function getBookedDatesForMonth(int $year, int $month): array
    {
        $firstDay = new \DateTimeImmutable("$year-$month-01");
        $lastDay = $firstDay->modify('last day of this month');

        $bookedDates = [];

        // 1. Get Delivery appointments
        $deliveryDates = $this->createQueryBuilder('d')
            ->select('d.appointmentDate')
            ->where('d.appointmentDate >= :start')
            ->andWhere('d.appointmentDate <= :end')
            ->andWhere('d.status = :status')
            ->setParameter('start', $firstDay)
            ->setParameter('end', $lastDay)
            ->setParameter('status', DeliveryAppointment::STATUS_SCHEDULED)
            ->getQuery()
            ->getResult();

        foreach ($deliveryDates as $row) {
            $bookedDates[] = $row['appointmentDate']->format('Y-m-d');
        }

        // 2. Get Buyback appointments
        $buybackRepo = $this->entityManager->getRepository(BuybackAppointment::class);
        $buybackDates = $buybackRepo->createQueryBuilder('b')
            ->select('b.appointmentDate')
            ->where('b.appointmentDate >= :start')
            ->andWhere('b.appointmentDate <= :end')
            ->andWhere('b.status = :status')
            ->setParameter('start', $firstDay->format('Y-m-d'))
            ->setParameter('end', $lastDay->format('Y-m-d'))
            ->setParameter('status', 'scheduled')
            ->getQuery()
            ->getResult();

        foreach ($buybackDates as $row) {
            $date = $row['appointmentDate'];
            if ($date instanceof \DateTimeImmutable) {
                $bookedDates[] = $date->format('Y-m-d');
            }
        }

        // 3. Get Repair appointments
        $repairRepo = $this->entityManager->getRepository(RepairAppointment::class);
        $repairDates = $repairRepo->createQueryBuilder('r')
            ->select('r.appointmentDate')
            ->where('r.appointmentDate >= :start')
            ->andWhere('r.appointmentDate <= :end')
            ->setParameter('start', $firstDay)
            ->setParameter('end', $lastDay)
            ->getQuery()
            ->getResult();

        foreach ($repairDates as $row) {
            $date = $row['appointmentDate'];
            if ($date instanceof \DateTimeInterface) {
                $bookedDates[] = $date->format('Y-m-d');
            }
        }

        return array_unique($bookedDates);
    }

    /**
     * Check if a specific date is available across ALL services
     */
    public function isDateAvailable(\DateTimeImmutable $date): bool
    {
        $dateStr = $date->format('Y-m-d');

        // 1. Check Delivery appointments
        $deliveryCount = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.appointmentDate = :date')
            ->andWhere('d.status = :status')
            ->setParameter('date', $date)
            ->setParameter('status', DeliveryAppointment::STATUS_SCHEDULED)
            ->getQuery()
            ->getSingleScalarResult();

        if ($deliveryCount > 0) {
            return false;
        }

        // 2. Check Buyback appointments
        $buybackRepo = $this->entityManager->getRepository(BuybackAppointment::class);
        $buybackCount = $buybackRepo->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.appointmentDate = :date')
            ->andWhere('b.status = :status')
            ->setParameter('date', $dateStr)
            ->setParameter('status', 'scheduled')
            ->getQuery()
            ->getSingleScalarResult();

        if ($buybackCount > 0) {
            return false;
        }

        // 3. Check Repair appointments
        $repairRepo = $this->entityManager->getRepository(RepairAppointment::class);
        $repairCount = $repairRepo->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.appointmentDate = :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getSingleScalarResult();

        if ($repairCount > 0) {
            return false;
        }

        return true;
    }

    /**
     * Get available dates for customer booking
     * Rules:
     * - Minimum: payment date + 2 days (or today + 2 if no payment date)
     * - Maximum: 30 days from now
     * - Excludes weekends (Saturday/Sunday)
     * - Excludes already booked dates across all services
     */
    public function getAvailableDates(\DateTimeImmutable $paymentDate = null): array
    {
        // Minimum date: payment date + 2 days or today + 2 days
        $baseDate = $paymentDate ?? new \DateTimeImmutable();
        $minDate = $baseDate->modify('+2 days');

        // Make sure minDate is at least today + 2 days
        $today = new \DateTimeImmutable('today');
        $todayPlusTwo = $today->modify('+2 days');
        if ($minDate < $todayPlusTwo) {
            $minDate = $todayPlusTwo;
        }

        // Maximum date: 30 days from now
        $maxDate = $today->modify('+30 days');

        // Get booked dates for the range
        $bookedDates = [];
        $currentMonth = (int)$minDate->format('n');
        $currentYear = (int)$minDate->format('Y');
        $endMonth = (int)$maxDate->format('n');
        $endYear = (int)$maxDate->format('Y');

        // Get booked dates for all relevant months
        while ($currentYear < $endYear || ($currentYear === $endYear && $currentMonth <= $endMonth)) {
            $monthBookedDates = $this->getBookedDatesForMonth($currentYear, $currentMonth);
            $bookedDates = array_merge($bookedDates, $monthBookedDates);

            $currentMonth++;
            if ($currentMonth > 12) {
                $currentMonth = 1;
                $currentYear++;
            }
        }

        $bookedDates = array_unique($bookedDates);

        // Generate available dates
        $availableDates = [];
        $currentDate = $minDate;

        while ($currentDate <= $maxDate) {
            $dayOfWeek = (int)$currentDate->format('w');
            $dateStr = $currentDate->format('Y-m-d');

            // Exclude weekends (0 = Sunday, 6 = Saturday)
            if ($dayOfWeek !== 0 && $dayOfWeek !== 6) {
                // Exclude booked dates
                if (!in_array($dateStr, $bookedDates)) {
                    $availableDates[] = [
                        'date' => $dateStr,
                        'formatted' => $this->formatDateFrench($currentDate),
                        'dayName' => $this->getDayNameFrench($dayOfWeek),
                    ];
                }
            }

            $currentDate = $currentDate->modify('+1 day');
        }

        return $availableDates;
    }

    /**
     * Get upcoming delivery appointments
     */
    public function getUpcomingAppointments(int $limit = 10): array
    {
        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('d')
            ->leftJoin('d.order', 'o')
            ->addSelect('o')
            ->where('d.appointmentDate >= :today')
            ->andWhere('d.status = :status')
            ->setParameter('today', $today)
            ->setParameter('status', DeliveryAppointment::STATUS_SCHEDULED)
            ->orderBy('d.appointmentDate', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all appointments for a specific date (for calendar view)
     */
    public function getAppointmentsForDate(\DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.order', 'o')
            ->addSelect('o')
            ->where('d.appointmentDate = :date')
            ->setParameter('date', $date)
            ->orderBy('d.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find appointment by order
     */
    public function findByOrder(int $orderId): ?DeliveryAppointment
    {
        return $this->createQueryBuilder('d')
            ->where('d.order = :orderId')
            ->setParameter('orderId', $orderId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function formatDateFrench(\DateTimeImmutable $date): string
    {
        $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        $months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

        $dayName = $days[(int)$date->format('w')];
        $day = $date->format('d');
        $month = $months[(int)$date->format('n') - 1];

        return "$dayName $day $month";
    }

    private function getDayNameFrench(int $dayOfWeek): string
    {
        $days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        return $days[$dayOfWeek];
    }
}
