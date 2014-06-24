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

use Sonata\BlockBundle\Exception\BlockNotFoundException;

class BlockLoaderChain implements BlockLoaderInterface
{
    protected $loaders;

    /**
     * @param array $loaders
     */
    public function __construct(array $loaders)
    {
        $this->loaders = $loaders;
    }

    /**
     * {@inheritdoc}
     */
    public function load($block)
    {
        foreach ($this->loaders as $loader) {
            if ($loader->support($block)) {
                return $loader->load($block);
            }
        }

        throw new BlockNotFoundException;
    }

    /**
     * {@inheritdoc}
     */
    public function support($name)
    {
        return true;
    }
}
