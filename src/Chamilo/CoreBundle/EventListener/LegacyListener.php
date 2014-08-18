<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

use Chamilo\CoreBundle\Framework\Container;

class LegacyListener
{
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $kernel = $event->getKernel();
        $request = $event->getRequest();
        $session = $request->getSession();
        $container = $this->container;

        // Setting session.
        Container::setSession($request->getSession());

        // Setting database.
        $dbConnection = $container->get('database_connection');

        // Setting db manager.
        $database  = new \Database($dbConnection, array());
        \Database::setManager($container->get('doctrine')->getManager());

        // Setting course tool chain.
        \CourseManager::setToolList($container->get('chamilo.tool_chain'));

        // Setting legacy properties
        Container::$urlGenerator = $container->get('router');
        Container::$security = $container->get('security.context');
        Container::$translator = $container->get('translator');
        Container::$rootDir = $container->get('kernel')->getRealRootDir();
        Container::$logDir = $container->get('kernel')->getLogDir();
        Container::$dataDir = $container->get('kernel')->getDataDir();
        Container::$tempDir = $container->get('kernel')->getCacheDir();
        Container::$courseDir = $container->get('kernel')->getDataDir();
        //Container::$configDir = $container->get('kernel')->getConfigDir();
        Container::$assets = $container->get('templating.helper.assets');
        Container::$htmlEditor = $container->get('html_editor');

        if (!defined('DEFAULT_DOCUMENT_QUOTA')) {
            $default_quota = api_get_setting('default_document_quotum');

            // Just in case the setting is not correctly set
            if (empty($default_quota)) {
                $default_quota = 100000000;
            }

            define('DEFAULT_DOCUMENT_QUOTA', $default_quota);
        }

        // Injecting course in twig.
        $courseCode = $request->get('course');

        // Detect if the course was set with a cidReq:
        if (empty($courseCode)) {
            $courseCodeFromRequest = $request->get('cidReq');
            $courseCode = $courseCodeFromRequest;
        }
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $container->get('doctrine')->getManager();

        if (!empty($courseCode)) {
            $course = $em->getRepository('ChamiloCoreBundle:Course')->findOneByCode($courseCode);
            if ($course) {
                $courseInfo = api_get_course_info($course->getCode());
                $container->get('twig')->addGlobal('course', $course);
                $request->getSession()->set('_real_cid', $course->getId());
                $request->getSession()->set('_cid', $course->getCode());
                $request->getSession()->set('_course', $courseInfo);
            }
        }

        // Loading portal settings from DB.
        $settingsRefreshInfo = $em->getRepository('ChamiloCoreBundle:SettingsCurrent')->findOneByVariable('settings_latest_update');
        $settingsLatestUpdate = !empty($settingsRefreshInfo) ? $settingsRefreshInfo->getSelectedValue() : null;

        $settings = $session->get('_setting');

        if (empty($settings)) {
            api_set_settings_and_plugins();
        } else {
            if (isset($settings['settings_latest_update']) &&
                $settings['settings_latest_update'] != $settingsLatestUpdate
            ) {
                api_set_settings_and_plugins();
            }
        }
    }

    /**
     * @param FilterResponseEvent $event
     */
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

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {

    }
}
