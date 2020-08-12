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

/**
 * SourceIterator implementation based on Iterator.
 */
class IteratorSourceIterator implements SourceIteratorInterface
{
    /**
     * @var \Iterator
     */
    protected $iterator;

    /**
     * @param \Iterator $iterator Iterator with string array elements
     */
    public function __construct(\Iterator $iterator)
    {
        $this->iterator = $iterator;
    }

    final public function getIterator(): \Iterator
    {
        return $this->iterator;
    }

    public function current()
    {
        return $this->iterator->current();
    }

    final public function next(): void
    {
        $this->iterator->next();
    }

    final public function key()
    {
        return $this->iterator->key();
    }

    final public function valid(): bool
    {
        return $this->iterator->valid();
    }

    final public function rewind(): void
    {
        $this->iterator->rewind();
    }
}
