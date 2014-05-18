<?php
/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\BlockBundle\Util;

/**
 * RecursiveBlockIteratorIterator
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class RecursiveBlockIteratorIterator extends \RecursiveIteratorIterator
{
    /**
     * @param \Traversable $array
     */
    public function __construct($array)
    {
        parent::__construct(new RecursiveBlockIterator($array), \RecursiveIteratorIterator::SELF_FIRST);
    }
}
