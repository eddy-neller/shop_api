<?php

declare(strict_types=1);

namespace App\Tests\Unit\State\User;

use ApiPlatform\Metadata\Operation;
use App\Dto\User\PasswordResetConfirmInput;
use App\Service\InfoCodes;
use App\Service\User\UserManager;
use App\State\User\PasswordResetConfirmProcessor;
use Exception;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PasswordResetConfirmProcessorTest extends KernelTestCase
{
    private UserManager&MockObject $userManager;

    private Operation&MockObject $operation;

    private PasswordResetConfirmProcessor $processor;

    protected function setUp(): void
    {
        $this->userManager = $this->createMock(UserManager::class);
        $this->operation = $this->createMock(Operation::class);

        $this->processor = new PasswordResetConfirmProcessor(
            $this->userManager
        );
    }

    public function testProcessWithValidInputCallsValidateResetPassword(): void
    {
        $input = $this->createValidPasswordResetConfirmInput();

        $this->userManager->expects($this->once())
            ->method('validateResetPassword')
            ->with($input->token, $input->newPassword);

        $this->processor->process($input, $this->operation);
    }

    public function testProcessWithDifferentTokensAndPasswordsCallsValidateResetPasswordWithCorrectParameters(): void
    {
        $input = new PasswordResetConfirmInput();
        $input->token = 'different-token-123';
        $input->newPassword = 'NewPassword123!';
        $input->confirmNewPassword = 'NewPassword123!';

        $this->userManager->expects($this->once())
            ->method('validateResetPassword')
            ->with('different-token-123', 'NewPassword123!');

        $this->processor->process($input, $this->operation);
    }

    public function testProcessWithEmptyTokenCallsValidateResetPasswordWithEmptyToken(): void
    {
        $input = new PasswordResetConfirmInput();
        $input->token = '';
        $input->newPassword = 'TestPassword123!';
        $input->confirmNewPassword = 'TestPassword123!';

        $this->userManager->expects($this->once())
            ->method('validateResetPassword')
            ->with('', 'TestPassword123!');

        $this->processor->process($input, $this->operation);
    }

    public function testProcessWithEmptyPasswordCallsValidateResetPasswordWithEmptyPassword(): void
    {
        $input = new PasswordResetConfirmInput();
        $input->token = 'valid-token-123';
        $input->newPassword = '';
        $input->confirmNewPassword = '';

        $this->userManager->expects($this->once())
            ->method('validateResetPassword')
            ->with('valid-token-123', '');

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

        $this->processor->process(['token' => 'test', 'newPassword' => 'test'], $this->operation);
    }

    public function testProcessWithUriVariablesAndContext(): void
    {
        $input = $this->createValidPasswordResetConfirmInput();
        $uriVariables = ['id' => '123'];
        $context = ['custom' => 'value'];

        $this->userManager->expects($this->once())
            ->method('validateResetPassword')
            ->with($input->token, $input->newPassword);

        // Le test vérifie que la méthode peut être appelée avec des paramètres supplémentaires
        // même si elle ne les utilise pas directement
        $this->processor->process($input, $this->operation, $uriVariables, $context);
    }

    public function testProcessWithUserManagerExceptionPropagatesException(): void
    {
        $input = $this->createValidPasswordResetConfirmInput();
        $exception = new Exception('Password reset validation failed');

        $this->userManager->expects($this->once())
            ->method('validateResetPassword')
            ->with($input->token, $input->newPassword)
            ->willThrowException($exception);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Password reset validation failed');

        $this->processor->process($input, $this->operation);
    }

    public function testProcessWithMultipleCallsCallsValidateResetPasswordEachTime(): void
    {
        $input1 = new PasswordResetConfirmInput();
        $input1->token = 'token1';
        $input1->newPassword = 'Password1!';
        $input1->confirmNewPassword = 'Password1!';

        $input2 = new PasswordResetConfirmInput();
        $input2->token = 'token2';
        $input2->newPassword = 'Password2!';
        $input2->confirmNewPassword = 'Password2!';

        $this->userManager->expects($this->exactly(2))
            ->method('validateResetPassword');

        $this->processor->process($input1, $this->operation);
        $this->processor->process($input2, $this->operation);
    }

    public function testProcessWithSpecialCharactersInPassword(): void
    {
        $input = new PasswordResetConfirmInput();
        $input->token = 'valid-token-123';
        $input->newPassword = 'Complex!Pass@123#';
        $input->confirmNewPassword = 'Complex!Pass@123#';

        $this->userManager->expects($this->once())
            ->method('validateResetPassword')
            ->with('valid-token-123', 'Complex!Pass@123#');

        $this->processor->process($input, $this->operation);
    }

    public function testProcessWithLongPassword(): void
    {
        $input = new PasswordResetConfirmInput();
        $input->token = 'valid-token-123';
        $input->newPassword = 'VeryLongPassword123!@#$%';
        $input->confirmNewPassword = 'VeryLongPassword123!@#$%';

        $this->userManager->expects($this->once())
            ->method('validateResetPassword')
            ->with('valid-token-123', 'VeryLongPassword123!@#$%');

        $this->processor->process($input, $this->operation);
    }

    private function createValidPasswordResetConfirmInput(): PasswordResetConfirmInput
    {
        $input = new PasswordResetConfirmInput();
        $input->token = 'valid-reset-token-123';
        $input->newPassword = 'NewPassword123!';
        $input->confirmNewPassword = 'NewPassword123!';

        return $input;
    }
}
