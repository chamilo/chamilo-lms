<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class LocaleListener.
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
        /** @var Request $request */
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        // try to see if the locale has been set as a _locale routing parameter
        if ($locale = $request->get('_locale')) {
            $request->headers->set('Accept-Language', $locale);
            $request->getSession()->set('_locale', $locale);
            $request->setLocale($locale);
        } else {
            $locale = $this->defaultLocale;

            // 2. Check user locale
            // _locale_user is set when user logins the system check UserLocaleListener
            $userLocale = $request->getSession()->get('_locale');
            if (!empty($userLocale)) {
                $locale = $userLocale;
            }

            // if no explicit locale has been set on this request, use one from the session
            $request->setLocale($locale);
            $request->getSession()->set('_locale', $locale);
        }

        /** @var EntityManager $em */
        $em = $this->container->get('doctrine')->getManager();
        $criteria = ['variable' => 'header_extra_content'];
        $setting = $em->getRepository('ChamiloCoreBundle:SettingsCurrent')->findOneBy($criteria);
        if ($setting) {
            $content = '';
            if (is_file($setting->getSelectedValue())) {
                $content = file_get_contents($setting->getSelectedValue());
            }
            $this->container->get('twig')->addGlobal('header_extra_content', $content);
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
