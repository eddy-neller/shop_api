<?php

namespace App\Service\User;

use App\Entity\User\User;
use App\Service\Mailer\Mailer;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserNotifier
{
    private const string TEMPLATE_REGISTER_ACTIVATION = 'emails/user/register-activation.html.twig';

    private const string TEMPLATE_RESET_PASSWORD = 'emails/user/reset-password.html.twig';

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ParameterBagInterface $bag,
        private readonly Mailer $mailer,
        private readonly TokenManager $tokenManager,
    ) {
    }

    public function sendRegisterActivationEmail(User $user): void
    {
        $this->sendUserMail(
            user: $user,
            subjectKey: 'user.register.activation.title',
            template: self::TEMPLATE_REGISTER_ACTIVATION,
            frontLinkParamKey: 'mailerFrontLinkRegisterValidation',
            tokenExtractor: static fn (User $usr) => $usr->getActiveEmail()->token,
        );
    }

    public function sendResetPasswordEmail(User $user): void
    {
        $this->sendUserMail(
            user: $user,
            subjectKey: 'user.reset.password.title',
            template: self::TEMPLATE_RESET_PASSWORD,
            frontLinkParamKey: 'mailerFrontLinkResetPassword',
            tokenExtractor: static fn (User $usr) => $usr->getResetPassword()->token,
        );
    }

    private function sendUserMail(
        User $user,
        string $subjectKey,
        string $template,
        string $frontLinkParamKey,
        callable $tokenExtractor,
    ): void {
        $locale = $this->resolveLocale($user);

        $subject = $this->translator->trans($subjectKey, [], 'messages', $locale);

        $rawToken = (string) $tokenExtractor($user);
        $emailToken = $this->tokenManager->generateEmailToken($rawToken, $user->getEmail());

        $base = (string) $this->bag->get($frontLinkParamKey);
        $link = $base . '?token=' . urlencode($emailToken);

        $payload = [
            'firstname' => $user->getFirstname(),
            'link' => $link,
            'userLocale' => $locale,
        ];

        $this->mailer->sendEmail(
            $user->getEmail(),
            $subject,
            $template,
            $payload
        );
    }

    private function resolveLocale(User $user): string
    {
        $allowed = $this->bag->get('app.enabled_locales');
        $default = $this->bag->get('app.default_locale');

        if (!is_array($allowed)) {
            $allowed = [$default];
        }

        $lang = $user->getPreferredLang();

        return (is_string($lang) && in_array($lang, $allowed, true)) ? $lang : $default;
    }
}
