<?php

declare(strict_types=1);

namespace App\Infrastructure\Tests\Unit\Service\Encoder;

use App\Infrastructure\Service\Encoder\CustomEncoder;
use Random\RandomException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class CustomEncoderTest extends KernelTestCase
{
    private CustomEncoder $customEncoder;

    protected function setUp(): void
    {
        parent::setUp();

        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')->willReturnMap([
            ['agency_key', 'agency_key_123'], // 13 caractères, valide
            ['agency_crypto_salt', '1234567890abcdef'],
        ]);

        $this->customEncoder = new CustomEncoder($parameterBag);
    }

    public function testEncodeMe(): void
    {
        $input = 'test string';
        $expected = base64_encode(bin2hex($input));

        $result = CustomEncoder::encodeMe($input);

        $this->assertSame($expected, $result);
    }

    public function testDecodeMe(): void
    {
        $input = 'test string';
        $encoded = CustomEncoder::encodeMe($input);

        $result = CustomEncoder::decodeMe($encoded);

        $this->assertSame($input, $result);
    }

    public function testDecodeMeWithInvalidBase64(): void
    {
        $result = CustomEncoder::decodeMe('invalid_base64!@#');

        $this->assertFalse($result);
    }

    public function testDecodeMeWithInvalidHex(): void
    {
        $result = CustomEncoder::decodeMe(base64_encode('invalid_hex'));

        $this->assertFalse($result);
    }

    /**
     * @throws RandomException
     */
    public function testRandomString(): void
    {
        $result = CustomEncoder::randomString(10);

        $this->assertSame(10, strlen($result));
        $this->assertMatchesRegularExpression('/^[0-9a-zA-Z]+$/', $result);
    }

    /**
     * @throws RandomException
     */
    public function testRandomStringWithDefaultLength(): void
    {
        $result = CustomEncoder::randomString();

        $this->assertSame(64, strlen($result));
        $this->assertMatchesRegularExpression('/^[0-9a-zA-Z]+$/', $result);
    }

    /**
     * @throws RandomException
     */
    public function testRandomStringWithZeroLength(): void
    {
        $result = CustomEncoder::randomString(0);

        $this->assertSame(0, strlen($result));
        $this->assertSame('', $result);
    }

    /**
     * @throws RandomException
     */
    public function testRandomCode(): void
    {
        $result = CustomEncoder::randomCode();

        $this->assertGreaterThanOrEqual(100000, $result);
        $this->assertLessThanOrEqual(999999, $result);
    }

    public function testEncryptSuccess(): void
    {
        $data = 'test data to encrypt';

        $result = $this->customEncoder->encrypt($data);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertNotSame($data, $result); // Should be encrypted
    }

    public function testDecryptSuccess(): void
    {
        $originalData = 'test data to encrypt and decrypt';
        $encrypted = $this->customEncoder->encrypt($originalData);

        $result = $this->customEncoder->decrypt($encrypted);

        $this->assertSame($originalData, $result);
    }

    public function testDecryptUnencryptedData(): void
    {
        $unencryptedData = 'this is not encrypted data';

        $result = $this->customEncoder->decrypt($unencryptedData);

        $this->assertNull($result);
    }

    public function testDecryptInvalidData(): void
    {
        // On génère une chaîne chiffrée avec une première instance
        $parameterBagValid = $this->createMock(ParameterBagInterface::class);
        $parameterBagValid->method('get')->willReturnMap([
            ['agency_key', 'valid_key_123'], // longueur OK
            ['agency_crypto_salt', '1234567890abcdef'],
        ]);
        $validEncoder = new CustomEncoder($parameterBagValid);
        $encrypted = $validEncoder->encrypt('some secret');

        // On tente de déchiffrer avec une autre clé invalide
        $parameterBagInvalid = $this->createMock(ParameterBagInterface::class);
        $parameterBagInvalid->method('get')->willReturnMap([
            ['agency_key', 'wrong_key_123'], // même longueur mais mauvaise clé
            ['agency_crypto_salt', '1234567890abcdef'],
        ]);
        $invalidEncoder = new CustomEncoder($parameterBagInvalid);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');

        $invalidEncoder->decrypt($encrypted);
    }

    public function testEncryptWithInvalidAgencyKeyTooShort(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')->willReturnMap([
            ['agency_key', 'short'],
            ['agency_crypto_salt', 'valid_salt_456'],
        ]);

        $customEncoder = new CustomEncoder($parameterBag);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid agency key');

        $customEncoder->encrypt('test data');
    }

    public function testEncryptWithInvalidAgencyKeyTooLong(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')->willReturnMap([
            ['agency_key', 'this_is_a_very_long_agency_key_that_exceeds_the_maximum_length_allowed'],
            ['agency_crypto_salt', 'valid_salt_456'],
        ]);

        $customEncoder = new CustomEncoder($parameterBag);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid agency key');

        $customEncoder->encrypt('test data');
    }

    public function testDecryptWithInvalidAgencyKey(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')->willReturnMap([
            ['agency_key', 'invalid_key'],
            ['agency_crypto_salt', 'valid_salt_456'],
        ]);

        $customEncoder = new CustomEncoder($parameterBag);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid agency key');

        $customEncoder->decrypt('some_encrypted_data');
    }

    public function testEncryptDecryptRoundTrip(): void
    {
        $testData = [
            'simple string',
            'string with spaces',
            'string with special chars: !@#$%^&*()',
            'string with numbers: 1234567890',
            'string with unicode: éàçùñ',
            '',
        ];

        foreach ($testData as $data) {
            $encrypted = $this->customEncoder->encrypt($data);
            $decrypted = $this->customEncoder->decrypt($encrypted);

            $this->assertEquals($data, $decrypted, sprintf("Failed for data: '%s'", $data));
        }
    }

    /**
     * @throws RandomException
     */
    public function testRandomStringGeneratesDifferentValues(): void
    {
        $strings = [];

        for ($i = 0; $i < 10; ++$i) {
            $strings[] = CustomEncoder::randomString(20);
        }

        // All strings should be different
        $uniqueStrings = array_unique($strings);
        $this->assertCount(count($strings), $uniqueStrings);
    }

    /**
     * @throws RandomException
     */
    public function testRandomCodeGeneratesDifferentValues(): void
    {
        $codes = [];

        for ($i = 0; $i < 10; ++$i) {
            $codes[] = CustomEncoder::randomCode();
        }

        // All codes should be different (though theoretically possible to have duplicates)
        $uniqueCodes = array_unique($codes);
        $this->assertGreaterThanOrEqual(count($codes) * 0.8, count($uniqueCodes)); // Allow some duplicates
    }
}
