<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\SendMailInput;
use App\Dto\SendMailOutput;
use App\Service\InfoCodes;
use LogicException;
use RuntimeException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Throwable;

readonly class SendMailProcessor implements ProcessorInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SendMailOutput
    {
        if (!$data instanceof SendMailInput) {
            throw new LogicException(InfoCodes::INTERNAL['INVALID_INPUT']);
        }

        $request = $context['request'] ?? null;
        $acceptLanguage = $request instanceof Request
            ? $request->headers->get('Accept-Language', 'en')
            : 'en';

        $email = (new TemplatedEmail())
            ->from(new Address($data->email, $data->name))
            ->to($this->parameterBag->get('mailer_to'))
            ->subject(sprintf('Message from %s: %s', $data->name, $data->subject))
            ->htmlTemplate('emails/' . $acceptLanguage . '_sendmail.html.twig')
            ->context([
                'name' => $data->name,
                'emailFrom' => $data->email,
                'subject' => $data->subject,
                'message' => $data->message,
            ]);

        try {
            $this->mailer->send($email);

            return new SendMailOutput('Email sent successfully');
        } catch (Throwable) {
            throw new RuntimeException('Failed to send email. Please try again later.');
        }
    }
}
