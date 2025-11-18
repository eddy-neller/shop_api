<?php

declare(strict_types=1);

namespace App\Tests\Unit\State\User;

use ApiPlatform\Metadata\Operation;
use App\Dto\User\UserPatchInput;
use App\Entity\User\User;
use App\Service\InfoCodes;
use App\Service\User\UserManager;
use App\State\User\UserPatchProcessor;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UserPatchProcessorTest extends KernelTestCase
{
    private UserManager&MockObject $userManager;

    private Operation&MockObject $operation;

    private User&MockObject $user;

    private UserPatchProcessor $userPatchProcessor;

    protected function setUp(): void
    {
        $this->userManager = $this->createMock(UserManager::class);
        $this->operation = $this->createMock(Operation::class);
        $this->user = $this->createMock(User::class);

        $this->userPatchProcessor = new UserPatchProcessor(
            $this->userManager
        );
    }

    public function testProcessWithValidInputCallsUpdateUserByAdmin(): void
    {
        $input = $this->createValidUserPatchInput();
        $userId = 'user-123';
        $uriVariables = ['id' => $userId];

        $this->userManager->expects($this->once())
            ->method('updateUserByAdmin')
            ->with($userId, $input)
            ->willReturn($this->user);

        $result = $this->userPatchProcessor->process($input, $this->operation, $uriVariables);

        $this->assertSame($this->user, $result);
    }

    public function testProcessThrowsLogicExceptionForInvalidInput(): void
    {
        $invalidInput = new stdClass();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->userPatchProcessor->process($invalidInput, $this->operation);
    }

    public function testProcessThrowsLogicExceptionForNullInput(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->userPatchProcessor->process(null, $this->operation);
    }

    public function testProcessThrowsLogicExceptionForStringInput(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->userPatchProcessor->process('invalid', $this->operation);
    }

    public function testProcessWithDifferentUserId(): void
    {
        $input = $this->createValidUserPatchInput();
        $userId = 'different-user-456';
        $uriVariables = ['id' => $userId];

        $this->userManager->expects($this->once())
            ->method('updateUserByAdmin')
            ->with($userId, $input)
            ->willReturn($this->user);

        $result = $this->userPatchProcessor->process($input, $this->operation, $uriVariables);

        $this->assertSame($this->user, $result);
    }

    public function testProcessWithContext(): void
    {
        $input = $this->createValidUserPatchInput();
        $userId = 'user-789';
        $uriVariables = ['id' => $userId];
        $context = ['custom' => 'value'];

        $this->userManager->expects($this->once())
            ->method('updateUserByAdmin')
            ->with($userId, $input)
            ->willReturn($this->user);

        $result = $this->userPatchProcessor->process($input, $this->operation, $uriVariables, $context);

        $this->assertSame($this->user, $result);
    }

    public function testProcessReturnsUpdatedUser(): void
    {
        $input = $this->createValidUserPatchInput();
        $userId = 'user-999';
        $uriVariables = ['id' => $userId];

        $this->userManager->expects($this->once())
            ->method('updateUserByAdmin')
            ->with($userId, $input)
            ->willReturn($this->user);

        $result = $this->userPatchProcessor->process($input, $this->operation, $uriVariables);

        $this->assertSame($this->user, $result);
    }

    public function testProcessWithPartialUpdate(): void
    {
        $input = new UserPatchInput();
        $input->username = 'updatedusername';
        // Tous les autres champs restent null

        $userId = 'user-partial';
        $uriVariables = ['id' => $userId];

        $this->userManager->expects($this->once())
            ->method('updateUserByAdmin')
            ->with($userId, $input)
            ->willReturn($this->user);

        $result = $this->userPatchProcessor->process($input, $this->operation, $uriVariables);

        $this->assertSame($this->user, $result);
    }

    private function createValidUserPatchInput(): UserPatchInput
    {
        $input = new UserPatchInput();
        $input->username = 'updateduser';
        $input->email = 'updated@example.com';
        $input->roles = [User::ROLES['admin']];
        $input->status = User::STATUS['ACTIVE'];

        return $input;
    }
}
