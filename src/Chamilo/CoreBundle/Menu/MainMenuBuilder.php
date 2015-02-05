<?php

namespace Chamilo\CoreBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class MainMenuBuilder
 * @package Chamilo\CoreBundle\Menu
 */
class MainMenuBuilder extends ContainerAware
{
    /**
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * Constructor
     *
     * @param MenuFactory $factory
     * @param RouterInterface $router
     */
    public function __construct(FactoryInterface $factory, RouterInterface $router)
    {
        $this->factory         = $factory;
        $this->router          = $router;
    }

    /**
     * @param array  $itemOptions The options given to the created menuItem
     * @param string $currentUri  The current URI
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function createCategoryMenu(array $itemOptions = array(), $currentUri = null)
    {
        $menu = $this->factory->createItem('categories', $itemOptions);

        $this->buildCategoryMenu($menu, $itemOptions, $currentUri);

        return $menu;
    }

    /**
     * @param \Knp\Menu\ItemInterface $menu        The item to fill with $routes
     * @param array                   $options     The item options
     * @param string                  $currentUri  The current URI
     */
    public function buildCategoryMenu(ItemInterface $menu, array $options = array(), $currentUri = null)
    {
        //$categories = $this->categoryManager->getCategoryTree();

        //$this->fillMenu($menu, $categories, $options, $currentUri);
        $menu->addChild('home', array('route' => 'home'));

        $menu->addChild('home2', array('route' => 'home'));
    }

    /**
     * Top menu left
     * @param FactoryInterface $factory
     * @param array $options
     * @return \Knp\Menu\ItemInterface
     */
    public function leftMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');
        $menu->setChildrenAttribute('class', 'nav navbar-nav');

        $menu->addChild(
            $this->container->get('translator')->trans('Home'),
            array('route' => 'home')
        );
        $menu->addChild('Administration', array(
            'route' => 'administration'
            //'routeParameters' => array('id' => 42)
        ));
        // ... add more children

        return $menu;
    }

    /**
     * Top menu right
     * @param FactoryInterface $factory
     * @param array $options
     * @return \Knp\Menu\ItemInterface
     */
    public function rightMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');
        $menu->setChildrenAttribute('class', 'nav navbar-nav navbar-right');

        $menu->addChild('User');

       /* {% if is_granted('IS_AUTHENTICATED_FULLY') == true %}
        <li>
                <a id="logout_button" class="logout" title="{{ "Logout"|trans }}" href="{{ url('logout') }}" >
                    <i class="fa fa-power-off"></i>
                </a>
            </li>
            {% endif %}
        */
        if ($this->container->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY')) {
            $logoutLink = $menu->addChild('Logout', array('route' => 'logout'));
            $logoutLink
                ->setLinkAttributes(array(
                    'id' => 'logout_button',
                    'class' => 'fa fa-power-off'
                ))
                ->setAttributes(array(
                    /*'id' => 'signin',
                    'class' => 'dropdown'*/
                ))
            ;
            //$logoutLink->addChild($this->templating->render('ChamiloUserBundle:Security:login_options.html.twig'));

        }

        return $menu;
    }

    public function profileMenu(FactoryInterface $factory, array $options)
    {
        $menu = $factory->createItem('root');
        $security = $this->container->get('security.context');
        if ($security->isGranted('IS_AUTHENTICATED_FULLY')) {
            $menu->setChildrenAttribute('class', 'nav nav-pills nav-stacked');

            $menu->addChild('Inbox', array('route' => 'logout'));
            $menu->addChild('Compose', array('route' => 'logout'));
            $menu->addChild('Edit', array('route' => 'logout'));
        }

        return $menu;
    }
}
