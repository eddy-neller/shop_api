<?php

namespace App\Infrastructure\Serializer\Normalizer;

use App\Entity\Shop\Product;
use App\Infrastructure\Entity\User\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

class ResolveFileUrlNormalizer implements NormalizerInterface
{
    private const string ALREADY_CALLED = 'FILE_URL_NORMALIZER_ALREADY_CALLED';

    public function __construct(
        #[Autowire(service: 'api_platform.serializer.normalizer.item')]
        private readonly NormalizerInterface $normalizer,
        private readonly StorageInterface $storage,
    ) {
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && (
            $data instanceof Product
            || $data instanceof User
        );
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|\ArrayObject|bool|float|int|string|null
    {
        $context[self::ALREADY_CALLED] = true;

        if ($object instanceof User) {
            $object->avatarUrl = $this->storage->resolveUri($object, 'avatarFile');
        } else {
            $object->imageUrl = $this->storage->resolveUri($object, 'imageFile');
        }

        return $this->normalizer->normalize($object, $format, $context);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            'object' => null,
            '*' => false,
            Product::class => true,
            User::class => true,
        ];
    }
}
