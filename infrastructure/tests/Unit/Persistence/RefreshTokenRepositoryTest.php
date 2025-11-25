<?php

declare(strict_types=1);

namespace App\Infrastructure\Tests\Unit\Persistence;

use App\Infrastructure\Entity\RefreshToken;
use App\Infrastructure\Persistence\Doctrine\RefreshTokenRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RefreshTokenRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private RefreshTokenRepository $repo;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->em = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        /** @var RefreshTokenRepository $repo */
        $repo = $this->em->getRepository(RefreshToken::class);
        $this->repo = $repo;
    }

    public function testFindInvalidReturnsExpiredTokens(): void
    {
        // Créer un token expiré (date dans le passé)
        $expiredToken = $this->createRefreshToken([
            'refreshToken' => 'expired-token-123',
            'username' => 'user@example.com',
            'valid' => new DateTimeImmutable('-1 day')->format('Y-m-d H:i:s'),
        ]);
        $this->em->persist($expiredToken);

        // Créer un token valide (date dans le futur)
        $validToken = $this->createRefreshToken([
            'refreshToken' => 'valid-token-456',
            'username' => 'user2@example.com',
            'valid' => new DateTimeImmutable('+1 day')->format('Y-m-d H:i:s'),
        ]);
        $this->em->persist($validToken);

        $this->em->flush();

        // Rechercher les tokens expirés
        $invalidTokens = $this->repo->findInvalid();

        $this->assertCount(1, $invalidTokens);
        $this->assertSame('expired-token-123', $invalidTokens[0]->getRefreshToken());

        // Cleanup
        $this->em->remove($expiredToken);
        $this->em->remove($validToken);
        $this->em->flush();
    }

    public function testFindInvalidWithCustomDate(): void
    {
        // Token qui expire dans 2 heures
        $tokenExpiring2Hours = $this->createRefreshToken([
            'refreshToken' => 'token-2h-789',
            'username' => 'user3@example.com',
            'valid' => new DateTimeImmutable('+2 hours')->format('Y-m-d H:i:s'),
        ]);
        $this->em->persist($tokenExpiring2Hours);

        // Token qui expire dans 5 heures
        $tokenExpiring5Hours = $this->createRefreshToken([
            'refreshToken' => 'token-5h-101',
            'username' => 'user4@example.com',
            'valid' => new DateTimeImmutable('+5 hours')->format('Y-m-d H:i:s'),
        ]);
        $this->em->persist($tokenExpiring5Hours);

        $this->em->flush();

        // Rechercher les tokens expirés avant +3 heures
        $customDate = new DateTime('+3 hours');
        $invalidTokens = $this->repo->findInvalid($customDate);

        $this->assertCount(1, $invalidTokens);
        $this->assertSame('token-2h-789', $invalidTokens[0]->getRefreshToken());

        // Cleanup
        $this->em->remove($tokenExpiring2Hours);
        $this->em->remove($tokenExpiring5Hours);
        $this->em->flush();
    }

    public function testFindInvalidReturnsEmptyArrayWhenNoExpiredTokens(): void
    {
        // Créer uniquement des tokens valides
        $validToken1 = $this->createRefreshToken([
            'refreshToken' => 'valid-token-111',
            'username' => 'user5@example.com',
            'valid' => new DateTimeImmutable('+1 day')->format('Y-m-d H:i:s'),
        ]);
        $this->em->persist($validToken1);

        $validToken2 = $this->createRefreshToken([
            'refreshToken' => 'valid-token-222',
            'username' => 'user6@example.com',
            'valid' => new DateTimeImmutable('+2 days')->format('Y-m-d H:i:s'),
        ]);
        $this->em->persist($validToken2);

        $this->em->flush();

        $invalidTokens = $this->repo->findInvalid();

        $this->assertIsArray($invalidTokens);
        $this->assertCount(0, $invalidTokens);

        // Cleanup
        $this->em->remove($validToken1);
        $this->em->remove($validToken2);
        $this->em->flush();
    }

    public function testFindInvalidReturnsMultipleExpiredTokens(): void
    {
        // Créer plusieurs tokens expirés
        $expiredToken1 = $this->createRefreshToken([
            'refreshToken' => 'expired-token-333',
            'username' => 'user7@example.com',
            'valid' => new DateTimeImmutable('-2 days')->format('Y-m-d H:i:s'),
        ]);
        $this->em->persist($expiredToken1);

        $expiredToken2 = $this->createRefreshToken([
            'refreshToken' => 'expired-token-444',
            'username' => 'user8@example.com',
            'valid' => new DateTimeImmutable('-1 hour')->format('Y-m-d H:i:s'),
        ]);
        $this->em->persist($expiredToken2);

        $expiredToken3 = $this->createRefreshToken([
            'refreshToken' => 'expired-token-555',
            'username' => 'user9@example.com',
            'valid' => new DateTimeImmutable('-3 days')->format('Y-m-d H:i:s'),
        ]);
        $this->em->persist($expiredToken3);

        $this->em->flush();

        $invalidTokens = $this->repo->findInvalid();

        $this->assertCount(3, $invalidTokens);

        // Cleanup
        $this->em->remove($expiredToken1);
        $this->em->remove($expiredToken2);
        $this->em->remove($expiredToken3);
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->em->close();
    }

    private function createRefreshToken(array $data): RefreshToken
    {
        $token = new RefreshToken();
        $token->setRefreshToken($data['refreshToken']);
        $token->setUsername($data['username']);
        $token->setValid(new DateTime($data['valid']));

        return $token;
    }
}
