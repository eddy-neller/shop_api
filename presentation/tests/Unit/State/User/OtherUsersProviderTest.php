<?php

declare(strict_types=1);

namespace App\Presentation\Tests\Unit\State\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Infrastructure\Entity\User\User;
use App\Presentation\Shared\State\PaginatedCollectionProvider;
use App\Presentation\User\State\OtherUsersProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class OtherUsersProviderTest extends KernelTestCase
{
    private ProviderInterface&MockObject $paginatedProvider;

    private Security&MockObject $security;

    private Operation&MockObject $operation;

    private User&MockObject $user;

    private OtherUsersProvider $otherUsersProvider;

    protected function setUp(): void
    {
        $this->paginatedProvider = $this->createMock(PaginatedCollectionProvider::class);
        $this->security = $this->createMock(Security::class);
        $this->operation = $this->createMock(Operation::class);
        $this->user = $this->createMock(User::class);

        $this->otherUsersProvider = new OtherUsersProvider(
            $this->paginatedProvider,
            $this->security
        );
    }

    public function testProvideExcludesCurrentUserFromResults(): void
    {
        $userId = Uuid::uuid4();

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($this->user);

        $this->user->expects($this->once())
            ->method('getId')
            ->willReturn($userId);

        $this->paginatedProvider->expects($this->once())
            ->method('provide')
            ->with(
                $this->operation,
                [],
                $this->callback(function ($context) use ($userId) {
                    $this->assertArrayHasKey('exclude_id', $context['filters']);
                    $this->assertSame($context['filters']['exclude_id'], [$userId->toString()]);

                    return true;
                })
            )
            ->willReturn([]);

        $result = $this->otherUsersProvider->provide($this->operation, [], []);

        $this->assertIsArray($result);
    }

    public function testProvideSetsDefaultOrderByUsername(): void
    {
        $userId = Uuid::uuid4();

        $this->security->method('getUser')->willReturn($this->user);
        $this->user->method('getId')->willReturn($userId);

        $this->paginatedProvider->expects($this->once())
            ->method('provide')
            ->with(
                $this->operation,
                [],
                $this->callback(function ($context) {
                    $this->assertArrayHasKey('username', $context['filters']['order']);
                    $this->assertSame('ASC', $context['filters']['order']['username']);

                    return true;
                })
            )
            ->willReturn([]);

        $this->otherUsersProvider->provide($this->operation);
    }

    public function testProvideDoesNotOverrideExistingOrder(): void
    {
        $userId = Uuid::uuid4();
        $existingContext = [
            'filters' => [
                'order' => [
                    'email' => 'DESC',
                ],
            ],
        ];

        $this->security->method('getUser')->willReturn($this->user);
        $this->user->method('getId')->willReturn($userId);

        $this->paginatedProvider->expects($this->once())
            ->method('provide')
            ->with(
                $this->operation,
                [],
                $this->callback(function ($context) use ($userId) {
                    $this->assertArrayHasKey('email', $context['filters']['order']);
                    $this->assertSame('DESC', $context['filters']['order']['email']);
                    $this->assertArrayHasKey('username', $context['filters']['order']);
                    $this->assertSame('ASC', $context['filters']['order']['username']);
                    $this->assertArrayHasKey('exclude_id', $context['filters']);
                    $this->assertSame($context['filters']['exclude_id'], [$userId->toString()]);

                    return true;
                })
            )
            ->willReturn([]);

        $this->otherUsersProvider->provide($this->operation, [], $existingContext);
    }

    public function testProvideDoesNotOverrideExistingUsernameOrder(): void
    {
        $userId = Uuid::uuid4();
        $existingContext = [
            'filters' => [
                'order' => [
                    'username' => 'DESC',
                ],
            ],
        ];

        $this->security->method('getUser')->willReturn($this->user);
        $this->user->method('getId')->willReturn($userId);

        $this->paginatedProvider->expects($this->once())
            ->method('provide')
            ->with(
                $this->operation,
                [],
                $this->callback(function ($context) {
                    $this->assertArrayHasKey('username', $context['filters']['order']);
                    $this->assertSame('DESC', $context['filters']['order']['username']);

                    return true;
                })
            )
            ->willReturn([]);

        $this->otherUsersProvider->provide($this->operation, [], $existingContext);
    }

    public function testProvideWithUriVariables(): void
    {
        $userId = Uuid::uuid4();
        $uriVariables = ['id' => '123'];

        $this->security->method('getUser')->willReturn($this->user);
        $this->user->method('getId')->willReturn($userId);

        $this->paginatedProvider->expects($this->once())
            ->method('provide')
            ->with($this->operation, $uriVariables, $this->anything())
            ->willReturn([]);

        $result = $this->otherUsersProvider->provide($this->operation, $uriVariables, []);

        $this->assertIsArray($result);
    }

    public function testProvideReturnsDataFromPaginatedProvider(): void
    {
        $userId = Uuid::uuid4();
        $expectedData = ['user1', 'user2'];

        $this->security->method('getUser')->willReturn($this->user);
        $this->user->method('getId')->willReturn($userId);

        $this->paginatedProvider->expects($this->once())
            ->method('provide')
            ->willReturn($expectedData);

        $result = $this->otherUsersProvider->provide($this->operation, [], []);

        $this->assertSame($expectedData, $result);
    }

    public function testProvideMergesExcludeIdWithExistingFilters(): void
    {
        $userId = Uuid::uuid4();
        $existingContext = [
            'filters' => [
                'status' => 1,
                'roles' => ['ROLE_USER'],
            ],
        ];

        $this->security->method('getUser')->willReturn($this->user);
        $this->user->method('getId')->willReturn($userId);

        $this->paginatedProvider->expects($this->once())
            ->method('provide')
            ->with(
                $this->operation,
                [],
                $this->callback(function ($context) use ($userId) {
                    $this->assertArrayHasKey('exclude_id', $context['filters']);
                    $this->assertSame($context['filters']['exclude_id'], [$userId->toString()]);
                    $this->assertArrayHasKey('status', $context['filters']);
                    $this->assertSame(1, $context['filters']['status']);
                    $this->assertArrayHasKey('roles', $context['filters']);

                    return true;
                })
            )
            ->willReturn([]);

        $this->otherUsersProvider->provide($this->operation, [], $existingContext);
    }

    public function testProvideThrowsAccessDeniedExceptionWhenNotAuthenticated(): void
    {
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->expectException(AccessDeniedException::class);

        $this->otherUsersProvider->provide($this->operation);
    }
}
