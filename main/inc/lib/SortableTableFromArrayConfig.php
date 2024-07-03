<?php
/* For licensing terms, see /license.txt */

/**
 * Sortable table which can be used for data available in an array.
 *
 * Is a variation of SortableTableFromArray because we add 2 new arrays  $column_show and $column_order
 * $column_show is an array that lets us decide which are going to be the columns to show
 * $column_order is an array that lets us decide the ordering of the columns
 * i.e: $column_header=array('a','b','c','d','e'); $column_order=array(1,2,5,4,5);
 * These means that the 3th column (letter "c") will be sort like the order we use in the 5th column
 */
class SortableTableFromArrayConfig extends SortableTable
{
    /**
     * The array containing the columns that will be show
     * i.e $column_show=array('1','0','0'); we will show only the 1st column.
     */
    private $column_show;

    /**
     * The array containing the real sort column
     * $column_order=array('1''4','3','4');
     * The 2nd column will be order like the 4th column.
     */
    private $column_order;

    private $doc_filter;
    private $handlePagination = true;

    /**
     * Constructor.
     *
     * @param array  $data         All the information of the table
     * @param int    $column       Default column that will be used in the sort functions
     * @param int    $itemsPerPage Number of items per pages that we are going to see
     * @param string $tableName    Name of the table
     * @param array  $columnShow   An array with binary values: 1 = show column, 2 = don't show it
     * @param array  $columnOrder  An array of integers that let us decide how the columns are going to be sort
     * @param string $direction    ASC/DESC
     * @param bool   $docFilter    special modification to fix the document name order
     */
    public function __construct(
        $data,
        $column = 1,
        $itemsPerPage = 20,
        $tableName = 'tablename',
        $columnShow = [],
        $columnOrder = [],
        $direction = 'ASC',
        $docFilter = false
    ) {
        $this->column_show = $columnShow;
        $this->column_order = $columnOrder;
        $this->doc_filter = $docFilter;

        // if data is empty the pagination is handled with query in database
        if (empty($data)) {
            $this->handlePagination = false;
        }
        parent::__construct(
            $tableName,
            null,
            null,
            $column,
            $itemsPerPage,
            $direction
        );
        $this->table_data = $data;
    }

    /**
     * Get table data to show on current page.
     *
     * @see SortableTable#get_table_data
     */
    public function get_table_data(
        $from = 1,
        $perPage = null,
        $column = null,
        $direction = null,
        $sort = true
    ) {
        $table = TableSort::sort_table_config(
            $this->table_data,
            $this->column,
            'ASC' === $this->direction ? SORT_ASC : SORT_DESC,
            $this->column_show,
            $this->column_order,
            SORT_REGULAR,
            $this->doc_filter
        );

        if ($this->handlePagination) {
            return array_slice($table, $from, $this->per_page);
        }

        return $table;
    }

    /**
     * Get total number of items.
     *
     * @see SortableTable#get_total_number_of_items
     */
    public function get_total_number_of_items()
    {
        if (!empty($this->total_number_of_items) && $this->total_number_of_items !== -1) {
            return $this->total_number_of_items;
        } else {
            if (!empty($this->table_data)) {
                return count($this->table_data);
            }

            return 0;
        }
    }
}
