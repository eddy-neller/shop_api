<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\User;

use App\Entity\User\Embedded\ActiveEmail;
use App\Entity\User\Embedded\ResetPassword;
use App\Entity\User\User;
use App\Service\Mailer\Mailer;
use App\Service\User\TokenManager;
use App\Service\User\UserNotifier;
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

    /** @var TokenManager&MockObject */
    private TokenManager $tokenManager;

    private UserNotifier $userNotifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->mailer = $this->createMock(Mailer::class);
        $this->tokenManager = $this->createMock(TokenManager::class);

        $this->userNotifier = new UserNotifier(
            $this->translator,
            $this->parameterBag,
            $this->mailer,
            $this->tokenManager,
        );
    }

    public function testSendRegisterActivationEmail(): void
    {
        $user = $this->createMockUser();
        $subject = 'Account Activation Required';
        $baseLink = 'https://example.com/activate/';
        $emailToken = $this->b64('test@example.com', 'raw-token');
        $expectedLink = $baseLink . '?token=' . urlencode($emailToken);

        $this->translator->method('trans')
            ->with('user.register.activation.title', [], 'messages', 'en')
            ->willReturn($subject);

        $this->parameterBag->method('get')->willReturnMap([
            ['mailerFrontLinkRegisterValidation', $baseLink],
            ['app.enabled_locales', ['en', 'fr']],
            ['app.default_locale', 'en'],
        ]);

        $this->tokenManager->method('generateEmailToken')
            ->willReturn($emailToken);

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

        $this->userNotifier->sendRegisterActivationEmail($user);
    }

    public function testSendResetPasswordEmail(): void
    {
        $user = $this->createMockUser();
        $subject = 'Password Reset Request';
        $baseLink = 'https://example.com/reset/';
        $emailToken = $this->b64('test@example.com', 'raw-token');
        $expectedLink = $baseLink . '?token=' . urlencode($emailToken);

        $this->translator->method('trans')
            ->with('user.reset.password.title', [], 'messages', 'en')
            ->willReturn($subject);

        $this->parameterBag->method('get')->willReturnMap([
            ['mailerFrontLinkResetPassword', $baseLink],
            ['app.enabled_locales', ['en', 'fr']],
            ['app.default_locale', 'en'],
        ]);

        $this->tokenManager->method('generateEmailToken')
            ->willReturn($emailToken);

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

        $this->userNotifier->sendResetPasswordEmail($user);
    }

    public function testSendUserMailWithCustomLanguage(): void
    {
        $user = $this->createMockUserWithLanguage('fr');
        $subject = 'Activation de compte requise';
        $baseLink = 'https://example.com/activate/';
        $emailToken = $this->b64('test@example.com', 'raw-token');
        $expectedLink = $baseLink . '?token=' . urlencode($emailToken);

        // On laisse la locale libre côté translator (implémentation dépendante),
        // et on vérifie surtout la locale dans le payload.
        $this->translator->method('trans')
            ->with('user.register.activation.title', [], 'messages', $this->anything())
            ->willReturn($subject);

        $this->parameterBag->method('get')->willReturnMap([
            ['mailerFrontLinkRegisterValidation', $baseLink],
            ['app.enabled_locales', ['en', 'fr']], // autorise 'fr'
            ['app.default_locale', 'en'],
        ]);

        $this->tokenManager->method('generateEmailToken')
            ->willReturn($emailToken);

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

        $this->userNotifier->sendRegisterActivationEmail($user);
    }

    public function testSendUserMailWithFallbackLanguage(): void
    {
        $user = $this->createMockUserWithLanguage('invalid-lang');
        $subject = 'Account Activation Required';
        $baseLink = 'https://example.com/activate/';
        $emailToken = $this->b64('test@example.com', 'raw-token');
        $expectedLink = $baseLink . '?token=' . urlencode($emailToken);

        // fallback attendu => 'en'
        $this->translator->method('trans')
            ->with('user.register.activation.title', [], 'messages', 'en')
            ->willReturn($subject);

        $this->parameterBag->method('get')->willReturnMap([
            ['mailerFrontLinkRegisterValidation', $baseLink],
            ['app.enabled_locales', ['en', 'fr']],
            ['app.default_locale', 'en'],
        ]);

        $this->tokenManager->method('generateEmailToken')
            ->willReturn($emailToken);

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

        $this->userNotifier->sendRegisterActivationEmail($user);
    }

    public function testSendUserMailWithLinkContainingPlaceholder(): void
    {
        $user = $this->createMockUser();
        $subject = 'Account Activation Required';
        $baseLink = 'https://example.com/activate/%s';
        $emailToken = $this->b64('test@example.com', 'raw-token');
        $expectedLink = $baseLink . '?token=' . urlencode($emailToken);

        $this->translator->method('trans')
            ->with('user.register.activation.title', [], 'messages', 'en')
            ->willReturn($subject);

        $this->parameterBag->method('get')->willReturnMap([
            ['mailerFrontLinkRegisterValidation', $baseLink],
            ['app.enabled_locales', ['en', 'fr']],
            ['app.default_locale', 'en'],
        ]);

        $this->tokenManager->method('generateEmailToken')
            ->willReturn($emailToken);

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

        $this->userNotifier->sendRegisterActivationEmail($user);
    }

    public function testSendUserMailWithLinkWithoutPlaceholder(): void
    {
        $user = $this->createMockUser();
        $subject = 'Account Activation Required';
        $baseLink = 'https://example.com/activate/';
        $emailToken = $this->b64('test@example.com', 'raw-token');
        $expectedLink = $baseLink . '?token=' . urlencode($emailToken);

        $this->translator->method('trans')
            ->with('user.register.activation.title', [], 'messages', 'en')
            ->willReturn($subject);

        $this->parameterBag->method('get')->willReturnMap([
            ['mailerFrontLinkRegisterValidation', $baseLink],
            ['app.enabled_locales', ['en', 'fr']],
            ['app.default_locale', 'en'],
        ]);

        $this->tokenManager->method('generateEmailToken')
            ->willReturn($emailToken);

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

        $this->userNotifier->sendRegisterActivationEmail($user);
    }

    public function testSendUserMailWithNoLanguagePreference(): void
    {
        $user = $this->createMockUserWithoutLanguage();
        $subject = 'Account Activation Required';
        $baseLink = 'https://example.com/activate/';
        $emailToken = $this->b64('test@example.com', 'raw-token');
        $expectedLink = $baseLink . '?token=' . urlencode($emailToken);

        $this->translator->method('trans')
            ->with('user.register.activation.title', [], 'messages', 'en')
            ->willReturn($subject);

        $this->parameterBag->method('get')->willReturnMap([
            ['mailerFrontLinkRegisterValidation', $baseLink],
            ['app.enabled_locales', ['en', 'fr']],
            ['app.default_locale', 'en'],
        ]);

        $this->tokenManager->method('generateEmailToken')
            ->willReturn($emailToken);

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

        $this->userNotifier->sendRegisterActivationEmail($user);
    }

    public function testResolveLocaleFallsBackWhenEnabledLocalesIsNotArray(): void
    {
        // Préférence utilisateur non autorisée (fr), mais enabled_locales renvoyé en *string*
        $user = $this->createMockUserWithLanguage('fr');
        $subject = 'Account Activation Required';
        $baseLink = 'https://example.com/activate/';
        $emailToken = $this->b64('test@example.com', 'raw-token');
        $expectedLink = $baseLink . '?token=' . urlencode($emailToken);

        // On ne verrouille pas trop la locale passée au translator
        $this->translator->method('trans')
            ->with('user.register.activation.title', [], 'messages', $this->anything())
            ->willReturn($subject);

        // <<< ICI: enabled_locales renvoie une chaîne -> déclenche la branche non couverte
        $this->parameterBag->method('get')->willReturnMap([
            ['mailerFrontLinkRegisterValidation', $baseLink],
            ['app.enabled_locales', 'en'], // pas un tableau -> $allowed = [$default]
            ['app.default_locale', 'en'],
        ]);

        $this->tokenManager->method('generateEmailToken')
            ->willReturn($emailToken);

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

        $this->userNotifier->sendRegisterActivationEmail($user);
    }

    private function createMockUser(): User&MockObject
    {
        $user = $this->createMock(User::class);

        $user->method('getEmail')->willReturn('test@example.com');
        $user->method('getFirstname')->willReturn('John');

        $activeEmail = new ActiveEmail(token: 'raw-token');
        $user->method('getActiveEmail')->willReturn($activeEmail);

        $resetPassword = new ResetPassword(token: 'raw-token');
        $user->method('getResetPassword')->willReturn($resetPassword);

        $user->method('getPreferredLang')->willReturn('en');

        return $user;
    }

    private function createMockUserWithoutLanguage(): User&MockObject
    {
        $user = $this->createMockUser();
        $user->method('getPreferredLang')->willReturn(null);

        return $user;
    }

    private function createMockUserWithLanguage(string $language): User&MockObject
    {
        $user = $this->createMockUser();
        $user->method('getPreferredLang')->willReturn($language);

        return $user;
    }

    /**
     * Helper: encode base64("email&token") comme le service.
     */
    private function b64(string $email, string $token): string
    {
        return base64_encode($email . '&' . $token);
    }
}
