<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Model;

/**
 * Base abstract Block class that provides a default implementation of the block interface.
 */
abstract class BaseBlock implements BlockInterface
{
    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @var int|null
     */
    protected $position;

    /**
     * @var BlockInterface|null
     */
    protected $parent;

    /**
     * @var BlockInterface[]
     */
    protected $children;

    /**
     * @var \DateTime|null
     */
    protected $createdAt;

    /**
     * @var \DateTime|null
     */
    protected $updatedAt;

    /**
     * @var string|null
     */
    protected $type;

    /**
     * @var int|null
     */
    protected $ttl;

    public function __construct()
    {
        $this->settings = [];
        $this->enabled = false;
        $this->children = [];
    }

    public function __toString()
    {
        return sprintf('%s ~ #%s', $this->getName(), $this->getId());
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setSettings(array $settings = [])
    {
        $this->settings = $settings;
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function setSetting($name, $value)
    {
        $this->settings[$name] = $value;
    }

    public function getSetting($name, $default = null)
    {
        return isset($this->settings[$name]) ? $this->settings[$name] : $default;
    }

    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    public function getEnabled()
    {
        return $this->enabled;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setCreatedAt(\DateTime $createdAt = null)
    {
        $this->createdAt = $createdAt;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt = null)
    {
        $this->updatedAt = $updatedAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function addChildren(BlockInterface $child)
    {
        $this->children[] = $child;

        $child->setParent($this);
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function setParent(BlockInterface $parent = null)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function hasParent()
    {
        return $this->getParent() instanceof self;
    }

    public function getTtl()
    {
        if (!$this->getSetting('use_cache', true)) {
            return 0;
        }

        $ttl = $this->getSetting('ttl', 86400);

        foreach ($this->getChildren() as $block) {
            $blockTtl = $block->getTtl();

            $ttl = ($blockTtl < $ttl) ? $blockTtl : $ttl;
        }

        $this->ttl = $ttl;

        return $this->ttl;
    }

    public function hasChildren()
    {
        return \count($this->children) > 0;
    }
}
