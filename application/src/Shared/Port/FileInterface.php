<?php

declare(strict_types=1);

namespace App\Application\Shared\Port;

/**
 * Interface abstraite pour représenter un fichier uploadé.
 * Permet de découpler la couche Application de Symfony.
 */
interface FileInterface
{
    /**
     * Retourne le chemin complet du fichier.
     */
    public function getPathname(): string;

    /**
     * Retourne le type MIME du fichier.
     */
    public function getMimeType(): string;

    /**
     * Retourne la taille du fichier en octets.
     */
    public function getSize(): int;

    /**
     * Retourne le nom original du fichier (nom fourni par le client).
     */
    public function getClientOriginalName(): string;

    /**
     * Retourne l'extension du fichier.
     */
    public function getExtension(): string;

    /**
     * Vérifie si le fichier est valide.
     */
    public function isValid(): bool;
}
