<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Entity\Course;
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

        // 5) Honor configured priorities language_priority_1..4
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

        // 6) Fallback order when priorities are absent
        foreach (['platform_lang', 'user_profil_lang', 'course_lang', 'user_selected_lang'] as $key) {
            if (!empty($localeList[$key])) {
                return $localeList[$key];
            }
        }

        // 7) Final fallback to system default
        return $this->defaultLocale;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Must run before Symfony's default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
