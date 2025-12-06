<?php

declare(strict_types=1);

namespace App\Presentation\Tests\Unit\State\User;

use ApiPlatform\Metadata\Operation;
use App\Application\Shared\CQRS\Command\CommandBusInterface;
use App\Application\User\Port\AvatarUrlResolverInterface;
use App\Application\User\UseCase\Command\UploadAndUpdateAvatar\UploadAndUpdateAvatarCommand;
use App\Application\User\UseCase\Command\UploadAndUpdateAvatar\UploadAndUpdateAvatarOutput;
use App\Domain\User\Identity\ValueObject\EmailAddress;
use App\Domain\User\Identity\ValueObject\UserId;
use App\Domain\User\Identity\ValueObject\Username;
use App\Domain\User\Model\User as DomainUser;
use App\Domain\User\Preference\ValueObject\Preferences;
use App\Domain\User\Security\ValueObject\HashedPassword;
use App\Presentation\Shared\State\PresentationErrorCode;
use App\Presentation\User\ApiResource\UserResource;
use App\Presentation\User\Dto\UserAvatarInput;
use App\Presentation\User\Presenter\UserResourcePresenter;
use App\Presentation\User\State\UserAvatarProcessor;
use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\File;

final class UserAvatarProcessorTest extends KernelTestCase
{
    private CommandBusInterface&MockObject $commandBus;

    private AvatarUrlResolverInterface&MockObject $avatarUrlResolver;

    private Operation&MockObject $operation;

    private UserAvatarProcessor $userAvatarProcessor;

    protected function setUp(): void
    {
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->avatarUrlResolver = $this->createMock(AvatarUrlResolverInterface::class);
        $userResourcePresenter = new UserResourcePresenter($this->avatarUrlResolver);
        $this->operation = $this->createMock(Operation::class);

        $this->userAvatarProcessor = new UserAvatarProcessor(
            $this->commandBus,
            $userResourcePresenter,
        );
    }

    public function testProcessWithValidInput(): void
    {
        $input = $this->createValidUserAvatarInput();
        $userId = Uuid::uuid4()->toString();
        $userIdVO = UserId::fromString($userId);
        $uriVariables = ['id' => $userId];
        $domainUser = $this->createDomainUser();
        $output = new UploadAndUpdateAvatarOutput($domainUser);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function ($command) use ($userIdVO, $output): UploadAndUpdateAvatarOutput {
                $this->assertInstanceOf(UploadAndUpdateAvatarCommand::class, $command);
                $this->assertTrue($command->userId->equals($userIdVO));

                return $output;
            });

        $this->avatarUrlResolver->expects($this->once())
            ->method('resolve')
            ->willReturn('/uploads/avatar.jpg');

        $result = $this->userAvatarProcessor->process($input, $this->operation, $uriVariables);

        $this->assertInstanceOf(UserResource::class, $result);
    }

    public function testProcessThrowsLogicExceptionForInvalidInput(): void
    {
        $invalidInput = new stdClass();
        $uriVariables = ['id' => 'test-id'];

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(PresentationErrorCode::INVALID_INPUT->value);

        $this->userAvatarProcessor->process($invalidInput, $this->operation, $uriVariables);
    }

    public function testProcessThrowsLogicExceptionForNullInput(): void
    {
        $uriVariables = ['id' => 'test-id'];

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(PresentationErrorCode::INVALID_INPUT->value);

        $this->userAvatarProcessor->process(null, $this->operation, $uriVariables);
    }

    public function testProcessThrowsLogicExceptionForStringInput(): void
    {
        $uriVariables = ['id' => 'test-id'];

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(PresentationErrorCode::INVALID_INPUT->value);

        $this->userAvatarProcessor->process('invalid', $this->operation, $uriVariables);
    }

    private function createValidUserAvatarInput(): UserAvatarInput
    {
        $input = new UserAvatarInput();
        /** @var File&MockObject $mockFile */
        $mockFile = $this->createMock(File::class);
        $input->avatarFile = $mockFile;

        return $input;
    }

    private function createDomainUser(): DomainUser
    {
        return DomainUser::register(
            id: UserId::fromString(Uuid::uuid4()->toString()),
            username: new Username('testuser'),
            email: new EmailAddress('test@example.com'),
            password: new HashedPassword('hash'),
            preferences: Preferences::fromArray(['lang' => 'fr']),
            now: new DateTimeImmutable(),
        );
    }
}
