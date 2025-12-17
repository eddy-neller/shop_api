<?php

declare(strict_types=1);

namespace App\Presentation\Shop\Validator\Catalog;

use App\Infrastructure\Entity\Shop\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class ShopCategoryNotExistsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ShopCategoryNotExists) {
            throw new UnexpectedTypeException($constraint, ShopCategoryNotExists::class);
        }

        if (empty($value)) {
            return;
        }

        $existingCategory = $this->em->getRepository(Category::class)->findOneBy(['title' => $value]);

        if (null === $existingCategory) {
            return;
        }

        $currentCategoryId = null;

        $request = $this->requestStack->getCurrentRequest();
        $requestCategoryId = $request?->attributes->get('id');

        if (is_string($requestCategoryId) && '' !== $requestCategoryId) {
            $currentCategoryId = $requestCategoryId;
        }

        if ($currentCategoryId === $existingCategory->getId()->toString()) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setCode(ShopCategoryNotExists::SAME_TITLE_ERROR)
            ->addViolation();
    }
}
