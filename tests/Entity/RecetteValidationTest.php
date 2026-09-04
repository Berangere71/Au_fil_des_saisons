<?php

namespace App\Tests\Entity;

use App\Entity\Recette;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class RecetteValidationTest extends TestCase
{
    public function testEmptyTitleProducesFrenchValidationMessageInsteadOfTypeError(): void
    {
        $recette = (new Recette())->setTitre(null);

        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validateProperty($recette, 'titre');

        self::assertCount(1, $violations);
        self::assertSame('Veuillez donner un nom à votre recette.', $violations[0]->getMessage());
    }

    public function testEmptyRecipeContentProducesFrenchMessagesInsteadOfTypeErrors(): void
    {
        $recette = (new Recette())
            ->setIngredient(null)
            ->setPreparation(null);

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $ingredientViolations = $validator->validateProperty($recette, 'ingredient');
        $preparationViolations = $validator->validateProperty($recette, 'preparation');

        self::assertCount(1, $ingredientViolations);
        self::assertSame('Ajoutez au moins un ingrédient.', $ingredientViolations[0]->getMessage());
        self::assertCount(1, $preparationViolations);
        self::assertSame('Ajoutez au moins une étape de préparation.', $preparationViolations[0]->getMessage());
    }
}
