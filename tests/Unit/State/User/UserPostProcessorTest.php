<?php

declare(strict_types=1);

namespace App\Tests\Unit\State\User;

use ApiPlatform\Metadata\Operation;
use App\Dto\User\UserPostInput;
use App\Entity\User\User;
use App\Service\InfoCodes;
use App\Service\User\UserManager;
use App\State\User\UserPostProcessor;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UserPostProcessorTest extends KernelTestCase
{
    private UserManager&MockObject $userManager;

    private Operation&MockObject $operation;

    private User&MockObject $user;

    private UserPostProcessor $userPostProcessor;

    protected function setUp(): void
    {
        $this->userManager = $this->createMock(UserManager::class);
        $this->operation = $this->createMock(Operation::class);
        $this->user = $this->createMock(User::class);

        $this->userPostProcessor = new UserPostProcessor(
            $this->userManager
        );
    }

    public function testProcessWithValidInputCallsCreateUserByAdmin(): void
    {
        $input = $this->createValidUserPostInput();

        $this->userManager->expects($this->once())
            ->method('createUserByAdmin')
            ->with($input)
            ->willReturn($this->user);

        $result = $this->userPostProcessor->process($input, $this->operation);

        $this->assertSame($this->user, $result);
    }

    public function testProcessThrowsLogicExceptionForInvalidInput(): void
    {
        $invalidInput = new stdClass();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->userPostProcessor->process($invalidInput, $this->operation);
    }

    public function testProcessThrowsLogicExceptionForNullInput(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->userPostProcessor->process(null, $this->operation);
    }

    public function testProcessThrowsLogicExceptionForStringInput(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->userPostProcessor->process('invalid', $this->operation);
    }

    public function testProcessWithUriVariablesAndContext(): void
    {
        $input = $this->createValidUserPostInput();
        $uriVariables = ['id' => '123'];
        $context = ['custom' => 'value'];

        $this->userManager->expects($this->once())
            ->method('createUserByAdmin')
            ->with($input)
            ->willReturn($this->user);

        $result = $this->userPostProcessor->process($input, $this->operation, $uriVariables, $context);

        $this->assertSame($this->user, $result);
    }

    public function testProcessReturnsCreatedUser(): void
    {
        $input = $this->createValidUserPostInput();

        $this->userManager->expects($this->once())
            ->method('createUserByAdmin')
            ->with($input)
            ->willReturn($this->user);

        $result = $this->userPostProcessor->process($input, $this->operation);

        $this->assertSame($this->user, $result);
    }

    private function createValidUserPostInput(): UserPostInput
    {
        $input = new UserPostInput();
        $input->email = 'admin-created@example.com';
        $input->username = 'admincreated';
        $input->password = 'SecurePassword123!';
        $input->roles = [User::ROLES['user']];
        $input->status = User::STATUS['ACTIVE'];

        return $input;
    }
}
