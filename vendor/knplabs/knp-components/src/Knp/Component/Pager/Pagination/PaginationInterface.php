<?php

namespace Knp\Component\Pager\Pagination;

/**
 * Pagination interface strictly defines
 * the methods - paginator will use to populate the
 * pagination data
 */
interface PaginationInterface
{
    /**
     * @param integer $pageNumber
     */
    function setCurrentPageNumber($pageNumber);

    /**
     * @param integer $numItemsPerPage
     */
    function setItemNumberPerPage($numItemsPerPage);

    /**
     * @param integer $numTotal
     */
    function setTotalItemCount($numTotal);

    /**
     * @param mixed $items
     */
    function setItems($items);

    /**
     * @param string $options
     */
    function setPaginatorOptions($options);

    /**
     * @param array $parameters
     */
    function setCustomParameters(array $parameters);
}
