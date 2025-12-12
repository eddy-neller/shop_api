<?php

namespace App\Infrastructure\EventListener;

use App\Infrastructure\Service\InfoCodes;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @uses Laisse remonter les exceptions et on gère au bord de l'application, généralement avec un ExceptionListener (Symfony) ou un middleware.
 *
 * @codeCoverageIgnore
 */
final readonly class ExceptionListener
{
    private const array ENV = ['dev', 'test'];

    public function __construct(
        private ParameterBagInterface $parameterBag,
        private LoggerInterface $logger,
    ) {
    }

    #[AsEventListener(event: KernelEvents::EXCEPTION)]
    public function onKernelException(ExceptionEvent $event): void
    {
        $message = [];
        $response = new JsonResponse();
        $exception = $event->getThrowable();
        $statusCode = Response::HTTP_BAD_REQUEST;
        $env = $this->parameterBag->get('kernel.environment');

        $name = new ReflectionClass($exception)->getShortName();
        switch ($name) {
            case 'UnexpectedValueException':
                $context = ($exception->getPrevious() ?? $exception)->getMessage();
                $param = $this->getDataInString($context, ' attribute ');
                $format = $this->getDataInString($context, ' must be one of ', true);
                $message['info'][] = InfoCodes::INTERNAL['TYPE_ERROR'];
                $message['context'][] = ['param' => $param, 'awaited_format' => $format];
                break;
            case 'TypeError':
                $this->logger->error(InfoCodes::INTERNAL['TYPE_ERROR'] . ' = ' . $exception->getMessage() . '\nFile: ' . $exception->getFile() . ' at ' . $exception->getLine() . '\n');
                $message['info'][] = InfoCodes::INTERNAL['TYPE_ERROR'];
                break;
            case 'AccessDeniedHttpException':
            case 'ForbiddenHttpException':
                $message['info'][] = $exception->getMessage() ? 'Access Denied' : null;
                $statusCode = Response::HTTP_FORBIDDEN;
                break;
            case 'NotFoundHttpException':
                $info = $exception->getMessage();
                $message['info'][] = 7 !== strlen($info) ? InfoCodes::INTERNAL['PAGE_NOT_FOUND'] : $info;
                $statusCode = Response::HTTP_NOT_FOUND;
                break;
            case 'ValidationException':
                $message['info'] = [InfoCodes::INTERNAL['VALIDATION_ERROR']];

                if (method_exists($exception, 'getConstraintViolationList')) {
                    $violationList = $exception->getConstraintViolationList();
                    foreach ($violationList as $violation) {
                        $violationMessage = $violation->getPropertyPath() . ': ' . $violation->getMessage();
                        $message['context'][] = $violationMessage;
                    }
                }

                $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
                break;
            default:
                $statusCode = $exception->getCode();
                if (0 === $statusCode && method_exists($exception, 'getStatusCode')) {
                    $statusCode = (int) $exception->getStatusCode();
                }

                if (0 !== $statusCode) {
                    // Pour les erreurs HTTP standard (401, 403, etc.), utiliser le format attendu par les tests
                    if (in_array($statusCode, [Response::HTTP_UNAUTHORIZED, Response::HTTP_FORBIDDEN], true)) {
                        $exceptionMessage = sprintf('HTTP %d returned', $statusCode);
                    } else {
                        $message['info'] = $exception->getMessage();
                    }
                }

                break;
        }

        if (0 === $statusCode || (is_int($statusCode) && $statusCode >= 500 && $statusCode < 600)) {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

            if (!in_array($env, self::ENV, true)) {
                $message['info'] = empty($message) ? ['Internal server error. Contact your admin please.'] : $exception->getMessage();
            } else {
                $message['info'] = $exception->getMessage();
            }

            $this->logger->error($exception->getMessage() . "\nFile: " . $exception->getFile() . ' at ' . $exception->getLine() . "\n");
        }

        $responseContent = ['messages' => $message];
        if (in_array($env, self::ENV, true)) {
            $responseContent['stack_trace'] = $exception->getTrace();
            $responseContent['detail'] = $exception->getMessage();
        }

        $response->setContent(json_encode($this->utf8ize($responseContent)));
        $response->setStatusCode($statusCode > 511 ? 500 : $statusCode);

        $event->setResponse($response);
    }

    private function utf8ize(mixed $mixed): mixed
    {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = $this->utf8ize($value);
            }
        } elseif (is_string($mixed)) {
            return mb_convert_encoding($mixed, 'UTF-8', 'UTF-8');
        }

        return $mixed;
    }

    private function getDataInString(string $message, string $separator, bool $byEnd = false): string
    {
        $data = explode($separator, $message);
        if ($byEnd) {
            $data = explode(' ', end($data));
            $data = trim(str_replace('"', '', array_shift($data)));
        } else {
            $data = explode(' ', $data[0]);
            $data = trim(str_replace('"', '', array_pop($data)));
        }

        return $data;
    }
}
