<?php

namespace App\Controller\Admin;

use App\Entity\BuybackRequest;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BuybackRequestCrudController extends AbstractCrudController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
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

        // ✅ CALCUL DES STATS
        $allRequests = $this->em->getRepository(BuybackRequest::class)->findAll();

        $stats = [
            'pending' => count(array_filter($allRequests, fn($r) => $r->getStatus() === 'pending')),
            'validated' => count(array_filter($allRequests, fn($r) => $r->getStatus() === 'validated')),
            'collected' => count(array_filter($allRequests, fn($r) => $r->getStatus() === 'collected')),
            'total_estimated' => array_reduce($allRequests, fn($carry, $r) => $carry + ($r->getEstimatedPrice() ?? 0), 0),
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

        return $this->render('admin/buyback_request/show.html.twig', [
            'request' => $buybackRequest,
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

    public function configureFields(string $pageName): iterable
    {
        return [];
    }
}
