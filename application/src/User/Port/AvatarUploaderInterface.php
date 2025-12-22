<?php

declare(strict_types=1);

namespace App\Application\User\Port;

use App\Application\Shared\Port\FileInterface;
use App\Domain\User\Identity\ValueObject\UserId;
use App\Domain\User\Profile\ValueObject\Avatar;

interface AvatarUploaderInterface
{
    public function upload(UserId $id, FileInterface $file): Avatar;
}
