<?php

declare(strict_types=1);

namespace App\Application\User\UseCase\Query\DisplayListUser;

use App\Application\Shared\CQRS\Query\CacheableQueryInterface;
use App\Application\Shared\ReadModel\Pagination;

final readonly class DisplayListUserQuery implements CacheableQueryInterface
{
    private const int CACHE_TTL_SECONDS = 3600;

    public function __construct(
        public Pagination $pagination,
        public ?string $username,
        public ?string $email,
        public array $orderBy = [],
    ) {
    }

    public function cacheKey(): string
    {
        $payload = [
            'page' => $this->pagination->page,
            'itemsPerPage' => $this->pagination->itemsPerPage,
            'username' => $this->username,
            'email' => $this->email,
            'orderBy' => $this->normalizedOrderBy(),
        ];

        $encoded = serialize($payload);

        return 'user:list:' . hash('sha256', $encoded);
    }

    public function cacheTtl(): int
    {
        return self::CACHE_TTL_SECONDS;
    }

    public function cacheTags(): array
    {
        return ['users-collection'];
    }

    private function normalizedOrderBy(): array
    {
        $orderBy = $this->orderBy;
        ksort($orderBy);

        return $orderBy;
    }
}
