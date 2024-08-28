<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Checks the portal listener depending of different settings:
 * platform, user, course.
 */
class LocaleSubscriber implements EventSubscriberInterface
{
    protected string $defaultLocale;
    protected ParameterBagInterface $parameterBag;
    protected SettingsManager $settingsManager;

    public function __construct(string $defaultLocale, SettingsManager $settingsManager, ParameterBagInterface $parameterBag)
    {
        $this->defaultLocale = $defaultLocale;
        $this->settingsManager = $settingsManager;
        $this->parameterBag = $parameterBag;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        /*if (!$request->hasPreviousSession()) {
            return;
        }*/

        $installed = false;
        if ($this->parameterBag->has('installed')) {
            $installed = 1 === (int) $this->parameterBag->get('installed');
        }

        if (!$installed) {
            return;
        }

        if (!$request->hasSession()) {
            return;
        }

        $sessionHandler = $request->getSession();

        if ($attrLocale = $request->query->get('_locale')) {
            $sessionHandler->set('_selected_locale', $attrLocale);
        }

        $locale = $this->getCurrentLanguage($request);
        // if no explicit locale has been set on this request, use one from the session
        $request->setLocale($locale);
        $sessionHandler->set('_locale', $locale);
    }

    public function getCurrentLanguage(Request $request): string
    {
        $sessionHandler = $request->getSession();
        $localeList = [];

        // 1. Check platform locale;
        if ($platformLocale = $this->settingsManager->getSetting('language.platform_language')) {
            $localeList['platform_lang'] = $platformLocale;
        }

        // 2. Check user locale
        // _locale_user is set when user logins the system check UserLocaleListener
        if ($userLocale = $sessionHandler->get('_locale_user')) {
            $localeList['user_profil_lang'] = $userLocale;
        }

        // 3. Check course locale
        if ($request->query->getInt('cid')
            || $request->request->getInt('cid')
            || $request->attributes->getInt('cid')
        ) {
            /** @var Course|null $course */
            // 3. Check course locale
            if ($course = $sessionHandler->get('course')) {
                if ($courseLocale = $course->getCourseLanguage()) {
                    $localeList['course_lang'] = $courseLocale;
                }
            }
        }

        // 4. force locale if it was selected from the URL
        if ($localeFromUrl = $sessionHandler->get('_selected_locale')) {
            $localeList['user_selected_lang'] = $localeFromUrl;
        }

        $priorityList = [
            'language_priority_1',
            'language_priority_2',
            'language_priority_3',
            'language_priority_4',
        ];

        $locale = '';
        foreach ($priorityList as $setting) {
            $priority = $this->settingsManager->getSetting(\sprintf('language.%s', $setting));
            if (!empty($priority) && !empty($localeList[$priority])) {
                $locale = $localeList[$priority];

                break;
            }
        }

        if (empty($locale)) {
            // Use default order
            $priorityList = [
                'platform_lang',
                'user_profil_lang',
                'course_lang',
                'user_selected_lang',
            ];
            foreach ($priorityList as $setting) {
                if (!empty($localeList[$setting])) {
                    $locale = $localeList[$setting];
                }
            }
        }

        if (empty($locale)) {
            $locale = $this->defaultLocale;
        }

        return $locale;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // must be registered before the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
