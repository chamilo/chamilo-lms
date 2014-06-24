<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Source;

use ArrayIterator;

class ChainSourceIterator implements SourceIteratorInterface
{
    protected $sources;

    /**
     * @param array $sources
     */
    public function __construct(array $sources = array())
    {
        $this->sources = new ArrayIterator();

        foreach ($sources as $source) {
            $this->addSource($source);
        }
    }

    /**
     * @param SourceIteratorInterface $source
     */
    public function addSource(SourceIteratorInterface $source)
    {
        $this->sources->append($source);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->sources->current()->current();
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->sources->current()->next();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->sources->current()->key();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
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

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        if ($this->sources->current()) {
            $this->sources->current()->rewind();
        }
    }
}
