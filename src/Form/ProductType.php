<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Category;
use App\Entity\Brand;
use App\Enum\EnergyLabel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du produit',
                'attr' => ['placeholder' => 'Ex: Réfrigérateur Samsung RB38...']
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'placeholder' => 'Sélectionnez une catégorie'
            ])
            ->add('brand', EntityType::class, [
                'class' => Brand::class,
                'choice_label' => 'name',
                'label' => 'Marque',
                'placeholder' => 'Sélectionnez une marque',
                'required' => false
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Prix',
                'currency' => false,
                'divisor' => 100,
                'attr' => [
                    'placeholder' => '0.00'
                ]
            ])
            ->add('energyLabel', EnumType::class, [
                'label' => 'Étiquette énergie',
                'class' => EnergyLabel::class,
                'placeholder' => 'Sélectionnez une étiquette',
                'required' => false,
                'attr' => [
                    'class' => 'energy-label-select'
                ],
                'choice_label' => function (?EnergyLabel $label): string {
                    if ($label === null) {
                        return '';
                    }
                    return $label === EnergyLabel::NA ? 'Non classé' : $label->value;
                }
            ])
            ->add('warrantyMonths', IntegerType::class, [
                'label' => 'Garantie (mois)',
                'required' => false,
                'attr' => ['placeholder' => '12']
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Stock disponible',
                'attr' => ['placeholder' => '0']
            ])
            ->add('isPublished', CheckboxType::class, [
                'label' => 'Produit publié',
                'required' => false
            ])
            ->add('shortDescription', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 6]
            ]);
    }

    public function configureOptions(OptionsResolver $options): void
    {
        $options->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
