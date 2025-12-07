<?php

declare(strict_types=1);

namespace App\Application\Shared\Messenger\Message;

/**
 * Transport payload for sending an email asynchronously.
 */
final class SendEmailMessage
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public readonly string $to,
        public readonly string $subject,
        public readonly string $template,
        public readonly array $context = [],
    ) {
    }
}
