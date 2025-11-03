<?php

namespace App\Controller\Admin;

use App\Entity\ProductImage;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductImageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return ProductImage::class; }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('product');
        yield ImageField::new('filename', 'Image')
            ->setBasePath('/uploads/products')
            ->setUploadDir('public/uploads/products')
            ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
            ->setRequired($pageName === Crud::PAGE_NEW); // obligatoire à la création
        yield TextField::new('alt', 'Texte alternatif')->setRequired(false);
        yield IntegerField::new('position')->setHelp('Ordre d’affichage');
        yield BooleanField::new('isPrimary', 'Image principale');
    }
}
