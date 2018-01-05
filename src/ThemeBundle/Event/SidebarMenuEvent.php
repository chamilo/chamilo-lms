<?php
/**
 * SidebarMenuEvent.php
 * avanzu-admin
 * Date: 23.02.14
 */

namespace Chamilo\ThemeBundle\Event;


use Chamilo\ThemeBundle\Model\MenuItemInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SidebarMenuEvent
 *
 * @package Chamilo\ThemeBundle\Event
 */
class SidebarMenuEvent extends ThemeEvent
{

    /**
     * @var array
     */
    protected $menuRootItems = array();

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
     * @return array
     */
    public function getItems()
    {
        return $this->menuRootItems;
    }

    /**
     * @param MenuItemInterface $item
     */
    public function addItem($item)
    {
        $this->menuRootItems[$item->getIdentifier()] = $item;
    }

    /**
     * @param $id
     *
     * @return null
     */
    public function getRootItem($id)
    {
        return isset($this->menuRootItems[$id]) ? $this->menuRootItems[$id] : null;
    }

    /**
     * @return MenuItemInterface|null
     */
    public function getActive() {

        foreach ($this->getItems() as $item) {
            /** @var $item MenuItemInterface */
            if ($item->isActive()) return $item;
        }
        return null;
    }

}
