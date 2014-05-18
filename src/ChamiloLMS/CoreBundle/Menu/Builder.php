<?php

namespace ChamiloLMS\CoreBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class Builder extends ContainerAware
{
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

        $menu->addChild($this->container->get('translator')->trans('Home'), array('route' => 'root'));
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
            //$logoutLink->addChild($this->templating->render('ApplicationSonataUserBundle:Security:login_options.html.twig'));

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

    public function courseMenu(FactoryInterface $factory, array $options)
    {
        $security = $this->container->get('security.context');
        $menu = $factory->createItem('root');
        if ($security->isGranted('IS_AUTHENTICATED_FULLY')) {

            $menu->setChildrenAttribute('class', 'nav nav-pills nav-stacked');

            $menu->addChild('Create course', array('route' => 'logout'));
            $menu->addChild('Catalog', array('route' => 'logout'));
            $menu->addChild('History', array('route' => 'logout'));
        }
        return $menu;
    }

}
