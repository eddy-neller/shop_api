<?php

declare(strict_types=1);

namespace App\Infrastructure\Notification\Messenger\Handler;

use App\Application\Shared\Messenger\Message\SendEmailMessage;
use App\Infrastructure\Notification\Mailer\Mailer;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SendEmailMessageHandler
{
    public function __construct(private readonly Mailer $mailer)
    {
    }

    public function __invoke(SendEmailMessage $message): void
    {
        $this->mailer->sendEmail(
            $message->to,
            $message->subject,
            $message->template,
            $message->context
        );
    }
}
