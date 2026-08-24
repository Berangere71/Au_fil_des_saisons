<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Recette;
use App\Enum\RecetteTypePlat;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

final class RecetteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, ['label' => 'Nom de la recette'])
            ->add('typePlat', ChoiceType::class, ['label' => 'Catégorie', 'choices' => RecetteTypePlat::cases(), 'choice_label' => static fn (RecetteTypePlat $type) => ucfirst($type->value)])
            ->add('photoFile', FileType::class, ['label' => 'Photo', 'mapped' => false, 'required' => false, 'constraints' => [new Image(maxSize: '5M')]])
            ->add('nbrPerson', ChoiceType::class, [
                'label' => 'Pour combien de personnes ?',
                'choices' => array_combine(range(1, 12), range(1, 12)),
            ])
            ->add('timePrepa', IntegerType::class, ['label' => 'Temps de préparation (minutes)', 'required' => false])
            ->add('ingredient', TextareaType::class, [
                'label' => 'Ingrédients',
                'required' => false,
                'attr' => ['class' => 'js-ingredient-storage', 'hidden' => true],
            ])
            ->add('preparation', TextareaType::class, [
                'label' => 'Préparation',
                'required' => false,
                'attr' => ['class' => 'js-preparation-storage', 'hidden' => true],
            ])
            ->add('isOven', CheckboxType::class, ['label' => 'Cuisson au four', 'required' => false])
            ->add('tempOven', TextType::class, ['label' => 'Température du four (°C)', 'required' => false, 'attr' => ['placeholder' => 'Ex. 180 °C']])
            ->add('timeOven', TextType::class, ['label' => 'Temps de cuisson (minutes)', 'required' => false, 'attr' => ['placeholder' => 'Ex. 30 min']])
            ->add('productCategory', ChoiceType::class, [
                'label' => false,
                'mapped' => false,
                'required' => false,
                'placeholder' => 'Choisir une catégorie',
                'choices' => [
                    'Tous les produits' => 'all',
                    'Fruits' => 'fruit',
                    'Légumes' => 'legume',
                    'Viandes' => 'viande',
                    'Poissons' => 'poisson',
                ],
                'attr' => ['class' => 'js-product-category'],
            ])
            ->add('products', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'nom',
                'choice_attr' => static fn (Product $product) => ['data-category' => $product->getCategory()->value],
                'label' => 'Produits de saison utilisés',
                'multiple' => true,
                'expanded' => true,
                'required' => true,
            ])
            ->add('isPublic', CheckboxType::class, ['label' => 'Publier cette recette pour tous', 'required' => false]);

        $numberWithUnitTransformer = new CallbackTransformer(
            static fn (?float $value) => $value === null ? '' : (string) $value,
            static function (mixed $value): ?float {
                $number = preg_replace('/[^0-9,.-]/', '', (string) $value);
                return $number === '' ? null : (float) str_replace(',', '.', $number);
            },
        );

        $builder->get('tempOven')->addModelTransformer($numberWithUnitTransformer);
        $builder->get('timeOven')->addModelTransformer(clone $numberWithUnitTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Recette::class]);
    }
}
