<?php

namespace App\Service;

use App\Repository\BuybackRequestRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private BuybackRequestRepository $buybackRequestRepository,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Récupère toutes les notifications non lues pour l'admin
     */
    public function getUnreadNotifications(): array
    {
        $notifications = [];

        // 1. Nouvelles demandes de rachat en attente
        $pendingCount = $this->buybackRequestRepository->countPending();
        if ($pendingCount > 0) {
            $recentPending = $this->buybackRequestRepository->getRecentPending(5);

            $notifications[] = [
                'type' => 'buyback_pending',
                'title' => 'Nouvelles demandes de rachat',
                'message' => sprintf('%d demande%s en attente de validation',
                    $pendingCount,
                    $pendingCount > 1 ? 's' : ''
                ),
                'count' => $pendingCount,
                'icon' => 'fa-shopping-cart',
                'color' => '#f59e0b',
                'url' => '/admin?crudAction=index&crudControllerFqcn=App\\Controller\\Admin\\BuybackRequestCrudController&filters[status][value]=pending',
                'items' => array_map(function($request) {
                    return [
                        'id' => $request->getId(),
                        'customer' => $request->getFirstName() . ' ' . $request->getLastName(),
                        'appliance' => $request->getBrand() . ' ' . $request->getModel(),
                        'price' => $request->getEstimatedPrice(),
                        'date' => $request->getCreatedAt()->format('d/m/Y H:i'),
                        'url' => sprintf('/admin?crudAction=showRequest&crudControllerFqcn=App\\Controller\\Admin\\BuybackRequestCrudController&entityId=%d', $request->getId())
                    ];
                }, $recentPending)
            ];
        }

        // 2. Rendez-vous d'enlèvement à venir (prochains 7 jours)
        $upcomingAppointments = $this->getUpcomingAppointments(7);
        if (count($upcomingAppointments) > 0) {
            $notifications[] = [
                'type' => 'appointments_upcoming',
                'title' => 'Enlèvements programmés',
                'message' => sprintf('%d enlèvement%s prévu%s dans les 7 prochains jours',
                    count($upcomingAppointments),
                    count($upcomingAppointments) > 1 ? 's' : '',
                    count($upcomingAppointments) > 1 ? 's' : ''
                ),
                'count' => count($upcomingAppointments),
                'icon' => 'fa-truck',
                'color' => '#3b82f6',
                'url' => '/admin?crudAction=index&crudControllerFqcn=App\\Controller\\Admin\\BuybackRequestCrudController&filters[status][value]=appointment_scheduled',
                'items' => array_map(function($appointment) {
                    $request = $appointment->getBuybackRequest();
                    return [
                        'id' => $appointment->getId(),
                        'customer' => $request->getFirstName() . ' ' . $request->getLastName(),
                        'appliance' => $request->getBrand() . ' ' . $request->getModel(),
                        'date' => $appointment->getFormattedDate(),
                        'time' => $appointment->getAppointmentTime(),
                        'address' => $request->getAddress() . ', ' . $request->getCity(),
                        'url' => sprintf('/admin?crudAction=showRequest&crudControllerFqcn=App\\Controller\\Admin\\BuybackRequestCrudController&entityId=%d', $request->getId())
                    ];
                }, $upcomingAppointments)
            ];
        }

        // 3. Appareils collectés en attente de paiement
        $collectedCount = $this->buybackRequestRepository->count(['status' => 'collected']);
        if ($collectedCount > 0) {
            $collectedRequests = $this->buybackRequestRepository->findBy(
                ['status' => 'collected'],
                ['updatedAt' => 'DESC'],
                5
            );

            $notifications[] = [
                'type' => 'payment_pending',
                'title' => 'Paiements en attente',
                'message' => sprintf('%d appareil%s collecté%s à payer',
                    $collectedCount,
                    $collectedCount > 1 ? 's' : '',
                    $collectedCount > 1 ? 's' : ''
                ),
                'count' => $collectedCount,
                'icon' => 'fa-euro-sign',
                'color' => '#10b981',
                'url' => '/admin?crudAction=index&crudControllerFqcn=App\\Controller\\Admin\\BuybackRequestCrudController&filters[status][value]=collected',
                'items' => array_map(function($request) {
                    return [
                        'id' => $request->getId(),
                        'customer' => $request->getFirstName() . ' ' . $request->getLastName(),
                        'appliance' => $request->getBrand() . ' ' . $request->getModel(),
                        'amount' => $request->getFinalPrice() ?? $request->getEstimatedPrice(),
                        'method' => $request->getPaymentMethod() === 'virement' ? 'Virement' : 'Espèces',
                        'collectedAt' => $request->getUpdatedAt()->format('d/m/Y'),
                        'url' => sprintf('/admin?crudAction=showRequest&crudControllerFqcn=App\\Controller\\Admin\\BuybackRequestCrudController&entityId=%d', $request->getId())
                    ];
                }, $collectedRequests)
            ];
        }

        return $notifications;
    }

    /**
     * Compte le nombre total de notifications non lues
     */
    public function getUnreadCount(): int
    {
        $count = 0;

        // Demandes en attente
        $count += $this->buybackRequestRepository->countPending();

        // Enlèvements à venir (7 jours)
        $count += count($this->getUpcomingAppointments(7));

        // Appareils collectés
        $count += $this->buybackRequestRepository->count(['status' => 'collected']);

        return $count;
    }

    /**
     * Récupère les rendez-vous à venir dans les X prochains jours
     */
    private function getUpcomingAppointments(int $days): array
    {
        $startDate = new \DateTime('today');
        $endDate = (new \DateTime('today'))->modify("+{$days} days");

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('a')
            ->from('App\Entity\BuybackAppointment', 'a')
            ->where('a.appointmentDate BETWEEN :start AND :end')
            ->andWhere('a.status = :status')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->setParameter('status', 'scheduled')
            ->orderBy('a.appointmentDate', 'ASC')
            ->addOrderBy('a.appointmentTime', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
