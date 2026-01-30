<?php

namespace App\Service;

use App\Repository\BuybackRequestRepository;
use App\Repository\ContactMessageRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private BuybackRequestRepository $buybackRequestRepository,
        private ContactMessageRepository $contactMessageRepository,
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

        // 4. Nouveaux messages de contact
        $newContactCount = $this->contactMessageRepository->countNew();
        if ($newContactCount > 0) {
            $recentNewContacts = $this->contactMessageRepository->findRecentNew(5);

            $notifications[] = [
                'type' => 'contact_new',
                'title' => 'Nouveaux messages',
                'message' => sprintf('%d nouveau%s message%s client',
                    $newContactCount,
                    $newContactCount > 1 ? 'x' : '',
                    $newContactCount > 1 ? 's' : ''
                ),
                'count' => $newContactCount,
                'icon' => 'fa-envelope',
                'color' => '#ef4444',
                'url' => '/admin/contacts?status=new',
                'items' => array_map(function($message) {
                    return [
                        'id' => $message->getId(),
                        'ticketNumber' => $message->getTicketNumber(),
                        'customer' => $message->getFullName(),
                        'email' => $message->getEmail(),
                        'subject' => $message->getSubject()->getLabel(),
                        'subjectIcon' => $message->getSubject()->getIcon(),
                        'subjectColor' => $message->getSubject()->getColor(),
                        'preview' => mb_substr($message->getMessage(), 0, 80) . (mb_strlen($message->getMessage()) > 80 ? '...' : ''),
                        'date' => $message->getCreatedAt()->format('d/m/Y H:i'),
                        'isPriority' => $message->isPriority(),
                        'url' => '/admin/contacts/' . $message->getId()
                    ];
                }, $recentNewContacts)
            ];
        }

        // 5. Messages en attente de réponse (client a répondu)
        $awaitingCount = $this->contactMessageRepository->countAwaitingResponse();
        if ($awaitingCount > 0) {
            $awaitingContacts = $this->contactMessageRepository->findAwaitingResponse(5);

            $notifications[] = [
                'type' => 'contact_awaiting',
                'title' => 'Réponses clients',
                'message' => sprintf('%d message%s en attente de réponse',
                    $awaitingCount,
                    $awaitingCount > 1 ? 's' : ''
                ),
                'count' => $awaitingCount,
                'icon' => 'fa-reply',
                'color' => '#8b5cf6',
                'url' => '/admin/contacts?status=in_progress',
                'items' => array_map(function($message) {
                    $lastReply = $message->getLastReply();
                    return [
                        'id' => $message->getId(),
                        'ticketNumber' => $message->getTicketNumber(),
                        'customer' => $message->getFullName(),
                        'subject' => $message->getSubject()->getLabel(),
                        'subjectIcon' => $message->getSubject()->getIcon(),
                        'subjectColor' => $message->getSubject()->getColor(),
                        'lastReplyDate' => $lastReply ? $lastReply->getCreatedAt()->format('d/m/Y H:i') : $message->getCreatedAt()->format('d/m/Y H:i'),
                        'repliesCount' => $message->getRepliesCount(),
                        'isPriority' => $message->isPriority(),
                        'url' => '/admin/contacts/' . $message->getId()
                    ];
                }, $awaitingContacts)
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

        // Nouveaux messages de contact
        $count += $this->contactMessageRepository->countNew();

        // Messages en attente de réponse
        $count += $this->contactMessageRepository->countAwaitingResponse();

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
