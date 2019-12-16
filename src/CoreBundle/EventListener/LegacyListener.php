<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Framework\Container;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Class LegacyListener
 * Works as old global.inc.php
 * Setting old php requirements so pages inside main/* could work correctly.
 */
class LegacyListener
{
    use ContainerAwareTrait;

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        $request = $event->getRequest();
        $session = $request->getSession();

        /** @var ContainerInterface $container */
        $container = $this->container;

        if (true === $request->get('load_legacy')) {
            /*$container->get('router.default')->getRouteCollection()->remove('legacy_index');
            $route = new Route('/aaa/');
            $container->get('router')->getRouteCollection()->add('legacy_index', $route);*/
        }

        /*$context = $container->get('router.request_context');
        $context->setBaseUrl('/');
        $container->get('router.default')->setContext($context);*/

        // Setting container
        Container::setRequest($request);
        Container::setContainer($container);
        Container::setLegacyServices($container);

        // Legacy way of detect current access_url
        $installed = $container->getParameter('installed');
        $urlId = 1;

        if (!empty($installed)) {
            /*$access_urls = api_get_access_urls();
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
            }*/

            $twig = $container->get('twig');

            // Set legacy twig globals _p, _u, _s
            $globals = \Template::getGlobals();
            foreach ($globals as $index => $value) {
                $twig->addGlobal($index, $value);
            }

            $token = $container->get('security.token_storage')->getToken();
            $userObject = null;
            if (null !== $token) {
                $userObject = $container->get('security.token_storage')->getToken()->getUser();
            }

            $userInfo = [];
            $userInfo['is_anonymous'] = true;
            $isAdmin = false;
            $allowedCreateCourse = false;
            $userStatus = null;
            $userId = $session->get('_uid');

            if (null !== $userObject && !empty($userId)) {
                $userInfo = api_get_user_info($userId);
                $userStatus = $userInfo['status'];
                $isAdmin = $userInfo['is_admin'];
                $userInfo['is_anonymous'] = false;
                $allowedCreateCourse = 1 === $userStatus;
            }
            $session->set('_user', $userInfo);
            $session->set('is_platformAdmin', $isAdmin);
            $session->set('is_allowedCreateCourse', $allowedCreateCourse);

            $adminInfo = [
                'email' => api_get_setting('emailAdministrator'),
                'surname' => api_get_setting('administratorSurname'),
                'name' => api_get_setting('administratorName'),
                'telephone' => api_get_setting('administratorTelephone'),
            ];

            $twig->addGlobal('_admin', $adminInfo);

            // Theme icon is loaded in the TwigListener src/ThemeBundle/EventListener/TwigListener.php
            //$theme = api_get_visual_theme();
            $languages = api_get_languages();
            $languageList = [];
            foreach ($languages as $isoCode => $language) {
                $languageList[languageToCountryIsoCode($isoCode)] = $language;
            }

            $isoFixed = languageToCountryIsoCode($request->getLocale());

            if (!isset($languageList[$isoFixed])) {
                $isoFixed = 'en';
            }

            $twig->addGlobal(
                'current_locale_info',
                [
                    'flag' => $isoFixed,
                    'text' => $languageList[$isoFixed] ?? 'English',
                ]
            );
            $twig->addGlobal('current_locale', $request->getLocale());
            $twig->addGlobal('available_locales', $languages);
            $twig->addGlobal('show_toolbar', \Template::isToolBarDisplayedForUser() ? 1 : 0);

            // Extra content
            $extraHeader = '';
            if (!api_is_platform_admin()) {
                $extraHeader = trim(api_get_setting('header_extra_content'));
            }
            $twig->addGlobal('header_extra_content', $extraHeader);

            $rightFloatMenu = '';
            $iconBug = \Display::return_icon(
                'bug.png',
                get_lang('Report a bug'),
                [],
                ICON_SIZE_LARGE
            );

            $allow = ANONYMOUS !== $userStatus;
            if ($allow && 'true' === api_get_setting('show_link_bug_notification')) {
                $rightFloatMenu = '<div class="report">
		        <a href="https://github.com/chamilo/chamilo-lms/wiki/How-to-report-issues" target="_blank">
                    '.$iconBug.'
                </a>
		        </div>';
            }

            if ($allow && 'true' === api_get_setting('show_link_ticket_notification')) {
                // by default is project_id = 1
                $defaultProjectId = 1;
                $allow = \TicketManager::userIsAllowInProject(api_get_user_info(), $defaultProjectId);
                if ($allow) {
                    $iconTicket = \Display::return_icon(
                        'help.png',
                        get_lang('Ticket'),
                        [],
                        ICON_SIZE_LARGE
                    );
                    $courseInfo = api_get_course_info();
                    $courseParams = '';
                    if (!empty($courseInfo)) {
                        $courseParams = api_get_cidreq();
                    }
                    $url = api_get_path(WEB_CODE_PATH).
                        'ticket/tickets.php?project_id='.$defaultProjectId.'&'.$courseParams;
                    $rightFloatMenu .= '<div class="help">
                        <a href="'.$url.'" target="_blank">
                            '.$iconTicket.'
                        </a>
                    </div>';
                }
            }

            $twig->addGlobal('bug_notification', $rightFloatMenu);
        }

        // We set cid_reset = true if we enter inside a main/admin url
        // CourseListener check this variable and deletes the course session
        if (false !== strpos($request->get('name'), 'admin/')) {
            $session->set('cid_reset', true);
        } else {
            $session->set('cid_reset', false);
        }
        $session->set('access_url_id', $urlId);
    }

    public function onKernelResponse(ResponseEvent $event)
    {
    }

    public function onKernelController(ControllerEvent $event)
    {
    }
}
