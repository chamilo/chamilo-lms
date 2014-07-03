<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Model;

/**
 * Interface of Block
 */
interface BlockInterface
{
    /**
     * Sets the block Id
     *
     * @param mixed $id
     *
     * @return void
     */
    public function setId($id);

    /**
     * Returns the block id
     *
     * @return mixed void
     */
    public function getId();

    /**
     * Sets the name
     *
     * @param string $name
     */
    public function setName($name);

    /**
     * Returns the name
     *
     * @return string
     */
    public function getName();

    /**
     * Sets the type
     *
     * @param string $type
     */
    public function setType($type);

    /**
     * Returns the type
     *
     * @return string $type
     */
    public function getType();

    /**
     * Sets whether or not this block is enabled
     *
     * @param boolean $enabled
     */
    public function setEnabled($enabled);

    /**
     * Returns whether or not this block is enabled
     *
     * @return boolean $enabled
     */
    public function getEnabled();

    /**
     * Set the block ordered position
     *
     * @param integer $position
     */
    public function setPosition($position);

    /**
     * Returns the block ordered position
     *
     * @return integer $position
     */
    public function getPosition();

    /**
     * Sets the creation date and time
     *
     * @param \Datetime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt = null);

    /**
     * Returns the creation date and time
     *
     * @return \Datetime $createdAt
     */
    public function getCreatedAt();

    /**
     * Set the last update date and time
     *
     * @param \Datetime $updatedAt
     */
    public function setUpdatedAt(\DateTime $updatedAt = null);

    /**
     * Returns the last update date and time
     *
     * @return \Datetime $updatedAt
     */
    public function getUpdatedAt();

    /**
     * Returns the block cache TTL
     *
     * @return integer
     */
    public function getTtl();

    /**
     * Sets the block settings
     *
     * @param array $settings An array of key/value
     */
    public function setSettings(array $settings = array());

    /**
     * Returns the block settings
     *
     * @return array $settings An array of key/value
     */
    public function getSettings();

    /**
     * Sets one block setting
     *
     * @param string $name  Key name
     * @param mixed  $value Value
     */
    public function setSetting($name, $value);

    /**
     * Returns one block setting or the given default value if no value is found
     *
     * @param string     $name    Key name
     * @param mixed|null $default Default value
     *
     * @return mixed
     */
    public function getSetting($name, $default = null);

    /**
     * Add one child block
     *
     * @param BlockInterface $children
     */
    public function addChildren(BlockInterface $children);

    /**
     * Returns child blocks
     *
     * @return \Doctrine\Common\Collections\Collection $children
     */
    public function getChildren();

    /**
     * Returns whether or not this block has children
     *
     * @return boolean
     */
    public function hasChildren();

    /**
     * Set the parent block
     *
     * @param BlockInterface|null $parent
     */
    public function setParent(BlockInterface $parent = null);

    /**
     * Returns the parent block
     *
     * @return BlockInterface $parent
     */
    public function getParent();

    /**
     * Returns whether or not this block has a parent
     *
     * @return boolean
     */
    public function hasParent();
}
