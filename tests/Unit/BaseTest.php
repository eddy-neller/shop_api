<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use Faker\Factory;
use Faker\Generator;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use ReflectionException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @codeCoverageIgnore
 */
abstract class BaseTest extends KernelTestCase
{
    protected const array MEDIA_TYPE = [
        'IMAGE' => 'IMAGE',
        'SON' => 'SON',
    ];

    protected Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();
    }

    /**
     * @template T of object
     *
     * @param class-string<T>                                       $className
     * @param array<string,mixed>|array<string,array<string,mixed>> $params
     *
     * @return MockObject&T
     */
    final protected function mock(string $className, array $params = [], ?string $name = null): MockObject
    {
        $mock = $this->createMock($className);

        if (!is_null($name)) {
            if (!empty($params[$name])) {
                foreach ($params[$name] as $key => $datum) {
                    $mock->method($key)->willReturn($datum);
                }
            }
        } elseif (!empty($params)) {
            foreach ($params as $key => $param) {
                $mock->method($key)->willReturn($param);
            }
        }

        return $mock;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return MockObject&T
     */
    final protected function mockExpectMethods(string $className, array $methods = [], int $invocations = 1): MockObject
    {
        $mock = $this->mock($className);

        foreach ($methods as $method => $value) {
            $mock->expects($this->atLeast($invocations))->method($method)->willReturn($value);
        }

        return $mock;
    }

    /**
     * @throws ReflectionException
     */
    final public static function generateId(object $object, ?int $id = null): mixed
    {
        $faker = Factory::create();
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty('id');
        $property->setValue($object, $id ?? $faker->randomDigitNotNull());

        return $object;
    }

    final protected function createDateInterval(?int $day = null, ?int $hour = null, ?int $minute = null): string
    {
        $interval = 'P';

        if ($day) {
            $interval .= $day . 'D';

            /* If $hour or $minute are not defined, no need to continue. */
            if (!$hour && !$minute) {
                return $interval;
            }
        }

        $interval .= 'T';
        $interval = $hour ? $interval . $hour . 'H' : $interval;

        return $minute ? $interval . $minute . 'M' : $interval;
    }

    /**
     * Call protected/private method of a class.
     *
     * @throws ReflectionException
     */
    protected function callEncapsulatedMethod(object $object, string $methodName, array $params = []): mixed
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);

        return $method->invokeArgs($object, $params);
    }

    final public function getValidatorInterface(bool $hasViolations = false, bool $hasParams = false): object
    {
        $validator = $this->mock(ValidatorInterface::class);

        if ($hasViolations) {
            $validator
                ->method('validate')
                ->willReturn(
                    new ConstraintViolationList([
                        new ConstraintViolation(
                            $this->faker->word(),
                            null,
                            [],
                            null,
                            $hasParams ? $this->faker->slug() : null,
                            null
                        ),
                    ])
                );

            return $validator;
        }

        $validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        return $validator;
    }

    protected static function streamDir(string $dirname, array $filenames = [], ?vfsStreamDirectory $root = null): string
    {
        $root = $root ?? vfsStream::setup();
        $dir = vfsStream::newDirectory($dirname)->at($root);

        foreach ($filenames as $filename => $content) {
            vfsStream::newFile($filename)->at($dir)->setContent($content);
        }

        return $dir->url();
    }

    protected static function streamImageContent(): string
    {
        $base64Image = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/wcAAwAB/8z9R5UAAAAASUVORK5CYII=';

        return base64_decode($base64Image);
    }

    /**
     * Créer un fichier UploadedFile temporaire à partir d'un fichier de test.
     *
     * @param string $filename  Nom du fichier dans assets/tests/{images|sons}
     * @param string $suffix    Suffixe unique pour le fichier temporaire
     * @param string $typeMedia Type de média (IMAGE ou SON)
     *
     * @return UploadedFile Fichier temporaire prêt pour l'upload
     */
    protected function getPhysicalTempFile(string $filename, string $suffix, string $typeMedia): UploadedFile
    {
        switch ($typeMedia) {
            case self::MEDIA_TYPE['IMAGE']:
            default:
                $dir = 'images';
                break;
            case self::MEDIA_TYPE['SON']:
                $dir = 'sons';
                break;
        }

        $cleanSuffix = explode('::', $suffix)[1];

        $tmpFilePath = sys_get_temp_dir() . '/' . $cleanSuffix . '.jpg';

        copy(
            static::getContainer()->getParameter('kernel.project_dir') . '/assets/tests/' . $dir . '/' . $filename,
            $tmpFilePath
        );

        return new UploadedFile($tmpFilePath, $filename);
    }

    /**
     * Helper pour créer un fichier image de test.
     *
     * @param string $filename Nom du fichier image dans assets/tests/images
     * @param string $suffix   Suffixe unique pour identifier le fichier temporaire
     */
    protected function getImage(string $filename, string $suffix): UploadedFile
    {
        return $this->getPhysicalTempFile($filename, $suffix, self::MEDIA_TYPE['IMAGE']);
    }

    /**
     * Helper pour créer un fichier son de test.
     *
     * @param string $filename Nom du fichier son dans assets/tests/sons
     * @param string $suffix   Suffixe unique pour identifier le fichier temporaire
     */
    protected function getSong(string $filename, string $suffix): UploadedFile
    {
        return $this->getPhysicalTempFile($filename, $suffix, self::MEDIA_TYPE['SON']);
    }
}
