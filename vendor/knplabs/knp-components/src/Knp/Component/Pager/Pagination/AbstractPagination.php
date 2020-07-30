<?php

namespace Knp\Component\Pager\Pagination;

use Countable, Iterator, ArrayAccess;

abstract class AbstractPagination implements PaginationInterface, Countable, Iterator, ArrayAccess
{
    protected $currentPageNumber;
    protected $numItemsPerPage;
    protected $items = array();
    protected $totalCount;
    protected $paginatorOptions;
    protected $customParameters;

    /**
     * {@inheritDoc}
     */
    public function rewind() {
        reset($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function current() {
        return current($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function key() {
        return key($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function next() {
        next($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function valid() {
        return key($this->items) !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return count($this->items);
    }

    public function setCustomParameters(array $parameters)
    {
        $this->customParameters = $parameters;
    }

    public function getCustomParameter($name)
    {
        return isset($this->customParameters[$name]) ? $this->customParameters[$name] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function setCurrentPageNumber($pageNumber)
    {
        $this->currentPageNumber = $pageNumber;
    }

    /**
     * Get currently used page number
     *
     * @return integer
     */
    public function getCurrentPageNumber()
    {
        return $this->currentPageNumber;
    }

    /**
     * {@inheritDoc}
     */
    public function setItemNumberPerPage($numItemsPerPage)
    {
        $this->numItemsPerPage = $numItemsPerPage;
    }

    /**
     * Get number of items per page
     *
     * @return integer
     */
    public function getItemNumberPerPage()
    {
        return $this->numItemsPerPage;
    }

    /**
     * {@inheritDoc}
     */
    public function setTotalItemCount($numTotal)
    {
        $this->totalCount = $numTotal;
    }

    /**
     * Get total item number available
     *
     * @return integer
     */
    public function getTotalItemCount()
    {
        return $this->totalCount;
    }

    /**
     * {@inheritDoc}
     */
    public function setPaginatorOptions($options)
    {
        $this->paginatorOptions = $options;
    }

    /**
     * Get pagination alias
     *
     * @return string
     */
    public function getPaginatorOption($name)
    {
        return isset($this->paginatorOptions[$name]) ? $this->paginatorOptions[$name] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function setItems($items)
    {
        if (!is_array($items) && !$items instanceof \Traversable) {
            throw new \UnexpectedValueException("Items must be an array type");
        }
        $this->items = $items;
    }

    /**
     * Get current items
     *
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->items);
    }

    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }
}
