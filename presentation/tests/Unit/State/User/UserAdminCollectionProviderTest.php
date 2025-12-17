<?php

declare(strict_types=1);

namespace App\Presentation\Tests\Unit\State\User;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\State\ProviderInterface;
use App\Application\User\Port\AvatarUrlResolverInterface;
use App\Domain\User\Profile\ValueObject\Avatar;
use App\Infrastructure\Entity\User\User as DoctrineUser;
use App\Presentation\User\ApiResource\UserResource;
use App\Presentation\User\State\UserAdminCollectionProvider;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use stdClass;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(UserAdminCollectionProvider::class)]
final class UserAdminCollectionProviderTest extends TestCase
{
    public function testItMapsDoctrineUsersToUserResourceAndKeepsPaginationAttributes(): void
    {
        $doctrineUser = new DoctrineUser();
        $doctrineUser->setId(Uuid::uuid4());
        $doctrineUser->setUsername('john');
        $doctrineUser->setEmail('john@example.com');
        $doctrineUser->setRoles(['ROLE_ADMIN']);
        $doctrineUser->setStatus(1);
        $doctrineUser->setAvatarName('avatar.jpg');
        $doctrineUser->setCreatedAt(new DateTimeImmutable('2025-01-01 10:00:00'));
        $doctrineUser->setUpdatedAt(new DateTimeImmutable('2025-01-02 10:00:00'));

        $request = new Request();

        $innerProvider = $this->createMock(ProviderInterface::class);
        $innerProvider
            ->expects($this->once())
            ->method('provide')
            ->willReturnCallback(static function (GetCollection $operation, array $uriVariables, array $context) use ($doctrineUser, $request): array {
                self::assertSame($request, $context['request'] ?? null);

                $filters = $context['filters'] ?? null;
                self::assertIsArray($filters);
                self::assertSame(['avatarFile' => 'desc', 'createdAt' => 'asc'], $filters['order'] ?? null);

                $request->attributes->set('_total_items', 1);
                $request->attributes->set('_total_pages', 1);

                return [$doctrineUser];
            });

        $avatarUrlResolver = $this->createMock(AvatarUrlResolverInterface::class);
        $avatarUrlResolver
            ->expects($this->once())
            ->method('resolve')
            ->with(self::callback(static function (Avatar $avatar): bool {
                return 'avatar.jpg' === $avatar->fileName();
            }))
            ->willReturn('/uploads/images/user/avatar/avatar.jpg');

        $provider = new UserAdminCollectionProvider(
            provider: $innerProvider,
            avatarUrlResolver: $avatarUrlResolver,
        );

        $result = $provider->provide(
            new GetCollection(name: 'users-admin-col'),
            context: [
                'request' => $request,
                'filters' => [
                    'order' => [
                        'avatarFile' => 'desc',
                        'createdAt' => 'asc',
                    ],
                ],
            ],
        );

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(UserResource::class, $result[0]);
        $this->assertSame('john', $result[0]->username);
        $this->assertSame('/uploads/images/user/avatar/avatar.jpg', $result[0]->avatarUrl);
        $this->assertSame(1, $request->attributes->get('_total_items'));
        $this->assertSame(1, $request->attributes->get('_total_pages'));
    }

    public function testItReturnsProviderResultWhenNotIterable(): void
    {
        $innerProvider = $this->createMock(ProviderInterface::class);
        $payload = new stdClass();

        $innerProvider
            ->expects($this->once())
            ->method('provide')
            ->willReturn($payload);

        $avatarUrlResolver = $this->createMock(AvatarUrlResolverInterface::class);

        $provider = new UserAdminCollectionProvider(
            provider: $innerProvider,
            avatarUrlResolver: $avatarUrlResolver,
        );

        $result = $provider->provide(new GetCollection(name: 'users-admin-col'));

        $this->assertSame($payload, $result);
    }
}
