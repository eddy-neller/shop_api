<?php

namespace App\Infrastructure\Notification\Mailer;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Throwable;
use Twig\Environment;

readonly class Mailer
{
    public function __construct(
        private Environment $twig,
        private ParameterBagInterface $parameter,
        private MailerInterface $mailer,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function sendEmail(string $to, string $subject, string $template, array $context, ?bool $response = null): bool
    {
        try {
            $email = new TemplatedEmail()
                ->from(new Address($this->parameter->get('mailer_reply'), $this->parameter->get('app_title')))
                ->to($to)
                ->subject($subject)
                ->htmlTemplate($template)
                ->context($context)
                ->html($this->twig->render($template, $context));

            $this->mailer->send($email);

            if (is_null($response)) {
                return true;
            }
        } catch (Throwable $e) {
            $this->logger?->error('send-email', ['email' => $to, 'ex' => $e]);
        }

        return true;
    }
}
