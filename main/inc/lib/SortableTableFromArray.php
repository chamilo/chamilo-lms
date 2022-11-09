<?php

/* For licensing terms, see /license.txt */

/**
 * Sortable table which can be used for data available in an array.
 */
class SortableTableFromArray extends SortableTable
{
    /**
     * The array containing all data for this table.
     */
    public $table_data;
    public $handlePagination;

    /**
     * Constructor.
     *
     * @param array  $table_data
     * @param int    $default_column
     * @param int    $default_items_per_page
     * @param string $tableName
     * @param string $get_total_number_function
     * @param string $tableId
     */
    public function __construct(
        $table_data,
        $default_column = 1,
        $default_items_per_page = 20,
        $tableName = 'tablename',
        $get_total_number_function = null,
        $tableId = ''
    ) {
        parent::__construct(
            $tableName,
            $get_total_number_function,
            null,
            $default_column,
            $default_items_per_page,
            null,
            $tableId
        );
        $this->table_data = $table_data;
        $this->handlePagination = false;
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
        if ($sort) {
            $content = TableSort::sort_table(
                $this->table_data,
                $this->column,
                'ASC' === $this->direction ? SORT_ASC : SORT_DESC
            );
        } else {
            $content = $this->table_data;
        }

        return array_slice($content, $from, $this->per_page);
    }

    /**
     * Get total number of items.
     *
     * @see SortableTable#get_total_number_of_items
     */
    public function get_total_number_of_items()
    {
        if (isset($this->total_number_of_items) && !empty($this->total_number_of_items) && $this->total_number_of_items != -1) {
            return $this->total_number_of_items;
        } else {
            if (!empty($this->table_data)) {
                return count($this->table_data);
            }

            return 0;
        }
    }
}
