<?php

namespace App\Form;

use App\Entity\ApplianceModel;
use App\Repository\CategoryRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplianceModelType extends AbstractType
{
    private CategoryRepository $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Récupère les catégories depuis la base de données
        $categories = $this->categoryRepository->findAll();
        $categoryChoices = [];
        foreach ($categories as $category) {
            $categoryChoices[$category->getName()] = $category->getSlug();
        }

        $builder
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => $categoryChoices,
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
                'placeholder' => 'Choisir une catégorie',
            ])
            ->add('brand', ChoiceType::class, [
                'label' => 'Marque',
                'choices' => [
                    'Miele' => 'miele',
                    'Bosch' => 'bosch',
                    'Siemens' => 'siemens',
                    'Samsung' => 'samsung',
                    'LG' => 'lg',
                    'Whirlpool' => 'whirlpool',
                    'Electrolux' => 'electrolux',
                    'AEG' => 'aeg',
                    'Liebherr' => 'liebherr',
                    'Beko' => 'beko',
                    'Candy' => 'candy',
                    'Indesit' => 'indesit',
                    'Hotpoint' => 'hotpoint',
                    'Haier' => 'haier',
                    'Smeg' => 'smeg',
                    'Brandt' => 'brandt',
                ],
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
                'placeholder' => 'Choisir une marque',
            ])
            ->add('modelReference', TextType::class, [
                'label' => 'Référence du modèle',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'RF65DG9H0ESR',
                    'style' => 'text-transform: uppercase;',
                ],
                'help' => 'Référence constructeur unique',
            ])
            ->add('modelName', TextType::class, [
                'label' => 'Nom commercial',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'French Door 650L Premium',
                ],
                'help' => 'Nom marketing du produit',
            ])
            ->add('releaseYear', IntegerType::class, [
                'label' => 'Année de sortie',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => date('Y'),
                    'min' => 2000,
                    'max' => date('Y') + 1,
                ],
                'help' => 'Année de mise sur le marché',
            ])
            ->add('tier', ChoiceType::class, [
                'label' => 'Gamme',
                'choices' => [
                    'Premium' => 'premium',
                    'Standard' => 'standard',
                    'Entrée de gamme' => 'entry',
                ],
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
                'help' => 'Positionnement commercial',
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Modèle actif',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
                'help' => 'Actif dans le calculateur',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ApplianceModel::class,
        ]);
    }
}
