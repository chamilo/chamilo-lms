<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Repository\LanguageRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Translation\Translator;

final class TranslatorFallbackLocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Translator $translator,
        private readonly LanguageRepository $languageRepository,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onKernelRequest', 10]];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $locale = $event->getRequest()->getLocale() ?: 'en_US';
        $fallbacks = $this->buildFallbackChain($locale);

        $this->translator->setFallbackLocales($fallbacks);
    }

    /**
     * Build fallback chain like: sublanguage -> parent -> ... -> en_US.
     */
    private function buildFallbackChain(string $locale): array
    {
        $fallbacks = [];
        $visited = [];
        $current = $locale;

        while (true) {
            $parent = $this->getParentLocaleFromDb($current);
            if (empty($parent) || isset($visited[$parent])) {
                break;
            }

            $visited[$parent] = true;
            $fallbacks[] = $parent;
            $current = $parent;
        }

        // Ensure English is always last fallback.
        if ('en_US' !== $locale && !in_array('en_US', $fallbacks, true)) {
            $fallbacks[] = 'en_US';
        }

        return array_values(array_unique($fallbacks));
    }

    private function getParentLocaleFromDb(string $locale): ?string
    {
        $lang = $this->languageRepository->findByIsoCode($locale);
        if (null === $lang) {
            return null;
        }

        $parent = $lang->getParent();
        if (null === $parent) {
            return null;
        }

        return $parent->getIsocode();
    }
}
