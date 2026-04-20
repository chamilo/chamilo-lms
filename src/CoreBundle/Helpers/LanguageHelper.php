<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class LanguageHelper
{
    public function __construct(
        private LanguageRepository $languageRepository,
        private RequestStack $requestStack,
        private UserHelper $userHelper,
    ) {}

    /**
     * Returns a BCP-47 language tag (WCAG-friendly), like "en-US" or "es-ES".
     * Existing behavior is preserved.
     */
    public function getWcagIso(): string
    {
        $locale = $this->requestStack->getMainRequest()?->getLocale();

        if ('' !== $locale) {
            $language = $this->languageRepository->findByIsoCode($locale);

            if ($language) {
                if ($language->getParent()) {
                    $language = $language->getParent();
                }

                return str_replace('_', '-', $language->getIsocode());
            }
        }

        return 'en-US';
    }

    /**
     * Returns the current interface language ISO for features like AI/chat.
     * Example outputs: "es", "en", "es_MX".
     */
    public function getInterfaceIso(): string
    {
        $locale = $this->resolveLocaleCandidate();
        if ('' === $locale) {
            return 'en';
        }

        $iso = $this->normalizeIso($locale);

        // Prefer exact match (es_MX), then short match (es).
        if (null !== $this->languageRepository->findByIsoCode($iso)) {
            return $iso;
        }

        $short = $this->toShortIso($iso);
        if ('' !== $short && null !== $this->languageRepository->findByIsoCode($short)) {
            return $short;
        }

        return 'en';
    }

    private function resolveLocaleCandidate(): string
    {
        // Request locale (usually already the effective UI locale)
        $reqLocale = (string) ($this->requestStack->getMainRequest()?->getLocale() ?? '');
        $reqLocale = trim($reqLocale);
        if ('' !== $reqLocale) {
            return $this->normalizeIso($reqLocale);
        }

        // Authenticated user locale (no legacy functions)
        $user = $this->userHelper->getCurrent();
        if ($user) {
            $userLocale = trim($user->getLocale());
            if ('' !== $userLocale) {
                return $this->normalizeIso($userLocale);
            }
        }

        // Platform default ISO from settings (via repository)
        $platformIso = trim((string) ($this->languageRepository->getPlatformDefaultIso() ?? ''));
        if ('' !== $platformIso) {
            return $this->normalizeIso($platformIso);
        }

        // 4) Final fallback
        return 'en';
    }

    private function normalizeIso(string $raw): string
    {
        $raw = trim($raw);
        if ('' === $raw) {
            return '';
        }

        // Convert "es-ES" to "es_ES"
        $raw = str_replace('-', '_', $raw);

        // Normalize casing for xx_YY
        if (preg_match('/^([a-z]{2})_([a-z]{2})$/i', $raw, $m)) {
            return strtolower($m[1]).'_'.strtoupper($m[2]);
        }

        // If it's already "xx_YY" keep it
        if (preg_match('/^[a-z]{2}_[A-Z]{2}$/', $raw)) {
            return $raw;
        }

        // If it's "xx", keep
        if (preg_match('/^[a-z]{2}$/', strtolower($raw))) {
            return strtolower($raw);
        }

        // Otherwise, reduce to 2 letters
        return substr(strtolower($raw), 0, 2);
    }

    public function getPlatformDefaultIso(): ?string
    {
        return $this->languageRepository->getPlatformDefaultIso();
    }

    /**
     * Resolves an ISO code (e.g. "fr_BE") to the best available Language:
     * 1. Exact match ("fr_BE")
     * 2. Short 2-letter code ("fr")
     * 3. Canonical variant where language = country ("fr_FR")
     * 4. Any available language sharing the same 2-letter prefix ("fr_*")
     */
    public function findBestAvailableMatch(string $isoCode): ?Language
    {
        $isoCode = str_replace('-', '_', $isoCode);

        if (preg_match('/^([a-zA-Z]{2})_([a-zA-Z]{2})$/', $isoCode, $m)) {
            $isoCode = strtolower($m[1]).'_'.strtoupper($m[2]);
        }

        $lang = $this->languageRepository->findByIsoCode($isoCode);
        if ($lang?->getAvailable()) {
            return $lang;
        }

        $short = strtolower(substr($isoCode, 0, 2));

        $lang = $this->languageRepository->findByIsoCode($short);
        if ($lang?->getAvailable()) {
            return $lang;
        }

        $lang = $this->languageRepository->findByIsoCode($short.'_'.strtoupper($short));
        if ($lang?->getAvailable()) {
            return $lang;
        }

        return $this->languageRepository->findFirstAvailableByIsoPrefix($short);
    }

    private function toShortIso(string $iso): string
    {
        if (preg_match('/^([a-z]{2})_/', $iso, $m)) {
            return $m[1];
        }

        return preg_match('/^[a-z]{2}$/', $iso) ? $iso : '';
    }
}
