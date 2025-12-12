<?php

namespace App\Controller\Admin;

use App\Entity\BuybackRequest;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BuybackRequestCrudController extends AbstractCrudController
{
    private EntityManagerInterface $em;

    public function __construct(
        EntityManagerInterface $entityManager,
        private EmailService $emailService,
        private UrlGeneratorInterface $urlGenerator
    ) {
        $this->em = $entityManager;
    }

    public static function getEntityFqcn(): string
    {
        return BuybackRequest::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Demande de rachat')
            ->setEntityLabelInPlural('Demandes de rachat');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::INDEX, Action::DETAIL, Action::DELETE)
            ->setPermission(Action::INDEX, 'ROLE_IMPOSSIBLE');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status'))
            ->add(ChoiceFilter::new('category'))
            ->add(DateTimeFilter::new('createdAt'));
    }

    /**
     * Redirection depuis EasyAdmin vers index custom
     */
    public function index(AdminContext $context)
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect($adminUrlGenerator
            ->setController(self::class)
            ->setAction('requestIndex')
            ->generateUrl()
        );
    }

    /**
     * INDEX PERSONNALISÉ
     */
    public function requestIndex(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, Request $request)
    {
        $repository = $this->em->getRepository(BuybackRequest::class);

        $queryBuilder = $repository->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC');

        // Gestion des filtres
        $filters = [
            'search' => $request->query->get('search'),
            'status' => $request->query->get('status'),
            'category' => $request->query->get('category'),
        ];

        if ($filters['search']) {
            $queryBuilder->andWhere('r.firstName LIKE :search OR r.lastName LIKE :search OR r.email LIKE :search OR r.brand LIKE :search OR r.model LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if ($filters['status']) {
            $queryBuilder->andWhere('r.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if ($filters['category']) {
            $queryBuilder->andWhere('r.category = :category')
                ->setParameter('category', $filters['category']);
        }

        $query = $queryBuilder->getQuery();

        // Pagination
        $page = $request->query->getInt('page', 1);
        $perPage = 30;

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query);
        $totalItems = count($paginator);
        $totalPages = ceil($totalItems / $perPage);

        $paginator->getQuery()
            ->setFirstResult($perPage * ($page - 1))
            ->setMaxResults($perPage);

        $requests = iterator_to_array($paginator);

        // ✅ CALCUL DES STATS PROFESSIONNELLES
        $stats = [
            'awaiting_action' => $repository->countAwaitingAction(),
            'to_pay_amount' => $repository->getTotalToPay(),
            'to_pay_count' => $repository->count(['status' => 'collected']),
            'paid_this_month_amount' => $repository->getTotalPaidThisMonth(),
            'paid_this_month_count' => $repository->countPaidThisMonth(),
            'validation_rate' => $repository->getValidationRate(),
            'appointments_this_week' => $repository->countAppointmentsThisWeek(),
        ];

        return $this->render('admin/buyback_request/index.html.twig', [
            'requests' => $requests,
            'total_items' => $totalItems,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'per_page' => $perPage,
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    /**
     * SHOW PERSONNALISÉ
     */
    public function showRequest(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, Request $request)
    {
        $entityId = $request->query->get('entityId');

        if (!$entityId) {
            $entity = $context->getEntity();
            if ($entity) {
                $buybackRequest = $entity->getInstance();
            } else {
                throw $this->createNotFoundException('Demande non trouvée');
            }
        } else {
            $buybackRequest = $this->em->getRepository(BuybackRequest::class)->find($entityId);

            if (!$buybackRequest) {
                throw $this->createNotFoundException('Demande non trouvée');
            }
        }

        // ✅ RÉCUPÉRER LE RDV SI EXISTE
        $appointment = null;
        if ($buybackRequest->getStatus() === 'appointment_scheduled') {
            $appointment = $this->em->getRepository(\App\Entity\BuybackAppointment::class)
                ->findOneBy(['buybackRequest' => $buybackRequest], ['createdAt' => 'DESC']);
        }

        return $this->render('admin/buyback_request/show.html.twig', [
            'request' => $buybackRequest,
            'appointment' => $appointment, // ✅ AJOUT
        ]);
    }

    /**
     * DELETE
     */
    #[Route('/admin/buyback-requests/{id}/delete', name: 'admin_buyback_request_delete', methods: ['POST'])]
    public function deleteRequest(int $id, Request $request): Response
    {
        $buybackRequest = $this->em->getRepository(BuybackRequest::class)->find($id);

        if (!$buybackRequest) {
            throw $this->createNotFoundException('Demande non trouvée');
        }

        if ($this->isCsrfTokenValid('delete' . $buybackRequest->getId(), $request->request->get('_token'))) {
            $this->em->remove($buybackRequest);
            $this->em->flush();

            $this->addFlash('success', 'Demande supprimée avec succès.');
        }

        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator
            ->setController(self::class)
            ->setAction('index')
            ->generateUrl()
        );
    }

    /**
     * Route temporaire pour les emails admin (sera remplacée par la vraie)
     */
    #[Route('/admin/buyback-requests/{id}/show', name: 'admin_buyback_request_show')]
    public function temporaryShow(int $id): Response
    {
        return $this->redirect($this->generateUrl('admin') . '?crudAction=showRequest&crudControllerFqcn=' . urlencode(self::class) . '&entityId=' . $id);
    }

    /**
     * Route API pour changer le statut
     */
    #[Route('/admin/buyback-requests/{id}/status', name: 'admin_buyback_request_change_status', methods: ['POST'])]
    public function changeStatus(int $id, Request $request): JsonResponse
    {
        $buybackRequest = $this->em
            ->getRepository(BuybackRequest::class)
            ->find($id);

        if (!$buybackRequest) {
            return $this->json(['error' => 'Demande introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? null;

        if (!$newStatus) {
            return $this->json(['error' => 'Statut requis'], 400);
        }

        // Validation des transitions de statut
        $allowedTransitions = $this->getAllowedStatusTransitions($buybackRequest->getStatus());

        if (!in_array($newStatus, $allowedTransitions)) {
            return $this->json(['error' => 'Transition de statut non autorisée'], 400);
        }

        $buybackRequest->setStatus($newStatus);

        // Ajouter des notes si fournies
        if (isset($data['notes'])) {
            $currentNotes = $buybackRequest->getAdminNotes() ?? '';
            $timestamp = (new \DateTime())->format('d/m/Y H:i');
            $newNote = "\n[{$timestamp}] {$data['notes']}";
            $buybackRequest->setAdminNotes($currentNotes . $newNote);
        }

        $this->em->flush();

        return $this->json(['success' => true, 'status' => $newStatus]);
    }

    /**
     * Route API pour sauvegarder les notes
     */
    #[Route('/admin/buyback-requests/{id}/notes', name: 'admin_buyback_request_save_notes', methods: ['POST'])]
    public function saveNotes(int $id, Request $request): JsonResponse
    {
        $buybackRequest = $this->em
            ->getRepository(BuybackRequest::class)
            ->find($id);

        if (!$buybackRequest) {
            return $this->json(['error' => 'Demande introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $notes = $data['notes'] ?? '';

        $buybackRequest->setAdminNotes($notes);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    /**
     * Valider une demande
     */
    #[Route('/admin/buyback-requests/{id}/validate', name: 'admin_buyback_request_validate', methods: ['POST'])]
    public function validateRequest(int $id, Request $request): JsonResponse
    {
        $buybackRequest = $this->em
            ->getRepository(BuybackRequest::class)
            ->find($id);

        if (!$buybackRequest) {
            return $this->json(['error' => 'Demande introuvable'], 404);
        }

        if (!$buybackRequest->canBeValidated()) {
            return $this->json(['error' => 'Cette demande ne peut pas être validée'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $customMessage = $data['custom_message'] ?? null;

        // ✅ CHANGEMENT : On ne sauvegarde PAS le finalPrice ici
        // Le prix final sera confirmé lors de la collecte
        $buybackRequest->setStatus('validated');

        // Générer le token de calendrier si nécessaire (gros électro)
        $appointmentUrl = null;
        if ($buybackRequest->needsHomePickup()) {
            $token = $buybackRequest->generateAppointmentToken();
            $appointmentUrl = $this->urlGenerator->generate(
                'buyback_appointment_calendar',
                ['token' => $token],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        // Ajouter note admin
        $currentNotes = $buybackRequest->getAdminNotes() ?? '';
        $timestamp = (new \DateTime())->format('d/m/Y H:i');
        $noteText = "Demande validée au prix estimé: {$buybackRequest->getEstimatedPrice()}€";
        if ($customMessage) {
            $noteText .= " - Message: {$customMessage}";
        }
        $buybackRequest->setAdminNotes($currentNotes . "\n[{$timestamp}] {$noteText}");

        $this->em->flush();

        // Envoyer l'email de validation
        try {
            $this->emailService->sendBuybackValidationEmail($buybackRequest, $appointmentUrl, $customMessage);
        } catch (\Exception $e) {
            error_log('Erreur envoi email validation: ' . $e->getMessage());
        }

        return $this->json([
            'success' => true,
            'status' => 'validated',
            'message' => 'Demande validée avec succès'
        ]);
    }

    /**
     * ✅ NOUVELLE ROUTE : Refuser une demande
     */
    #[Route('/admin/buyback-requests/{id}/refuse', name: 'admin_buyback_request_refuse', methods: ['POST'])]
    public function refuseRequest(int $id, Request $request): JsonResponse
    {
        $buybackRequest = $this->em
            ->getRepository(BuybackRequest::class)
            ->find($id);

        if (!$buybackRequest) {
            return $this->json(['error' => 'Demande introuvable'], 404);
        }

        if (!$buybackRequest->canBeRefused()) {
            return $this->json(['error' => 'Cette demande ne peut pas être refusée'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $refusalReason = $data['refusal_reason'] ?? null;

        if (!$refusalReason) {
            return $this->json(['error' => 'Motif de refus requis'], 400);
        }

        // Mettre à jour la demande
        $buybackRequest->setStatus('refused');
        $buybackRequest->setRefusalReason($refusalReason);

        // Ajouter note admin
        $currentNotes = $buybackRequest->getAdminNotes() ?? '';
        $timestamp = (new \DateTime())->format('d/m/Y H:i');
        $buybackRequest->setAdminNotes($currentNotes . "\n[{$timestamp}] Demande refusée - Motif: {$refusalReason}");

        $this->em->flush();

        // Envoyer l'email de refus
        try {
            $this->emailService->sendBuybackRefusalEmail($buybackRequest);
        } catch (\Exception $e) {
            error_log('Erreur envoi email refus: ' . $e->getMessage());
        }

        return $this->json([
            'success' => true,
            'status' => 'refused',
            'message' => 'Demande refusée'
        ]);
    }

    /**
     * Marquer comme payé
     */
    #[Route('/admin/buyback-requests/{id}/mark-paid', name: 'admin_buyback_request_mark_paid', methods: ['POST'])]
    public function markAsPaid(int $id, Request $request): JsonResponse
    {
        $buybackRequest = $this->em
            ->getRepository(BuybackRequest::class)
            ->find($id);

        if (!$buybackRequest) {
            return $this->json(['error' => 'Demande introuvable'], 404);
        }

        if (!$buybackRequest->canMarkAsPaid()) {
            return $this->json(['error' => 'Cette demande ne peut pas être marquée comme payée'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $amount = $data['amount'] ?? null;
        $paymentMethod = $data['payment_method'] ?? null;
        $note = $data['note'] ?? '';

        if (!$amount || $amount <= 0) {
            return $this->json(['error' => 'Montant requis'], 400);
        }

        // Mettre à jour la demande
        $buybackRequest->setStatus('paid');

        // Ajouter note admin détaillée
        $currentNotes = $buybackRequest->getAdminNotes() ?? '';
        $timestamp = (new \DateTime())->format('d/m/Y H:i');
        $paymentMethodLabel = $paymentMethod === 'virement' ? 'Virement bancaire' : 'Espèces';
        $noteText = "Client payé - Montant: {$amount}€ - Mode: {$paymentMethodLabel}";
        if ($note) {
            $noteText .= " - Note: {$note}";
        }
        $buybackRequest->setAdminNotes($currentNotes . "\n[{$timestamp}] {$noteText}");

        $this->em->flush();

        if ($buybackRequest->getPaymentMethod() === 'virement') {
            try {
                $this->emailService->sendBuybackPaymentProcessing($buybackRequest);
            } catch (\Exception $e) {
                error_log('Erreur envoi email paiement: ' . $e->getMessage());
            }
        }

        // TODO: Email de confirmation paiement (optionnel)

        return $this->json([
            'success' => true,
            'status' => 'paid',
            'message' => 'Paiement enregistré avec succès'
        ]);
    }

    /**
     * Marquer comme collecté avec prix confirmé
     */
    #[Route('/admin/buyback-requests/{id}/mark-collected', name: 'admin_buyback_request_mark_collected', methods: ['POST'])]
    public function markAsCollected(int $id, Request $request): JsonResponse
    {
        $buybackRequest = $this->em
            ->getRepository(BuybackRequest::class)
            ->find($id);

        if (!$buybackRequest) {
            return $this->json(['error' => 'Demande introuvable'], 404);
        }

        if (!$buybackRequest->canMarkAsCollected()) {
            return $this->json(['error' => 'Cette demande ne peut pas être marquée comme collectée'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $confirmedPrice = $data['confirmed_price'] ?? null;
        $note = $data['note'] ?? '';

        if (!$confirmedPrice || $confirmedPrice <= 0) {
            return $this->json(['error' => 'Prix confirmé requis'], 400);
        }

        // Mettre à jour la demande
        $buybackRequest->setStatus('collected');
        $buybackRequest->setFinalPrice($confirmedPrice); // ✅ C'est ICI qu'on sauvegarde le prix final

        // Ajouter note admin détaillée
        $currentNotes = $buybackRequest->getAdminNotes() ?? '';
        $timestamp = (new \DateTime())->format('d/m/Y H:i');
        $noteText = "Appareil collecté - Prix confirmé: {$confirmedPrice}€";

        // Indiquer si le prix a été ajusté
        if ($confirmedPrice != $buybackRequest->getEstimatedPrice()) {
            $difference = $confirmedPrice - $buybackRequest->getEstimatedPrice();
            $noteText .= " (ajustement: " . ($difference > 0 ? '+' : '') . "{$difference}€)";
        }

        if ($note) {
            $noteText .= " - Note: {$note}";
        }
        $buybackRequest->setAdminNotes($currentNotes . "\n[{$timestamp}] {$noteText}");

        $this->em->flush();

        return $this->json([
            'success' => true,
            'status' => 'collected',
            'message' => 'Appareil marqué comme collecté'
        ]);
    }

    /**
     * Retourne les transitions de statut autorisées
     */
    private function getAllowedStatusTransitions(string $currentStatus): array
    {
        return match($currentStatus) {
            'pending' => ['validated', 'refused', 'cancelled'],
            'validated' => ['awaiting_collection', 'cancelled'],
            'appointment_scheduled' => ['collected', 'cancelled'],
            'awaiting_collection' => ['collected', 'cancelled'],
            'collected' => ['paid', 'cancelled'],
            default => []
        };
    }

    public function configureFields(string $pageName): iterable
    {
        return [];
    }
}
