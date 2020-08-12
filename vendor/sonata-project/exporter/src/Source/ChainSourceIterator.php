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

use ArrayIterator;

final class ChainSourceIterator implements SourceIteratorInterface
{
    /**
     * @var ArrayIterator
     */
    private $sources;

    public function __construct(array $sources = [])
    {
        $this->sources = new ArrayIterator();

        foreach ($sources as $source) {
            $this->addSource($source);
        }
    }

    public function addSource(SourceIteratorInterface $source): void
    {
        $this->sources->append($source);
    }

    public function current()
    {
        return $this->sources->current()->current();
    }

    public function next(): void
    {
        $this->sources->current()->next();
    }

    public function key()
    {
        return $this->sources->current()->key();
    }

    public function valid(): bool
    {
        while (!$this->sources->current()->valid()) {
            $this->sources->next();

            if (!$this->sources->valid()) {
                return false;
            }

            $this->sources->current()->rewind();
        }

        return true;
    }

    public function rewind(): void
    {
        if ($this->sources->current()) {
            $this->sources->current()->rewind();
        }
    }
}
