<?php

declare(strict_types=1);

namespace App\Application\User\Port;

use App\Application\Shared\Port\FileInterface;
use App\Domain\User\ValueObject\UserId;

interface AvatarUploaderInterface
{
    /**
     * Upload un fichier avatar pour un utilisateur.
     *
     * @return array{fileName: ?string, url: ?string}
     */
    public function upload(UserId $userId, FileInterface $file): array;
}
