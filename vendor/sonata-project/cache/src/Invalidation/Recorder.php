<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Cache\Invalidation;

class Recorder
{
    protected $collectionIdentifiers;

    protected $stack = [];

    protected $current = 0;

    /**
     * @param ModelCollectionIdentifiers $collectionIdentifiers
     */
    public function __construct(ModelCollectionIdentifiers $collectionIdentifiers)
    {
        $this->collectionIdentifiers = $collectionIdentifiers;
        $this->stack[$this->current] = [];
    }

    /**
     * @param $object
     */
    public function add($object): void
    {
        $class = get_class($object);

        $identifier = $this->collectionIdentifiers->getIdentifier($object);

        if (false === $identifier) {
            return;
        }

        if (!isset($this->stack[$this->current][$class])) {
            $this->stack[$this->current][$class] = [];
        }

        if (!in_array($identifier, $this->stack[$this->current][$class])) {
            $this->stack[$this->current][$class][] = $identifier;
        }
    }

    /**
     * Add a new elements into the stack.
     */
    public function push(): void
    {
        $this->stack[$this->current + 1] = $this->stack[$this->current];

        ++$this->current;
    }

    /**
     * Remove an element from the stack and return it.
     *
     * @return array
     */
    public function pop()
    {
        $value = $this->stack[$this->current];

        unset($this->stack[$this->current]);

        --$this->current;

        if ($this->current < 0) {
            $this->reset();
        }

        return $value;
    }

    public function reset(): void
    {
        $this->current = 0;
        $this->stack = [];
    }
}
