<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Source;

class ArraySourceIterator extends IteratorSourceIterator
{
    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        parent::__construct(new \ArrayIterator($data));
    }
}
