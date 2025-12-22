<?php

declare(strict_types=1);

namespace App\Application\Tests\Unit\User\UseCase\Query;

use App\Application\Shared\ReadModel\Pagination;
use App\Application\User\Port\UserRepositoryInterface;
use App\Application\User\ReadModel\UserList;
use App\Application\User\UseCase\Query\DisplayListUser\DisplayListUserQuery;
use App\Application\User\UseCase\Query\DisplayListUser\DisplayListUserQueryHandler;
use App\Domain\User\Identity\ValueObject\EmailAddress;
use App\Domain\User\Identity\ValueObject\UserId;
use App\Domain\User\Identity\ValueObject\Username;
use App\Domain\User\Model\User;
use App\Domain\User\Preference\ValueObject\Preferences;
use App\Domain\User\Security\ValueObject\HashedPassword;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DisplayListUserTest extends TestCase
{
    private UserRepositoryInterface&MockObject $repository;

    private DisplayListUserQueryHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->handler = new DisplayListUserQueryHandler($this->repository);
    }

    public function testHandleReturnsUsersAndPagination(): void
    {
        $query = new DisplayListUserQuery(
            pagination: Pagination::fromValues(2, 5),
            username: 'john',
            email: null,
            orderBy: ['username' => 'ASC'],
        );

        $user = $this->createUser(UserId::fromString('550e8400-e29b-41d4-a716-446655440000'));
        $list = new UserList([$user], 10, 2);

        $this->repository->expects($this->once())
            ->method('list')
            ->with('john', null, ['username' => 'ASC'], 2, 5)
            ->willReturn($list);

        $output = $this->handler->handle($query);

        $this->assertSame([$user], $output->users);
        $this->assertSame(10, $output->totalItems);
        $this->assertSame(2, $output->totalPages);
    }

    public function testHandleAppliesDefaultsWhenValuesAreInvalid(): void
    {
        $query = new DisplayListUserQuery(
            pagination: Pagination::fromValues(0, 0),
            username: null,
            email: null,
            orderBy: [],
        );

        $user = $this->createUser(UserId::fromString('550e8400-e29b-41d4-a716-446655440001'));
        $list = new UserList([$user], 1, 1);

        $this->repository->expects($this->once())
            ->method('list')
            ->with(null, null, ['createdAt' => 'DESC'], 1, 30)
            ->willReturn($list);

        $output = $this->handler->handle($query);

        $this->assertSame([$user], $output->users);
    }

    private function createUser(UserId $userId): User
    {
        return User::register(
            id: $userId,
            username: new Username('testuser'),
            email: new EmailAddress('test@example.com'),
            password: new HashedPassword('hash'),
            preferences: Preferences::fromArray(['lang' => 'fr']),
            now: new DateTimeImmutable(),
        );
    }
}
