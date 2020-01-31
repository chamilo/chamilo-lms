<?php

namespace SAML2\Utilities;

use SAML2\Exception\RuntimeException;

/**
 * Simple Array implementation of Collection.
 *
 * @SuppressWarnings(PHPMD.TooManyMethods) - it just has a large api.
 */
class ArrayCollection implements Collection
{
    /**
     * @var array
     */
    protected $elements;


    /**
     * @return void
     */
    public function __construct(array $elements = [])
    {
        $this->elements = $elements;
    }


    /**
     * @return void
     */
    public function add($element)
    {
        $this->elements[] = $element;
    }


    /**
     * @return mixed|null
     */
    public function get($key)
    {
        return isset($this->elements[$key]) ? $this->elements[$key] : null;
    }


    /**
     * @return ArrayCollection
     */
    public function filter(\Closure $f)
    {
        return new self(array_filter($this->elements, $f));
    }


    /**
     * @return void
     */
    public function set($key, $value)
    {
        $this->elements[$key] = $value;
    }


    /**
     * @return mixed
     */
    public function remove($element)
    {
        $key = array_search($element, $this->elements);

        if ($key === false) {
            return false;
        }

        $removed = $this->elements[$key];
        unset($this->elements[$key]);

        return $removed;
    }


    /**
     * @throws RuntimeException
     * @return mixed
     */
    public function getOnlyElement()
    {
        if ($this->count() !== 1) {
            throw new RuntimeException(sprintf(
                __CLASS__.'::'.__METHOD__.' requires that the collection has exactly one element, '
                . '"%d" elements found',
                $this->count()
            ));
        }

        return reset($this->elements);
    }


    /**
     * @return mixed
     */
    public function first()
    {
        return reset($this->elements);
    }


    /**
     * @return mixed
     */
    public function last()
    {
        return end($this->elements);
    }


    /**
     * @return ArrayCollection
     */
    public function map(\Closure $function)
    {
        return new self(array_map($function, $this->elements));
    }


    /**
     * @return int
     */
    public function count()
    {
        return count($this->elements);
    }


    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->elements);
    }


    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->elements[$offset]);
    }


    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->elements[$offset];
    }


    /**
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->elements[$offset] = $value;
    }


    /**
     * @param mixed
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->elements[$offset]);
    }
}
