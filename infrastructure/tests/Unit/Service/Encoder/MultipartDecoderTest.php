<?php

declare(strict_types=1);

namespace App\Infrastructure\Tests\Unit\Service\Encoder;

use App\Infrastructure\Service\Encoder\MultipartDecoder;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\RequestStack;

final class MultipartDecoderTest extends KernelTestCase
{
    /** @var RequestStack&MockObject */
    private RequestStack $requestStack;

    private MultipartDecoder $multipartDecoder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestStack = $this->createMock(RequestStack::class);
        $this->multipartDecoder = new MultipartDecoder($this->requestStack);
    }

    public function testSupportsDecodingWithValidFormat(): void
    {
        $result = $this->multipartDecoder->supportsDecoding('multipart');

        $this->assertTrue($result);
    }

    public function testSupportsDecodingWithInvalidFormat(): void
    {
        $result = $this->multipartDecoder->supportsDecoding('json');

        $this->assertFalse($result);
    }

    public function testSupportsDecodingWithEmptyFormat(): void
    {
        $result = $this->multipartDecoder->supportsDecoding('');

        $this->assertFalse($result);
    }

    public function testSupportsDecodingWithNullFormat(): void
    {
        $result = $this->multipartDecoder->supportsDecoding('null');

        $this->assertFalse($result);
    }

    public function testDecodeWithNoCurrentRequest(): void
    {
        $this->requestStack->method('getCurrentRequest')
            ->willReturn(null);

        $result = $this->multipartDecoder->decode('data', 'multipart');

        $this->assertNull($result);
    }
}
