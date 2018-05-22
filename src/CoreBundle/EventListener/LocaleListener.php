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
     * @param string $defaultLocale
     */
    public function __construct($defaultLocale = 'en', $container)
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

        $request = $event->getRequest();
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
        }

        // try to see if the locale has been set as a _locale routing parameter
        if ($locale = $request->attributes->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
        } else {
            // 1. Check platform locale
            /** @var SettingsManager $settings */
            $settings = $this->container->get('chamilo.settings.manager');
            $platformLocale = $settings->getSetting('language.platform_language');

            if (!empty($platformLocale)) {
                $locale = $platformLocale;
            }

            // 2. Check user locale
            // _locale_user is set when user logins the system check UserLocaleListener
            $userLocale = $request->getSession()->get('_locale_user');
            if (!empty($userLocale)) {
                $locale = $userLocale;
            }

            // 3. Check course locale
            /** @var Course $course */
            if (!empty($course)) {
                $courseLocale = $course->getCourseLanguage();
                if (!empty($courseLocale)) {
                    $locale = $courseLocale;
                }
            }

            // 4. force locale if it was selected from the URL
            $localeFromUrl = $request->get('_locale');
            if (!empty($localeFromUrl)) {
                $locale = $localeFromUrl;
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
            KernelEvents::REQUEST => [['onKernelRequest', 17]],
        ];
    }
}
