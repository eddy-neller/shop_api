<?php

namespace App\Infrastructure\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE)]
readonly class PaginationHeaderListener
{
    public function __invoke(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $totalItems = $request->attributes->get('_total_items');
        $totalPages = $request->attributes->get('_total_pages');

        if (null !== $totalItems) {
            $response->headers->set('X-Total-Count', $totalItems);
            $response->headers->set('X-Total-Pages', $totalPages ?? 1);
        }
    }
}
