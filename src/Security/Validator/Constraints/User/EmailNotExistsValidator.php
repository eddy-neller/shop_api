<?php

namespace App\Security\Validator\Constraints\User;

use App\Repository\User\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class EmailNotExistsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof EmailNotExists) {
            throw new UnexpectedTypeException($constraint, EmailNotExists::class);
        }

        /* Custom constraints should ignore null and empty values to allow  other constraints (NotBlank, NotNull, etc.) to take care of that. */
        if (empty($value)) {
            return;
        }

        /* @var string $value */
        if (null !== $this->userRepository->findOneBy(['email' => $value])) {
            $this->context->buildViolation($constraint->message)
                ->setCode(EmailNotExists::SAME_EMAIL_ERROR)
                ->addViolation();
        }
    }
}
