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

namespace Sonata\BlockBundle\Block\Loader;

use Sonata\BlockBundle\Block\BlockLoaderInterface;
use Sonata\BlockBundle\Model\Block;

/**
 * @final since sonata-project/block-bundle 3.0
 */
class ServiceLoader implements BlockLoaderInterface
{
    /**
     * @var string[]
     */
    protected $types;

    /**
     * @param string[] $types
     */
    public function __construct(array $types)
    {
        $this->types = $types;
    }

    /**
     * Check if a given block type exists.
     *
     * @param string $type Block type to check for
     *
     * @return bool
     */
    public function exists($type)
    {
        return \in_array($type, $this->types, true);
    }

    public function load($configuration)
    {
        if (!\in_array($configuration['type'], $this->types, true)) {
            throw new \RuntimeException(sprintf(
                'The block type "%s" does not exist',
                $configuration['type']
            ));
        }

        $block = new Block();
        $block->setId(uniqid('', true));
        $block->setType($configuration['type']);
        $block->setEnabled(true);
        $block->setCreatedAt(new \DateTime());
        $block->setUpdatedAt(new \DateTime());
        $block->setSettings($configuration['settings'] ?? []);

        return $block;
    }

    public function support($configuration)
    {
        if (!\is_array($configuration)) {
            return false;
        }

        if (!isset($configuration['type'])) {
            return false;
        }

        return true;
    }
}
