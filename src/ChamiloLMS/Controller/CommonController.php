<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller;

use Silex\Application;
use Knp\Menu\Matcher\Matcher;
use ChamiloLMS\Controller\BaseController;


/**
 * @package ChamiloLMS.CommonController
 * @author Julio Montoya <gugli100@gmail.com>
 * @todo improve breadcrumb management
 */
class CommonController extends BaseController
{
    /**
     * @return bool
     */
    public function isCourseTeacher()
    {
        $course = $this->getCourse();
        if (!$course) {
            return false;
        } else {
            if ($this->getSecurity()->isGranted('ROLE_ADMIN')) {
                return true;
            }
            $course->getId();
            $role = "ROLE_TEACHER_COURSE_".$course->getId().'_SESSION_0';
            //var_dump($role);
            return $this->getSecurity()->isGranted($role);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getRepository()
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewEntity()
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function getControllerAlias()
    {
    }

    /**
     * {@inheritdoc}
     */
    protected function generateLinks()
    {
        return $this->generateDefaultCrudRoutes();
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplatePath()
    {
    }

    protected function generateDefaultCrudRoutes()
    {
        $className = $this->getControllerAlias();
        return array(
            'create_link' => $className.':addAction',
            'read_link' => $className.':readAction',
            'update_link' => $className.':editAction',
            'delete_link' => $className.':deleteAction',
            'list_link' => $className.':indexAction'
        );
    }

    /**
     *
     * @param array $breadcrumbs
     */
    protected function setBreadcrumb($breadcrumbs)
    {
        $course = $this->getCourse();
        //$session =  $this->getSession();

        // Adding course breadcrumb.
        if (!empty($course)) {
            $courseBreadcrumb = array(
                'name' => \Display::return_icon('home.png').' '.$course->getTitle(),
                'url' => array(
                    'route' => 'course',
                    'routeParameters' => array(
                        'cidReq' => $course->getCode(),
                        'id_session' => api_get_session_id()
                    )
                )
            );
            array_unshift($breadcrumbs, $courseBreadcrumb);
        }

        $app = $this->app;

        $app['main_breadcrumb'] = function ($app) use ($breadcrumbs) {
            /** @var  \Knp\Menu\MenuItem $menu */
            $menu = $app['knp_menu.factory']->createItem(
                'root',
                array(
                    'childrenAttributes' => array(
                        'class'        => 'breadcrumb',
                        'currentClass' => 'active'
                    )
                )
            );

            if (!empty($breadcrumbs)) {
                foreach ($breadcrumbs as $item) {
                    if (empty($item['url'])) {
                        $item['url'] = array();
                    }
                    $menu->addChild($item['name'], $item['url']);
                }
            }

            return $menu;
        };

        $matcher = new Matcher();
        $voter = new \Knp\Menu\Silex\Voter\RouteVoter();
        $voter->setRequest($this->getRequest());
        $matcher->addVoter($voter);
        $renderer = new \Knp\Menu\Renderer\TwigRenderer($this->get('twig'), 'bread.tpl', $matcher);
        $bread = $renderer->render(
            $this->get('main_breadcrumb'),
            array(
                'template' => 'default/layout/bread.tpl'
            )
        );
        $app['breadcrumbs'] = $bread;
    }
}
