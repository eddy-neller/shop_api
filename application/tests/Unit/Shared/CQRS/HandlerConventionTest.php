<?php

declare(strict_types=1);

namespace App\Application\Tests\Unit\Shared\CQRS;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

final class HandlerConventionTest extends TestCase
{
    public function testAllCommandsHaveHandlers(): void
    {
        $baseDir = dirname(__DIR__, 4) . '/src';
        $commandFiles = $this->findFiles($baseDir, '/Command\\.php$/');

        foreach ($commandFiles as $file) {
            $commandClass = $this->classFromFile($file, $baseDir, 'App\\Application\\');

            $this->assertTrue(class_exists($commandClass), sprintf('Command class not found for file: %s', $file));

            $handlerClass = preg_replace('/Command$/', 'CommandHandler', $commandClass);

            $this->assertTrue(class_exists($handlerClass), sprintf('Missing handler for command: %s (expected %s)', $commandClass, $handlerClass));

            $this->assertTrue(method_exists($handlerClass, 'handle'), sprintf('Handler %s must define handle()', $handlerClass));
        }
    }

    public function testAllQueriesHaveHandlers(): void
    {
        $baseDir = dirname(__DIR__, 4) . '/src';
        $queryFiles = $this->findFiles($baseDir, '/Query\\.php$/');

        foreach ($queryFiles as $file) {
            $queryClass = $this->classFromFile($file, $baseDir, 'App\\Application\\');

            $this->assertTrue(class_exists($queryClass), sprintf('Query class not found for file: %s', $file));

            $handlerClass = preg_replace('/Query$/', 'QueryHandler', $queryClass);

            $this->assertTrue(class_exists($handlerClass), sprintf('Missing handler for query: %s (expected %s)', $queryClass, $handlerClass));

            $this->assertTrue(method_exists($handlerClass, 'handle'), sprintf('Handler %s must define handle()', $handlerClass));
        }
    }

    /**
     * @return array<int, string>
     */
    private function findFiles(string $baseDir, string $pattern): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($baseDir)
        );

        $files = new RegexIterator($iterator, $pattern);
        $results = [];

        foreach ($files as $file) {
            $results[] = $file->getPathname();
        }

        return $results;
    }

    private function classFromFile(string $file, string $baseDir, string $baseNamespace): string
    {
        $relative = substr($file, strlen($baseDir) + 1);

        return $baseNamespace . str_replace(
            ['/', '.php'],
            ['\\', ''],
            $relative
        );
    }
}
