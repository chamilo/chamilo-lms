<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Block;

use Sonata\BlockBundle\Exception\BlockNotFoundException;
use Sonata\BlockBundle\Model\BlockInterface;

interface BlockLoaderInterface
{
    /**
     * @param mixed $name
     *
     * @throws BlockNotFoundException if no block with that name is found
     *
     * @return BlockInterface
     */
    public function load($name);

    /**
     * @param mixed $name
     *
     * @return bool
     */
    public function support($name);
}
