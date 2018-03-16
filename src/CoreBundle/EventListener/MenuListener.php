<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\ThemeBundle\Event\SidebarMenuKnpEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MenuListener
 * This is needed to load theme events to be executed.
 *
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
        //$request = $event->getRequest();
        //$event->setMenu($this->getMenu($request));
    }

    /**
     * @param $route
     * @param $items
     *
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
            }
        }

        return $items;
    }
}
