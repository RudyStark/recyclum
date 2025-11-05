<?php

namespace App\Controller\Admin;

use App\Entity\ProductImage;
use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Vich\UploaderBundle\Form\Type\VichImageType;

final class ProductImageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductImage::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Image produit')
            ->setEntityLabelInPlural('Images produit')
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter une image')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier une image')
            ->setDefaultSort(['id' => 'DESC'])
            ->showEntityActionsInlined()
            // Thème du champ Vich + aperçu live
            ->addFormTheme('admin/form/image_upload_theme.html.twig');
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
                'help' => 'PNG/JPG jusqu’à 4 Mo. Aperçu live dès la sélection.',
            ]);

        $filenameIndex  = ImageField::new('filename', 'Aperçu')->setBasePath('/uploads/products')->onlyOnIndex();
        $filenameDetail = ImageField::new('filename', 'Aperçu')->setBasePath('/uploads/products')->onlyOnDetail();

        $alt      = TextField::new('alt', 'Texte alternatif')->hideOnIndex();
        $position = IntegerField::new('position', 'Ordre');
        $isMain   = BooleanField::new('isMain', 'Image principale');

        if (Crud::PAGE_INDEX === $pageName) {
            return [$filenameIndex, $product, $position, $isMain];
        }
        if (Crud::PAGE_DETAIL === $pageName) {
            return [$filenameDetail, $product, $alt, $position, $isMain];
        }
        return [$product, $imageFile, $alt, $position, $isMain];
    }
}
