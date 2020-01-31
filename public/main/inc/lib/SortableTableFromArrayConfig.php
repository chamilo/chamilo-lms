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

    /**
     * Constructor.
     *
     * @param array  $data         All the information of the table
     * @param int    $column       Default column that will be use in the sorts functions
     * @param int    $itemsPerPage quantity of pages that we are going to see
     * @param string $tableName    Name of the table
     * @param array  $column_show  An array with binary values 1: we show the column 2: we don't show it
     * @param array  $column_order an array of integers that let us decide how the columns are going to be sort
     * @param string $direction
     * @param bool   $doc_filter   special modification to fix the document name order
     */
    public function __construct(
        $data,
        $column = 1,
        $itemsPerPage = 20,
        $tableName = 'tablename',
        $column_show = [],
        $column_order = [],
        $direction = 'ASC',
        $doc_filter = false
    ) {
        $this->column_show = $column_show;
        $this->column_order = $column_order;
        $this->doc_filter = $doc_filter;

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
        $per_page = null,
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

//        return array_slice($table, $from, $this->per_page);
        return $table;
    }

    /**
     * Get total number of items.
     *
     * @see SortableTable#get_total_number_of_items
     */
    public function get_total_number_of_items()
    {
        if (isset($this->total_number_of_items) && !empty($this->total_number_of_items)) {
            return $this->total_number_of_items;
        } else {
            if (!empty($this->table_data)) {
                return count($this->table_data);
            }

            return 0;
        }
    }
}
