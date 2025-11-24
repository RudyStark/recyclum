<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Action, Actions, Crud};
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\{DateTimeField, TextField};
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CategoryCrudController extends AbstractCrudController
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Catégorie')
            ->setEntityLabelInPlural('Catégories')
            ->setPageTitle(Crud::PAGE_INDEX, 'Catalogue — Catégories')
            ->setSearchFields(['name'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $newCategory = Action::new('newCategory', 'Nouvelle catégorie')
            ->linkToCrudAction('newCategory')
            ->addCssClass('btn btn-primary')
            ->setIcon('fa fa-plus');

        $editCategory = Action::new('editCategory', 'Modifier')
            ->linkToCrudAction('editCategory')
            ->setIcon('fa fa-edit');

        return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->add(Crud::PAGE_INDEX, $newCategory)
            ->add(Crud::PAGE_INDEX, $editCategory);
    }

    public function index(AdminContext $context)
    {
        // Redirige vers notre index personnalisé
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect($adminUrlGenerator
            ->setController(self::class)
            ->setAction('categoryIndex')
            ->generateUrl()
        );
    }

    public function categoryIndex(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, Request $request)
    {
        // Récupère les catégories avec pagination
        $repository = $this->em->getRepository(Category::class);

        $queryBuilder = $repository->createQueryBuilder('c')
            ->leftJoin('c.products', 'p')
            ->addSelect('p')
            ->orderBy('c.createdAt', 'DESC');

        $query = $queryBuilder->getQuery();

        // Pagination
        $page = $request->query->getInt('page', 1);
        $perPage = 20;

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query);
        $totalItems = count($paginator);
        $totalPages = ceil($totalItems / $perPage);

        $paginator->getQuery()
            ->setFirstResult($perPage * ($page - 1))
            ->setMaxResults($perPage);

        $categories = iterator_to_array($paginator);

        return $this->render('admin/category/category_index.html.twig', [
            'categories' => $categories,
            'total_items' => $totalItems,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'per_page' => $perPage,
        ]);
    }

    #[Route('/admin/categories/export', name: 'admin_categories_export')]
    public function exportCategories(): Response
    {
        $categories = $this->em->getRepository(Category::class)->findAll();

        $csv = "ID;Nom;Slug;Nombre de produits;Date de création\n";

        foreach ($categories as $category) {
            $csv .= sprintf(
                "%d;%s;%s;%d;%s\n",
                $category->getId(),
                str_replace(';', ',', $category->getName()),
                $category->getSlug(),
                $category->getProducts()->count(),
                $category->getCreatedAt()->format('d/m/Y H:i')
            );
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="categories_' . date('Y-m-d') . '.csv"');

        return $response;
    }

    public function newCategory(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, Request $request)
    {
        $category = new Category();
        $form = $this->createForm(\App\Form\CategoryType::class, $category);

        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction('newCategory')
            ->generateUrl();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Génération du slug
            $slugger = new AsciiSlugger();
            $slug = $slugger->slug($category->getName())->lower();
            $category->setSlug($slug);

            $this->em->persist($category);
            $this->em->flush();

            $this->addFlash('success', 'Catégorie créée avec succès.');

            if ($request->request->has('save_and_add')) {
                return $this->redirect($adminUrlGenerator
                    ->setController(self::class)
                    ->setAction('newCategory')
                    ->generateUrl()
                );
            }

            return $this->redirect($adminUrlGenerator
                ->setController(self::class)
                ->setAction('index')
                ->generateUrl()
            );
        }

        return $this->render('admin/category/category_form.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
            'current_url' => $url,
            'is_edit' => false,
        ]);
    }

    public function editCategory(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, Request $request)
    {
        $entityId = $request->query->get('entityId');

        if (!$entityId) {
            $entity = $context->getEntity();
            if ($entity) {
                $category = $entity->getInstance();
            } else {
                throw $this->createNotFoundException('Catégorie non trouvée');
            }
        } else {
            $category = $this->em->getRepository(Category::class)->find($entityId);

            if (!$category) {
                throw $this->createNotFoundException('Catégorie non trouvée');
            }
        }

        $form = $this->createForm(\App\Form\CategoryType::class, $category);

        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction('editCategory')
            ->setEntityId($category->getId())
            ->generateUrl();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slugger = new AsciiSlugger();
            $slug = $slugger->slug($category->getName())->lower();
            $category->setSlug($slug);

            $this->em->flush();

            $this->addFlash('success', 'Catégorie modifiée avec succès.');

            if ($request->request->has('save_and_add')) {
                return $this->redirect($adminUrlGenerator
                    ->setController(self::class)
                    ->setAction('newCategory')
                    ->generateUrl()
                );
            }

            return $this->redirect($adminUrlGenerator
                ->setController(self::class)
                ->setAction('index')
                ->generateUrl()
            );
        }

        return $this->render('admin/category/category_form.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
            'current_url' => $url,
            'is_edit' => true,
        ]);
    }

    #[Route('/admin/categories/{id}/delete', name: 'admin_category_delete', methods: ['POST'])]
    public function deleteCategory(int $id, Request $request): Response
    {
        $category = $this->em->getRepository(Category::class)->find($id);

        if (!$category) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }

        // Vérifie s'il y a des produits associés
        if ($category->getProducts()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer cette catégorie car elle contient des produits.');

            $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
            return $this->redirect($adminUrlGenerator
                ->setController(self::class)
                ->setAction('index')
                ->generateUrl()
            );
        }

        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->request->get('_token'))) {
            $this->em->remove($category);
            $this->em->flush();

            $this->addFlash('success', 'Catégorie supprimée avec succès.');
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
        yield TextField::new('name', 'Nom');
        yield DateTimeField::new('createdAt')->hideOnForm();
        yield DateTimeField::new('updatedAt')->hideOnForm();
    }
}
