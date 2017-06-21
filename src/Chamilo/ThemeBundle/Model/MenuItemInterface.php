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
     * @return string
     */
    public function getLabel();

    /**
     * @return string
     */
    public function getRoute();

    /**
     * @return boolean
     */
    public function isActive();

    /**
     * @param boolean $isActive
     *
     * @return MenuItemModel
     */
    public function setIsActive($isActive);

    /**
     * @return boolean
     */
    public function hasChildren();

    /**
     * @return mixed
     */
    public function getChildren();

    /**
     * @param MenuItemInterface $child
     *
     * @return MenuItemModel
     */
    public function addChild(MenuItemInterface $child);

    /**
     * @param MenuItemInterface $child
     *
     * @return MenuItemModel
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
     * @return string
     */
    public function getBadgeColor();

    /**
     * @return MenuItemInterface
     */
    public function getParent();

    /**
     * @return boolean
     */
    public function hasParent();

    /**
     * @param MenuItemInterface $parent
     *
     * @return MenuItemModel
     */
    public function setParent(MenuItemInterface $parent = null);

    /**
     * @return MenuItemInterface|null
     */
    public function getActiveChild();
}
