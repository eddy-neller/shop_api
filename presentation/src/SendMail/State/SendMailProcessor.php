<?php

namespace App\Presentation\SendMail\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Shared\Messenger\Message\SendEmailMessage;
use App\Presentation\SendMail\Dto\SendMailInput;
use App\Presentation\SendMail\Dto\SendMailOutput;
use App\Presentation\Shared\State\PresentationErrorCode;
use LogicException;
use RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

readonly class SendMailProcessor implements ProcessorInterface
{
    public function __construct(
        private MessageBusInterface $bus,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SendMailOutput
    {
        if (!$data instanceof SendMailInput) {
            throw new LogicException(PresentationErrorCode::INVALID_INPUT->value);
        }

        $request = $context['request'] ?? null;

        $acceptLanguage = $request instanceof Request
            ? $request->getPreferredLanguage($this->parameterBag->get('app.enabled_locales'))
            : 'en';

        try {
            $this->bus->dispatch(new SendEmailMessage(
                to: $this->parameterBag->get('mailer_to'),
                subject: sprintf('Message from %s: %s', $data->name, $data->subject),
                template: 'emails/' . $acceptLanguage . '_sendmail.html.twig',
                context: [
                    'name' => $data->name,
                    'emailFrom' => $data->email,
                    'subject' => $data->subject,
                    'message' => $data->message,
                ],
            ));

            return new SendMailOutput('Email sent successfully');
        } catch (Throwable) {
            throw new RuntimeException('Failed to send email. Please try again later.');
        }
    }
}
