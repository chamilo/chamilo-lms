<?php
/*
 * This file is part of the Sonata package.
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

    protected $stack = array();

    protected $current = 0;

    /**
     * @param ModelCollectionIdentifiers $collectionIdentifiers
     */
    public function __construct(ModelCollectionIdentifiers $collectionIdentifiers)
    {
        $this->collectionIdentifiers = $collectionIdentifiers;
        $this->stack[$this->current] = array();
    }

    /**
     * @param $object
     *
     * @return void
     */
    public function add($object)
    {
        $class = get_class($object);

        $identifier = $this->collectionIdentifiers->getIdentifier($object);

        if ($identifier === false) {
            return;
        }

        if (!isset($this->stack[$this->current][$class])) {
            $this->stack[$this->current][$class] = array();
        }

        if (!in_array($identifier, $this->stack[$this->current][$class])) {
            $this->stack[$this->current][$class][] = $identifier;
        }
    }

    /**
     * Add a new elements into the stack
     *
     * @return void
     */
    public function push()
    {
        $this->stack[$this->current + 1] = $this->stack[$this->current];

        $this->current++;
    }

    /**
     * Remove an element from the stack and return it
     *
     * @return array
     */
    public function pop()
    {
        $value = $this->stack[$this->current];

        unset($this->stack[$this->current]);

        $this->current--;

        if ($this->current < 0) {
            $this->reset();
        }

        return $value;
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->current = 0;
        $this->stack = array();
    }
}
