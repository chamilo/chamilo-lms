<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DatagridBundle\Datagrid;

use Sonata\DatagridBundle\Filter\FilterInterface;
use Sonata\DatagridBundle\Pager\PagerInterface;
use Sonata\DatagridBundle\ProxyQuery\ProxyQueryInterface;

interface DatagridInterface
{
    /**
     * @return PagerInterface
     */
    public function getPager();

    /**
     * @return ProxyQueryInterface
     */
    public function getQuery();

    /**
     * @return array
     */
    public function getResults();

    public function buildPager();

    /**
     * @param FilterInterface $filter
     *
     * @return FilterInterface
     */
    public function addFilter(FilterInterface $filter);

    /**
     * @return array
     */
    public function getFilters();

    /**
     * Reorder filters.
     */
    public function reorderFilters(array $keys);

    /**
     * @return array
     */
    public function getValues();

    /**
     * @param string $name
     * @param string $operator
     * @param mixed  $value
     */
    public function setValue($name, $operator, $value);

    /**
     * @return FormInterface
     */
    public function getForm();

    /**
     * @param string $name
     *
     * @return FilterInterface
     */
    public function getFilter($name);

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasFilter($name);

    /**
     * @param string $name
     */
    public function removeFilter($name);

    /**
     * @return bool
     */
    public function hasActiveFilters();
}
