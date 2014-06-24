<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\EventListener;

use Avanzu\AdminThemeBundle\Event\SidebarMenuKnpEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Request;

class MenuListener
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onSetupMenu(SidebarMenuKnpEvent $event)
    {
        $request = $event->getRequest();

        foreach ($this->getMenu($request) as $item) {
            $event->addItem($item);
        }
    }

    protected function getMenu(Request $request)
    {
        $menu = $this->container->get('chamilolms.menu.simple_menu');

        /** @var \Knp\Menu\ItemInterface $menuItems */
        $menuItems = $menu->mainMenu(
            $this->container->get('knp_menu.factory'),
            array()
        );

        return $this->activateByRoute($request->get('_route'), $menuItems);
    }

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
