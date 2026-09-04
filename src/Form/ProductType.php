<?php

namespace App\Form;

use App\Entity\Month;
use App\Entity\Product;
use App\Entity\Recette;
use App\Enum\ProductCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;


class ProductType extends AbstractType
{
   public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $builder

        ->add('nom', TextType::class, [
            'label' => 'Nom du produit',
            'attr' => [
                'placeholder' => 'Ex : Fraise'
            ]
        ])

        ->add('category', EnumType::class, [
            'class' => ProductCategory::class,
            'label' => 'Catégorie'
        ])

        ->add('description', TextareaType::class, [
            'label' => 'Description',
            'required' => false,
            'attr' => [
                'rows' => 5
            ]
        ])

        ->add('conservation', TextareaType::class, [
            'label' => 'Conseils de conservation',
            'attr' => [
                'rows' => 4
            ]
        ])

        ->add('debutRecolteMois', EntityType::class, [
            'class' => Month::class,
            'label' => 'Début de récolte'
        ])

        ->add('finRecolteMois', EntityType::class, [
            'class' => Month::class,
            'label' => 'Fin de récolte'
        ])

        ->add('recettes', EntityType::class, [
            'class' => Recette::class,
            'label' => 'Recettes associées',
            'multiple' => true,
            'expanded' => true,
            'required' => false,
            'by_reference' => false,
            'choice_attr' => static function (Recette $recette): array {
                return [
                    'data-recipe-search' => mb_strtolower($recette->getTitre() . ' ' . $recette->getIngredient(), 'UTF-8'),
                ];
            },
        ])

        ->add('photoFile', FileType::class, [
            'label' => 'Image du produit',
            'mapped' => false,
            'required' => false,
            'constraints' => [
                new File(
                    maxSize: '5M',
                    mimeTypes: [
                        'image/jpeg',
                        'image/png',
                        'image/webp'
                    ],
                    mimeTypesMessage: 'Veuillez sélectionner une image valide.',
                )
            ]
        ]);
}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
