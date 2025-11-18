<?php

declare(strict_types=1);

namespace App\Tests\Unit\State\User;

use ApiPlatform\Metadata\Operation;
use App\Dto\User\Partial\UserPreferences;
use App\Dto\User\UserRegisterInput;
use App\Entity\User\User;
use App\Service\InfoCodes;
use App\Service\User\UserManager;
use App\State\User\UserRegisterProcessor;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UserRegisterProcessorTest extends KernelTestCase
{
    private UserManager&MockObject $userManager;

    private Operation&MockObject $operation;

    private User&MockObject $user;

    private UserRegisterProcessor $userRegisterProcessor;

    protected function setUp(): void
    {
        $this->userManager = $this->createMock(UserManager::class);
        $this->operation = $this->createMock(Operation::class);
        $this->user = $this->createMock(User::class);

        $this->userRegisterProcessor = new UserRegisterProcessor(
            $this->userManager
        );
    }

    public function testProcessWithValidInputCallsCreateUser(): void
    {
        $input = $this->createValidUserRegisterInput();

        $this->userManager->expects($this->once())
            ->method('registerUser')
            ->with($input)
            ->willReturn($this->user);

        $result = $this->userRegisterProcessor->process($input, $this->operation);

        $this->assertSame($this->user, $result);
    }

    public function testProcessThrowsLogicExceptionForInvalidInput(): void
    {
        $invalidInput = new stdClass();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->userRegisterProcessor->process($invalidInput, $this->operation);
    }

    public function testProcessThrowsLogicExceptionForNullInput(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->userRegisterProcessor->process(null, $this->operation);
    }

    public function testProcessThrowsLogicExceptionForStringInput(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InfoCodes::INTERNAL['INVALID_INPUT']);

        $this->userRegisterProcessor->process('invalid', $this->operation);
    }

    public function testProcessWithUriVariablesAndContext(): void
    {
        $input = $this->createValidUserRegisterInput();
        $uriVariables = ['id' => '123'];
        $context = ['custom' => 'value'];

        $this->userManager->expects($this->once())
            ->method('registerUser')
            ->with($input)
            ->willReturn($this->user);

        $result = $this->userRegisterProcessor->process($input, $this->operation, $uriVariables, $context);

        $this->assertSame($this->user, $result);
    }

    public function testProcessReturnsCreatedUser(): void
    {
        $input = $this->createValidUserRegisterInput();

        $this->userManager->expects($this->once())
            ->method('registerUser')
            ->with($input)
            ->willReturn($this->user);

        $result = $this->userRegisterProcessor->process($input, $this->operation);

        $this->assertSame($this->user, $result);
    }

    private function createValidUserRegisterInput(): UserRegisterInput
    {
        $input = new UserRegisterInput();
        $input->email = 'test@example.com';
        $input->username = 'testuser';
        $input->password = 'TestPassword123!';
        $input->confirmPassword = 'TestPassword123!';

        $preferences = new UserPreferences();
        $preferences->lang = 'fr';

        $input->preferences = $preferences;

        return $input;
    }
}
