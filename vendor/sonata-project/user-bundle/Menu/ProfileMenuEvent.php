<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Sonata\UserBundle\Menu;

use Knp\Menu\ItemInterface;
use Symfony\Component\EventDispatcher\Event;


/**
 * Class ProfileMenuEvent
 *
 * @package Sonata\UserBundle\Menu
 *
 * @author Hugo Briand <briand@ekino.com>
 */
class ProfileMenuEvent extends Event
{
    /**
     * @var ItemInterface
     */
    private $menu;

    /**
     * @param ItemInterface $menu
     */
    public function __construct(ItemInterface $menu)
    {
        $this->menu = $menu;
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    public function getMenu()
    {
        return $this->menu;
    }
}