<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Exception\Filter;

use Sonata\BlockBundle\Model\BlockInterface;

/**
 * This filters will ignore all exceptions.
 *
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 */
class KeepNoneFilter implements FilterInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(\Exception $exception, BlockInterface $block)
    {
        return false;
    }
}
