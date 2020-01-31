<?php

namespace SAML2\Utilities;

interface Collection extends \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * Add an element to the collection
     *
     * @param $element
     *
     * @return $this|\SAML2\Utilities\Collection
     */
    public function add($element);


    /**
     * Shorthand for getting a single element that also must be the only element in the collection.
     *
     * @throws \SAML2\Exception\RuntimeException if the element was not the only element
     *
     * @return mixed
     */
    public function getOnlyElement();


    /**
     * Return the first element from the collection
     *
     * @return mixed
     */
    public function first();


    /**
     * Return the last element from the collection
     *
     * @return mixed
     */
    public function last();


    /**
     * Applies the given function to each element in the collection and returns a new collection with the elements returned by the function.
     *
     * @param callable $function
     *
     * @return mixed
     */
    public function map(\Closure $function);


    /**
     * @param callable $filterFunction
     *
     * @return \SAML2\Utilities\Collection
     */
    public function filter(\Closure $filterFunction);


    /**
     * Get the element at index
     *
     * @param mixed $key
     *
     * @return mixed
     */
    public function get($key);


    /**
     * @param $element
     * @return void
     */
    public function remove($element);


    /**
     * Set the value for index
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value);
}
