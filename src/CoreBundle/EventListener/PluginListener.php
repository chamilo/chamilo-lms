<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Class PluginListener.
 *
 * @package Chamilo\CoreBundle\EventListener
 */
class PluginListener
{
    use ContainerAwareTrait;

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
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
        $request = $event->getRequest();
        $controller = $request->get('_controller');
        // Only process legacy listener when loading legacy controller
        /*if ($controller != 'Chamilo\CoreBundle\Controller\LegacyController::classicAction') {
            return;
        }*/

        $skipControllers = [
            'web_profiler.controller.profiler:toolbarAction',
            'fos_js_routing.controller:indexAction',
        ];

        // Skip legacy listener
        if (in_array($controller, $skipControllers)) {
            return;
        }

        // Legacy way of detect current access_url
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        $controller = $request->get('_controller');

        // Only process legacy listener when loading legacy controller
        /*if ($controller != 'Chamilo\CoreBundle\Controller\LegacyController::classicAction') {
            return;
        }*/
        $skipControllers = [
            'web_profiler.controller.profiler:searchBarAction',
            'FOS\RestBundle\Controller\ExceptionController::showAction',
            'web_profiler.controller.profiler:panelAction',
            'web_profiler.controller.profiler:toolbarAction', // debug toolbar
            'fos_js_routing.controller:indexAction', // js/routing?callback=fos.Router.setData
        ];

        // Skip legacy listener
        if (in_array($controller, $skipControllers)) {
            return;
        }

        /** @var ContainerInterface $container */
        $container = $this->container;
        $installed = $this->container->getParameter('installed');
        if (!empty($installed)) {
            return;
            //$result = & api_get_settings('Plugins', 'list', $_configuration['access_url']);
            $result = &api_get_settings('Plugins', 'list', 1);

            $_plugins = [];
            foreach ($result as &$row) {
                $key = $row['variable'];
                $_plugins[$key][] = $row['selected_value'];
            }

            // Loading Chamilo plugins
            $appPlugin = new \AppPlugin();
            $pluginRegions = $appPlugin->get_plugin_regions();

            $force_plugin_load = true;
            $pluginList = $appPlugin->get_installed_plugins();
            $courseId = $request->getSession()->get('_real_cid');

            foreach ($pluginRegions as $pluginRegion) {
                $regionContent = $appPlugin->load_region(
                    $pluginRegion,
                    $container->get('twig'),
                    $_plugins,
                    $force_plugin_load
                );

                foreach ($pluginList as $pluginName) {
                    // The plugin_info variable is available inside the plugin index
                    $pluginInfo = $appPlugin->getPluginInfo($pluginName);
                    if (isset($pluginInfo['is_course_plugin']) && $pluginInfo['is_course_plugin']) {
                        if (!empty($courseId)) {
                            if (isset($pluginInfo['obj']) && $pluginInfo['obj'] instanceof \Plugin) {
                                /** @var \Plugin $plugin */
                                $plugin = $pluginInfo['obj'];
                                $regionContent .= $plugin->renderRegion($pluginRegion);
                            }
                        }
                    } else {
                        continue;
                    }
                }

                $container->get('twig')->addGlobal('plugin_'.$pluginRegion, $regionContent);
            }
        }
    }
}
