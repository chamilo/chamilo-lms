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

namespace Sonata\Exporter\Source;

final class ArraySourceIterator extends IteratorSourceIterator
{
    public function __construct(array $data)
    {
        parent::__construct(new \ArrayIterator($data));
    }
}
