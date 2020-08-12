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

namespace Sonata\BlockBundle\Event;

use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * @final since sonata-project/block-bundle 3.0
 */
class BlockEvent extends Event
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * @var BlockInterface[]
     */
    protected $blocks = [];

    public function __construct(array $settings = [])
    {
        $this->settings = $settings;
    }

    public function addBlock(BlockInterface $block)
    {
        $this->blocks[] = $block;
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @return BlockInterface[]
     */
    public function getBlocks()
    {
        return $this->blocks;
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getSetting($name, $default = null)
    {
        return isset($this->settings[$name]) ? $this->settings[$name] : $default;
    }
}
