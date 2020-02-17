<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ThemeBundle\Event;

use Chamilo\ThemeBundle\Model\MenuItemInterface;
use Knp\Menu\MenuItem;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SidebarMenuKnpEvent.
 *
 * @package Chamilo\ThemeBundle\Event
 */
class SidebarMenuKnpEvent extends ThemeEvent
{
    /**
     * @var array
     */
    protected $menuRootItems = [];

    protected $menu;

    /**
     * @var Request
     */
    protected $request;

    public function __construct($request = null)
    {
        $this->request = $request;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return MenuItem
     */
    public function getMenu()
    {
        return $this->menu;
    }

    public function setMenu(MenuItem $menu)
    {
        $this->menu = $menu;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->menuRootItems;
    }

    public function addItem(MenuItem $item)
    {
        $this->menuRootItems[$item->getUri()] = $item;
    }

    /**
     * @param $id
     */
    public function getRootItem($id)
    {
        return isset($this->menuRootItems[$id]) ? $this->menuRootItems[$id] : null;
    }

    /**
     * @return MenuItemInterface|null
     */
    public function getActive()
    {
        foreach ($this->getMenu() as $child) {
            if ($child->isCurrent()) {
                return $child;
            }
        }

        return null;
    }
}
