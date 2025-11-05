<?php

// src/Controller/Admin/ProductCrudController.php
namespace App\Controller\Admin;

use App\Entity\Product;
use App\Enum\EnergyLabel;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Crud, Filters};
use EasyCorp\Bundle\EasyAdminBundle\Filter\{BooleanFilter, ChoiceFilter, EntityFilter, NumericFilter, TextFilter};
use EasyCorp\Bundle\EasyAdminBundle\Field\{
    AssociationField, BooleanField, DateTimeField, Field, IntegerField, MoneyField, TextEditorField, TextField, ChoiceField
};

final class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return Product::class; }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Produit')
            ->setEntityLabelInPlural('Produits')
            ->setPageTitle(Crud::PAGE_INDEX, 'Catalogue — Produits')
            ->setPageTitle(Crud::PAGE_NEW, 'Nouveau produit')
            ->setPageTitle(Crud::PAGE_EDIT, 'Éditer le produit')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail du produit')
            ->setSearchFields(['title', 'shortDescription']) // <-- pas energyLabel (Enum)
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        // Templates custom
        $preview = Field::new('preview', 'Aperçu')
            ->setTemplatePath('admin/product/_preview.html.twig')
            ->onlyOnIndex();

        $gallery = Field::new('gallery', 'Galerie')
            ->setTemplatePath('admin/product/_gallery.html.twig')
            ->onlyOnDetail();

        // Métier
        $title = TextField::new('title', 'Titre')->setColumns(8);
        $slug  = TextField::new('slug')->hideOnForm()->onlyOnDetail();
        $desc  = TextEditorField::new('shortDescription', 'Description')->hideOnIndex()->setNumOfRows(8);

        $price = MoneyField::new('price', 'Prix')->setCurrency('EUR')->setStoredAsCents()->setColumns(4);

        // === Étiquette énergie ===
        // INDEX/DETAIL -> on lit *energyLabelValue* (string), et on affiche un badge via template
        $energyIndex = TextField::new('energyLabelValue', 'Étiquette énergie')
            ->setTemplatePath('admin/field/energy_label_badge.html.twig')
            ->setSortable(false) // champ virtuel (getter), pas sortable en DQL
            ->onlyOnIndex();

        $energyDetail = TextField::new('energyLabelValue', 'Étiquette énergie')
            ->setTemplatePath('admin/field/energy_label_badge.html.twig')
            ->setSortable(false)
            ->onlyOnDetail();

        // FORM -> vrai ChoiceField sur l’Enum mappé (persisté en DB via Doctrine)
        $energyForm = ChoiceField::new('energyLabel', 'Étiquette énergie')
            ->setChoices(EnergyLabel::formChoices()) // 'A' => EnergyLabel::A (objet Enum)
            ->renderAsBadges()
            ->setColumns(4);

        $warr  = IntegerField::new('warrantyMonths', 'Garantie (mois)')->setColumns(4);
        $stock = IntegerField::new('stock', 'Stock')->setColumns(4);
        $pub   = BooleanField::new('isPublished', 'Publié')->setColumns(4);

        $cat   = AssociationField::new('category', 'Catégorie')->setColumns(6);
        $brand = AssociationField::new('brand', 'Marque')->setColumns(6);

        $created = DateTimeField::new('createdAt', 'Créé le')->onlyOnDetail();
        $updated = DateTimeField::new('updatedAt', 'Modifié le')->onlyOnDetail();

        if (Crud::PAGE_INDEX === $pageName) {
            return [$preview, $title, $price, $energyIndex, $stock, $pub, $cat, $brand];
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            return [$title, $slug, $price, $energyDetail, $warr, $stock, $pub, $cat, $brand, $desc, $gallery, $created, $updated];
        }

        // NEW / EDIT
        return [$title, $slug, $cat, $brand, $price, $energyForm, $warr, $stock, $pub, $desc];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('title', 'Titre'))
            ->add(EntityFilter::new('category', 'Catégorie'))
            ->add(EntityFilter::new('brand', 'Marque'))
            ->add(BooleanFilter::new('isPublished', 'Publié'))
            ->add(NumericFilter::new('price', 'Prix'))
            ->add(NumericFilter::new('stock', 'Stock'))
            // filtre basé sur la valeur string persistée
            ->add(ChoiceFilter::new('energyLabel', 'Étiquette énergie')->setChoices(EnergyLabel::filterChoices()));
    }
}
