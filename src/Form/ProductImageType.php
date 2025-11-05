<?php

namespace App\Form;

use App\Entity\ProductImage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichFileType;

class ProductImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('imageFile', VichFileType::class, [
                'label' => 'Fichier Image',
                'required' => $options['is_create'],
                'allow_delete' => true,
                'download_uri' => false,
                'imagine_pattern' => null,
                'attr' => [
                    'accept' => 'image/*',
                ],
                'help' => 'Formats recommandés: JPG/PNG. Taille max raisonnable.',
                ])
            ->add('isMain', CheckboxType::class, [
                'label' => 'Image principale',
                'required' => false,
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Ordre',
                'empty_data' => 0,
                'attr' => ['min' => 0],
                'help' => '0 = en premier.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductImage::class,
            'is_create' => true
        ]);
    }
}
