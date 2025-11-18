<?php

declare(strict_types=1);

namespace App\Tests\Unit\State\User;

use ApiPlatform\Metadata\Operation;
use App\Dto\User\UserAvatarInput;
use App\Entity\User\User;
use App\Service\InfoCodes;
use App\Service\User\UserManager;
use App\State\User\UserAvatarProcessor;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\File;

final class UserAvatarProcessorTest extends KernelTestCase
{
    private UserManager&MockObject $userManager;

    private Operation&MockObject $operation;

    private User&MockObject $user;

    private UserAvatarProcessor $userAvatarProcessor;

    protected function setUp(): void
    {
        $this->userManager = $this->createMock(UserManager::class);
        $this->operation = $this->createMock(Operation::class);
        $this->user = $this->createMock(User::class);

        $this->userAvatarProcessor = new UserAvatarProcessor(
            $this->userManager
        );
    }

    public function testProcessWithValidInputCallsGetUserByIdAndUpdateAvatar(): void
    {
        $input = $this->createValidUserAvatarInput();
        $userId = 'user-123';
        $uriVariables = ['id' => $userId];

        $this->userManager->expects($this->once())
            ->method('getUserById')
            ->with($userId)
            ->willReturn($this->user);

        $this->userManager->expects($this->once())
            ->method('updateAvatar')
            ->with($this->user, $input->avatarFile)
            ->willReturn($this->user);

        $result = $this->userAvatarProcessor->process($input, $this->operation, $uriVariables);

        $this->assertSame($this->user, $result);
    }

    public function testProcessThrowsLogicExceptionForInvalidInput(): void
    {
        $invalidInput = new stdClass();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->userAvatarProcessor->process($invalidInput, $this->operation);
    }

    public function testProcessThrowsLogicExceptionForNullInput(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->userAvatarProcessor->process(null, $this->operation);
    }

    public function testProcessThrowsLogicExceptionForStringInput(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->userAvatarProcessor->process('invalid', $this->operation);
    }

    public function testProcessWithDifferentUserId(): void
    {
        $input = $this->createValidUserAvatarInput();
        $userId = 'different-user-456';
        $uriVariables = ['id' => $userId];

        $this->userManager->expects($this->once())
            ->method('getUserById')
            ->with($userId)
            ->willReturn($this->user);

        $this->userManager->expects($this->once())
            ->method('updateAvatar')
            ->with($this->user, $input->avatarFile)
            ->willReturn($this->user);

        $result = $this->userAvatarProcessor->process($input, $this->operation, $uriVariables);

        $this->assertSame($this->user, $result);
    }

    public function testProcessWithContext(): void
    {
        $input = $this->createValidUserAvatarInput();
        $userId = 'user-789';
        $uriVariables = ['id' => $userId];
        $context = ['custom' => 'value'];

        $this->userManager->expects($this->once())
            ->method('getUserById')
            ->with($userId)
            ->willReturn($this->user);

        $this->userManager->expects($this->once())
            ->method('updateAvatar')
            ->with($this->user, $input->avatarFile)
            ->willReturn($this->user);

        $result = $this->userAvatarProcessor->process($input, $this->operation, $uriVariables, $context);

        $this->assertSame($this->user, $result);
    }

    public function testProcessReturnsUpdatedUser(): void
    {
        $input = $this->createValidUserAvatarInput();
        $userId = 'user-999';
        $uriVariables = ['id' => $userId];

        $this->userManager->expects($this->once())
            ->method('getUserById')
            ->with($userId)
            ->willReturn($this->user);

        $this->userManager->expects($this->once())
            ->method('updateAvatar')
            ->with($this->user, $input->avatarFile)
            ->willReturn($this->user);

        $result = $this->userAvatarProcessor->process($input, $this->operation, $uriVariables);

        $this->assertSame($this->user, $result);
    }

    public function testProcessWithDifferentAvatarFile(): void
    {
        $input = new UserAvatarInput();
        /** @var File&MockObject $mockFile */
        $mockFile = $this->createMock(File::class);
        $input->avatarFile = $mockFile;

        $userId = 'user-avatar';
        $uriVariables = ['id' => $userId];

        $this->userManager->expects($this->once())
            ->method('getUserById')
            ->with($userId)
            ->willReturn($this->user);

        $this->userManager->expects($this->once())
            ->method('updateAvatar')
            ->with($this->user, $mockFile)
            ->willReturn($this->user);

        $result = $this->userAvatarProcessor->process($input, $this->operation, $uriVariables);

        $this->assertSame($this->user, $result);
    }

    private function createValidUserAvatarInput(): UserAvatarInput
    {
        $input = new UserAvatarInput();
        /** @var File&MockObject $mockFile */
        $mockFile = $this->createMock(File::class);
        $input->avatarFile = $mockFile;

        return $input;
    }
}
