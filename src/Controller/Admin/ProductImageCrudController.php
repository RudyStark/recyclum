<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\ProductImage;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Action, Actions, Crud};
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\{AssociationField, BooleanField, Field, ImageField, IntegerField, TextField};
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Vich\UploaderBundle\Form\Type\VichImageType;

final class ProductImageCrudController extends AbstractCrudController
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public static function getEntityFqcn(): string
    {
        return ProductImage::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Image produit')
            ->setEntityLabelInPlural('Images produit')
            ->setPageTitle(Crud::PAGE_INDEX, 'Galerie — Images produit')
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter une image')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier une image')
            ->setDefaultSort(['id' => 'DESC'])
            ->showEntityActionsInlined()
            ->addFormTheme('admin/form/image_upload_theme.html.twig');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->setPermission(Action::INDEX, 'ROLE_IMPOSSIBLE');
    }

    public function index(AdminContext $context)
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        return $this->redirect($adminUrlGenerator
            ->setController(self::class)
            ->setAction('productImagesIndex')
            ->generateUrl()
        );
    }

    public function productImagesIndex(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, Request $request)
    {
        $repository = $this->em->getRepository(Product::class);

        $queryBuilder = $repository->createQueryBuilder('p')
            ->leftJoin('p.images', 'i')
            ->addSelect('i')
            ->orderBy('p.createdAt', 'DESC');

        $query = $queryBuilder->getQuery();

        $page = $request->query->getInt('page', 1);
        $perPage = 12;

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query);
        $totalItems = count($paginator);
        $totalPages = ceil($totalItems / $perPage);

        $paginator->getQuery()
            ->setFirstResult($perPage * ($page - 1))
            ->setMaxResults($perPage);

        $products = iterator_to_array($paginator);

        $totalImages = $this->em->getRepository(ProductImage::class)->count([]);

        $allProducts = $this->em->getRepository(Product::class)->findAll();
        $productsWithImages = 0;
        $productsWithoutImages = 0;

        foreach ($allProducts as $product) {
            if ($product->getImages()->count() > 0) {
                $productsWithImages++;
            } else {
                $productsWithoutImages++;
            }
        }

        return $this->render('admin/product_image/product_images_index.html.twig', [
            'products' => $products,
            'total_items' => $totalItems,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'per_page' => $perPage,
            'total_images' => $totalImages,
            'products_with_images' => $productsWithImages,
            'products_without_images' => $productsWithoutImages,
        ]);
    }

    public function productGallery(AdminContext $context, AdminUrlGenerator $adminUrlGenerator, Request $request)
    {
        $productId = $request->query->getInt('productId');

        $product = $this->em->getRepository(Product::class)->find($productId);

        if (!$product) {
            throw $this->createNotFoundException('Produit non trouvé');
        }

        $images = $this->em->getRepository(ProductImage::class)
            ->createQueryBuilder('pi')
            ->where('pi.product = :product')
            ->setParameter('product', $product)
            ->orderBy('pi.position', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/product_image/product_gallery.html.twig', [
            'product' => $product,
            'images' => $images,
        ]);
    }

    public function configureFields(string $pageName): iterable
    {
        $product = AssociationField::new('product', 'Produit')->setRequired(true);

        $imageFile = Field::new('imageFile', 'Fichier')
            ->setFormType(VichImageType::class)
            ->setFormTypeOptions([
                'allow_delete' => true,
                'download_uri' => false,
                'asset_helper' => true,
                'required' => $pageName === Crud::PAGE_NEW,
                'help' => 'PNG/JPG jusqu\'à 4 Mo. Aperçu live dès la sélection.',
            ]);

        $filenameIndex = ImageField::new('filename', 'Aperçu')->setBasePath('/uploads/products')->onlyOnIndex();
        $filenameDetail = ImageField::new('filename', 'Aperçu')->setBasePath('/uploads/products')->onlyOnDetail();

        $alt = TextField::new('alt', 'Texte alternatif')->hideOnIndex();
        $position = IntegerField::new('position', 'Ordre');
        $isMain = BooleanField::new('isMain', 'Image principale');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$filenameIndex, $product, $position, $isMain];
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            return [$filenameDetail, $product, $alt, $position, $isMain];
        }
        return [$product, $imageFile, $alt, $position, $isMain];
    }
}
