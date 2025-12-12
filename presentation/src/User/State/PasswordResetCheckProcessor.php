<?php

namespace App\Presentation\User\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Shared\CQRS\Query\QueryBusInterface;
use App\Application\User\UseCase\Query\CheckPasswordResetToken\CheckPasswordResetTokenQuery;
use App\Presentation\Shared\State\PresentationErrorCode;
use App\Presentation\User\Dto\PasswordResetCheckInput;
use LogicException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

readonly class PasswordResetCheckProcessor implements ProcessorInterface
{
    public function __construct(
        private QueryBusInterface $queryBus,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof PasswordResetCheckInput) {
            throw new LogicException(PresentationErrorCode::INVALID_INPUT->value);
        }

        $query = new CheckPasswordResetTokenQuery($data->token);

        $output = $this->queryBus->dispatch($query);

        if (!$output->isValid) {
            throw new BadRequestHttpException('Token de réinitialisation invalide ou expiré.');
        }
    }
}
