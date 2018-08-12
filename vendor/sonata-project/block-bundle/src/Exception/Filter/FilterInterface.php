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
 * Interface for the exception filter used in the exception strategy management.
 *
 * It's purpose is to define which exceptions should be managed and which should simply be ignored.
 *
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 */
interface FilterInterface
{
    /**
     * Returns whether or not this filter handles this exception for given block.
     *
     * @param \Exception     $exception Exception to manage
     * @param BlockInterface $block     Block that provoked the exception
     *
     * @return bool
     */
    public function handle(\Exception $exception, BlockInterface $block);
}
