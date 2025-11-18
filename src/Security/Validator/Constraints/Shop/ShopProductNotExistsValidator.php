<?php

namespace App\Security\Validator\Constraints\Shop;

use App\Entity\Shop\Product;
use App\Repository\Shop\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ShopProductNotExistsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ShopProductNotExists) {
            throw new UnexpectedTypeException($constraint, ShopProductNotExists::class);
        }

        /* Custom constraints should ignore null and empty values to allow  other constraints (NotBlank, NotNull, etc.) to take care of that. */
        if (empty($value)) {
            return;
        }

        /* @var string $value */
        $existingProduct = $this->productRepository->findOneBy(['title' => $value]);

        // Si aucun produit n'existe avec ce titre, c'est OK
        if (null === $existingProduct) {
            return;
        }

        // Récupérer le produit en cours de validation depuis le contexte
        $currentProduct = $this->context->getObject();

        // Si c'est le même produit (édition), c'est OK
        if ($currentProduct instanceof Product) {
            // Vérifier si l'entité est déjà persistée (managed by Doctrine)
            if ($this->em->contains($currentProduct) && $currentProduct === $existingProduct) {
                return;
            }
        }

        // Sinon, c'est une violation (un autre produit a déjà ce titre)
        $this->context->buildViolation($constraint->message)
            ->setCode(ShopProductNotExists::SAME_TITLE_ERROR)
            ->addViolation();
    }
}
