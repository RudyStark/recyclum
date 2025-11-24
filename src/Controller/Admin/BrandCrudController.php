<?php

namespace App\Controller\Admin;

use App\Entity\Brand;
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

class BrandCrudController extends AbstractCrudController
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public static function getEntityFqcn(): string
    {
        return Brand::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Marque')
            ->setEntityLabelInPlural('Marques')
            ->setPageTitle(Crud::PAGE_INDEX, 'Catalogue — Marques')
            ->setSearchFields(['name'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $newBrand = Action::new('newBrand', 'Nouvelle marque')
            ->linkToCrudAction('newBrand')
            ->addCssClass('btn btn-primary')
            ->setIcon('fa fa-plus');

        $editBrand = Action::new('editBrand', 'Modifier')
            ->linkToCrudAction('editBrand')
            ->setIcon('fa fa-edit');

        return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->add(Crud::PAGE_INDEX, $newBrand)
            ->add(Crud::PAGE_INDEX, $editBrand);
    }

    public function index(AdminContext $context)
    {
        // Redirige vers notre index personnalisé
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect($adminUrlGenerator
            ->setController(self::class)
            ->setAction('brandIndex')
            ->generateUrl()
        );
    }

    public function brandIndex(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, Request $request)
    {
        // Récupère les marques avec pagination
        $repository = $this->em->getRepository(Brand::class);

        $queryBuilder = $repository->createQueryBuilder('b')
            ->leftJoin('b.products', 'p')
            ->addSelect('p')
            ->orderBy('b.createdAt', 'DESC');

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

        $brands = iterator_to_array($paginator);

        return $this->render('admin/brand/brand_index.html.twig', [
            'brands' => $brands,
            'total_items' => $totalItems,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'per_page' => $perPage,
        ]);
    }

    #[Route('/admin/brands/export', name: 'admin_brands_export')]
    public function exportBrands(): Response
    {
        $brands = $this->em->getRepository(Brand::class)->findAll();

        $csv = "ID;Nom;Slug;Nombre de produits;Date de création\n";

        foreach ($brands as $brand) {
            $csv .= sprintf(
                "%d;%s;%s;%d;%s\n",
                $brand->getId(),
                str_replace(';', ',', $brand->getName()),
                $brand->getSlug(),
                $brand->getProducts()->count(),
                $brand->getCreatedAt()->format('d/m/Y H:i')
            );
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="marques_' . date('Y-m-d') . '.csv"');

        return $response;
    }

    public function newBrand(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, Request $request)
    {
        $brand = new Brand();
        $form = $this->createForm(\App\Form\BrandType::class, $brand);

        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction('newBrand')
            ->generateUrl();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Génération du slug
            $slugger = new AsciiSlugger();
            $slug = $slugger->slug($brand->getName())->lower();
            $brand->setSlug($slug);

            $this->em->persist($brand);
            $this->em->flush();

            $this->addFlash('success', 'Marque créée avec succès.');

            if ($request->request->has('save_and_add')) {
                return $this->redirect($adminUrlGenerator
                    ->setController(self::class)
                    ->setAction('newBrand')
                    ->generateUrl()
                );
            }

            return $this->redirect($adminUrlGenerator
                ->setController(self::class)
                ->setAction('index')
                ->generateUrl()
            );
        }

        return $this->render('admin/brand/brand_form.html.twig', [
            'form' => $form->createView(),
            'brand' => $brand,
            'current_url' => $url,
            'is_edit' => false,
        ]);
    }

    public function editBrand(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, Request $request)
    {
        $entityId = $request->query->get('entityId');

        if (!$entityId) {
            $entity = $context->getEntity();
            if ($entity) {
                $brand = $entity->getInstance();
            } else {
                throw $this->createNotFoundException('Marque non trouvée');
            }
        } else {
            $brand = $this->em->getRepository(Brand::class)->find($entityId);

            if (!$brand) {
                throw $this->createNotFoundException('Marque non trouvée');
            }
        }

        $form = $this->createForm(\App\Form\BrandType::class, $brand);

        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction('editBrand')
            ->setEntityId($brand->getId())
            ->generateUrl();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slugger = new AsciiSlugger();
            $slug = $slugger->slug($brand->getName())->lower();
            $brand->setSlug($slug);

            $this->em->flush();

            $this->addFlash('success', 'Marque modifiée avec succès.');

            if ($request->request->has('save_and_add')) {
                return $this->redirect($adminUrlGenerator
                    ->setController(self::class)
                    ->setAction('newBrand')
                    ->generateUrl()
                );
            }

            return $this->redirect($adminUrlGenerator
                ->setController(self::class)
                ->setAction('index')
                ->generateUrl()
            );
        }

        return $this->render('admin/brand/brand_form.html.twig', [
            'form' => $form->createView(),
            'brand' => $brand,
            'current_url' => $url,
            'is_edit' => true,
        ]);
    }

    #[Route('/admin/brands/{id}/delete', name: 'admin_brand_delete', methods: ['POST'])]
    public function deleteBrand(int $id, Request $request): Response
    {
        $brand = $this->em->getRepository(Brand::class)->find($id);

        if (!$brand) {
            throw $this->createNotFoundException('Marque non trouvée');
        }

        // Vérifie s'il y a des produits associés
        if ($brand->getProducts()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer cette marque car elle contient des produits.');

            $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
            return $this->redirect($adminUrlGenerator
                ->setController(self::class)
                ->setAction('index')
                ->generateUrl()
            );
        }

        if ($this->isCsrfTokenValid('delete' . $brand->getId(), $request->request->get('_token'))) {
            $this->em->remove($brand);
            $this->em->flush();

            $this->addFlash('success', 'Marque supprimée avec succès.');
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
