<?php

namespace App\EventListener;

use Gedmo\Translatable\TranslatableListener;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST)]
readonly class LocaleListener
{
    public function __construct(
        private TranslatableListener $translatableListener,
        private array $enabledLocales,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $locale = $event->getRequest()->getPreferredLanguage($this->enabledLocales);
        $this->translatableListener->setTranslationFallback(true);
        $this->translatableListener->setTranslatableLocale($locale);
    }
}
