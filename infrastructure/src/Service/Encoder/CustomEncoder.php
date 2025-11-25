<?php

namespace App\Infrastructure\Service\Encoder;

use Exception;
use ParagonIE\Halite\KeyFactory;
use ParagonIE\Halite\Symmetric\Crypto;
use ParagonIE\HiddenString\HiddenString;
use Random\RandomException;
use RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Throwable;

class CustomEncoder
{
    private const array KEY_LENGTH = [
        'min' => 12,
        'max' => 18,
    ];

    /* These are messages thrown when we try to decrypt unencrypted data */
    private const array DECRYPT_UNENCRYPTED_ERROR_MESSAGES = [
        'Invalid character encoding',
        'Message is too short',
        'Invalid version tag',
    ];

    private mixed $salt;

    private mixed $key;

    public function __construct(protected ParameterBagInterface $parameterBag)
    {
        $this->key = $parameterBag->get('agency_key');
        $this->salt = $parameterBag->get('agency_crypto_salt');
    }

    public static function encodeMe(string $value): string
    {
        return base64_encode(bin2hex($value));
    }

    public static function decodeMe(string $value): string|false
    {
        $decoded = base64_decode($value, true);

        if (false === $decoded || 0 !== strlen($decoded) % 2) {
            return false;
        }

        return hex2bin($decoded);
    }

    /**
     * @throws RandomException
     */
    public static function randomString(int $length = 64): string
    {
        $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $pieces = [];
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $pieces[] = $keyspace[random_int(0, $max)];
        }

        return implode('', $pieces);
    }

    /**
     * @throws RandomException
     */
    public static function randomCode(): int
    {
        return random_int(100000, 999999);
    }

    public function encrypt(string $data): ?string
    {
        try {
            $this->validateAgencyKey();
            $encryptionKey = KeyFactory::deriveEncryptionKey(new HiddenString($this->key), $this->salt);

            return Crypto::encrypt(new HiddenString($data), $encryptionKey);
        } catch (Throwable $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }
    }

    public function decrypt(string $data): ?string
    {
        $this->validateAgencyKey();

        try {
            $encryptionKey = KeyFactory::deriveEncryptionKey(new HiddenString($this->key), $this->salt);

            return Crypto::decrypt($data, $encryptionKey)->getString();
        } catch (Exception $e) {
            // On ne relance que si ce n'est pas une erreur liée à une donnée non chiffrée
            foreach (self::DECRYPT_UNENCRYPTED_ERROR_MESSAGES as $expectedMessage) {
                if (str_contains($e->getMessage(), $expectedMessage)) {
                    return null;
                }
            }

            throw new RuntimeException('Decryption failed', 0, $e);
        }
    }

    private function validateAgencyKey(): void
    {
        if (strlen($this->key) < self::KEY_LENGTH['min'] || strlen($this->key) > self::KEY_LENGTH['max']) {
            throw new RuntimeException('Invalid agency key');
        }
    }
}
