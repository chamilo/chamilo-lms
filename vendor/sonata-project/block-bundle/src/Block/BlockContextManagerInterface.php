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

namespace Sonata\BlockBundle\Block;

use Sonata\BlockBundle\Exception\BlockOptionsException;
use Sonata\BlockBundle\Model\BlockInterface;

/**
 * Interface BlockContextManagerInterface.
 */
interface BlockContextManagerInterface
{
    public const CACHE_KEY = 'context';

    /**
     * Add settings for a block service.
     *
     * @param string $type    block service
     * @param bool   $replace replace existing settings
     */
    public function addSettingsByType($type, array $settings, $replace = false);

    /**
     * Add settings for a block class.
     *
     * @param string $class   block class
     * @param bool   $replace replace existing settings
     */
    public function addSettingsByClass($class, array $settings, $replace = false);

    /**
     * @param BlockInterface|array $meta Data send to the loader to load a block, can be anything...
     *
     * @throws BlockOptionsException
     *
     * @return BlockContextInterface|false
     */
    public function get($meta, array $settings = []);
}
