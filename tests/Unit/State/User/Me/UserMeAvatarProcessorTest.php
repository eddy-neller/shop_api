<?php

declare(strict_types=1);

namespace App\Tests\Unit\State\User\Me;

use ApiPlatform\Metadata\Operation;
use App\Dto\User\Me\UserMeAvatarInput;
use App\Entity\User\User;
use App\Service\InfoCodes;
use App\Service\User\UserManager;
use App\State\User\Me\UserMeAvatarProcessor;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\File;

final class UserMeAvatarProcessorTest extends KernelTestCase
{
    private Security&MockObject $security;

    private UserManager&MockObject $userManager;

    private Operation&MockObject $operation;

    private User&MockObject $user;

    private UserMeAvatarProcessor $userMeAvatarProcessor;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->userManager = $this->createMock(UserManager::class);
        $this->operation = $this->createMock(Operation::class);
        $this->user = $this->createMock(User::class);

        $this->userMeAvatarProcessor = new UserMeAvatarProcessor(
            $this->security,
            $this->userManager
        );
    }

    public function testProcessWithValidInputCallsUserManagerAndProcessor(): void
    {
        $input = $this->createValidUserMeAvatarInput();

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($this->user);

        $this->userManager->expects($this->once())
            ->method('updateAvatar')
            ->with($this->user, $input->avatarFile)
            ->willReturn($this->user);

        $this->userMeAvatarProcessor->process($input, $this->operation);
    }

    public function testProcessWithDifferentAvatarFileCallsUserManagerWithCorrectFile(): void
    {
        $input = new UserMeAvatarInput();
        /** @var File&MockObject $mockFile */
        $mockFile = $this->createMock(File::class);
        $input->avatarFile = $mockFile;

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($this->user);

        $this->userManager->expects($this->once())
            ->method('updateAvatar')
            ->with($this->user, $input->avatarFile)
            ->willReturn($this->user);

        $this->userMeAvatarProcessor->process($input, $this->operation);
    }

    public function testProcessThrowsLogicExceptionForInvalidInput(): void
    {
        $invalidInput = new stdClass();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->userMeAvatarProcessor->process($invalidInput, $this->operation);
    }

    public function testProcessThrowsLogicExceptionForNullInput(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->userMeAvatarProcessor->process(null, $this->operation);
    }

    public function testProcessThrowsLogicExceptionForStringInput(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->userMeAvatarProcessor->process('invalid', $this->operation);
    }

    public function testProcessThrowsLogicExceptionForArrayInput(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->userMeAvatarProcessor->process(['avatarFile' => 'test'], $this->operation);
    }

    public function testProcessWithUriVariablesAndContext(): void
    {
        $input = $this->createValidUserMeAvatarInput();
        $uriVariables = ['id' => '123'];
        $context = ['custom' => 'value'];

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($this->user);

        $this->userManager->expects($this->once())
            ->method('updateAvatar')
            ->with($this->user, $input->avatarFile)
            ->willReturn($this->user);

        $this->userMeAvatarProcessor->process($input, $this->operation, $uriVariables, $context);
    }

    public function testProcessWithMultipleCallsCallsUpdateAvatarEachTime(): void
    {
        $input1 = new UserMeAvatarInput();
        /** @var File&MockObject $mockFile1 */
        $mockFile1 = $this->createMock(File::class);
        $input1->avatarFile = $mockFile1;

        $input2 = new UserMeAvatarInput();
        /** @var File&MockObject $mockFile2 */
        $mockFile2 = $this->createMock(File::class);
        $input2->avatarFile = $mockFile2;

        $this->security->expects($this->exactly(2))
            ->method('getUser')
            ->willReturn($this->user);

        $this->userManager->expects($this->exactly(2))
            ->method('updateAvatar')
            ->willReturn($this->user);

        $this->userMeAvatarProcessor->process($input1, $this->operation);
        $this->userMeAvatarProcessor->process($input2, $this->operation);
    }

    private function createValidUserMeAvatarInput(): UserMeAvatarInput
    {
        $input = new UserMeAvatarInput();
        /** @var File&MockObject $mockFile */
        $mockFile = $this->createMock(File::class);
        $input->avatarFile = $mockFile;

        return $input;
    }
}
