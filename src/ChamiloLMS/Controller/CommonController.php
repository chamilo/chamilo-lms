<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Controller;

use \ChamiloSession as Session;
use Silex\Application;
use Knp\Menu\Matcher\Matcher;


/**
 * @package ChamiloLMS.CommonController
 * @author Julio Montoya <gugli100@gmail.com>
 */
class CommonController
{

    public $languageFiles = array();

    /**
     *
    */
    public function __construct()
    {
    }

    /**
     *
     */
    public function cidReset()
    {
        Session::erase('_cid');
        Session::erase('_real_cid');
        Session::erase('_course');

        if (!empty($_SESSION)) {
            foreach ($_SESSION as $key => $item) {
                if (strpos($key, 'lp_autolunch_') === false) {
                    continue;
                } else {
                    if (isset($_SESSION[$key])) {
                        Session::erase($key);
                    }
                }
            }
        }
        // Deleting session info.
        if (api_get_session_id()) {
            Session::erase('id_session');
            Session::erase('session_name');
        }
        // Deleting group info.
        if (api_get_group_id()) {
            Session::erase('_gid');
        }
    }

    /**
     * @param Application $app
     * @param array $breadcrumbs
     */
    public function setBreadcrumb(Application $app, $breadcrumbs)
    {
        $courseInfo = api_get_course_info();

        // Adding course breadcrumb.
        if (!empty($courseInfo)) {
            $courseBreadcrumb = array(
                'name' => \Display::return_icon('home.png').' '.$courseInfo['title'],
                'url' => array(
                    'route' => 'course',
                    'routeParameters' => array(
                        'cidReq' => api_get_course_id(),
                        'id_session' => api_get_session_id()
                    )
                )
            );
            array_unshift($breadcrumbs, $courseBreadcrumb);
        }

        $app['main_breadcrumb'] = function ($app) use ($breadcrumbs) {
            /** @var  \Knp\Menu\Silex\RouterAwareFactory $menu */
            $menu = $app['knp_menu.factory']->createItem(
                'root',
                array(
                    'childrenAttributes' => array(
                        'class'        => 'breadcrumb',
                        'currentClass' => 'active'
                    )
                )
            );

            foreach ($breadcrumbs as $item) {
                $menu->addChild($item['name'], $item['url']);
            }
            return $menu;
        };

        $matcher = new Matcher();
        $voter = new \Knp\Menu\Silex\Voter\RouteVoter();
        $voter->setRequest($app['request']);
        $matcher->addVoter($voter);
        $renderer = new \Knp\Menu\Renderer\TwigRenderer($app['twig'], 'bread.tpl', $matcher);
        $bread = $renderer->render(
            $app['main_breadcrumb'],
            array(
                'template' => 'default/layout/bread.tpl'
            )
        );
        $app['breadcrumbs'] = $bread;
    }

}
