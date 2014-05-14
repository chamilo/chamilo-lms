<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Knp\Menu\Matcher\Matcher;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Silex\Application;
use Flint\Controller\Controller as FlintController;
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
abstract class BaseController extends FlintController
{
    protected $app;
    protected $pimple;
    private $classParts;
    protected $breadcrumbs = array();
    protected $classNameLabel;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        // In order to use the Flint Controller.
        $this->pimple = $app;

        $className = get_class($this);
        $this->classParts = explode('\\', Container::underscore($className));

        //if (!$this->classnameLabel) {
        $this->classNameLabel = str_replace('Controller', '', substr($className, strrpos($className, '\\') + 1));
        //}
    }

    /**
     * @return array
     */
    protected function getClassParts()
    {
        return $this->classParts;
    }

    /**
     * Converts string 'ChamiloLMS\Controller\Admin\QuestionManager' into
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
        return $this->get('translator')->trans($variable);
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
        return $this->get('knp_menu.factory');
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
     * @return \ChamiloLMS\Entity\Course
     */
    protected function getCourse()
    {
        //if (isset($this->app['course'])) {
            return $this->app['course'];
        //}
        return false;
    }

    /**
     * @return \ChamiloLMS\Entity\Session
     */
    protected function getSession()
    {
        if (isset($this->app['course_session']) && !empty($this->app['course_session'])) {
            return $this->app['course_session'];
        }
        return false;
    }

    /**
     * @return null|\Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    protected function getSessionHandler()
    {
        return $this->getRequest()->getSession();
    }

    /**
     * @return \ChamiloLMS\Framework\Template
     */
    protected function getTemplate()
    {
        return $this->get('template');
    }

    /**
     * @return \ChamiloLMS\Component\Editor\Editor
     */
    protected function getHtmlEditor()
    {
        return $this->get('html_editor');
    }

    /**
     * @return \ChamiloLMS\Component\Editor\Connector
     */
    protected function getEditorConnector()
    {
        return $this->get('editor_connector');
    }

    /**
     * @return \ChamiloLMS\Component\DataFilesystem\DataFilesystem
     */
    protected function getDataFileSystem()
    {
        return $this->get('chamilo.filesystem');
    }

    /**
     * @return \ChamiloLMS\Entity\User
     */
    public function getUser()
    {
        $user = parent::getUser();
        if (empty($user)) {
            return $this->abort(404, $this->trans('Login required.'));
        }
        return $user;
    }

    /**
     * @return \Symfony\Component\Security\Core\SecurityContext
     */
    public function getSecurity()
    {
        return $this->get('security');
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getManager()
    {
        return $this->get('orm.em');
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    protected function getDatabase()
    {
        return $this->get('db');
    }

    /**
     * Shortcut of
     * $this->getManager()->getRepository('ChamiloLMS\Entity\MyClass')
     * @param string $entity
     * @return \Doctrine\ORM\EntityRepository
     */
    /*sprotected function getRepository($entity)
    {
        return $this->getManager()->getRepository('ChamiloLMS\Entity\\'.$entity);
    }*/

    /**
     * @see \Silex\Application::sendFile
     */
    public function sendFile($file, $status = 200, $headers = array(), $contentDisposition = null)
    {
        return $this->pimple->sendFile($file, $status, $headers, $contentDisposition);
    }

    /**
     * Converts an array of URL to absolute URLs using the url_generator service
     * @param string $label
     * @param array
     * @return mixed
     * @deprecated
     */
    protected function createUrl($label, $parameters = array())
    {
        $links = $this->generateLinks();
        $course = $this->getCourse();

        if (!empty($course)) {
            $parameters['course'] = $course->getCode();
        }
        $session = $this->getSession();
        if (!empty($session)) {
            $parameters['id_session'] = $session->getId();
        }

        $extraParams = $this->getExtraParameters();

        if (!empty($extraParams)) {
            $request = $this->getRequest();
            $dynamicParams = array();
            foreach ($extraParams as $param) {
                $value = $request->get($param);
                if (!empty($value)) {
                    $dynamicParams[$param] = $value;
                }
            }
            $parameters = array_merge($parameters, $dynamicParams);
        }

        if (isset($links) && is_array($links) && isset($links[$label])) {
            $url = $this->generateUrl($links[$label], $parameters);
            return $url;
        }
        return $url = $this->generateUrl($links['list_link']);
    }

    /**
     * Add extra parameters when generating URLs
     * @return array
     */
    protected function getExtraParameters()
    {
        return array();
    }

    /**
     * @see Symfony\Component\Routing\RouterInterface::generate()
     */
    public function generateUrl(
        $name,
        array $parameters = array(),
        $reference = UrlGeneratorInterface::ABSOLUTE_PATH
    ) {
        if ($name != 'home') {
            $course = $this->getCourse();
            if (!empty($course)) {
                $parameters['cidReq'] = $course->getCode();
            }
            $session = $this->getSession();
            if (!empty($session)) {
                $parameters['id_session'] = $session->getId();
            }
        }
        return parent::generateUrl($name, $parameters, $reference);
    }

    /**
     * In a controller like RoleController when calling the indexAction URL
     * this function will transform to role.controller:indexAction
     * @param string $name
     * @param array $parameters
     * @param bool $reference
     * @return mixed
     */
    public function generateControllerUrl(
        $name,
        array $parameters = array(),
        $reference = UrlGeneratorInterface::ABSOLUTE_PATH
    ) {
        $name = $this->getControllerAlias().':'.$name;
        return $this->generateUrl($name, $parameters, $reference);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param string
     */
    protected function setCourseParameters(\Doctrine\ORM\QueryBuilder & $qb, $prefix)
    {
        $course = $this->getCourse();
        if ($course) {
            $qb->andWhere($prefix.'.cId = :id');
            $qb->setParameter('id', $course->getId());

            $session = $this->getSession();
            if (!empty($session)) {
                $qb->andWhere($prefix.'.sessionId = :session_id');
                $qb->setParameter('session_id', $session->getId());
            }
        }
    }

    /**
     * Get system setting.
     * @param string $variable
     * @param string $key
     * @return string
     */
    public function getSetting($variable, $key = null)
    {
        $session = $this->getRequest()->getSession();
        $settings = $session->get('_setting');
        if (empty($key)) {
            if (isset($settings[$variable])) {
                return $settings[$variable];
            }
        } else {
            if (isset($settings[$variable]) && isset($settings[$variable])) {
                return $settings[$variable];
            }
        }
    }

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

            return $this->getSecurity()->isGranted($role);
        }
    }

    /**
     * Add flash messages.
     * @param string $message
     * @param string $type example: info|success|warning
     */
    public function addMessage($message, $type = 'info')
    {
        if ($type == 'confirmation') {
            $type = 'info';
        }
        $this->get('session')->getFlashBag()->add($type, $message);
    }

    /**
     * @param array $breadcrumbs
     * @deprecated
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
            /** @var \Knp\Menu\MenuItem $menu */
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
        $renderer = new \Knp\Menu\Renderer\TwigRenderer(
            $this->get('twig'),
            'bread.tpl',
            $matcher
        );
        $bread = $renderer->render(
            $this->get('main_breadcrumb'),
            array(
                'template' => 'default/layout/bread.tpl'
            )
        );
        $app['breadcrumbs'] = $bread;
    }

    /**
     * @return array
     */
    public function menuList()
    {
        return array(
            'index',
            'users' => array(
                'list', 'add', 'edit', 'export', 'import', 'profiling', 'roles'
            ),
            'courses' => array(
                array(
                    'list',
                    'add',
                    'edit',
                    'export',
                    'import',
                    'add_users',
                    'import_users',
                    'course_categories',
                    'extra_fields',
                    'question_extra_fields'
                )
            ),
            'sessions',
            'classes',
            'appearance',
            'plugins',
            'settings',
            'tools'
        );
    }

    public function before(Request $request)
    {

    }
}
