<?php

namespace App\Infrastructure\EventSubscriber;

use App\Infrastructure\Entity\User\User;
use App\Infrastructure\Service\InfoCodes;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTExpiredEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTInvalidEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class JWTSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $em,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => ['onAuthenticationSuccessResponse'],
            Events::AUTHENTICATION_FAILURE => ['onAuthenticationFailureResponse'],
            Events::JWT_CREATED => ['onJWTCreated'],
            Events::JWT_DECODED => ['onJWTDecoded'],
            Events::JWT_INVALID => ['onJWTInvalid'],
            Events::JWT_NOT_FOUND => ['onJWTNotFound'],
            Events::JWT_EXPIRED => ['onJWTExpired'],
        ];
    }

    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $user->setNbLogin($user->getNbLogin() + 1);
        $user->setLastVisit(new DateTimeImmutable());

        $this->em->flush();
    }

    public function onAuthenticationFailureResponse(AuthenticationFailureEvent $event): void
    {
        $response = new JWTAuthenticationFailureResponse(InfoCodes::JWT['BAD_CREDENTIALS']);

        $event->setResponse($response);
    }

    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $payload = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $payload['id'] = $user->getId();
        $payload['username'] = $user->getUsername();

        $request = $this->requestStack->getCurrentRequest();
        if ($request instanceof Request) {
            $payload['ip'] = $request->getClientIp();
        }

        $event->setData($payload);
    }

    public function onJWTDecoded(JWTDecodedEvent $event): void
    {
        $payload = $event->getPayload();

        $request = $this->requestStack->getCurrentRequest();

        if ($request instanceof Request) {
            if (!isset($payload['ip']) || $payload['ip'] !== $request->getClientIp()) {
                $event->markAsInvalid();
            }
        }
    }

    public function onJWTInvalid(JWTInvalidEvent $event): void
    {
        $response = new JWTAuthenticationFailureResponse(InfoCodes::JWT['INVALID_TOKEN']);

        $event->setResponse($response);
    }

    public function onJWTNotFound(JWTNotFoundEvent $event): void
    {
        $response = new JWTAuthenticationFailureResponse(InfoCodes::JWT['MISSING_TOKEN']);

        $event->setResponse($response);
    }

    public function onJWTExpired(JWTExpiredEvent $event): void
    {
        $response = new JWTAuthenticationFailureResponse(InfoCodes::JWT['EXPIRED_TOKEN']);

        $event->setResponse($response);
    }
}
