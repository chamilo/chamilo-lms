<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\ThemeBundle\Event\SidebarMenuKnpEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class MenuListener
 * @package Chamilo\CoreBundle\EventListener
 */
class MenuListener
{
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param SidebarMenuKnpEvent $event
     */
    public function onSetupMenu(SidebarMenuKnpEvent $event)
    {
        $request = $event->getRequest();

        /*foreach ($this->getMenu($request) as $item) {
            $event->addItem($item);
        }*/
        $event->setMenu($this->getMenu($request));
    }

    /**
     * @param Request $request
     * @return mixed
     */
    protected function getMenu(Request $request)
    {
        $menu = $this->container->get('chamilo_core.menu.simple_menu');

        $menuItems = $menu->mainMenu(
            $this->container->get('knp_menu.factory'),
            array()
        );

        return $this->activateByRoute($request->get('_route'), $menuItems);
    }

    /**
     * @param $route
     * @param $items
     * @return mixed
     */
    protected function activateByRoute($route, $items)
    {
        /** @var \Knp\Menu\MenuItem $item */
        foreach ($items as $item) {
            if ($item->hasChildren()) {
                $this->activateByRoute($route, $item->getChildren());
            } else {
                if ($item->isCurrent()) {
                    $item->setCurrent(true);
                }
                /*if ($item->getRoute() == $route) {
                    $item->setIsActive(true);
                }*/
            }
        }

        return $items;
    }
}
