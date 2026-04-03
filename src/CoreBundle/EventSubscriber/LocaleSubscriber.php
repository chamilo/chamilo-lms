<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handles locale selection based on platform, user, and course settings.
 */
class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private string $defaultLocale,
        private SettingsManager $settingsManager,
        private ParameterBagInterface $parameterBag,
        private SettingsCourseManager $courseSettingsManager,
        private EntityManagerInterface $em,
        private LanguageRepository $languageRepository,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Skip if not installed or no session is available
        $installed = $this->parameterBag->has('installed') && 1 === (int) $this->parameterBag->get('installed');
        if (!$installed || !$request->hasSession()) {
            return;
        }

        $session = $request->getSession();

        // Honor explicit override via ?_locale=xx (persist for later requests)
        if ($queryLocale = $request->query->get('_locale')) {
            $session->set('_selected_locale', $queryLocale);
        }

        // Resolve locale and apply
        $locale = $this->getCurrentLanguage($request);
        $request->setLocale($locale);
        $session->set('_locale', $locale);
    }

    public function getCurrentLanguage(Request $request): string
    {
        $session = $request->getSession();
        $localeList = [];

        // 1) Platform default
        if ($platformLocale = $this->settingsManager->getSetting('language.platform_language')) {
            $localeList['platform_lang'] = $platformLocale;
        }

        // 2) User profile (from session)
        if ($userLocale = $session->get('_locale_user')) {
            $localeList['user_profil_lang'] = $userLocale;
        }

        // 3) Course language, or user language if course allows it
        // First try: course already in session
        $course = $session->get('course');

        // Fallback: resolve course from request if not in session yet
        if (!$course instanceof Course) {
            // Accept both numeric id (?cid=123) and code (?cid=ABC) as well as legacy ?cidReq=CODE
            $cid = $request->query->get('cid');
            $cidReq = $request->query->get('cidReq');

            if ($cid) {
                if (ctype_digit((string) $cid)) {
                    $course = $this->em->getRepository(Course::class)->find((int) $cid);
                } else {
                    $course = $this->em->getRepository(Course::class)->findOneBy(['code' => (string) $cid]);
                }
            }

            if (!$course && $cidReq) {
                $course = $this->em->getRepository(Course::class)->findOneBy(['code' => (string) $cidReq]);
            }
        }

        if ($course instanceof Course) {
            $userLocale = $localeList['user_profil_lang'] ?? null;
            $courseLocale = $course->getCourseLanguage();

            // The per-course setting decides whether to use user language
            $this->courseSettingsManager->setCourse($course);
            $allowUser = '1' === $this->courseSettingsManager->getCourseSettingValue('show_course_in_user_language');

            if ($allowUser && $userLocale) {
                $localeList['course_lang'] = $userLocale;
            } elseif ($courseLocale) {
                $localeList['course_lang'] = $courseLocale;
            }
        }

        // 4) Explicit selection via URL (?_locale=xx) saved earlier in session
        if ($selected = $session->get('_selected_locale')) {
            $localeList['user_selected_lang'] = $selected;
        }

        // 5) Browser Accept-Language preference (overrides platform default, but not user/course/selected)
        if (empty($localeList['user_profil_lang'])) {
            $browserLocale = $this->detectBrowserLanguage($request);
            if (null !== $browserLocale) {
                $localeList['browser_lang'] = $browserLocale;
            }
        }

        // 6) Honor configured priorities language_priority_1..4
        // Collect only the priorities that have a resolved value, preserving order.
        // browser_lang may substitute platform_lang, but only when platform_lang is the
        // last matching priority — i.e. no more-specific configured priority follows it.
        $matchingPriorities = [];
        foreach (['language_priority_1', 'language_priority_2', 'language_priority_3', 'language_priority_4'] as $settingKey) {
            $priority = $this->settingsManager->getSetting("language.$settingKey");
            if (!empty($priority) && !empty($localeList[$priority])) {
                $matchingPriorities[] = $priority;
            }
        }

        foreach ($matchingPriorities as $index => $priority) {
            if ('platform_lang' === $priority && !empty($localeList['browser_lang'])) {
                // browser_lang replaces platform_lang only when no subsequent priority matches.
                if (!isset($matchingPriorities[$index + 1])) {
                    return $localeList['browser_lang'];
                }
                // A more-specific priority follows — skip platform_lang and let it win.
                continue;
            }

            return $localeList[$priority];
        }

        // 7) Fallback order when priorities are absent — last non-empty wins (lowest → highest priority)
        $result = $this->defaultLocale;
        foreach (['platform_lang', 'browser_lang', 'user_profil_lang', 'course_lang', 'user_selected_lang'] as $key) {
            if (!empty($localeList[$key])) {
                $result = $localeList[$key];
            }
        }

        return $result;
    }

    /**
     * Parses the browser's Accept-Language header and returns the best matching
     * available Chamilo language isocode, or null if none matches.
     *
     * Matching order for each browser preference (highest quality first):
     *   1. Exact isocode match      (e.g. "fr-FR" → "fr_FR")
     *   2. Bare root exact match    (e.g. "fr-BE" → root "fr" → isocode "fr")
     *   3. Root prefix match        (e.g. "fr-BE" → root "fr" → first available "fr_XX")
     */
    private function detectBrowserLanguage(Request $request): ?string
    {
        $acceptLanguage = $request->headers->get('Accept-Language', '');
        if ('' === $acceptLanguage) {
            return null;
        }

        // Build [langTag => quality] map, sorted by quality descending
        $preferences = [];
        foreach (explode(',', $acceptLanguage) as $part) {
            $part = trim($part);
            if (str_contains($part, ';q=')) {
                [$tag, $q] = explode(';q=', $part, 2);
                $preferences[trim($tag)] = (float) $q;
            } else {
                $preferences[$part] = 1.0;
            }
        }
        arsort($preferences);

        // Fetch all available isocodes once
        $availableIsocodes = array_keys($this->languageRepository->getAllAvailableToArray());

        foreach (array_keys($preferences) as $browserTag) {
            // Normalize "fr-BE" → "fr_BE", "fr" → "fr"
            $normalized = str_replace('-', '_', $browserTag);
            if (preg_match('/^([a-z]{2})_([a-z]{2})$/i', $normalized, $m)) {
                $normalized = strtolower($m[1]).'_'.strtoupper($m[2]);
            } else {
                $normalized = strtolower($normalized);
            }

            // 1. Exact match (e.g. "fr_FR")
            if (\in_array($normalized, $availableIsocodes, true)) {
                return $normalized;
            }

            // Extract root language code ("fr" from "fr_BE" or from bare "fr")
            $root = substr($normalized, 0, 2);

            // 2. Bare root exact match (e.g. "es" isocode exists)
            if (\in_array($root, $availableIsocodes, true)) {
                return $root;
            }

            // 3. Root prefix match: "fr-BE" → no "fr_BE", no "fr" → first available "fr_XX"
            foreach ($availableIsocodes as $iso) {
                if (str_starts_with($iso, $root.'_')) {
                    return $iso;
                }
            }
        }

        return null;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Must run before Symfony's default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
