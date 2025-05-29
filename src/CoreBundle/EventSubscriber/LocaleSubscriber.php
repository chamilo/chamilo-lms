<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
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
        private SettingsCourseManager $courseSettingsManager
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $installed = $this->parameterBag->has('installed') && 1 === (int) $this->parameterBag->get('installed');
        if (!$installed || !$request->hasSession()) {
            return;
        }

        $sessionHandler = $request->getSession();

        // Override locale if forced via ?_locale=xx
        if ($attrLocale = $request->query->get('_locale')) {
            $sessionHandler->set('_selected_locale', $attrLocale);
        }

        // Determine locale based on priority logic
        $locale = $this->getCurrentLanguage($request);

        // Apply locale to request and session
        $request->setLocale($locale);
        $sessionHandler->set('_locale', $locale);
    }

    public function getCurrentLanguage(Request $request): string
    {
        $sessionHandler = $request->getSession();
        $localeList = [];

        // 1. Platform default locale
        if ($platformLocale = $this->settingsManager->getSetting('language.platform_language')) {
            $localeList['platform_lang'] = $platformLocale;
        }

        // 2. User profile locale from session
        if ($userLocale = $sessionHandler->get('_locale_user')) {
            $localeList['user_profil_lang'] = $userLocale;
        }

        // 3. Course locale or user locale if course allows user language
        $course = $sessionHandler->get('course');
        if ($course instanceof Course) {
            $userLocale = $localeList['user_profil_lang'] ?? null;
            $courseLocale = $course->getCourseLanguage();

            $this->courseSettingsManager->setCourse($course);
            if ('1' === $this->courseSettingsManager->getCourseSettingValue('show_course_in_user_language') && $userLocale) {
                $localeList['course_lang'] = $userLocale;
            } elseif ($courseLocale) {
                $localeList['course_lang'] = $courseLocale;
            }
        }

        // 4. Locale selected manually via URL
        if ($localeFromUrl = $sessionHandler->get('_selected_locale')) {
            $localeList['user_selected_lang'] = $localeFromUrl;
        }

        // 5. Resolve locale based on configured language priorities
        foreach ([
            'language_priority_1',
            'language_priority_2',
            'language_priority_3',
            'language_priority_4',
        ] as $settingKey) {
            $priority = $this->settingsManager->getSetting("language.$settingKey");
            if (!empty($priority) && !empty($localeList[$priority])) {
                return $localeList[$priority];
            }
        }

        // 6. Fallback order if priorities are not defined
        foreach (['platform_lang', 'user_profil_lang', 'course_lang', 'user_selected_lang'] as $key) {
            if (!empty($localeList[$key])) {
                return $localeList[$key];
            }
        }

        // 7. Final fallback to system default
        return $this->defaultLocale;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // must be registered before the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
