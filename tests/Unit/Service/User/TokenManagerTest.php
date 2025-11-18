<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\User;

use App\Entity\User\User;
use App\Repository\User\UserRepository;
use App\Service\User\TokenManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TokenManagerTest extends KernelTestCase
{
    /** @var UserRepository&MockObject */
    private UserRepository $userRepository;

    private TokenManager $tokenManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepository::class);

        $this->tokenManager = new TokenManager(
            $this->userRepository,
        );
    }

    public function testSplitTokenSuccess(): void
    {
        $token = 'sometoken';
        $email = 'test@example.com';

        $encoded = $this->tokenManager->generateEmailToken($token, $email);
        $result = $this->tokenManager->splitToken($encoded);

        $this->assertEquals($email, $result['email']);
        $this->assertEquals($token, $result['token']);
    }

    public function testGenerateEmailToken(): void
    {
        $token = 'tok';
        $email = 'a@b.com';

        $encoded = $this->tokenManager->generateEmailToken($token, $email);

        $decoded = base64_decode($encoded);
        $this->assertStringContainsString($email, $decoded);
        $this->assertStringContainsString($token, $decoded);
    }

    public function testRetrieveUserReturnsUser(): void
    {
        $user = $this->createMock(User::class);
        $this->userRepository->method('findInJsonField')->willReturn($user);

        $result = $this->tokenManager->retrieveUser('field', 'key', 'token');

        $this->assertSame($user, $result);
    }

    public function testClearJsonField(): void
    {
        $array = ['foo' => 'bar', 'baz' => 123];

        $result = $this->tokenManager->clearJsonField($array);

        $this->assertNull($result['foo']);
        $this->assertNull($result['baz']);
        $this->assertEquals(0, $result['mailSent']);
    }
}
