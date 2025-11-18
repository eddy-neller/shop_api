<?php

declare(strict_types=1);

namespace App\Tests\Unit\State\User\Me;

use ApiPlatform\Metadata\Operation;
use App\Entity\User\User;
use App\Service\InfoCodes;
use App\State\User\Me\MeProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserInterface;

final class MeProviderTest extends KernelTestCase
{
    private Security&MockObject $security;

    private User&MockObject $user;

    private Operation&MockObject $operation;

    private MeProvider $provider;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->user = $this->createMock(User::class);
        $this->operation = $this->createMock(Operation::class);

        $this->provider = new MeProvider(
            $this->security
        );
    }

    public function testProvideReturnsCurrentUser(): void
    {
        $user = $this->user;

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $operation = $this->operation;
        $result = $this->provider->provide($operation);

        $this->assertSame($user, $result);
    }

    public function testProvideReturnsCurrentUserWithUriVariablesAndContext(): void
    {
        $user = $this->user;
        $uriVariables = ['id' => '123'];
        $context = ['custom' => 'value'];

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $operation = $this->operation;
        $result = $this->provider->provide($operation, $uriVariables, $context);

        $this->assertSame($user, $result);
    }

    public function testProvideThrowsAccessDeniedExceptionForUnauthenticatedUser(): void
    {
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(InfoCodes::USER['USER_AUTH_NOT_FOUND']);

        $operation = $this->operation;
        $this->provider->provide($operation);
    }

    public function testProvideThrowsAccessDeniedExceptionForNonUserObject(): void
    {
        /** @var UserInterface&MockObject $nonUserObject */
        $nonUserObject = $this->createMock(UserInterface::class);

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($nonUserObject);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(InfoCodes::USER['USER_AUTH_NOT_FOUND']);

        $operation = $this->operation;
        $this->provider->provide($operation);
    }

    public function testProvideThrowsAccessDeniedExceptionForDifferentUserInterface(): void
    {
        /** @var UserInterface&MockObject $otherUserInterface */
        $otherUserInterface = $this->createMock(UserInterface::class);

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($otherUserInterface);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(InfoCodes::USER['USER_AUTH_NOT_FOUND']);

        $operation = $this->operation;
        $this->provider->provide($operation);
    }

    public function testProvideThrowsAccessDeniedExceptionForAnonymousUser(): void
    {
        /** @var UserInterface&MockObject $anonymousUser */
        $anonymousUser = $this->createMock(UserInterface::class);

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($anonymousUser);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(InfoCodes::USER['USER_AUTH_NOT_FOUND']);

        $operation = $this->operation;
        $this->provider->provide($operation);
    }

    public function testProvideWithMultipleCallsReturnsSameUser(): void
    {
        $user = $this->user;

        $this->security->expects($this->exactly(2))
            ->method('getUser')
            ->willReturn($user);

        $operation = $this->operation;

        $result1 = $this->provider->provide($operation);
        $result2 = $this->provider->provide($operation);

        $this->assertSame($user, $result1);
        $this->assertSame($user, $result2);
        $this->assertSame($result1, $result2);
    }

    public function testProvideWithDifferentOperationsReturnsSameUser(): void
    {
        $user = $this->user;
        /** @var Operation&MockObject $operation1 */
        $operation1 = $this->createMock(Operation::class);
        /** @var Operation&MockObject $operation2 */
        $operation2 = $this->createMock(Operation::class);

        $this->security->expects($this->exactly(2))
            ->method('getUser')
            ->willReturn($user);

        $result1 = $this->provider->provide($operation1);
        $result2 = $this->provider->provide($operation2);

        $this->assertSame($user, $result1);
        $this->assertSame($user, $result2);
        $this->assertSame($result1, $result2);
    }

    public function testProvideReturnsUserInstance(): void
    {
        $user = $this->user;

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $operation = $this->operation;
        $result = $this->provider->provide($operation);

        $this->assertInstanceOf(User::class, $result);
    }

    public function testProvideWithNullOperationStillReturnsUser(): void
    {
        $user = $this->user;

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($user, $result);
    }
}
