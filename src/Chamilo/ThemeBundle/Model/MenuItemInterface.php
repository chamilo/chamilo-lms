<?php
/**
 * MenuItemInterface.php
 * avanzu-admin
 * Date: 23.02.14
 */

namespace Chamilo\ThemeBundle\Model;


/**
 * Interface MenuItemInterface
 *
 * @package Chamilo\ThemeBundle\Model
 */
interface MenuItemInterface {
    /**
     * @return mixed
     */
    public function getIdentifier();

    /**
     * @return mixed
     */
    public function getLabel();

    /**
     * @return mixed
     */
    public function getRoute();

    /**
     * @return mixed
     */
    public function isActive();

    /**
     * @param $isActive
     *
     * @return mixed
     */
    public function setIsActive($isActive);

    /**
     * @return mixed
     */
    public function hasChildren();

    /**
     * @return mixed
     */
    public function getChildren();

    /**
     * @param MenuItemInterface $child
     *
     * @return mixed
     */
    public function addChild(MenuItemInterface $child);

    /**
     * @param MenuItemInterface $child
     *
     * @return mixed
     */
    public function removeChild(MenuItemInterface $child);

    /**
     * @return mixed
     */
    public function getIcon();

    /**
     * @return mixed
     */
    public function getBadge();

    /**
     * @return mixed
     */
    public function getBadgeColor();

    /**
     * @return mixed
     */
    public function getParent();

    /**
     * @return mixed
     */
    public function hasParent();

    /**
     * @param MenuItemInterface $parent
     *
     * @return mixed
     */
    public function setParent(MenuItemInterface $parent = null);

    /**
     * @return MenuItemInterface|null
     */
    public function getActiveChild();
}
