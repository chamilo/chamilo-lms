<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Block;

use Sonata\BlockBundle\Exception\BlockOptionsException;
use Sonata\BlockBundle\Model\BlockInterface;

interface BlockContextManagerInterface
{
    const CACHE_KEY = 'context';

    /**
     * Add settings for a block service
     *
     * @param string  $type     block service
     * @param array   $settings
     * @param boolean $replace  replace existing settings
     */
    public function addSettingsByType($type, array $settings, $replace = false);

    /**
     * Add settings for a block class
     *
     * @param string  $class    block class
     * @param array   $settings
     * @param boolean $replace replace existing settings
     */
    public function addSettingsByClass($class, array $settings, $replace = false);

    /**
     * @param mixed $meta     Data send to the loader to load a block, can be anything...
     * @param array $settings
     *
     * @return BlockContextInterface
     *
     * @throws BlockOptionsException
     */
    public function get($meta, array $settings = array());
}