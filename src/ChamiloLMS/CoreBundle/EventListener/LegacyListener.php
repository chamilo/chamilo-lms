<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

use \ChamiloSession as Session;

class LegacyListener
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $kernel = $event->getKernel();
        $request = $event->getRequest();
        $container = $this->container;

        // Loading legacy variables
        Session::setSession($request->getSession());
        $dbConnection = $this->container->get('database_connection');
        $database  = new \Database($dbConnection, array());
        \Database::setManager($this->container->get('doctrine')->getManager());
        Session::$urlGenerator = $this->container->get('router');
        Session::$security = $this->container->get('security.context');
        Session::$translator = $this->container->get('translator');
        Session::$rootDir = $this->container->get('kernel')->getRealRootDir();
        Session::$logDir = $this->container->get('kernel')->getLogDir();
        Session::$dataDir = $this->container->get('kernel')->getDataDir();
        Session::$tempDir = $this->container->get('kernel')->getCacheDir();
        Session::$courseDir = $this->container->get('kernel')->getDataDir();
        Session::$configDir = $this->container->get('kernel')->getConfigDir();

        // Injecting course in twig
        $courseCode = $request->get('code');

        if (empty($courseCode)) {
            $courseCodeFromRequest = $request->get('cidReq');
            $courseCode = $courseCodeFromRequest;
        }

        if (!empty($courseCode)) {
            $em = $this->container->get('doctrine')->getManager();
            $course = $em->getRepository('ChamiloLMSCoreBundle:Course')->findOneByCode($courseCode);
            if ($course) {
                $this->container->get('twig')->addGlobal('course', $course);
                $request->getSession()->set('_real_cid', $course->getId());
                $request->getSession()->set('_cid', $course->getCode());
                $courseInfo = api_get_course_info($course->getCode());
                $request->getSession()->set('_course', $courseInfo);
            }
        }

        // Loading portal settings from DB.
        $settingsRefreshInfo = api_get_settings_params_simple(array('variable = ?' => 'settings_latest_update'));
        $settingsLatestUpdate = $settingsRefreshInfo ? $settingsRefreshInfo['selected_value'] : null;

        $settings = Session::read('_setting');

        if (empty($settings)) {
            api_set_settings_and_plugins();
        } else {
            if (isset($settings['settings_latest_update']) && $settings['settings_latest_update'] != $settingsLatestUpdate) {
                api_set_settings_and_plugins();
            }
        }
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $request = $event->getRequest();
        $kernel = $event->getKernel();
        $container = $this->container;

        /*switch ($request->query->get('option')) {
            case 2:
                $response->setContent('Blah');
                break;

            case 3:
                $response->headers->setCookie(new Cookie('test', 1));
                break;
        }*/
    }

    public function onKernelController(FilterControllerEvent $event)
    {

    }
}
