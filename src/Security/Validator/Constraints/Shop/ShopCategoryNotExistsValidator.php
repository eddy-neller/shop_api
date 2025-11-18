<?php

namespace App\Security\Validator\Constraints\Shop;

use App\Entity\Shop\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ShopCategoryNotExistsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ShopCategoryNotExists) {
            throw new UnexpectedTypeException($constraint, ShopCategoryNotExists::class);
        }

        /* Custom constraints should ignore null and empty values to allow  other constraints (NotBlank, NotNull, etc.) to take care of that. */
        if (empty($value)) {
            return;
        }

        /* @var string $value */
        $existingCategory = $this->em->getRepository(Category::class)->findOneBy(['title' => $value]);

        // Si aucune catégorie n'existe avec ce titre, c'est OK
        if (null === $existingCategory) {
            return;
        }

        // Récupérer la catégorie en cours de validation depuis le contexte
        $currentCategory = $this->context->getObject();

        // Si c'est la même catégorie (édition), c'est OK
        if ($currentCategory instanceof Category) {
            // Vérifier si l'entité est déjà persistée (managed by Doctrine)
            if ($this->em->contains($currentCategory) && $currentCategory === $existingCategory) {
                return;
            }
        }

        // Sinon, c'est une violation (une autre catégorie a déjà ce titre)
        $this->context->buildViolation($constraint->message)
            ->setCode(ShopCategoryNotExists::SAME_TITLE_ERROR)
            ->addViolation();
    }
}
