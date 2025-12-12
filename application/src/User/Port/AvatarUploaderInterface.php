<?php

declare(strict_types=1);

namespace App\Application\User\Port;

use App\Application\Shared\Port\FileInterface;
use App\Domain\User\Identity\ValueObject\UserId;

interface AvatarUploaderInterface
{
    public function upload(UserId $userId, FileInterface $file): array;
}
