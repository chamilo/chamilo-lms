<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Event;

use Sonata\BlockBundle\Model\BlockInterface;
use Symfony\Component\EventDispatcher\Event;

class BlockEvent extends Event
{
    protected $settings;

    protected $blocks = array();

    /**
     * @param array  $settings
     */
    public function __construct(array $settings = array())
    {
        $this->settings = $settings;
    }

    /**
     * @param BlockInterface $block
     */
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
     * @return mixed
     */
    public function getBlocks()
    {
        return $this->blocks;
    }

    /**
     * @param string $name
     * @param mixed  $default
     */
    public function getSetting($name, $default = null)
    {
        return isset($this->settings[$name]) ? $this->settings[$name] : $default;
    }
}
