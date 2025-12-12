<?php

namespace App\Presentation\User\Validator;

use App\Application\User\Port\UserRepositoryInterface;
use App\Domain\User\Identity\ValueObject\Username;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UsernameNotExistsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UsernameNotExists) {
            throw new UnexpectedTypeException($constraint, UsernameNotExists::class);
        }

        if (empty($value)) {
            return;
        }

        /* @var string $value */
        $username = new Username($value);
        if (null !== $this->userRepository->findByUsername($username)) {
            $this->context->buildViolation($constraint->message)
                ->setCode(UsernameNotExists::SAME_USERNAME_ERROR)
                ->addViolation();
        }
    }
}
