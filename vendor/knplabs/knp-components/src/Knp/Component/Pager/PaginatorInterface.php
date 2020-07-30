<?php

namespace Knp\Component\Pager;

/**
 * PaginatorInterface
 */
interface PaginatorInterface
{
    const DEFAULT_SORT_FIELD_NAME = 'defaultSortFieldName';
    const DEFAULT_SORT_DIRECTION = 'defaultSortDirection';
    const DEFAULT_FILTER_FIELDS = 'defaultFilterFields';
    const SORT_FIELD_PARAMETER_NAME = 'sortFieldParameterName';
    const SORT_FIELD_WHITELIST = 'sortFieldWhitelist';
    const SORT_DIRECTION_PARAMETER_NAME = 'sortDirectionParameterName';
    const PAGE_PARAMETER_NAME = 'pageParameterName';
    const FILTER_FIELD_PARAMETER_NAME = 'filterFieldParameterName';
    const FILTER_VALUE_PARAMETER_NAME = 'filterValueParameterName';
    const FILTER_FIELD_WHITELIST = 'filterFieldWhitelist';
    const DISTINCT = 'distinct';

    /**
     * Paginates anything (depending on event listeners)
     * into Pagination object, which is a view targeted
     * pagination object (might be aggregated helper object)
     * responsible for the pagination result representation
     *
     * @param mixed $target - anything what needs to be paginated
     * @param integer $page - page number, starting from 1
     * @param integer $limit - number of items per page
     * @param array $options - less used options:
     *     boolean $distinct - default true for distinction of results
     *     string $alias - pagination alias, default none
     *     array $whitelist - sortable whitelist for target fields being paginated
     * @throws \LogicException
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    function paginate($target, $page = 1, $limit = 10, array $options = array());
}
