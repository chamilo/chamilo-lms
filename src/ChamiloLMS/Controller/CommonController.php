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
     * @param Application $app
     * @param $breadcrumbs
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
