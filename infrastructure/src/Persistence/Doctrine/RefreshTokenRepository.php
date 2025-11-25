<?php

namespace App\Infrastructure\Persistence\Doctrine;

use App\Infrastructure\Entity\RefreshToken;
use DateTime;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Gesdinet\JWTRefreshTokenBundle\Doctrine\RefreshTokenRepositoryInterface;

/**
 * @extends ServiceEntityRepository<RefreshToken>
 *
 * @implements RefreshTokenRepositoryInterface<RefreshToken>
 */
class RefreshTokenRepository extends ServiceEntityRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    /**
     * @param DateTimeInterface|null $datetime
     *
     * @return array<RefreshToken>
     */
    public function findInvalid($datetime = null)
    {
        $expirationLimit = $datetime instanceof DateTimeInterface ? $datetime : new DateTime();

        // On reste proche de l'implémentation officielle tout en conservant l'intégration Doctrine moderne.
        return $this->createQueryBuilder('refreshToken')
            ->where('refreshToken.valid < :limit')
            ->setParameter('limit', $expirationLimit)
            ->getQuery()
            ->getResult();
    }
}
