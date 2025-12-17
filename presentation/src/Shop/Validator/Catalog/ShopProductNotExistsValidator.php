<?php

declare(strict_types=1);

namespace App\Presentation\Shop\Validator\Catalog;

use App\Infrastructure\Entity\Shop\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class ShopProductNotExistsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ShopProductNotExists) {
            throw new UnexpectedTypeException($constraint, ShopProductNotExists::class);
        }

        if (empty($value)) {
            return;
        }

        $existingProduct = $this->em->getRepository(Product::class)->findOneBy(['title' => $value]);

        if (null === $existingProduct) {
            return;
        }

        $currentProductId = null;

        $request = $this->requestStack->getCurrentRequest();
        $requestProductId = $request?->attributes->get('id');

        if (is_string($requestProductId) && '' !== $requestProductId) {
            $currentProductId = $requestProductId;
        }

        if ($currentProductId === $existingProduct->getId()->toString()) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setCode(ShopProductNotExists::SAME_TITLE_ERROR)
            ->addViolation();
    }
}
