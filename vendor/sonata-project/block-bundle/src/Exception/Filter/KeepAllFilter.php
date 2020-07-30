<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Exception\Filter;

use Sonata\BlockBundle\Model\BlockInterface;

/**
 * This filter will handle all exceptions.
 *
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 */
class KeepAllFilter implements FilterInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(\Exception $exception, BlockInterface $block)
    {
        return true;
    }
}
