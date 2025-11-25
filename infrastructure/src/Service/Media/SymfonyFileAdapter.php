<?php

declare(strict_types=1);

namespace App\Infrastructure\Service\Media;

use App\Application\Shared\Port\FileInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Adapter qui wrap un File Symfony pour l'adapter à FileInterface.
 * Permet de convertir un File Symfony en FileInterface pour la couche Application.
 */
final class SymfonyFileAdapter implements FileInterface
{
    public function __construct(
        private readonly File $file,
    ) {
    }

    public function getPathname(): string
    {
        return $this->file->getPathname();
    }

    public function getMimeType(): string
    {
        return $this->file->getMimeType() ?? 'application/octet-stream';
    }

    public function getSize(): int
    {
        return $this->file->getSize();
    }

    public function getClientOriginalName(): string
    {
        // Si c'est un UploadedFile, on récupère le nom original
        if ($this->file instanceof UploadedFile) {
            return $this->file->getClientOriginalName();
        }

        // Sinon, on utilise le nom du fichier
        return $this->file->getFilename();
    }

    public function getExtension(): string
    {
        return $this->file->getExtension();
    }

    public function isValid(): bool
    {
        if ($this->file instanceof UploadedFile) {
            return $this->file->isValid();
        }

        return $this->file->isFile() && $this->file->isReadable();
    }
}
