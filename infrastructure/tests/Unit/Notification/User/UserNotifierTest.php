<?php

declare(strict_types=1);

namespace App\Infrastructure\Tests\Unit\Notification\User;

use App\Domain\User\Identity\ValueObject\EmailAddress;
use App\Domain\User\Identity\ValueObject\Firstname;
use App\Domain\User\Identity\ValueObject\UserId;
use App\Domain\User\Identity\ValueObject\Username;
use App\Domain\User\Model\User;
use App\Domain\User\Preference\ValueObject\Preferences;
use App\Domain\User\Security\ValueObject\HashedPassword;
use App\Infrastructure\Notification\Mailer\Mailer;
use App\Infrastructure\Notification\User\UserNotifier;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserNotifierTest extends KernelTestCase
{
    /** @var TranslatorInterface&MockObject */
    private TranslatorInterface $translator;

    /** @var ParameterBagInterface&MockObject */
    private ParameterBagInterface $parameterBag;

    /** @var Mailer&MockObject */
    private Mailer $mailer;

    private UserNotifier $userNotifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->mailer = $this->createMock(Mailer::class);

        $this->userNotifier = new UserNotifier(
            $this->translator,
            $this->parameterBag,
            $this->mailer,
        );
    }

    public function testSendActivationEmail(): void
    {
        $user = $this->createUser('en');
        $subject = 'Account Activation Required';
        $baseLink = 'https://example.com/activate/';
        $encodedToken = 'encoded-token-123';
        $expectedLink = $baseLink . '?token=' . urlencode($encodedToken);

        $this->translator->method('trans')
            ->with('user.register.activation.title', [], 'messages', 'en')
            ->willReturn($subject);

        $this->parameterBag->method('get')->willReturnMap([
            ['mailerFrontLinkRegisterValidation', $baseLink],
            ['app.enabled_locales', ['en', 'fr']],
            ['app.default_locale', 'en'],
        ]);

        $this->mailer->expects($this->once())
            ->method('sendEmail')
            ->with(
                'test@example.com',
                $subject,
                'emails/user/register-activation.html.twig',
                [
                    'firstname' => 'John',
                    'link' => $expectedLink,
                    'userLocale' => 'en',
                ]
            );

        $this->userNotifier->sendActivationEmail($user, $encodedToken);
    }

    public function testSendResetPasswordEmail(): void
    {
        $user = $this->createUser('en');
        $subject = 'Password Reset Request';
        $baseLink = 'https://example.com/reset/';
        $encodedToken = 'encoded-token-456';
        $expectedLink = $baseLink . '?token=' . urlencode($encodedToken);

        $this->translator->method('trans')
            ->with('user.reset.password.title', [], 'messages', 'en')
            ->willReturn($subject);

        $this->parameterBag->method('get')->willReturnMap([
            ['mailerFrontLinkResetPassword', $baseLink],
            ['app.enabled_locales', ['en', 'fr']],
            ['app.default_locale', 'en'],
        ]);

        $this->mailer->expects($this->once())
            ->method('sendEmail')
            ->with(
                'test@example.com',
                $subject,
                'emails/user/reset-password.html.twig',
                [
                    'firstname' => 'John',
                    'link' => $expectedLink,
                    'userLocale' => 'en',
                ]
            );

        $this->userNotifier->sendResetPasswordEmail($user, $encodedToken);
    }

    public function testSendUserMailWithCustomLanguage(): void
    {
        $user = $this->createUser('fr');
        $subject = 'Activation de compte requise';
        $baseLink = 'https://example.com/activate/';
        $encodedToken = 'encoded-token-789';
        $expectedLink = $baseLink . '?token=' . urlencode($encodedToken);

        $this->translator->method('trans')
            ->with('user.register.activation.title', [], 'messages', 'fr')
            ->willReturn($subject);

        $this->parameterBag->method('get')->willReturnMap([
            ['mailerFrontLinkRegisterValidation', $baseLink],
            ['app.enabled_locales', ['en', 'fr']],
            ['app.default_locale', 'en'],
        ]);

        $this->mailer->expects($this->once())
            ->method('sendEmail')
            ->with(
                'test@example.com',
                $subject,
                'emails/user/register-activation.html.twig',
                [
                    'firstname' => 'John',
                    'link' => $expectedLink,
                    'userLocale' => 'fr',
                ]
            );

        $this->userNotifier->sendActivationEmail($user, $encodedToken);
    }

    public function testSendUserMailWithFallbackLanguage(): void
    {
        $user = $this->createUser('invalid-lang');
        $subject = 'Account Activation Required';
        $baseLink = 'https://example.com/activate/';
        $encodedToken = 'encoded-token-abc';
        $expectedLink = $baseLink . '?token=' . urlencode($encodedToken);

        // fallback attendu => 'en'
        $this->translator->method('trans')
            ->with('user.register.activation.title', [], 'messages', 'en')
            ->willReturn($subject);

        $this->parameterBag->method('get')->willReturnMap([
            ['mailerFrontLinkRegisterValidation', $baseLink],
            ['app.enabled_locales', ['en', 'fr']],
            ['app.default_locale', 'en'],
        ]);

        $this->mailer->expects($this->once())
            ->method('sendEmail')
            ->with(
                'test@example.com',
                $subject,
                'emails/user/register-activation.html.twig',
                [
                    'firstname' => 'John',
                    'link' => $expectedLink,
                    'userLocale' => 'en',
                ]
            );

        $this->userNotifier->sendActivationEmail($user, $encodedToken);
    }

    public function testResolveLocaleFallsBackWhenEnabledLocalesIsNotArray(): void
    {
        // Préférence utilisateur 'fr', mais enabled_locales n'est pas un tableau
        // donc le système doit utiliser le default 'en'
        $user = $this->createUser('fr');
        $subject = 'Account Activation Required';
        $baseLink = 'https://example.com/activate/';
        $encodedToken = 'encoded-token-mno';
        $expectedLink = $baseLink . '?token=' . urlencode($encodedToken);

        $this->translator->method('trans')
            ->with('user.register.activation.title', [], 'messages', 'en')
            ->willReturn($subject);

        // enabled_locales renvoie une chaîne -> $allowed = [$default]
        // 'fr' n'est pas dans [$default], donc fallback à 'en'
        $this->parameterBag->method('get')->willReturnMap([
            ['mailerFrontLinkRegisterValidation', $baseLink],
            ['app.enabled_locales', 'en'], // pas un tableau -> $allowed = [$default]
            ['app.default_locale', 'en'],
        ]);

        $this->mailer->expects($this->once())
            ->method('sendEmail')
            ->with(
                'test@example.com',
                $subject,
                'emails/user/register-activation.html.twig',
                [
                    'firstname' => 'John',
                    'link' => $expectedLink,
                    'userLocale' => 'en',
                ]
            );

        $this->userNotifier->sendActivationEmail($user, $encodedToken);
    }

    public function testSendActivationEmailWithoutFirstname(): void
    {
        $user = $this->createUserWithoutFirstname('en');
        $subject = 'Account Activation Required';
        $baseLink = 'https://example.com/activate/';
        $encodedToken = 'encoded-token-pqr';
        $expectedLink = $baseLink . '?token=' . urlencode($encodedToken);

        $this->translator->method('trans')
            ->with('user.register.activation.title', [], 'messages', 'en')
            ->willReturn($subject);

        $this->parameterBag->method('get')->willReturnMap([
            ['mailerFrontLinkRegisterValidation', $baseLink],
            ['app.enabled_locales', ['en', 'fr']],
            ['app.default_locale', 'en'],
        ]);

        $this->mailer->expects($this->once())
            ->method('sendEmail')
            ->with(
                'test@example.com',
                $subject,
                'emails/user/register-activation.html.twig',
                [
                    'firstname' => null,
                    'link' => $expectedLink,
                    'userLocale' => 'en',
                ]
            );

        $this->userNotifier->sendActivationEmail($user, $encodedToken);
    }

    private function createUser(?string $lang): User
    {
        // Si lang est null, utiliser une langue non autorisée pour tester le fallback
        // Sinon, utiliser la langue fournie
        $preferences = Preferences::fromArray($lang ? ['lang' => $lang] : ['lang' => 'invalid-lang']);

        return User::register(
            id: UserId::fromString('550e8400-e29b-41d4-a716-446655440000'),
            username: new Username('testuser'),
            email: new EmailAddress('test@example.com'),
            password: new HashedPassword('hashed-password'),
            preferences: $preferences,
            now: new DateTimeImmutable(),
            firstname: new Firstname('John'),
        );
    }

    private function createUserWithoutFirstname(?string $lang): User
    {
        $preferences = Preferences::fromArray($lang ? ['lang' => $lang] : ['lang' => 'invalid-lang']);

        return User::register(
            id: UserId::fromString('550e8400-e29b-41d4-a716-446655440001'),
            username: new Username('testuser'),
            email: new EmailAddress('test@example.com'),
            password: new HashedPassword('hashed-password'),
            preferences: $preferences,
            now: new DateTimeImmutable(),
        );
    }
}
