<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Chamilo\CoreBundle\Framework\Container;

/**
 * Class LegacyListener
 * Works as old global.inc.php
 * Setting old php requirements so pages inside main/* could work correctly.
 *
 * @package Chamilo\CoreBundle\EventListener
 */
class LegacyListener
{
    use ContainerAwareTrait;

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $controller = $request->get('_controller');
        // Only process legacy listener when loading legacy controller
        /*if ($controller != 'Chamilo\CoreBundle\Controller\LegacyController::classicAction') {
            return;
        }*/

        /*$skipControllers = [
            'web_profiler.controller.profiler:toolbarAction', //
            'fos_js_routing.controller:indexAction'//
        ];

        // Skip legacy listener
        if (in_array($controller, $skipControllers)) {
            return;
        }*/

        $session = $request->getSession();

        /** @var ContainerInterface $container */
        $container = $this->container;

        // Setting container
        Container::setContainer($container);
        Container::setLegacyServices($container);
        Container::setRequest($request);

        // Legacy way of detect current access_url
        $installed = $this->container->getParameter('installed');
        $urlId = 1;

        if (!empty($installed)) {
            $access_urls = api_get_access_urls();
            $root_rel = api_get_self();
            $root_rel = substr($root_rel, 1);
            $pos = strpos($root_rel, '/');
            $root_rel = substr($root_rel, 0, $pos);
            $protocol = ((!empty($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) != 'OFF') ? 'https' : 'http').'://';
            //urls with subdomains (HTTP_HOST is preferred - see #6764)
            if (empty($_SERVER['HTTP_HOST'])) {
                if (empty($_SERVER['SERVER_NAME'])) {
                    $request_url_root = $protocol.'localhost/';
                } else {
                    $request_url_root = $protocol.$_SERVER['SERVER_NAME'].'/';
                }
            } else {
                $request_url_root = $protocol.$_SERVER['HTTP_HOST'].'/';
            }
            //urls with subdirs
            $request_url_sub = $request_url_root.$root_rel.'/';

            // You can use subdirs as multi-urls, but in this case none of them can be
            // the root dir. The admin portal should be something like https://host/adm/
            // At this time, subdirs will still hold a share cookie, so not ideal yet
            // see #6510
            foreach ($access_urls as $details) {
                if ($request_url_sub == $details['url']) {
                    $urlId = $details['id'];
                    break; //found one match with subdir, get out of foreach
                }
                // Didn't find any? Now try without subdirs
                if ($request_url_root == $details['url']) {
                    $urlId = $details['id'];
                    break; //found one match, get out of foreach
                }
            }

            // Set legacy twig globals _p, _u, _s
            $globals = \Template::getGlobals();
            foreach ($globals as $index => $value) {
                $container->get('twig')->addGlobal($index, $value);
            }

            $_admin = [
                'email' => api_get_setting('emailAdministrator'),
                'surname' => api_get_setting('administratorSurname'),
                'name' => api_get_setting('administratorName'),
                'telephone' => api_get_setting('administratorTelephone')
            ];

            $container->get('twig')->addGlobal('_admin', $_admin);

            $theme = api_get_visual_theme();
            $container->get('twig')->addGlobal('favico', \Template::getPortalIcon($theme));

            $extraFooter = trim(api_get_setting('footer_extra_content'));
            $container->get('twig')->addGlobal('footer_extra_content', $extraFooter);

            $extraHeader = trim(api_get_setting('header_extra_content'));
            $container->get('twig')->addGlobal('header_extra_content', $extraHeader);
        }

        // We set cid_reset = true if we enter inside a main/admin url
        // CourseListener check this variable and deletes the course session
        if (strpos($request->get('name'), 'admin/') !== false) {
            $session->set('cid_reset', true);
        } else {
            $session->set('cid_reset', false);
        }

        $session->set('access_url_id', $urlId);
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
