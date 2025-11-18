<?php

declare(strict_types=1);

namespace App\Tests\Unit\State\User;

use ApiPlatform\Metadata\Operation;
use App\Dto\User\PasswordResetCheckInput;
use App\Service\InfoCodes;
use App\Service\User\UserManager;
use App\State\User\PasswordResetCheckProcessor;
use Exception;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PasswordResetCheckProcessorTest extends KernelTestCase
{
    private UserManager&MockObject $userManager;

    private Operation&MockObject $operation;

    private PasswordResetCheckProcessor $processor;

    protected function setUp(): void
    {
        $this->userManager = $this->createMock(UserManager::class);
        $this->operation = $this->createMock(Operation::class);

        $this->processor = new PasswordResetCheckProcessor(
            $this->userManager
        );
    }

    public function testProcessWithValidInputCallsAssertValidResetPasswordToken(): void
    {
        $input = $this->createValidPasswordResetCheckInput();

        $this->userManager->expects($this->once())
            ->method('assertValidResetPasswordToken')
            ->with($input->token);

        $this->processor->process($input, $this->operation);
    }

    public function testProcessWithDifferentTokensCallsAssertValidResetPasswordTokenWithCorrectToken(): void
    {
        $input = new PasswordResetCheckInput();
        $input->token = 'different-token-123';

        $this->userManager->expects($this->once())
            ->method('assertValidResetPasswordToken')
            ->with('different-token-123');

        $this->processor->process($input, $this->operation);
    }

    public function testProcessWithEmptyTokenCallsAssertValidResetPasswordTokenWithEmptyToken(): void
    {
        $input = new PasswordResetCheckInput();
        $input->token = '';

        $this->userManager->expects($this->once())
            ->method('assertValidResetPasswordToken')
            ->with('');

        $this->processor->process($input, $this->operation);
    }

    public function testProcessThrowsLogicExceptionForInvalidInput(): void
    {
        $invalidInput = new stdClass();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->processor->process($invalidInput, $this->operation);
    }

    public function testProcessThrowsLogicExceptionForNullInput(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->processor->process(null, $this->operation);
    }

    public function testProcessThrowsLogicExceptionForStringInput(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->processor->process('invalid', $this->operation);
    }

    public function testProcessThrowsLogicExceptionForArrayInput(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->processor->process(['token' => 'test'], $this->operation);
    }

    public function testProcessWithUriVariablesAndContext(): void
    {
        $input = $this->createValidPasswordResetCheckInput();
        $uriVariables = ['id' => '123'];
        $context = ['custom' => 'value'];

        $this->userManager->expects($this->once())
            ->method('assertValidResetPasswordToken')
            ->with($input->token);

        // Le test vérifie que la méthode peut être appelée avec des paramètres supplémentaires
        // même si elle ne les utilise pas directement
        $this->processor->process($input, $this->operation, $uriVariables, $context);
    }

    public function testProcessWithUserManagerExceptionPropagatesException(): void
    {
        $input = $this->createValidPasswordResetCheckInput();
        $exception = new Exception('Token validation failed');

        $this->userManager->expects($this->once())
            ->method('assertValidResetPasswordToken')
            ->with($input->token)
            ->willThrowException($exception);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Token validation failed');

        $this->processor->process($input, $this->operation);
    }

    public function testProcessWithMultipleCallsCallsAssertValidResetPasswordTokenEachTime(): void
    {
        $input1 = new PasswordResetCheckInput();
        $input1->token = 'token1';

        $input2 = new PasswordResetCheckInput();
        $input2->token = 'token2';

        $this->userManager->expects($this->exactly(2))
            ->method('assertValidResetPasswordToken');

        $this->processor->process($input1, $this->operation);
        $this->processor->process($input2, $this->operation);
    }

    private function createValidPasswordResetCheckInput(): PasswordResetCheckInput
    {
        $input = new PasswordResetCheckInput();
        $input->token = 'valid-reset-token-123';

        return $input;
    }
}
