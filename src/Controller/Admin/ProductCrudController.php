<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\ProductImage;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductCrudController extends AbstractCrudController
{
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
            ->setPageTitle(Crud::PAGE_NEW, 'Créer un produit')
            ->setPageTitle(Crud::PAGE_EDIT, fn(Product $p) => sprintf('Modifier “%s”', $p->getTitle() ?? 'Produit'))
            ->setSearchFields(['id', 'title', 'slug', 'shortDescription'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->showEntityActionsInlined()
            ->setPaginatorPageSize(25);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('category')
            ->add('brand')
            ->add('isPublished')
            ->add('energyLabel')
            ->add('createdAt');
    }

    public function configureFields(string $pageName): iterable
    {
        /* --------- SECTION: Infos générales --------- */
        yield FormField::addPanel('Infos générales')
            ->setIcon('fa fa-box')
            ->collapsible();

        yield TextField::new('title', 'Titre')
            ->setHelp('Nom commercial court, clair et unique.')
            ->setColumns(8);

        yield SlugField::new('slug', 'Slug')
            ->setTargetFieldName('title')
            ->setHelp('Généré automatiquement depuis le titre. Ne pas modifier.')
            ->setUnlockConfirmationMessage('Modifier le slug peut casser des liens publics.')
            ->setColumns(4);

        yield TextareaField::new('shortDescription', 'Description courte')
            ->setHelp('Résumé marketing/technique concis (affiché sur les cartes).')
            ->setNumOfRows(4)
            ->hideOnIndex();

        /* --------- SECTION: Catalogue --------- */
        yield FormField::addPanel('Catalogue')
            ->setIcon('fa fa-tags')
            ->collapsible();

        yield AssociationField::new('category', 'Catégorie')
            ->setRequired(true)
            ->setColumns(6);

        yield AssociationField::new('brand', 'Marque')
            ->setRequired(false)
            ->setColumns(6);

        yield MoneyField::new('price', 'Prix')
            ->setCurrency('EUR')
            // ton entité stocke un int "euros". Si tu passes en centimes plus tard, mets ->setStoredAsCents(true)
            ->setStoredAsCents(false)
            ->setHelp('Prix TTC affiché en boutique.')
            ->setColumns(4);

        yield ChoiceField::new('energyLabel', 'Étiquette énergie')
            ->setChoices([
                'A+++' => 'A+++',
                'A++'  => 'A++',
                'A+'   => 'A+',
                'A'    => 'A',
                'B'    => 'B',
                'C'    => 'C',
                'D'    => 'D',
                'E'    => 'E',
                'F'    => 'F',
                'G'    => 'G',
            ])
            ->allowMultipleChoices(false)
            ->renderAsBadges()
            ->setColumns(4);

        yield IntegerField::new('warrantyMonths', 'Garantie (mois)')
            ->setHelp('Durée de garantie contractuelle du produit.')
            ->setColumns(4);

        yield IntegerField::new('stock', 'Stock')
            ->setColumns(4)
            ->setHelp('Quantité disponible.');

        /* --------- SECTION: Médias --------- */
        yield FormField::addPanel('Médias')
            ->setIcon('fa fa-image')
            ->collapsible();

        // OneToMany Product->images (ProductImage) en formulaire imbriqué
        yield CollectionField::new('images', 'Images du produit')
            ->useEntryCrudForm()               // ouvre le form du ProductImage
            ->setFormTypeOption('by_reference', false)
            ->setHelp('Ajoute plusieurs visuels. L’ordre dépendra de l’index.')
            ->onlyOnForms();

        /* --------- SECTION: Publication --------- */
        yield FormField::addPanel('Publication & Métadonnées')
            ->setIcon('fa fa-rocket')
            ->collapsible();

        yield BooleanField::new('isPublished', 'Publié')
            ->renderAsSwitch(true)
            ->setHelp('Si activé, le produit est visible sur le site.');

        yield DateTimeField::new('createdAt', 'Créé le')
            ->setFormTypeOption('html5', true)
            ->hideOnForm();

        yield DateTimeField::new('updatedAt', 'Modifié le')
            ->setFormTypeOption('html5', true)
            ->hideOnForm();
    }
}
