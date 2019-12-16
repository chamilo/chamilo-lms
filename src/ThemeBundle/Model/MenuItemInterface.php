<?php
/**
 * MenuItemInterface.php
 * avanzu-admin
 * Date: 23.02.14.
 */

namespace Chamilo\ThemeBundle\Model;

/**
 * Interface MenuItemInterface.
 */
interface MenuItemInterface
{
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
     * @return bool
     */
    public function isActive();

    /**
     * @param bool $isActive
     *
     * @return MenuItemModel
     */
    public function setIsActive($isActive);

    /**
     * @return bool
     */
    public function hasChildren();

    /**
     * @return mixed
     */
    public function getChildren();

    /**
     * @return MenuItemModel
     */
    public function addChild(self $child);

    /**
     * @return MenuItemModel
     */
    public function removeChild(self $child);

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
     * @return bool
     */
    public function hasParent();

    /**
     * @param MenuItemInterface $parent
     *
     * @return MenuItemModel
     */
    public function setParent(self $parent = null);

    /**
     * @return MenuItemInterface|null
     */
    public function getActiveChild();
}
