<?php

namespace App\Controller\Admin;

use App\Entity\ApplianceModel;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApplianceModelCrudController extends AbstractCrudController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public static function getEntityFqcn(): string
    {
        return ApplianceModel::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Modèle d\'électroménager')
            ->setEntityLabelInPlural('Modèles d\'électroménager')
            ->setPageTitle('index', '📦 Catalogue des modèles')
            ->setPageTitle('new', '➕ Ajouter un modèle')
            ->setPageTitle('edit', '✏️ Modifier un modèle')
            ->setPageTitle('detail', '🔍 Détails du modèle')
            ->setSearchFields(['modelReference', 'modelName', 'brand', 'category'])
            ->setDefaultSort(['releaseYear' => 'DESC', 'brand' => 'ASC'])
            ->setPaginatorPageSize(30)
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $newModel = Action::new('newModel', 'Ajouter un modèle')
            ->linkToCrudAction('newModel')
            ->addCssClass('btn btn-primary')
            ->setIcon('fa fa-plus');

        $editModel = Action::new('editModel', 'Modifier')
            ->linkToCrudAction('editModel')
            ->setIcon('fa fa-edit');

        return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->add(Crud::PAGE_INDEX, $newModel)
            ->add(Crud::PAGE_INDEX, $editModel)
            ->setPermission(Action::INDEX, 'ROLE_IMPOSSIBLE');
    }

    public function index(AdminContext $context)
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect($adminUrlGenerator
            ->setController(self::class)
            ->setAction('modelIndex')
            ->generateUrl()
        );
    }

    public function modelIndex(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, Request $request)
    {
        $repository = $this->em->getRepository(ApplianceModel::class);

        $queryBuilder = $repository->createQueryBuilder('m')
            ->where('m.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('m.releaseYear', 'DESC')
            ->addOrderBy('m.brand', 'ASC');

        // Gestion des filtres
        $filters = [
            'search' => $request->query->get('search'),
            'category' => $request->query->get('category'),
            'brand' => $request->query->get('brand'),
            'tier' => $request->query->get('tier'),
        ];

        if ($filters['search']) {
            $queryBuilder->andWhere('m.modelReference LIKE :search OR m.modelName LIKE :search OR m.brand LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if ($filters['category']) {
            $queryBuilder->andWhere('m.category = :category')
                ->setParameter('category', $filters['category']);
        }

        if ($filters['brand']) {
            $queryBuilder->andWhere('m.brand = :brand')
                ->setParameter('brand', $filters['brand']);
        }

        if ($filters['tier']) {
            $queryBuilder->andWhere('m.tier = :tier')
                ->setParameter('tier', $filters['tier']);
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

        $models = iterator_to_array($paginator);

        // ✅ CALCUL DES STATS
        $allModels = $this->em->getRepository(ApplianceModel::class)
            ->findBy(['isActive' => true]);

        $uniqueCategories = [];
        $uniqueBrands = [];

        foreach ($allModels as $model) {
            $uniqueCategories[$model->getCategory()] = true;
            $uniqueBrands[$model->getBrand()] = true;
        }

        $totalCategories = count($uniqueCategories);
        $totalBrands = count($uniqueBrands);

        return $this->render('admin/appliance_model/appliance_model_index.html.twig', [
            'models' => $models,
            'total_items' => $totalItems,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'per_page' => $perPage,
            'filters' => $filters,
            'total_categories' => $totalCategories,
            'total_brands' => $totalBrands,
        ]);
    }

    public function newModel(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, Request $request)
    {
        $model = new ApplianceModel();
        $form = $this->createForm(\App\Form\ApplianceModelType::class, $model);

        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction('newModel')
            ->generateUrl();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($model);
            $this->em->flush();

            $this->addFlash('success', 'Modèle créé avec succès.');

            if ($request->request->has('save_and_add')) {
                return $this->redirect($adminUrlGenerator
                    ->setController(self::class)
                    ->setAction('newModel')
                    ->generateUrl()
                );
            }

            return $this->redirect($adminUrlGenerator
                ->setController(self::class)
                ->setAction('index')
                ->generateUrl()
            );
        }

        return $this->render('admin/appliance_model/appliance_model_form.html.twig', [
            'form' => $form->createView(),
            'model' => $model,
            'current_url' => $url,
            'is_edit' => false,
        ]);
    }

    public function editModel(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, Request $request)
    {
        $entityId = $request->query->get('entityId');

        if (!$entityId) {
            $entity = $context->getEntity();
            if ($entity) {
                $model = $entity->getInstance();
            } else {
                throw $this->createNotFoundException('Modèle non trouvé');
            }
        } else {
            $model = $this->em->getRepository(ApplianceModel::class)->find($entityId);

            if (!$model) {
                throw $this->createNotFoundException('Modèle non trouvé');
            }
        }

        $form = $this->createForm(\App\Form\ApplianceModelType::class, $model);

        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction('editModel')
            ->setEntityId($model->getId())
            ->generateUrl();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Modèle modifié avec succès.');

            if ($request->request->has('save_and_add')) {
                return $this->redirect($adminUrlGenerator
                    ->setController(self::class)
                    ->setAction('newModel')
                    ->generateUrl()
                );
            }

            return $this->redirect($adminUrlGenerator
                ->setController(self::class)
                ->setAction('index')
                ->generateUrl()
            );
        }

        return $this->render('admin/appliance_model/appliance_model_form.html.twig', [
            'form' => $form->createView(),
            'model' => $model,
            'current_url' => $url,
            'is_edit' => true,
        ]);
    }

    #[Route('/admin/appliance-models/{id}/delete', name: 'admin_appliance_model_delete', methods: ['POST'])]
    public function deleteModel(int $id, Request $request): Response
    {
        $model = $this->em->getRepository(ApplianceModel::class)->find($id);

        if (!$model) {
            throw $this->createNotFoundException('Modèle non trouvé');
        }

        if ($this->isCsrfTokenValid('delete' . $model->getId(), $request->request->get('_token'))) {
            $this->em->remove($model);
            $this->em->flush();

            $this->addFlash('success', 'Modèle supprimé avec succès.');
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
        // Configuration minimale car on utilise des templates custom
        return [];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('category'))
            ->add(ChoiceFilter::new('brand'))
            ->add(ChoiceFilter::new('tier'))
            ->add(BooleanFilter::new('isActive'));
    }
}
