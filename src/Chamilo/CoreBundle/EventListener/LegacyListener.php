<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Doctrine\ORM\EntityManager;

use Chamilo\CoreBundle\Framework\Container;

/**
 * Class LegacyListener
 * Adds objects into the session like the old global.inc
 * @package Chamilo\CoreBundle\EventListener
 */
class LegacyListener
{
    /** @var ContainerInterface */
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
        /** @var ContainerInterface $container */
        $container = $this->container;

        // Setting container
        Container::setContainer($container);

        // Setting session.
        Container::setSession($request->getSession());

        // Setting database.
        $dbConnection = $container->get('database_connection');

        // Setting DB connection and Doctrine Manager.
        $database = new \Database();
        $database->setConnection($dbConnection);
        $entityManager = $container->get('doctrine')->getManager();
        $database->setManager($entityManager);

        // Setting course tool chain (in order to create tools to a course)
        \CourseManager::setToolList($container->get('chamilo_course.tool_chain'));
        \CourseManager::setEntityManager($entityManager);
        \CourseManager::setCourseManager($container->get('chamilo_core.manager.course'));
        \CourseManager::setCourseSettingsManager($container->get('chamilo_course.settings.manager'));
        Container::$mailer = $container->get('mailer');


        // Setting legacy properties.
        Container::$urlGenerator = $container->get('router');
        Container::$security = $container->get('security.context');
        Container::$translator = $container->get('translator');

        // Setting paths.
        Container::$rootDir = $container->get('kernel')->getRealRootDir();
        Container::$logDir = $container->get('kernel')->getLogDir();
        Container::$dataDir = $container->get('kernel')->getDataDir();
        Container::$tempDir = $container->get('kernel')->getCacheDir();
        Container::$courseDir = $container->get('kernel')->getDataDir();
        //Container::$configDir = $container->get('kernel')->getConfigDir();
        Container::$assets = $container->get('templating.helper.assets');
        Container::$roles = $container->get('security.role_hierarchy');

        // Setting editor
        Container::$htmlEditor = $container->get('chamilo_core.html_editor');

        if (!defined('DEFAULT_DOCUMENT_QUOTA')) {
            $default_quota = api_get_setting('document.default_document_quotum');

            // Just in case the setting is not correctly set
            if (empty($default_quota)) {
                $default_quota = 100000000;
            }

            define('DEFAULT_DOCUMENT_QUOTA', $default_quota);
        }


        // Access URL (multiple URL)
        /** @var \Sonata\PageBundle\Model\SnapshotPageProxy $page */
        $page = $request->get('page');
        if (isset($page) && !is_numeric($page)) {
            $siteId = $page->getSite()->getId();
            $request->getSession()->set('url_info', $page->getSite());
        } else {
            $siteId = 1;
        }

        $request->getSession()->set('url_id', $siteId);

/*
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
        }*/
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {

    }
}
