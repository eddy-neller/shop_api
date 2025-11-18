<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\User;

use App\Dto\User\Partial\UserPreferences;
use App\Dto\User\UserPatchInput;
use App\Dto\User\UserPostInput;
use App\Dto\User\UserRegisterInput;
use App\Entity\User\Embedded\ActiveEmail;
use App\Entity\User\Embedded\ResetPassword;
use App\Entity\User\User;
use App\Enum\User\UserTokenScope;
use App\Repository\User\UserRepository;
use App\Service\BitField;
use App\Service\InfoCodes;
use App\Service\User\TokenManager;
use App\Service\User\UserManager;
use App\Service\User\UserNotifier;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserManagerTest extends KernelTestCase
{
    /** @var ParameterBagInterface&MockObject */
    private ParameterBagInterface $parameterBag;

    /** @var UserRepository&MockObject */
    private UserRepository $userRepository;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;

    /** @var TokenManager&MockObject */
    private TokenManager $tokenManager;

    /** @var BitField&MockObject */
    private BitField $bitField;

    /** @var UserPasswordHasherInterface&MockObject */
    private UserPasswordHasherInterface $passwordHasher;

    /** @var UserNotifier&MockObject */
    private UserNotifier $userNotifier;

    private UserManager $userManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->tokenManager = $this->createMock(TokenManager::class);
        $this->bitField = $this->createMock(BitField::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->userNotifier = $this->createMock(UserNotifier::class);

        $this->userManager = new UserManager(
            $this->parameterBag,
            $this->userRepository,
            $this->entityManager,
            $this->tokenManager,
            $this->bitField,
            $this->passwordHasher,
            $this->userNotifier
        );
    }

    public function testCreateUserWithBasicData(): void
    {
        $input = new UserRegisterInput();
        $input->username = 'testuser';
        $input->email = 'test@example.com';
        $input->password = 'plain-password';
        $input->preferences = new UserPreferences();

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($this->isInstanceOf(User::class), 'plain-password')
            ->willReturn('hashed-password');

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->atLeastOnce())->method('flush');
        $this->userNotifier->expects($this->once())->method('sendRegisterActivationEmail');
        $this->bitField->method('checkValue')->willReturn(false);

        $user = $this->userManager->registerUser($input);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('test@example.com', $user->getEmail());
        $this->assertSame('hashed-password', $user->getPassword());
        $this->assertSame([User::ROLES['user']], $user->getRoles());
    }

    public function testRequestActivationWithInactiveUser(): void
    {
        $user = $this->mockUserWithStatus(User::STATUS['INACTIVE']);

        $this->bitField->method('checkValue')
            ->with(User::STATUS['INACTIVE'], User::STATUS['ACTIVE'])
            ->willReturn(false);

        $this->parameterBag->method('get')
            ->with('register_token_ttl')
            ->willReturn('P2D');

        $activeEmail = new ActiveEmail();
        $user->method('getActiveEmail')
            ->willReturn($activeEmail);

        $user->expects($this->once())
            ->method('setActiveEmail')
            ->with($this->callback(function (ActiveEmail $data) {
                return null !== $data->token && null !== $data->tokenTtl && 1 === $data->mailSent;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->userManager->requestActivation($user);
    }

    public function testRequestActivationWithActiveUser(): void
    {
        $user = $this->mockUserWithStatus(User::STATUS['ACTIVE']);

        $this->bitField->method('checkValue')
            ->with(User::STATUS['ACTIVE'], User::STATUS['ACTIVE'])
            ->willReturn(true);

        $user->expects($this->never())
            ->method('setActiveEmail');

        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->userManager->requestActivation($user);
    }

    public function testRequestActivationWithMaxMailSent(): void
    {
        $user = $this->mockUserWithStatus(User::STATUS['INACTIVE']);
        $activeEmail = new ActiveEmail(mailSent: UserManager::MAX_RESET_MAIL);
        $user->method('getActiveEmail')
            ->willReturn($activeEmail);

        $this->bitField->method('checkValue')
            ->with(User::STATUS['INACTIVE'], User::STATUS['ACTIVE'])
            ->willReturn(false);

        $user->expects($this->never())
            ->method('setActiveEmail');

        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->userManager->requestActivation($user);
    }

    public function testRequestActivationIncrementsMailSent(): void
    {
        $user = $this->mockUserWithStatus(User::STATUS['INACTIVE']);

        $this->bitField->method('checkValue')
            ->with(User::STATUS['INACTIVE'], User::STATUS['ACTIVE'])
            ->willReturn(false);

        $this->parameterBag->method('get')
            ->with('register_token_ttl')
            ->willReturn('P2D');

        $activeEmail = new ActiveEmail(mailSent: 1);
        $user->method('getActiveEmail')
            ->willReturn($activeEmail);

        $user->expects($this->once())
            ->method('setActiveEmail')
            ->with($this->callback(function (ActiveEmail $data) {
                $this->assertSame(2, $data->mailSent);

                return true;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->userManager->requestActivation($user);
    }

    public function testValidateActivationSuccess(): void
    {
        $user = $this->mockUserWithStatus(User::STATUS['INACTIVE']);

        $rawToken = 'encoded-token';
        $splitResult = ['email' => 'test@example.com', 'token' => 'valid-token'];

        $this->tokenManager->method('splitToken')
            ->with($rawToken)
            ->willReturn($splitResult);

        $this->tokenManager->method('retrieveUser')
            ->with(UserTokenScope::RegisterActivation->value, 'token', 'valid-token')
            ->willReturn($user);

        $user->method('getEmail')->willReturn('test@example.com');

        $this->bitField->method('checkValue')
            ->with(User::STATUS['INACTIVE'], User::STATUS['BLOCKED'])
            ->willReturn(false);

        $activeEmail = new ActiveEmail(
            token: 'valid-token',
            tokenTtl: time() + 3600 // 1 hour in future
        );
        $user->method('getActiveEmail')->willReturn($activeEmail);

        $this->bitField->method('addValue')
            ->with(User::STATUS['INACTIVE'], User::STATUS['ACTIVE'])
            ->willReturn(User::STATUS['ACTIVE']);

        $user->expects($this->once())
            ->method('setStatus')
            ->with(User::STATUS['ACTIVE']);

        $user->expects($this->once())
            ->method('setActiveEmail')
            ->with($this->callback(function (ActiveEmail $data) {
                $this->assertSame(0, $data->mailSent);
                $this->assertNull($data->token);
                $this->assertNull($data->tokenTtl);

                return true;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->userManager->validateActivation($rawToken);
    }

    public function testValidateActivationThrowsOnInvalidToken(): void
    {
        $user = $this->mockUserWithStatus(User::STATUS['INACTIVE']);

        $rawToken = 'encoded-token';
        $splitResult = ['email' => 'test@example.com', 'token' => 'different-token'];

        $this->tokenManager->method('splitToken')
            ->with($rawToken)
            ->willReturn($splitResult);

        $this->tokenManager->method('retrieveUser')->willReturn($user);

        $user->method('getEmail')->willReturn('test@example.com');

        $this->bitField->method('checkValue')
            ->with(User::STATUS['INACTIVE'], User::STATUS['BLOCKED'])
            ->willReturn(false);

        $activeEmail = new ActiveEmail(token: 'valid-token');
        $user->method('getActiveEmail')->willReturn($activeEmail);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(InfoCodes::ACCOUNT_VALIDATION['EMAIL_TOKEN_INVALID']);

        $this->userManager->validateActivation($rawToken);
    }

    public function testValidateActivationThrowsOnExpiredToken(): void
    {
        $user = $this->mockUserWithStatus(User::STATUS['INACTIVE']);

        $rawToken = 'encoded-token';
        $splitResult = ['email' => 'test@example.com', 'token' => 'valid-token'];

        $this->tokenManager->method('splitToken')
            ->with($rawToken)
            ->willReturn($splitResult);

        $this->tokenManager->method('retrieveUser')->willReturn($user);

        $user->method('getEmail')->willReturn('test@example.com');

        $this->bitField->method('checkValue')
            ->with(User::STATUS['INACTIVE'], User::STATUS['BLOCKED'])
            ->willReturn(false);

        $activeEmail = new ActiveEmail(
            token: 'valid-token',
            tokenTtl: time() - 3600 // 1 hour in past
        );
        $user->method('getActiveEmail')->willReturn($activeEmail);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(InfoCodes::ACCOUNT_VALIDATION['EMAIL_TOKEN_EXPIRED']);

        $this->userManager->validateActivation($rawToken);
    }

    public function testRequestPasswordResetSuccess(): void
    {
        $user = $this->mockUserWithStatus(User::STATUS['INACTIVE']);

        $this->bitField->method('checkValue')
            ->with(User::STATUS['INACTIVE'], User::STATUS['BLOCKED'])
            ->willReturn(false);

        $this->parameterBag->method('get')
            ->with('reset_password_token_ttl')
            ->willReturn('PT15M');

        $resetPassword = new ResetPassword();
        $user->method('getResetPassword')->willReturn($resetPassword);

        $user->expects($this->once())
            ->method('setResetPassword')
            ->with($this->callback(function (ResetPassword $data) {
                return null !== $data->token && null !== $data->tokenTtl && 1 === $data->mailSent;
            }));

        $this->entityManager->expects($this->once())->method('flush');

        $this->userManager->requestPasswordReset($user);
    }

    public function testRequestPasswordResetThrowsOnLockedUser(): void
    {
        $user = $this->mockUserWithStatus(User::STATUS['BLOCKED']);

        $this->bitField->method('checkValue')
            ->with(User::STATUS['BLOCKED'], User::STATUS['BLOCKED'])
            ->willReturn(true);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(InfoCodes::USER['LOCKED_ACCOUNT']);

        $this->userManager->requestPasswordReset($user);
    }

    public function testRequestPasswordResetWithMaxMailSent(): void
    {
        $user = $this->mockUserWithStatus(User::STATUS['INACTIVE']);

        $this->bitField->method('checkValue')
            ->with(User::STATUS['INACTIVE'], User::STATUS['BLOCKED'])
            ->willReturn(false);

        $resetPassword = new ResetPassword(mailSent: UserManager::MAX_RESET_MAIL);
        $user->method('getResetPassword')->willReturn($resetPassword);

        $user->expects($this->never())->method('setResetPassword');
        $this->entityManager->expects($this->never())->method('flush');

        $this->userManager->requestPasswordReset($user);
    }

    public function testAssertValidResetPasswordTokenSuccess(): void
    {
        $user = $this->mockUserWithStatus(User::STATUS['INACTIVE']);

        $rawToken = 'encoded-token';
        $splitResult = ['email' => 'test@example.com', 'token' => 'valid-token'];

        $this->tokenManager->method('splitToken')
            ->with($rawToken)
            ->willReturn($splitResult);

        $this->tokenManager->method('retrieveUser')
            ->with(UserTokenScope::ResetPassword->value, 'token', 'valid-token')
            ->willReturn($user);

        $user->method('getEmail')->willReturn('test@example.com');

        $this->bitField->method('checkValue')
            ->with(User::STATUS['INACTIVE'], User::STATUS['BLOCKED'])
            ->willReturn(false);

        $resetPassword = new ResetPassword(
            token: 'valid-token',
            tokenTtl: time() + 3600 // 1 hour in future
        );
        $user->method('getResetPassword')->willReturn($resetPassword);

        $result = $this->userManager->assertValidResetPasswordToken($rawToken);

        $this->assertSame($user, $result);
    }

    public function testAssertValidResetPasswordTokenThrowsOnInvalidToken(): void
    {
        $user = $this->mockUserWithStatus(User::STATUS['INACTIVE']);

        $rawToken = 'encoded-token';
        $splitResult = ['email' => 'test@example.com', 'token' => 'different-token'];

        $this->tokenManager->method('splitToken')
            ->with($rawToken)
            ->willReturn($splitResult);

        $this->tokenManager->method('retrieveUser')->willReturn($user);

        $user->method('getEmail')->willReturn('test@example.com');

        $this->bitField->method('checkValue')
            ->with(User::STATUS['INACTIVE'], User::STATUS['BLOCKED'])
            ->willReturn(false);

        $resetPassword = new ResetPassword(token: 'valid-token');
        $user->method('getResetPassword')->willReturn($resetPassword);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(InfoCodes::RESET_PASSWORD['EMAIL_TOKEN_INVALID']);

        $this->userManager->assertValidResetPasswordToken($rawToken);
    }

    public function testAssertValidResetPasswordTokenThrowsOnExpiredToken(): void
    {
        $user = $this->mockUserWithStatus(User::STATUS['INACTIVE']);

        $rawToken = 'encoded-token';
        $splitResult = ['email' => 'test@example.com', 'token' => 'valid-token'];

        $this->tokenManager->method('splitToken')
            ->with($rawToken)
            ->willReturn($splitResult);

        $this->tokenManager->method('retrieveUser')->willReturn($user);

        $user->method('getEmail')->willReturn('test@example.com');

        $this->bitField->method('checkValue')
            ->with(User::STATUS['INACTIVE'], User::STATUS['BLOCKED'])
            ->willReturn(false);

        $resetPassword = new ResetPassword(
            token: 'valid-token',
            tokenTtl: time() - 3600 // 1 hour in past
        );
        $user->method('getResetPassword')->willReturn($resetPassword);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(InfoCodes::RESET_PASSWORD['EMAIL_TOKEN_EXPIRED']);

        $this->userManager->assertValidResetPasswordToken($rawToken);
    }

    public function testValidateResetPasswordSuccess(): void
    {
        $user = $this->mockUserWithStatus(User::STATUS['INACTIVE']);

        $rawToken = 'encoded-token';
        $splitResult = ['email' => 'test@example.com', 'token' => 'valid-token'];
        $plainPassword = 'new-password';
        $hashedPassword = 'hashed-password';

        $this->tokenManager->method('splitToken')
            ->with($rawToken)
            ->willReturn($splitResult);

        $this->tokenManager->method('retrieveUser')->willReturn($user);

        $user->method('getEmail')->willReturn('test@example.com');

        $this->bitField->method('checkValue')
            ->with(User::STATUS['INACTIVE'], User::STATUS['BLOCKED'])
            ->willReturn(false);

        $resetPassword = new ResetPassword(
            token: 'valid-token',
            tokenTtl: time() + 3600
        );
        $user->method('getResetPassword')->willReturn($resetPassword);

        $this->passwordHasher->method('hashPassword')
            ->with($user, $plainPassword)
            ->willReturn($hashedPassword);

        $user->expects($this->once())
            ->method('setPassword')
            ->with($hashedPassword);

        $user->expects($this->once())
            ->method('setResetPassword')
            ->with($this->callback(function (ResetPassword $data) {
                $this->assertSame(0, $data->mailSent);
                $this->assertNull($data->token);
                $this->assertNull($data->tokenTtl);

                return true;
            }));

        $this->entityManager->expects($this->once())->method('flush');

        $this->userManager->validateResetPassword($rawToken, $plainPassword);
    }

    /**
     * Fournit les scopes + le message attendu "email token invalid" par scope.
     */
    public static function provideScopesAndEmailTokenInvalidMessage(): iterable
    {
        yield 'activation' => [
            UserTokenScope::RegisterActivation,
            InfoCodes::ACCOUNT_VALIDATION['EMAIL_TOKEN_INVALID'],
        ];
        yield 'reset_password' => [
            UserTokenScope::ResetPassword,
            InfoCodes::RESET_PASSWORD['EMAIL_TOKEN_INVALID'],
        ];
    }

    #[DataProvider('provideScopesAndEmailTokenInvalidMessage')]
    public function testResolveUserByTokenThrowsOnEmptyEmail(UserTokenScope $scope, string $expectedMessage): void
    {
        $rawToken = 'encoded-token';
        $splitResult = ['email' => '', 'token' => 'valid-token'];

        $this->tokenManager->method('splitToken')
            ->with($rawToken)
            ->willReturn($splitResult);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->invokeResolveUserByToken($rawToken, $scope);
    }

    #[DataProvider('provideScopesAndEmailTokenInvalidMessage')]
    public function testResolveUserByTokenThrowsOnEmptyToken(UserTokenScope $scope, string $expectedMessage): void
    {
        $rawToken = 'encoded-token';
        $splitResult = ['email' => 'test@example.com', 'token' => ''];

        $this->tokenManager->method('splitToken')
            ->with($rawToken)
            ->willReturn($splitResult);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->invokeResolveUserByToken($rawToken, $scope);
    }

    #[DataProvider('provideScopesAndEmailTokenInvalidMessage')]
    public function testResolveUserByTokenThrowsOnEmptyEmailAndToken(UserTokenScope $scope, string $expectedMessage): void
    {
        $rawToken = 'encoded-token';
        $splitResult = ['email' => '', 'token' => ''];

        $this->tokenManager->method('splitToken')
            ->with($rawToken)
            ->willReturn($splitResult);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->invokeResolveUserByToken($rawToken, $scope);
    }

    #[DataProvider('provideScopesAndEmailTokenInvalidMessage')]
    public function testResolveUserByTokenThrowsOnMissingEmailKey(UserTokenScope $scope, string $expectedMessage): void
    {
        $rawToken = 'encoded-token';
        $splitResult = ['token' => 'valid-token']; // email key missing

        $this->tokenManager->method('splitToken')
            ->with($rawToken)
            ->willReturn($splitResult);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->invokeResolveUserByToken($rawToken, $scope);
    }

    #[DataProvider('provideScopesAndEmailTokenInvalidMessage')]
    public function testResolveUserByTokenThrowsOnMissingTokenKey(UserTokenScope $scope, string $expectedMessage): void
    {
        $rawToken = 'encoded-token';
        $splitResult = ['email' => 'test@example.com']; // token key missing

        $this->tokenManager->method('splitToken')
            ->with($rawToken)
            ->willReturn($splitResult);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->invokeResolveUserByToken($rawToken, $scope);
    }

    #[DataProvider('provideScopesAndEmailTokenInvalidMessage')]
    public function testResolveUserByTokenThrowsOnNullEmail(UserTokenScope $scope, string $expectedMessage): void
    {
        $rawToken = 'encoded-token';
        $splitResult = ['email' => null, 'token' => 'valid-token'];

        $this->tokenManager->method('splitToken')
            ->with($rawToken)
            ->willReturn($splitResult);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->invokeResolveUserByToken($rawToken, $scope);
    }

    #[DataProvider('provideScopesAndEmailTokenInvalidMessage')]
    public function testResolveUserByTokenThrowsOnNullToken(UserTokenScope $scope, string $expectedMessage): void
    {
        $rawToken = 'encoded-token';
        $splitResult = ['email' => 'test@example.com', 'token' => null];

        $this->tokenManager->method('splitToken')
            ->with($rawToken)
            ->willReturn($splitResult);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->invokeResolveUserByToken($rawToken, $scope);
    }

    /**
     * Fournit les scopes + le message attendu "user not found" par scope.
     * Adapte les constantes si ton InfoCodes diffère.
     */
    public static function provideScopesAndUserNotFoundMessage(): iterable
    {
        yield 'activation' => [
            UserTokenScope::RegisterActivation,
            InfoCodes::ACCOUNT_VALIDATION['USER_NOT_FOUND_WITH_TOKEN'],
        ];
        yield 'reset_password' => [
            UserTokenScope::ResetPassword,
            InfoCodes::RESET_PASSWORD['USER_NOT_FOUND_WITH_TOKEN'],
        ];
    }

    #[DataProvider('provideScopesAndUserNotFoundMessage')]
    public function testResolveUserByTokenThrowsOnUserNotFound(UserTokenScope $scope, string $expectedMessage): void
    {
        $rawToken = 'encoded-token';
        $splitResult = ['email' => 'test@example.com', 'token' => 'valid-token'];

        $this->tokenManager->method('splitToken')
            ->with($rawToken)
            ->willReturn($splitResult);

        // Aucun user trouvé pour ce scope
        $this->tokenManager->method('retrieveUser')
            ->with($scope->value, 'token', 'valid-token')
            ->willReturn(null);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->invokeResolveUserByToken($rawToken, $scope);
    }

    #[DataProvider('provideScopesAndUserNotFoundMessage')]
    public function testResolveUserByTokenThrowsOnEmailMismatch(UserTokenScope $scope, string $expectedMessage): void
    {
        $user = $this->mockUserWithStatus(User::STATUS['INACTIVE']);
        $rawToken = 'encoded-token';
        $splitResult = ['email' => 'test@example.com', 'token' => 'valid-token'];

        $this->tokenManager->method('splitToken')
            ->with($rawToken)
            ->willReturn($splitResult);

        $this->tokenManager->method('retrieveUser')
            ->with($scope->value, 'token', 'valid-token')
            ->willReturn($user);

        $user->method('getEmail')->willReturn('different@example.com');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->invokeResolveUserByToken($rawToken, $scope);
    }

    /** Fournit simplement les scopes. */
    public static function provideScopes(): iterable
    {
        yield 'activation' => [UserTokenScope::RegisterActivation];
        yield 'reset_password' => [UserTokenScope::ResetPassword];
    }

    #[DataProvider('provideScopes')]
    public function testResolveUserByTokenThrowsOnLockedUser(UserTokenScope $scope): void
    {
        $user = $this->mockUserWithStatus(User::STATUS['BLOCKED']);
        $rawToken = 'encoded-token';
        $splitResult = ['email' => 'test@example.com', 'token' => 'valid-token'];

        $this->tokenManager->method('splitToken')
            ->with($rawToken)
            ->willReturn($splitResult);

        $this->tokenManager->method('retrieveUser')
            ->with($scope->value, 'token', 'valid-token')
            ->willReturn($user);

        $user->method('getEmail')->willReturn('test@example.com');

        // Locked => checkValue(..., BLOCKED) => true
        $this->bitField->method('checkValue')
            ->with(User::STATUS['BLOCKED'], User::STATUS['BLOCKED'])
            ->willReturn(true);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(InfoCodes::USER['LOCKED_ACCOUNT']);

        $this->invokeResolveUserByToken($rawToken, $scope);
    }

    #[DataProvider('provideScopes')]
    public function testResolveUserByTokenReturnsCorrectData(UserTokenScope $scope): void
    {
        $user = $this->mockUserWithStatus(User::STATUS['INACTIVE']);
        $rawToken = 'encoded-token';
        $splitResult = ['email' => 'test@example.com', 'token' => 'valid-token'];

        $this->tokenManager->method('splitToken')
            ->with($rawToken)
            ->willReturn($splitResult);

        $this->tokenManager->method('retrieveUser')
            ->with($scope->value, 'token', 'valid-token')
            ->willReturn($user);

        $user->method('getEmail')->willReturn('test@example.com');

        // Not locked
        $this->bitField->method('checkValue')
            ->with(User::STATUS['INACTIVE'], User::STATUS['BLOCKED'])
            ->willReturn(false);

        $result = $this->invokeResolveUserByToken($rawToken, $scope);

        $this->assertCount(2, $result);
        $this->assertSame($user, $result[0]);
        $this->assertSame('valid-token', $result[1]);
    }

    public function testIsActiveReturnsTrue(): void
    {
        $user = $this->createMock(User::class);

        $user->method('getStatus')
            ->willReturn(User::STATUS['ACTIVE']);

        $this->bitField->method('checkValue')
            ->with(User::STATUS['ACTIVE'], User::STATUS['ACTIVE'])
            ->willReturn(true);

        $reflection = new ReflectionClass($this->userManager);
        $method = $reflection->getMethod('isActive');

        $result = $method->invoke($this->userManager, $user);

        $this->assertTrue($result);
    }

    public function testIsActiveReturnsFalse(): void
    {
        $user = $this->createMock(User::class);

        $user->method('getStatus')
            ->willReturn(User::STATUS['INACTIVE']);

        $this->bitField->method('checkValue')
            ->with(User::STATUS['INACTIVE'], User::STATUS['ACTIVE'])
            ->willReturn(false);

        $reflection = new ReflectionClass($this->userManager);
        $method = $reflection->getMethod('isActive');

        $result = $method->invoke($this->userManager, $user);

        $this->assertFalse($result);
    }

    public function testIsLockedThrowsException(): void
    {
        $user = $this->createMock(User::class);

        $user->method('getStatus')
            ->willReturn(User::STATUS['BLOCKED']);

        $this->bitField->method('checkValue')
            ->with(User::STATUS['BLOCKED'], User::STATUS['BLOCKED'])
            ->willReturn(true);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage(InfoCodes::USER['LOCKED_ACCOUNT']);

        $reflection = new ReflectionClass($this->userManager);
        $method = $reflection->getMethod('isLocked');

        $method->invoke($this->userManager, $user);
    }

    public function testIsLockedDoesNotThrowException(): void
    {
        $user = $this->createMock(User::class);

        $user->method('getStatus')
            ->willReturn(User::STATUS['ACTIVE']);

        $this->bitField->method('checkValue')
            ->with(User::STATUS['ACTIVE'], User::STATUS['BLOCKED'])
            ->willReturn(false);

        $reflection = new ReflectionClass($this->userManager);
        $method = $reflection->getMethod('isLocked');

        // Should not throw exception
        $result = $method->invoke($this->userManager, $user);

        $this->assertNull($result); // no exception
    }

    public function testUpdateAvatarSetsAvatarFileAndFlushes(): void
    {
        $user = $this->createMock(User::class);
        $avatarFile = $this->createMock(File::class);

        $user->expects($this->once())
            ->method('setAvatarFile')
            ->with($avatarFile);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->userManager->updateAvatar($user, $avatarFile);
    }

    public function testUpdatePasswordHashesPasswordAndFlushes(): void
    {
        $user = $this->createMock(User::class);
        $plainPassword = 'NewPassword123!';
        $hashedPassword = 'hashed_new_password';

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($user, $plainPassword)
            ->willReturn($hashedPassword);

        $user->expects($this->once())
            ->method('setPassword')
            ->with($hashedPassword);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->userManager->updatePassword($user, $plainPassword);
    }

    public function testUpdatePasswordWithDifferentPasswords(): void
    {
        $user = $this->createMock(User::class);

        $this->passwordHasher->method('hashPassword')
            ->willReturnCallback(fn ($u, string $plain): string => 'hashed_' . $plain);

        $user->expects($this->once())
            ->method('setPassword')
            ->with('hashed_ComplexPassword!@#');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->userManager->updatePassword($user, 'ComplexPassword!@#');
    }

    public function testGetUserByIdReturnsUserWhenExists(): void
    {
        $userId = 'user-123';
        $user = $this->createMock(User::class);

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);

        $result = $this->userManager->getUserById($userId);

        $this->assertSame($user, $result);
    }

    public function testGetUserByIdThrowsNotFoundExceptionWhenUserDoesNotExist(): void
    {
        $userId = 'non-existent-user';

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage(InfoCodes::USER['USR_NOT_FOUND']);

        $this->userManager->getUserById($userId);
    }

    public function testCreateUserByAdminWithBasicData(): void
    {
        $input = new UserPostInput();
        $input->username = 'adminuser';
        $input->email = 'admin@example.com';
        $input->password = 'plain-password';
        $input->roles = [User::ROLES['admin']];
        $input->status = User::STATUS['ACTIVE'];

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($this->isInstanceOf(User::class), 'plain-password')
            ->willReturn('hashed-password');

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $user = $this->userManager->createUserByAdmin($input);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('admin@example.com', $user->getEmail());
        $this->assertSame('hashed-password', $user->getPassword());
        $this->assertSame([User::ROLES['admin']], $user->getRoles());
        $this->assertSame(User::STATUS['ACTIVE'], $user->getStatus());
    }

    public function testCreateUserByAdminWithDifferentRoles(): void
    {
        $input = new UserPostInput();
        $input->username = 'moderator';
        $input->email = 'mod@example.com';
        $input->password = 'password';
        $input->roles = [User::ROLES['user'], User::ROLES['moder']];
        $input->status = User::STATUS['ACTIVE'];

        $this->passwordHasher->method('hashPassword')->willReturn('hashed-password');
        $this->entityManager->method('persist');
        $this->entityManager->method('flush');

        $user = $this->userManager->createUserByAdmin($input);

        $this->assertSame([User::ROLES['user'], User::ROLES['moder']], $user->getRoles());
    }

    public function testUpdateUserByAdminWithAllFields(): void
    {
        $userId = 'user-123';
        $existingUser = new User();
        $existingUser->setUsername('oldusername');
        $existingUser->setEmail('old@example.com');
        $existingUser->setRoles([User::ROLES['user']]);
        $existingUser->setStatus(User::STATUS['INACTIVE']);

        $input = new UserPatchInput();
        $input->username = 'newusername';
        $input->email = 'new@example.com';
        $input->firstname = 'John';
        $input->lastname = 'Doe';
        $input->roles = [User::ROLES['admin']];
        $input->status = User::STATUS['ACTIVE'];
        $input->password = 'new-password';

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($existingUser);

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($existingUser, 'new-password')
            ->willReturn('hashed-new-password');

        $this->entityManager->expects($this->once())->method('flush');

        $user = $this->userManager->updateUserByAdmin($userId, $input);

        $this->assertSame('newusername', $user->getUsername());
        $this->assertSame('new@example.com', $user->getEmail());
        $this->assertSame('John', $user->getFirstname());
        $this->assertSame('Doe', $user->getLastname());
        $this->assertSame([User::ROLES['admin']], $user->getRoles());
        $this->assertSame(User::STATUS['ACTIVE'], $user->getStatus());
        $this->assertSame('hashed-new-password', $user->getPassword());
    }

    public function testUpdateUserByAdminWithPartialFields(): void
    {
        $userId = 'user-456';
        $existingUser = new User();
        $existingUser->setUsername('oldusername');
        $existingUser->setEmail('old@example.com');
        $existingUser->setFirstname('OldFirst');
        $existingUser->setLastname('OldLast');
        $existingUser->setRoles([User::ROLES['user']]);
        $existingUser->setStatus(User::STATUS['ACTIVE']);
        $existingUser->setPassword('old-hashed-password');

        $input = new UserPatchInput();
        $input->username = 'newusername';
        $input->roles = [User::ROLES['moder']];
        // Les autres champs sont null

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($existingUser);

        $this->entityManager->expects($this->once())->method('flush');

        $user = $this->userManager->updateUserByAdmin($userId, $input);

        // Seuls les champs fournis doivent être modifiés
        $this->assertSame('newusername', $user->getUsername());
        $this->assertSame([User::ROLES['moder']], $user->getRoles());
        // Les autres champs doivent rester inchangés
        $this->assertSame('old@example.com', $user->getEmail());
        $this->assertSame('OldFirst', $user->getFirstname());
        $this->assertSame('OldLast', $user->getLastname());
        $this->assertSame(User::STATUS['ACTIVE'], $user->getStatus());
        $this->assertSame('old-hashed-password', $user->getPassword());
    }

    public function testUpdateUserByAdminWithEmptyPasswordDoesNotUpdatePassword(): void
    {
        $userId = 'user-789';
        $existingUser = new User();
        $existingUser->setUsername('user');
        $existingUser->setPassword('old-hashed-password');

        $input = new UserPatchInput();
        $input->username = 'updateduser';
        $input->password = '   '; // Whitespace only

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn($existingUser);

        // Le passwordHasher ne doit pas être appelé
        $this->passwordHasher->expects($this->never())
            ->method('hashPassword');

        $this->entityManager->expects($this->once())->method('flush');

        $user = $this->userManager->updateUserByAdmin($userId, $input);

        // Le mot de passe ne doit pas changer
        $this->assertSame('old-hashed-password', $user->getPassword());
        // Mais le username doit être mis à jour
        $this->assertSame('updateduser', $user->getUsername());
    }

    public function testUpdateUserByAdminThrowsNotFoundExceptionWhenUserDoesNotExist(): void
    {
        $userId = 'non-existent-user';
        $input = new UserPatchInput();
        $input->username = 'newusername';

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with($userId)
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage(InfoCodes::USER['USR_NOT_FOUND']);

        $this->userManager->updateUserByAdmin($userId, $input);
    }

    /** Petit helper pour invoquer la méthode privée proprement. */
    private function invokeResolveUserByToken(string $rawToken, UserTokenScope $scope): array
    {
        $ref = new ReflectionClass($this->userManager);
        $method = $ref->getMethod('resolveUserByToken');

        /* @var array{0: User, 1: string} */
        return $method->invoke($this->userManager, $rawToken, $scope);
    }

    /**
     * Helper: crée un mock User avec un status défini.
     */
    private function mockUserWithStatus(int $status): User&MockObject
    {
        $user = $this->createMock(User::class);
        $user->method('getStatus')->willReturn($status);

        return $user;
    }
}
