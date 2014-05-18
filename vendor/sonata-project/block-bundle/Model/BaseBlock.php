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
 * Base abstract Block class that provides a default implementation of the block interface
 */
abstract class BaseBlock implements BlockInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var boolean
     */
    protected $enabled;

    /**
     * @var integer
     */
    protected $position;

    /**
     * @var BlockInterface
     */
    protected $parent;

    /**
     * @var array
     */
    protected $children;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime
     */
    protected $updatedAt;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var integer
     */
    protected $ttl;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->settings = array();
        $this->enabled  = false;
        $this->children = array();
    }

    /**
     * {@inheritDoc}
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function setSettings(array $settings = array())
    {
        $this->settings = $settings;
    }

    /**
     * {@inheritDoc}
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * {@inheritDoc}
     */
    public function setSetting($name, $value)
    {
        $this->settings[$name] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getSetting($name, $default = null)
    {
        return isset($this->settings[$name]) ? $this->settings[$name] : $default;
    }

    /**
     * {@inheritDoc}
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * {@inheritDoc}
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * {@inheritDoc}
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * {@inheritDoc}
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * {@inheritDoc}
     */
    public function setCreatedAt(\DateTime $createdAt = null)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * {@inheritDoc}
     */
    public function setUpdatedAt(\DateTime $updatedAt = null)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * {@inheritDoc}
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * {@inheritDoc}
     */
    public function addChildren(BlockInterface $child)
    {
        $this->children[] = $child;

        $child->setParent($this);
    }

    /**
     * {@inheritDoc}
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * {@inheritDoc}
     */
    public function setParent(BlockInterface $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * {@inheritDoc}
     */
    public function hasParent()
    {
        return $this->getParent() instanceof self;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return sprintf("%s ~ #%s", $this->getname(), $this->getId());
    }

    /**
     * {@inheritDoc}
     */
    public function getTtl()
    {
        if (!$this->getSetting('use_cache', true)) {
            return 0;
        }

        $ttl = $this->getSetting('ttl', 84600);

        foreach ($this->getChildren() as $block) {
            $blockTtl = $block->getTtl();

            $ttl = ($blockTtl < $ttl) ? $blockTtl : $ttl;
        }

        $this->ttl = $ttl;

        return $this->ttl;
    }

    /**
     * {@inheritDoc}
     */
    public function hasChildren()
    {
        return count($this->children) > 0;
    }
}
