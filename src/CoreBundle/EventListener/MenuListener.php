<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\ThemeBundle\Event\SidebarMenuKnpEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MenuListener
 * This is needed to load theme events to be executed.
 */
class MenuListener
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onSetupMenu(SidebarMenuKnpEvent $event)
    {
        //$request = $event->getRequest();
        //$event->setMenu($this->getMenu($request));
    }

    /**
     * @param $route
     * @param $items
     * @param \Knp\Menu\ItemInterface[] $items
     */
    protected function activateByRoute($route, array $items)
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
