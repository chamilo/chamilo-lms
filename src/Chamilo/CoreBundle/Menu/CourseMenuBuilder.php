<?php

namespace Chamilo\CoreBundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class CourseMenuBuilder extends ContainerAware
{

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
