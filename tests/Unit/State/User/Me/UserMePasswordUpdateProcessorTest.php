<?php

declare(strict_types=1);

namespace App\Tests\Unit\State\User\Me;

use ApiPlatform\Metadata\Operation;
use App\Dto\User\Me\UserMePasswordUpdateInput;
use App\Entity\User\User;
use App\Service\InfoCodes;
use App\Service\User\UserManager;
use App\State\User\Me\UserMePasswordUpdateProcessor;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;

final class UserMePasswordUpdateProcessorTest extends KernelTestCase
{
    private Security&MockObject $security;

    private UserManager&MockObject $userManager;

    private Operation&MockObject $operation;

    private User&MockObject $user;

    private UserMePasswordUpdateProcessor $userMePasswordUpdateProcessor;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->userManager = $this->createMock(UserManager::class);
        $this->operation = $this->createMock(Operation::class);
        $this->user = $this->createMock(User::class);

        $this->userMePasswordUpdateProcessor = new UserMePasswordUpdateProcessor(
            $this->security,
            $this->userManager
        );
    }

    public function testProcessWithDifferentPasswordCallsUserManagerWithCorrectPassword(): void
    {
        $input = new UserMePasswordUpdateInput();
        $input->currentPassword = 'old_password';
        $input->newPassword = 'NewPassword123!';
        $input->confirmNewPassword = 'NewPassword123!';

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($this->user);

        $this->userManager->expects($this->once())
            ->method('updatePassword')
            ->with($this->user, 'NewPassword123!')
            ->willReturn($this->user);

        $this->userMePasswordUpdateProcessor->process($input, $this->operation);
    }

    public function testProcessThrowsLogicExceptionForInvalidInput(): void
    {
        $invalidInput = new stdClass();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->userMePasswordUpdateProcessor->process($invalidInput, $this->operation);
    }

    public function testProcessThrowsLogicExceptionForNullInput(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->userMePasswordUpdateProcessor->process(null, $this->operation);
    }

    public function testProcessThrowsLogicExceptionForStringInput(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->userMePasswordUpdateProcessor->process('invalid', $this->operation);
    }

    public function testProcessThrowsLogicExceptionForArrayInput(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->userMePasswordUpdateProcessor->process(['newPassword' => 'test'], $this->operation);
    }

    public function testProcessWithUriVariablesAndContext(): void
    {
        $input = $this->createValidUserMePasswordUpdateInput();
        $uriVariables = ['id' => '123'];
        $context = ['custom' => 'value'];

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($this->user);

        $this->userManager->expects($this->once())
            ->method('updatePassword')
            ->with($this->user, $input->newPassword)
            ->willReturn($this->user);

        $this->userMePasswordUpdateProcessor->process($input, $this->operation, $uriVariables, $context);
    }

    public function testProcessWithMultipleCallsCallsUpdatePasswordEachTime(): void
    {
        $input1 = new UserMePasswordUpdateInput();
        $input1->currentPassword = 'old_password';
        $input1->newPassword = 'Password1!';
        $input1->confirmNewPassword = 'Password1!';

        $input2 = new UserMePasswordUpdateInput();
        $input2->currentPassword = 'old_password';
        $input2->newPassword = 'Password2!';
        $input2->confirmNewPassword = 'Password2!';

        $this->security->expects($this->exactly(2))
            ->method('getUser')
            ->willReturn($this->user);

        $this->userManager->expects($this->exactly(2))
            ->method('updatePassword')
            ->willReturn($this->user);

        $this->userMePasswordUpdateProcessor->process($input1, $this->operation);
        $this->userMePasswordUpdateProcessor->process($input2, $this->operation);
    }

    private function createValidUserMePasswordUpdateInput(): UserMePasswordUpdateInput
    {
        $input = new UserMePasswordUpdateInput();
        $input->currentPassword = 'current_password';
        $input->newPassword = 'NewPassword123!';
        $input->confirmNewPassword = 'NewPassword123!';

        return $input;
    }
}
