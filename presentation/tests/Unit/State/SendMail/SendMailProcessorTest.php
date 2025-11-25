<?php

declare(strict_types=1);

namespace App\Presentation\Tests\Unit\State\SendMail;

use ApiPlatform\Metadata\Operation;
use App\Presentation\SendMail\Dto\SendMailInput;
use App\Presentation\SendMail\Dto\SendMailOutput;
use App\Presentation\SendMail\State\SendMailProcessor;
use App\Presentation\Shared\State\PresentationErrorCode;
use Exception;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use stdClass;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;

final class SendMailProcessorTest extends KernelTestCase
{
    private SendMailProcessor $sendMailProcessor;

    /** @var MailerInterface&MockObject */
    private MockObject $mailer;

    /** @var ParameterBagInterface&MockObject */
    private MockObject $parameterBag;

    /** @var SendMailInput&MockObject */
    private MockObject $data;

    /** @var Request&MockObject */
    private MockObject $request;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->data = $this->createMock(SendMailInput::class);
        $this->request = $this->createMock(Request::class);

        $this->sendMailProcessor = new SendMailProcessor(
            $this->mailer,
            $this->parameterBag
        );
    }

    public function testProcessWithInvalidDataThrowsLogicException(): void
    {
        $invalidData = new stdClass();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(PresentationErrorCode::INVALID_INPUT->value);

        /** @var Operation&MockObject $operation */
        $operation = $this->createMock(Operation::class);
        $this->sendMailProcessor->process($invalidData, $operation);
    }

    public function testProcessSendsEmailSuccessfully(): void
    {
        /** @var Operation&MockObject $operation */
        $operation = $this->createMock(Operation::class);
        $context = ['request' => $this->request];

        $this->data->name = 'John Doe';
        $this->data->email = 'john@example.com';
        $this->data->subject = 'Test Subject';
        $this->data->message = 'Test Message';

        $this->request
            ->expects($this->once())
            ->method('getPreferredLanguage')
            ->with(['en', 'fr'])
            ->willReturn('en');

        $this->parameterBag
            ->method('get')
            ->willReturnMap([
                ['app.enabled_locales', ['en', 'fr']],
                ['mailer_to', 'admin@example.com'],
            ]);

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) {
                $this->assertSame('john@example.com', $email->getFrom()[0]->getAddress());
                $this->assertSame('John Doe', $email->getFrom()[0]->getName());
                $this->assertSame('admin@example.com', $email->getTo()[0]->getAddress());
                $this->assertSame('Message from John Doe: Test Subject', $email->getSubject());
                $this->assertSame('emails/en_sendmail.html.twig', $email->getHtmlTemplate());

                return true;
            }));

        $result = $this->sendMailProcessor->process($this->data, $operation, [], $context);

        $this->assertInstanceOf(SendMailOutput::class, $result);
        $this->assertSame('Email sent successfully', $result->message);
    }

    public function testProcessWithFrenchLanguage(): void
    {
        /** @var Operation&MockObject $operation */
        $operation = $this->createMock(Operation::class);
        $context = ['request' => $this->request];

        $this->data->name = 'Jean Dupont';
        $this->data->email = 'jean@example.com';
        $this->data->subject = 'Sujet Test';
        $this->data->message = 'Message Test';

        $this->request
            ->expects($this->once())
            ->method('getPreferredLanguage')
            ->with(['en', 'fr'])
            ->willReturn('fr');

        $this->parameterBag
            ->method('get')
            ->willReturnMap([
                ['app.enabled_locales', ['en', 'fr']],
                ['mailer_to', 'admin@example.com'],
            ]);

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) {
                $this->assertSame('emails/fr_sendmail.html.twig', $email->getHtmlTemplate());

                return true;
            }));

        $result = $this->sendMailProcessor->process($this->data, $operation, [], $context);

        $this->assertInstanceOf(SendMailOutput::class, $result);
    }

    public function testProcessWithNoRequestContextUsesDefaultLanguage(): void
    {
        /** @var Operation&MockObject $operation */
        $operation = $this->createMock(Operation::class);
        $context = [];

        $this->data->name = 'Jane Doe';
        $this->data->email = 'jane@example.com';
        $this->data->subject = 'Test Subject';
        $this->data->message = 'Test Message';

        $this->parameterBag
            ->method('get')
            ->with('mailer_to')
            ->willReturn('admin@example.com');

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) {
                $this->assertSame('emails/en_sendmail.html.twig', $email->getHtmlTemplate());

                return true;
            }));

        $result = $this->sendMailProcessor->process($this->data, $operation, [], $context);

        $this->assertInstanceOf(SendMailOutput::class, $result);
    }

    public function testProcessWithRealRequestUsesPreferredLanguage(): void
    {
        /** @var Operation&MockObject $operation */
        $operation = $this->createMock(Operation::class);

        $realRequest = new Request();
        $realRequest->headers->set('Accept-Language', 'en');

        $context = ['request' => $realRequest];

        $this->data->name = 'Bob Smith';
        $this->data->email = 'bob@example.com';
        $this->data->subject = 'Test Subject';
        $this->data->message = 'Test Message';

        $this->parameterBag
            ->method('get')
            ->willReturnMap([
                ['app.enabled_locales', ['en', 'fr']],
                ['mailer_to', 'admin@example.com'],
            ]);

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) {
                $this->assertSame('emails/en_sendmail.html.twig', $email->getHtmlTemplate());

                return true;
            }));

        $result = $this->sendMailProcessor->process($this->data, $operation, [], $context);

        $this->assertInstanceOf(SendMailOutput::class, $result);
    }

    public function testProcessWithMailerExceptionThrowsRuntimeException(): void
    {
        /** @var Operation&MockObject $operation */
        $operation = $this->createMock(Operation::class);
        $context = ['request' => $this->request];

        $this->data->name = 'John Doe';
        $this->data->email = 'john@example.com';
        $this->data->subject = 'Test Subject';
        $this->data->message = 'Test Message';

        $this->request
            ->expects($this->once())
            ->method('getPreferredLanguage')
            ->with(['en', 'fr'])
            ->willReturn('en');

        $this->parameterBag
            ->method('get')
            ->willReturnMap([
                ['app.enabled_locales', ['en', 'fr']],
                ['mailer_to', 'admin@example.com'],
            ]);

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new Exception('Mailer error'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to send email. Please try again later.');

        $this->sendMailProcessor->process($this->data, $operation, [], $context);
    }

    public function testProcessWithCorrectEmailContext(): void
    {
        /** @var Operation&MockObject $operation */
        $operation = $this->createMock(Operation::class);
        $context = ['request' => $this->request];

        $this->data->name = 'Alice Johnson';
        $this->data->email = 'alice@example.com';
        $this->data->subject = 'Important Subject';
        $this->data->message = 'Important message content';

        $this->request
            ->expects($this->once())
            ->method('getPreferredLanguage')
            ->with(['en', 'fr'])
            ->willReturn('en');

        $this->parameterBag
            ->method('get')
            ->willReturnMap([
                ['app.enabled_locales', ['en', 'fr']],
                ['mailer_to', 'admin@example.com'],
            ]);

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (TemplatedEmail $email) {
                $emailContext = $email->getContext();
                $this->assertSame('Alice Johnson', $emailContext['name']);
                $this->assertSame('alice@example.com', $emailContext['emailFrom']);
                $this->assertSame('Important Subject', $emailContext['subject']);
                $this->assertSame('Important message content', $emailContext['message']);

                return true;
            }));

        $result = $this->sendMailProcessor->process($this->data, $operation, [], $context);

        $this->assertInstanceOf(SendMailOutput::class, $result);
    }
}
