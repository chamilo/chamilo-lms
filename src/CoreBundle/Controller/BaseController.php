<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Knp\Menu\Matcher\Matcher;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\DependencyInjection\Container;

use Knp\Menu\FactoryInterface as MenuFactoryInterface;
use Knp\Menu\ItemInterface as MenuItemInterface;
use Knp\Menu\Renderer\ListRenderer;

/**
 * Each entity controller must extends this class.
 *
 * @abstract
 */
abstract class BaseController extends Controller
{
    /**
     * @return \Symfony\Component\Security\Core\SecurityContextInterface
     */
    /*public function getSecurity()
    {
        return $this->container->get('security.context');
    }*/

    /**
     * @return TwigEngine
     */
    public function getTemplate()
    {
        return $this->container->get('templating');
    }

    /**
     * @return NotFoundHttpException
     */
    public function abort()
    {
        return new NotFoundHttpException();
    }

    /**
     * Converts string 'Chamilo\CoreBundle\Controller\Admin\QuestionManager' into
     * 'admin/question_manager'
     */
    public function getTemplatePath()
    {
        $parts = $this->classParts;

        $newPath = array();
        foreach ($parts as $part) {
            if (in_array($part, array('chamilo_lms', 'controller'))
                //strpos($part, '_controller') > 0
            ) {
                continue;
            }
            $newPath[] = $part;
        }

        $template = implode('/', $newPath);
        return str_replace('_controller', '', $template);
    }

    /**
     * Transforms 'QuestionManagerController' to 'question_manager.controller'
     * @return string
     */
    public function getControllerAlias()
    {
        $parts = $this->classParts;
        $parts = array_reverse($parts);
        $alias = str_replace('_controller', '.controller', $parts[0]);
        return $alias;
    }

    /**
     * Translator shortcut
     * @param string $variable
     * @return string
     */
    public function trans($variable)
    {
        return $this->container->get('translator')->trans($variable);
    }

    /**
     * Returns the class name label
     * @example RoleController -> Role
     *
     * @return string the class name label
     */
    public function getClassNameLabel()
    {
        return $this->classNameLabel;
    }

    /**
     * @return MenuFactoryInterface
     */
    public function getMenuFactory()
    {
        return $this->container->get('knp_menu.factory');
    }

    /**
     * @param string $action
     * @return MenuItemInterface
     */
    protected function getBreadcrumbs($action)
    {
        $breadcrumbs = $this->buildBreadcrumbs($action);

        return $breadcrumbs;
    }

    /** Main home URL
     * @return MenuItemInterface
     */
    protected function getHomeBreadCrumb()
    {
        $menu = $this->getMenuFactory()->createItem(
            'root',
            array(
                'childrenAttributes' => array(
                    'class'        => 'breadcrumb',
                    'currentClass' => 'active'
                )
            )
        );

        $menu->addChild(
            $this->trans('Home'),
            array('uri' => $this->generateUrl('home'))
        );

        return $menu;
    }

    /**
     * @param $action
     * @param MenuItemInterface $menu
     * @return MenuItemInterface
     */
    public function buildBreadcrumbs($action, MenuItemInterface $menu = null)
    {
        if (!$menu) {
            $menu = $this->getHomeBreadCrumb();
        }

        $menu->addChild(
            $this->trans($this->getClassnameLabel().'List'),
            array('uri' => $this->generateControllerUrl('listingAction'))
        );

        $action = str_replace(
            array($this->getControllerAlias().':', 'Action'),
            '',
            $action
        );

        switch ($action) {
            case 'add':
            case 'edit':
                $menu->addChild(
                    $this->trans($this->getClassnameLabel().ucfirst($action))
                    //array('uri' => $this->generateControllerUrl($action.'Action'))
                );
                break;
        }

        return $menu;
    }

    /**
     * @param array $breadCrumbList
     * @return string
     */
    protected function parseLegacyBreadCrumb($breadCrumbList = array())
    {
        $menu = $this->getHomeBreadCrumb();
        foreach ($breadCrumbList as $item) {
            $menu->addChild(
                $this->trans($item['title']),
                array('uri' => $item['url'])
            );
        }

        $renderer = new ListRenderer(new \Knp\Menu\Matcher\Matcher());
        $result = $renderer->render($menu);

        return $result;
    }

    /**
     * Renders the current controller template
     * @param string $name
     * @param array $elements
     * @return mixed
     */
    public function renderTemplate($name, $elements = array())
    {
        $name = $this->getTemplatePath().'/'.$name;

        $renderer = new ListRenderer(new \Knp\Menu\Matcher\Matcher());
        $action = $this->getRequest()->get('_route');
        $result = $renderer->render($this->getBreadcrumbs($action));
        $elements['new_breadcrumb'] = $result;

        return $this->getTemplate()->renderTemplate($name, $elements);
    }

    /**
     * @return Course
     */
    public function getCourse()
    {
        return $this->getRequest()->getSession()->get('course');
    }
}
