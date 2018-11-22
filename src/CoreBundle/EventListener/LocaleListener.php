<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\SettingsBundle\Manager\SettingsManager;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class LocaleListener
 * Checks the portal listener depending of different settings:
 * platform, user, course.
 *
 * @package Chamilo\CoreBundle\EventListener
 */
class LocaleListener implements EventSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;
    private $defaultLocale;

    /**
     * LocaleListener constructor.
     *
     * @param string             $defaultLocale
     * @param ContainerInterface $container
     */
    public function __construct($defaultLocale, ContainerInterface $container)
    {
        $this->defaultLocale = $defaultLocale;
        $this->container = $container;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$request->hasPreviousSession()) {
            return;
        }

        $container = $this->container;
        $installed = $container->get('kernel')->isInstalled();

        if (!$installed) {
            return;
        }

        // Try to see if the locale has been set as a _locale routing parameter (from lang switcher)
        //if ($locale = $request->getSession('_locale')) {
        if (false) {
            //if ($locale = $request->attributes->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
        } else {
            $localeList = [];

            // 1. Check platform locale
            /** @var SettingsManager $settings */
            $settings = $this->container->get('chamilo.settings.manager');
            $platformLocale = $settings->getSetting('language.platform_language');

            if (!empty($platformLocale)) {
                $localeList['platform_lang'] = $platformLocale;
            }

            // 2. Check user locale
            // _locale_user is set when user logins the system check UserLocaleListener
            $userLocale = $request->getSession()->get('_locale_user');
            if (!empty($userLocale)) {
                //$locale = $userLocale;
                $localeList['user_profil_lang'] = $userLocale;
            }

            // 3. Check course locale
            $courseCode = $request->get('course');

            // Detect if the course was set with a cidReq:
            if (empty($courseCode)) {
                $courseCodeFromRequest = $request->get('cidReq');
                $courseCode = $courseCodeFromRequest;
            }

            /** @var EntityManager $em */
            $em = $container->get('doctrine')->getManager();

            if (!empty($courseCode)) {
                /** @var Course $course */
                $course = $em->getRepository('ChamiloCoreBundle:Course')->findOneByCode($courseCode);
                // 3. Check course locale
                /** @var Course $course */
                if (!empty($course)) {
                    $courseLocale = $course->getCourseLanguage();
                    if (!empty($courseLocale)) {
                        //$locale = $courseLocale;
                        $localeList['course_lang'] = $platformLocale;
                    }
                }
            }

            // 4. force locale if it was selected from the URL
            $localeFromUrl = $request->get('_locale');
            if (!empty($localeFromUrl)) {
                $localeList['user_selected_lang'] = $platformLocale;
            }

            $priorityList = [
                'language_priority_4',
                'language_priority_3',
                'language_priority_2',
                'language_priority_1',
            ];

            //var_dump($localeList);exit;
            $locale = '';
            foreach ($priorityList as $setting) {
                $priority = $settings->getSetting("language.$setting");
                if (!empty($priority) && isset($localeList[$priority])) {
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
                    if (isset($localeList[$setting])) {
                        //var_dump($setting);
                        $locale = $localeList[$setting];
                    }
                }
            }

            if (empty($locale)) {
                $locale = $this->defaultLocale;
            }

            // if no explicit locale has been set on this request, use one from the session
            $request->setLocale($locale);
            $request->getSession()->set('_locale', $locale);
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            // must be registered before the default Locale listener
            KernelEvents::REQUEST => [['onKernelRequest', 15]],
        ];
    }
}
