<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Enum\EnergyLabel;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Action, Actions, Crud, Filters};
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Filter\{BooleanFilter, ChoiceFilter, EntityFilter, NumericFilter, TextFilter};
use EasyCorp\Bundle\EasyAdminBundle\Field\{
    AssociationField, BooleanField, DateTimeField, Field, IntegerField, MoneyField, TextEditorField, TextField, ChoiceField
};
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ProductCrudController extends AbstractCrudController
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Produit')
            ->setEntityLabelInPlural('Produits')
            ->setPageTitle(Crud::PAGE_INDEX, 'Catalogue — Produits')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail du produit')
            ->setSearchFields(['title', 'shortDescription'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $newProduct = Action::new('newProduct', 'Nouveau produit')
            ->linkToCrudAction('newProduct')
            ->addCssClass('btn btn-primary')
            ->setIcon('fa fa-plus');

        $editProduct = Action::new('editProduct', 'Modifier')
            ->linkToCrudAction('editProduct')
            ->setIcon('fa fa-edit');

        return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->add(Crud::PAGE_INDEX, $newProduct)
            ->add(Crud::PAGE_INDEX, $editProduct)
            // Redirige l'index vers notre action personnalisée
            ->setPermission(Action::INDEX, 'ROLE_IMPOSSIBLE'); // Cache l'index par défaut
    }

    public function index(AdminContext $context)
    {
        // Redirige vers notre index personnalisé
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect($adminUrlGenerator
            ->setController(self::class)
            ->setAction('productIndex')
            ->generateUrl()
        );
    }

    public function productIndex(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, Request $request)
    {
        // Récupère les produits avec pagination
        $repository = $this->em->getRepository(Product::class);

        $queryBuilder = $repository->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->leftJoin('p.brand', 'b')
            ->addSelect('c', 'b')
            ->orderBy('p.createdAt', 'DESC');

        // Gestion des filtres
        $filters = [
            'search' => $request->query->get('search'),
            'category' => $request->query->get('category'),
            'brand' => $request->query->get('brand'),
            'published' => $request->query->get('published'),
        ];

        // Applique les filtres
        if ($filters['search']) {
            $queryBuilder->andWhere('p.title LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if ($filters['category']) {
            $queryBuilder->andWhere('c.id = :category')
                ->setParameter('category', $filters['category']);
        }

        if ($filters['brand']) {
            $queryBuilder->andWhere('b.id = :brand')
                ->setParameter('brand', $filters['brand']);
        }

        if ($filters['published'] !== null && $filters['published'] !== '') {
            $queryBuilder->andWhere('p.isPublished = :published')
                ->setParameter('published', (bool) $filters['published']);
        }

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

        $products = iterator_to_array($paginator);

        // Récupère les catégories et marques pour les filtres
        $categories = $this->em->getRepository(\App\Entity\Category::class)->findAll();
        $brands = $this->em->getRepository(\App\Entity\Brand::class)->findAll();

        return $this->render('admin/product/product_index.html.twig', [
            'products' => $products,
            'total_items' => $totalItems,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'per_page' => $perPage,
            'filters' => $filters,
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }

    #[Route('/admin/products/export', name: 'admin_products_export')]
    public function exportProducts(): Response
    {
        $products = $this->em->getRepository(Product::class)->findAll();

        $csv = "ID;Titre;Prix;Stock;Catégorie;Marque;Publié\n";

        foreach ($products as $product) {
            $csv .= sprintf(
                "%d;%s;%.2f;%d;%s;%s;%s\n",
                $product->getId(),
                str_replace(';', ',', $product->getTitle()),
                $product->getPrice() / 100,
                $product->getStock(),
                $product->getCategory() ? str_replace(';', ',', $product->getCategory()->getName()) : '-',
                $product->getBrand() ? str_replace(';', ',', $product->getBrand()->getName()) : '-',
                $product->isPublished() ? 'Oui' : 'Non'
            );
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="produits_' . date('Y-m-d') . '.csv"');

        return $response;
    }

    public function newProduct(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, Request $request)
    {
        $product = new Product();
        $form = $this->createForm(\App\Form\ProductType::class, $product);

        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction('newProduct')
            ->generateUrl();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($product);
            $this->em->flush();

            $this->addFlash('success', 'Produit créé avec succès.');

            // Vérifie quel bouton a été cliqué
            if ($request->request->has('save_and_add')) {
                // Redirige vers le formulaire de création
                return $this->redirect($adminUrlGenerator
                    ->setController(self::class)
                    ->setAction('newProduct')
                    ->generateUrl()
                );
            }

            // Par défaut : save_and_return - retourne à l'index
            return $this->redirect($adminUrlGenerator
                ->setController(self::class)
                ->setAction('index')
                ->generateUrl()
            );
        }

        return $this->render('admin/product/product_form.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
            'current_url' => $url,
            'is_edit' => false,
        ]);
    }

    public function editProduct(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, Request $request)
    {
        // Récupère l'ID depuis l'URL ou le contexte
        $entityId = $request->query->get('entityId');

        if (!$entityId) {
            // Si pas d'entityId dans l'URL, essaie de le récupérer depuis le contexte
            $entity = $context->getEntity();
            if ($entity) {
                $product = $entity->getInstance();
            } else {
                throw $this->createNotFoundException('Produit non trouvé');
            }
        } else {
            // Récupère le produit depuis la base de données
            $product = $this->em->getRepository(Product::class)->find($entityId);

            if (!$product) {
                throw $this->createNotFoundException('Produit non trouvé');
            }
        }

        $form = $this->createForm(\App\Form\ProductType::class, $product);

        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction('editProduct')
            ->setEntityId($product->getId())
            ->generateUrl();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Produit modifié avec succès.');

            if ($request->request->has('save_and_add')) {
                return $this->redirect($adminUrlGenerator
                    ->setController(self::class)
                    ->setAction('newProduct')
                    ->generateUrl()
                );
            }

            return $this->redirect($adminUrlGenerator
                ->setController(self::class)
                ->setAction('index')
                ->generateUrl()
            );
        }

        return $this->render('admin/product/product_form.html.twig', [
            'form' => $form->createView(),
            'product' => $product,
            'current_url' => $url,
            'is_edit' => true,
        ]);
    }

    #[Route('/admin/products/{id}/delete', name: 'admin_product_delete', methods: ['POST'])]
    public function deleteProduct(int $id, Request $request): Response
    {
        $product = $this->em->getRepository(Product::class)->find($id);

        if (!$product) {
            throw $this->createNotFoundException('Produit non trouvé');
        }

        // Vérifie le token CSRF pour la sécurité
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            $this->em->remove($product);
            $this->em->flush();

            $this->addFlash('success', 'Produit supprimé avec succès.');
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
        $preview = Field::new('preview', 'Aperçu')
            ->setTemplatePath('admin/product/_preview.html.twig')
            ->onlyOnIndex();

        $title = TextField::new('title', 'Titre');
        $price = MoneyField::new('price', 'Prix')->setCurrency('EUR')->setStoredAsCents();

        $energyIndex = TextField::new('energyLabelValue', 'Étiquette énergie')
            ->setTemplatePath('admin/field/energy_label_badge.html.twig')
            ->setSortable(false)
            ->onlyOnIndex();

        $stock = IntegerField::new('stock', 'Stock');
        $pub = BooleanField::new('isPublished', 'Publié');
        $cat = AssociationField::new('category', 'Catégorie');
        $brand = AssociationField::new('brand', 'Marque');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$preview, $title, $price, $energyIndex, $stock, $pub, $cat, $brand];
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            return [$title, $price, $energyIndex, $stock, $pub, $cat, $brand];
        }

        return [$title, $price, $stock, $pub, $cat, $brand];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('title', 'Titre'))
            ->add(EntityFilter::new('category', 'Catégorie'))
            ->add(EntityFilter::new('brand', 'Marque'))
            ->add(BooleanFilter::new('isPublished', 'Publié'))
            ->add(NumericFilter::new('price', 'Prix'))
            ->add(NumericFilter::new('stock', 'Stock'));
    }
}
