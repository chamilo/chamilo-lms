<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This class allows you to display a sortable data-table. It is possible to
 * split the data in several pages.
 * Using this class you can:
 * - automatically create checkboxes of the first table column
 *     - a "select all" and "deselect all" link is added
 *     - only if you provide a list of actions for the selected items
 * - click on the table header to sort the data
 * - choose how many items you see per page
 * - navigate through all data-pages.
 */
class SortableTable extends HTML_Table
{
    /**
     * A name for this table.
     */
    public $table_name;
    /**
     * The page to display.
     */
    public $page_nr;
    /**
     * The column to sort the data.
     */
    public $column;
    /**
     * The sorting direction (ASC or DESC).
     */
    public $direction;
    /**
     * Number of items to display per page.
     */
    public $per_page;
    /**
     * The default number of items to display per page.
     */
    public $default_items_per_page;
    /**
     * A prefix for the URL-parameters, can be used on pages with multiple
     * SortableTables.
     */
    public $param_prefix;
    /**
     * The pager object to split the data in several pages.
     */
    public $pager;
    /**
     * The total number of items in the table.
     */
    public $total_number_of_items;
    /**
     * The function to get the total number of items.
     */
    public $get_total_number_function;
    /**
     * The function to the the data to display.
     */
    public $get_data_function;
    /**
     * An array with defined column-filters.
     */
    public $column_filters;
    /**
     * A list of actions which will be available through a select list.
     */
    public $form_actions;
    /**
     * Additional parameters to pass in the URL.
     */
    public $additional_parameters;
    /**
     * Additional attributes for the th-tags.
     */
    public $th_attributes;
    /**
     * Additional attributes for the td-tags.
     */
    public $td_attributes;
    /**
     * Array with names of the other tables defined on the same page of this
     * table.
     */
    public $other_tables;
    /**
     * Activates the odd even rows.
     * */
    public $odd_even_rows_enabled = true;
    public $use_jqgrid = false;
    public $table_id = null;
    public $headers = [];
    public $actionButtons = [];
    /**
     * The array containing all data for this table.
     */
    public $table_data;
    public $hideItemSelector;
    // Hide table navigation, better to be use when exporting table to PDF.
    public $hideNavigation = false;

    /**
     * @var array Columns to hide
     */
    private $columnsToHide = [];
    private $dataFunctionParams;
    private $defaultColumn;
    private $defaultItemsPerPage;

    /**
     * Create a new SortableTable.
     *
     * @param string $table_name                A name for the table (default = 'table')
     * @param string $get_total_number_function A user defined function to get
     *                                          the total number of items in the table
     * @param string $get_data_function         A function to get the data to display on
     *                                          the current page
     * @param int    $default_column            The default column on which the data should be
     *                                          sorted
     * @param int    $default_items_per_page    The default number of items to show
     *                                          on one page
     * @param string $default_order_direction   The default order direction;
     *                                          either the constant 'ASC' or 'DESC'
     * @param string $table_id
     * @param array  $attributes                They are custom attributes of the table
     */
    public function __construct(
        $table_name = 'table',
        $get_total_number_function = null,
        $get_data_function = null,
        $default_column = 1,
        $default_items_per_page = 20,
        $default_order_direction = 'ASC',
        $table_id = null,
        $attributes = []
    ) {
        if (empty($table_id)) {
            $table_id = $table_name.uniqid('table', true);
        }

        if (empty($attributes)) {
            $attributes = [];
            $attributes['class'] = 'table table-hover table-striped table-bordered data_table';
            $attributes['id'] = $table_id;
        }

        $this->table_id = $table_id;
        parent::__construct($attributes);
        $this->table_name = $table_name;
        $this->additional_parameters = [];
        $this->param_prefix = $table_name.'_';
        $this->defaultColumn = (int) $default_column;
        $this->defaultItemsPerPage = $default_items_per_page;
        $this->hideItemSelector = false;

        $defaultRow = api_get_configuration_value('table_default_row');
        if (!empty($defaultRow)) {
            $this->defaultItemsPerPage = $default_items_per_page = $defaultRow;
        }

        $cleanSessionData = Session::read('clean_sortable_table');
        if (true === $cleanSessionData) {
            $this->cleanUrlSessionParams();
        }

        // Allow to change paginate in multiples tabs
        $this->per_page = Session::read($this->param_prefix.'per_page', $default_items_per_page);

        // If per page changed, then reset the page to 1
        if (!empty($this->per_page) && isset($_GET[$this->param_prefix.'per_page']) &&
            $this->per_page != $_GET[$this->param_prefix.'per_page']
        ) {
            Session::erase($this->param_prefix.'page_nr');
            $_GET[$this->param_prefix.'page_nr'] = 1;
        }

        $this->per_page = isset($_GET[$this->param_prefix.'per_page'])
            ? (int) $_GET[$this->param_prefix.'per_page']
            : $this->per_page;

        if (isset($_GET[$this->param_prefix.'per_page'])) {
            Session::erase($this->param_prefix.'page_nr');
        }

        $this->page_nr = Session::read($this->param_prefix.'page_nr', 1);
        $this->page_nr = isset($_GET[$this->param_prefix.'page_nr'])
            ? (int) $_GET[$this->param_prefix.'page_nr']
            : $this->page_nr;

        $this->column = Session::read($this->param_prefix.'column', $default_column);
        $this->column = isset($_GET[$this->param_prefix.'column'])
            ? (int) $_GET[$this->param_prefix.'column']
            : $this->column;

        // Default direction.
        if (in_array(strtoupper($default_order_direction), ['ASC', 'DESC'])) {
            $this->direction = $default_order_direction;
        }

        $my_session_direction = Session::read($this->param_prefix.'direction');
        if (!empty($my_session_direction)) {
            if (!in_array($my_session_direction, ['ASC', 'DESC'])) {
                $this->direction = 'ASC';
            } else {
                if ('ASC' === $my_session_direction) {
                    $this->direction = 'ASC';
                } elseif ('DESC' === $my_session_direction) {
                    $this->direction = 'DESC';
                }
            }
        }

        if (isset($_GET[$this->param_prefix.'direction'])) {
            $my_get_direction = $_GET[$this->param_prefix.'direction'];
            if (!in_array($my_get_direction, ['ASC', 'DESC'])) {
                $this->direction = 'ASC';
            } else {
                if ('ASC' === $my_get_direction) {
                    $this->direction = 'ASC';
                } elseif ('DESC' === $my_get_direction) {
                    $this->direction = 'DESC';
                }
            }
        }

        Session::write($this->param_prefix.'per_page', $this->per_page);
        Session::write($this->param_prefix.'direction', $this->direction);
        Session::write($this->param_prefix.'page_nr', $this->page_nr);
        Session::write($this->param_prefix.'column', $this->column);

        $this->pager = null;
        $this->default_items_per_page = $default_items_per_page;
        $this->total_number_of_items = -1;
        $this->get_total_number_function = $get_total_number_function;
        $this->get_data_function = $get_data_function;
        $this->column_filters = [];
        $this->form_actions = [];
        $this->checkbox_name = null;
        $this->td_attributes = [];
        $this->th_attributes = [];
        $this->other_tables = [];
        $this->dataFunctionParams = [];
    }

    /**
     * Clean URL params when changing student view.
     */
    public function cleanUrlSessionParams()
    {
        Session::erase('clean_sortable_table');

        $prefix = $this->param_prefix;

        Session::erase($prefix.'page_nr');
        Session::erase($prefix.'column');
        Session::erase($prefix.'direction');
        Session::erase($prefix.'per_page');

        $_GET[$this->param_prefix.'per_page'] = $this->default_items_per_page;
        $_GET[$this->param_prefix.'page_nr'] = 1;
        $_GET[$this->param_prefix.'column'] = $this->defaultColumn;
        $_GET[$this->param_prefix.'direction'] = $this->direction;
    }

    /**
     * @return array
     */
    public function getDataFunctionParams()
    {
        return $this->dataFunctionParams;
    }

    /**
     * @param array $dataFunctionParams
     *
     * @return $this
     */
    public function setDataFunctionParams($dataFunctionParams)
    {
        $this->dataFunctionParams = $dataFunctionParams;

        return $this;
    }

    /**
     * Get the Pager object to split the showed data in several pages.
     *
     * @return Pager_Sliding
     */
    public function get_pager()
    {
        if (null === $this->pager) {
            $params['mode'] = 'Sliding';
            $params['perPage'] = $this->per_page;
            $params['totalItems'] = $this->get_total_number_of_items();
            $params['urlVar'] = $this->param_prefix.'page_nr';
            $params['currentPage'] = $this->page_nr;
            $icon_attributes = ['style' => 'vertical-align: middle;'];
            $params['prevImg'] = Display::return_icon(
                'action_prev.png',
                get_lang('PreviousPage'),
                $icon_attributes
            );
            $params['nextImg'] = Display::return_icon(
                'action_next.png',
                get_lang('NextPage'),
                $icon_attributes
            );
            $params['firstPageText'] = Display::return_icon(
                'action_first.png',
                get_lang('FirstPage'),
                $icon_attributes
            );
            $params['lastPageText'] = Display::return_icon(
                'action_last.png',
                get_lang('LastPage'),
                $icon_attributes
            );
            $params['firstPagePre'] = '';
            $params['lastPagePre'] = '';
            $params['firstPagePost'] = '';
            $params['lastPagePost'] = '';
            $params['spacesBeforeSeparator'] = '';
            $params['spacesAfterSeparator'] = '';
            $query_vars = array_keys($_GET);
            $query_vars_needed = [
                $this->param_prefix.'column',
                $this->param_prefix.'direction',
                $this->param_prefix.'per_page',
            ];
            if (!empty($this->additional_parameters) && count($this->additional_parameters) > 0) {
                $query_vars_needed = array_merge(
                    $query_vars_needed,
                    array_keys($this->additional_parameters)
                );
            }
            $query_vars_exclude = array_diff($query_vars, $query_vars_needed);

            $params['excludeVars'] = $query_vars_exclude;
            $this->pager = &Pager::factory($params);
        }

        return $this->pager;
    }

    /**
     * Display the table.
     */
    public function display()
    {
        echo $this->return_table();
    }

    public function toArray($applyFilters = false, $stripTags = false)
    {
        $headers = array_column($this->getHeaders(), 'label');

        if ($stripTags) {
            $headers = array_map('strip_tags', $headers);
        }

        if ($applyFilters) {
            $data = [];

            foreach ($this->table_data as $row) {
                $filtered = $this->filter_data($row);

                if ($stripTags) {
                    $filtered = array_map('strip_tags', $filtered);
                }

                $data[] = $filtered;
            }
        } else {
            $data = $this->table_data;
        }

        return array_merge([$headers], $data);
    }

    /**
     * Displays the table, complete with navigation buttons to browse through
     * the data-pages.
     */
    public function return_table()
    {
        $empty_table = false;
        $content = $this->get_table_html();
        if ($this->get_total_number_of_items() == 0) {
            $cols = $this->getColCount();
            $this->setCellAttributes(
                1,
                0,
                'style="font-style: italic;text-align:center;" colspan='.$cols
            );
            $message_empty = api_xml_http_response_encode(get_lang('TheListIsEmpty'));
            $this->setCellContents(1, 0, $message_empty);
            $empty_table = true;
        }

        if ($empty_table) {
            return '';
        }

        $params = $this->get_sortable_table_param_string().'&amp;'.$this->get_additional_url_paramstring();
        $table_id = 'form_'.$this->table_name.'_id';
        $html = '';
        if (false === $this->hideNavigation) {
            $form = $this->get_page_select_form();
            $nav = $this->get_navigation_html();

            $html .= '<div class="table-well">';
            $html .= '<table class="data_table_pagination">';
            $html .= '<tr>';
            $html .= '<td style="width:25%;">';
            $html .= $form;
            $html .= '</td>';
            $html .= '<td style="text-align:center;">';
            $html .= $this->get_table_title();
            $html .= '</td>';
            $html .= '<td style="text-align:right;width:25%;">';
            $html .= $nav;
            $html .= '</td>';
            $html .= '</tr>';
            $html .= '</table>';
            $html .= '</div>';
        }

        if (count($this->form_actions) > 0) {
            $html .= '<form
                id ="'.$table_id.'"
                name="form_'.$this->table_name.'"
                class="form-search"
                method="post" action="'.api_get_self().'?'.$params.'" >';
        }

        $html .= '<div class="table-responsive">'.$content.'</div>';

        if (!empty($this->additional_parameters)) {
            foreach ($this->additional_parameters as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $subKey => $subValue) {
                        $html .= '<input type="hidden" name ="'.Security::remove_XSS($subKey).'" value ="'
                            .Security::remove_XSS($subValue).'" />';
                    }
                } else {
                    $html .= '<input type="hidden" name ="'.Security::remove_XSS($key).'" value ="'
                        .Security::remove_XSS($value).'" />';
                }
            }
        }
        $html .= '<input type="hidden" name="action">';

        $showFooter = count($this->actionButtons) > 0 ||
            count($this->form_actions) > 0 ||
            $this->get_total_number_of_items() > $this->default_items_per_page;

        if ($showFooter) {
            $html .= '<div class="table-well">';
            $html .= '<table class="data_table_pagination">';
            $html .= '<tr>';
            $html .= '<td>';

            if (count($this->actionButtons) > 0) {
                $html .= '<div class="btn-toolbar">';
                $html .= '<div class="btn-group">';

                foreach ($this->actionButtons as $action => $data) {
                    $label = $data['label'];
                    $icon = $data['icon'];
                    $html .= '<a class="btn btn-default" href="?'.$params.'&action_table='.$action.'" >'.$icon.'&nbsp;'
                        .$label.'</a>';
                }
                $html .= '</div>'; //btn-group
                $html .= '</div>'; //toolbar
            }

            if (count($this->form_actions) > 0) {
                $html .= '<div class="btn-toolbar">';
                $html .= '<div class="btn-group">';
                $html .= '<a class="btn btn-default" href="?'.$params.'&amp;'.$this->param_prefix
                    .'selectall=1" onclick="javascript: setCheckbox(true, \''.$table_id.'\'); return false;">'
                    .get_lang('SelectAll').'</a>';
                $html .= '<a class="btn btn-default" href="?'.$params
                    .'" onclick="javascript: setCheckbox(false, \''.$table_id.'\'); return false;">'
                    .get_lang('UnSelectAll').'</a> ';
                $html .= '</div>';
                $html .= '<div class="btn-group">
                        <button class="btn btn-default" type="button">'.get_lang('Actions').'</button>
                        <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                            <span class="caret"></span>
                        </button>';
                $html .= '<ul class="dropdown-menu">';
                foreach ($this->form_actions as $action => &$label) {
                    $html .= '<li><a data-action ="'.$action
                        .'" href="#" onclick="javascript:action_click(this, \''.$table_id.'\');">'.$label.'</a></li>';
                }
                $html .= '</ul>';
                $html .= '</div>'; //btn-group
                $html .= '</div>'; //toolbar
            }

            $html .= '</td>';
            // Pagination
            if ($this->get_total_number_of_items() > $this->default_items_per_page) {
                $html .= '<td class="text-right">';
                $html .= $nav;
                $html .= '</td>';
            }

            $html .= '</tr>';
            $html .= '</table>';
            $html .= '</div>'; //toolbar
        }

        if (count($this->form_actions) > 0) {
            $html .= '</form>';
        }

        return $html;
    }

    /**
     * This function shows the content of a table in a grid.
     * Should not be use to edit information (edit/delete rows) only.
     */
    public function display_grid()
    {
        $empty_table = false;
        if ($this->get_total_number_of_items() == 0) {
            $message_empty = api_xml_http_response_encode(get_lang('TheListIsEmpty'));
            $this->setCellContents(1, 0, $message_empty);
            $empty_table = true;
        }
        $html = '';
        if (!$empty_table) {
            $form = $this->get_page_select_form();
            $nav = $this->get_navigation_html();

            // @todo This style css must be moved to default.css only for dev
            echo '<style>
                    .main-grid { width:100%;}
                    .sub-header { width:100%; padding-top: 10px; padding-right: 10px; padding-left: 10px; height:30px;}
                    .grid_container { width:100%;}
                    .grid_item { height: 120px; width:98px;  float:left; padding:5px; margin:8px;}
                    .grid_element_0 { width:100px; height: 100px; float:left; text-align:center; margin-bottom:5px;}
                    .grid_element_1 { width:100px; float:left; text-align:center;margin-bottom:5px;}
                    .grid_element_2 { width:150px; float:left;}
                    .grid_selectbox { width:30%; float:left;}
                    .grid_title     { width:30%; float:left;}
                    .grid_nav         { }
            </style>';

            // @todo  This also must be moved
            // Show only navigations if there are more than 1 page
            $my_pager = $this->get_pager();
            $html .= '<div class="main-grid">';
            if ($my_pager->numPages() > 1) {
                $html .= '<div class="sub-header">';
                $html .= '<div class="grid_selectbox">'.$form.'</div>';
                $html .= '<div class="grid_title">'.$this->get_table_title().'</div>';
                $html .= '<div class="grid_nav">'.$nav.'</div>';
                $html .= '</div>';
            }

            $html .= '<div class="clear"></div>';
            if (count($this->form_actions) > 0) {
                $params = $this->get_sortable_table_param_string().'&amp;'.$this->get_additional_url_paramstring();
                $html .= '<form method="post" action="'.api_get_self().'?'.$params
                    .'" name="form_'.$this->table_name.'">';
            }
        }
        // Getting the items of the table
        $items = $this->get_clean_html(false); //no sort

        // Generation of style classes must be improved. Maybe we need a a table name to create style on the fly:
        // i.e: .whoisonline_table_grid_container instead of  .grid_container
        // where whoisonline is the table's name like drupal's template engine
        $html .= '<div class="grid_container">';
        if (is_array($items) && count($items) > 0) {
            foreach ($items as &$row) {
                $html .= '<div class="grid_item">';
                $i = 0;
                foreach ($row as &$element) {
                    $html .= '<div class="grid_element_'.$i.'">'.$element.'</div>';
                    $i++;
                }
                $html .= '</div>';
            }
        }
        $html .= '</div>'; //close grid_container
        $html .= '</div>'; //close main grid
        $html .= '<div class="clear"></div>';

        echo $html;
    }

    /**
     * This function returns the content of a table in a grid
     * Should not be use to edit information (edit/delete rows) only.
     *
     * @param array      options of visibility
     * @param bool       hide navigation optionally
     * @param int        content per page when show navigation (optional)
     * @param bool       sort data optionally
     *
     * @return string grid html
     */
    public function display_simple_grid(
        $visibility_options,
        $hide_navigation = true,
        $per_page = 20,
        $sort_data = true,
        $grid_class = []
    ) {
        $empty_table = false;
        if ($this->get_total_number_of_items() == 0) {
            $message_empty = api_xml_http_response_encode(get_lang('TheListIsEmpty'));
            $this->setCellContents(1, 0, $message_empty);
            $empty_table = true;
        }
        $html = '';
        if (!$empty_table) {
            // If we show the pagination
            if (!$hide_navigation) {
                $form = '&nbsp;';

                if ($this->get_total_number_of_items() > $per_page) {
                    if ($per_page > 10) {
                        $form = $this->get_page_select_form();
                    }
                    $nav = $this->get_navigation_html();
                    // This also must be moved
                    $html = '<div class="sub-header">';
                    $html .= '<div class="grid_selectbox">'.$form.'</div>';
                    $html .= '<div class="grid_title">'.$this->get_table_title().'</div>';
                    $html .= '<div class="grid_nav">'.$nav.'</div>';
                    $html .= '</div>';
                }
            }

            $html .= '<div class="clear"></div>';
            if (count($this->form_actions) > 0) {
                $params = $this->get_sortable_table_param_string().'&amp;'.$this->get_additional_url_paramstring();
                $html .= '<form method="post" action="'.api_get_self().'?'.$params
                    .'" name="form_'.$this->table_name.'">';
            }
        }

        if ($hide_navigation) {
            $items = $this->table_data; // This is a faster way to get what we want
        } else {
            // The normal way
            $items = $this->get_clean_html($sort_data); // Getting the items of the table
        }

        // Generation of style classes must be improved. Maybe we need a a table name to create style on the fly:
        // i.e: .whoisonline_table_grid_container instead of  .grid_container
        // where whoisonline is the table's name like drupal's template engine

        if (is_array($visibility_options)) {
            $filter = false; // The 2nd condition of the if will be loaded
        } else {
            $filter = $visibility_options !== false;
        }

        $item_css_class = $item_css_style = $grid_css_class = $grid_css_style = '';
        if (!empty($grid_class)) {
            $grid_css_class = $grid_class['main']['class'];
            $item_css_class = $grid_class['item']['class'];

            $grid_css_style = isset($grid_class['main']['style']) ? $grid_class['main']['style'] : null;
            $item_css_style = isset($grid_class['item']['style']) ? $grid_class['item']['style'] : null;
        }

        $div = '';
        if (is_array($items) && count($items) > 0) {
            foreach ($items as &$row) {
                $i = 0;
                $rows = '';
                foreach ($row as &$element) {
                    if ($filter || isset($visibility_options[$i]) && $visibility_options[$i]
                    ) {
                        $rows .= '<div class="'.$this->table_name.'_grid_element_'.$i.'">'.$element.'</div>';
                    }
                    $i++;
                }
                $div .= Display::div(
                    $rows,
                    [
                        'class' => $item_css_class.' '.$this->table_name.'_grid_item',
                        'style' => $item_css_style,
                    ]
                );
            }
        }

        $html .= Display::div(
            $div,
            [
                'class' => $grid_css_class.' '.$this->table_name.'_grid_container',
                'style' => $grid_css_style,
            ]
        );
        $html .= '<div class="clear"></div>';

        return $html;
    }

    /**
     * Get the HTML-code with the navigational buttons to browse through the
     * data-pages.
     */
    public function get_navigation_html()
    {
        $pager = $this->get_pager();
        $pager_links = $pager->getLinks();
        $nav = $pager_links['first'].' '.$pager_links['back'];
        $nav .= ' '.$pager->getCurrentPageId().' / '.$pager->numPages().' ';
        $nav .= $pager_links['next'].' '.$pager_links['last'];

        return $nav;
    }

    /**
     * Get the HTML-code with the data-table.
     */
    public function get_table_html()
    {
        $pager = $this->get_pager();
        $offset = $pager->getOffsetByPageId();
        $from = $offset[0] - 1;
        $table_data = $this->get_table_data($from, $this->per_page, $this->column);
        $this->processHeaders();
        if (is_array($table_data)) {
            $count = 1;
            foreach ($table_data as &$row) {
                $row = $this->filter_data($row);

                $newRow = [];
                if (!empty($this->columnsToHide)) {
                    $counter = 0;
                    foreach ($row as $index => $rowInfo) {
                        if (!isset($this->columnsToHide[$index])) {
                            $newRow[$counter] = $rowInfo;
                            $counter++;
                        }
                    }
                    $row = $newRow;
                }
                $this->addRow($row);
                if (isset($row['child_of'])) {
                    $this->setRowAttributes(
                        $count,
                        ['class' => 'hidden hidden_'.$row['child_of']],
                        true
                    );
                }
                $count++;
            }
        }

        if (true == $this->odd_even_rows_enabled) {
            $this->altRowAttributes(
                0,
                ['class' => 'row_odd'],
                ['class' => 'row_even'],
                true
            );
        }

        foreach ($this->th_attributes as $column => $attributes) {
            $this->setCellAttributes(0, $column, $attributes);
        }
        foreach ($this->td_attributes as $column => $attributes) {
            $this->setColAttributes($column, $attributes);
        }

        return $this->toHTML();
    }

    /**
     * This function return the items of the table.
     *
     * @param bool true for sorting table data or false otherwise
     *
     * @return array table row items
     */
    public function get_clean_html($sort = true)
    {
        $pager = $this->get_pager();
        $offset = $pager->getOffsetByPageId();
        $from = $offset[0] - 1;
        $table_data = $this->get_table_data($from, null, null, null, $sort);
        $new_table_data = [];
        if (is_array($table_data)) {
            foreach ($table_data as $index => &$row) {
                $row = $this->filter_data($row);
                $new_table_data[] = $row;
            }
        }

        return $new_table_data;
    }

    /**
     * Get the HTML-code which represents a form to select how many items a page
     * should contain.
     */
    public function get_page_select_form()
    {
        $total_number_of_items = $this->get_total_number_of_items();
        if ($total_number_of_items <= $this->default_items_per_page) {
            return '';
        }

        if (true === $this->hideItemSelector) {
            return '';
        }

        $result[] = '<form method="GET" action="'.api_get_self().'" style="display:inline;">';
        $param[$this->param_prefix.'direction'] = $this->direction;
        $param[$this->param_prefix.'page_nr'] = $this->page_nr;
        $param[$this->param_prefix.'column'] = $this->column;

        if (is_array($this->additional_parameters)) {
            $param = array_merge($param, $this->additional_parameters);
        }

        foreach ($param as $key => &$value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $result[] = '<input type="hidden" name="'.$subKey.'" value="'.$subValue.'"/>';
                }
            } else {
                $result[] = '<input type="hidden" name="'.$key.'" value="'.$value.'"/>';
            }
        }
        $result[] = '<select style="width: auto;" class="form-control" name="'.$this->param_prefix
            .'per_page" onchange="javascript: this.form.submit();">';
        $list = [10, 20, 50, 100, 500, 1000];

        $rowList = api_get_configuration_value('table_row_list');
        if (!empty($rowList) && isset($rowList['options'])) {
            $list = $rowList['options'];
        }

        foreach ($list as $nr) {
            if ($total_number_of_items <= $nr) {
                break;
            }
            $result[] = '<option value="'.$nr.'" '.($nr == $this->per_page ? 'selected="selected"' : '').'>'.$nr
                .'</option>';
        }

        $result[] = '<option value="'.$total_number_of_items.'" '
            .($total_number_of_items == $this->per_page ? 'selected="selected"' : '')
            .'>'.api_ucfirst(get_lang('All')).'</option>';
        //}
        $result[] = '</select>';
        $result[] = '<noscript>';
        $result[] = '<button class="btn btn-success" type="submit">'.get_lang('Save').'</button>';
        $result[] = '</noscript>';
        $result[] = '</form>';
        $result = implode("\n", $result);

        return $result;
    }

    /**
     * Get the table title.
     */
    public function get_table_title()
    {
        $pager = $this->get_pager();
        $showed_items = $pager->getOffsetByPageId();

        return $showed_items[0].' - '.$showed_items[1].' / '.$this->get_total_number_of_items();
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Process headers.
     */
    public function processHeaders()
    {
        $counter = 0;
        foreach ($this->headers as $column => $columnInfo) {
            $label = $columnInfo['label'];
            $sortable = $columnInfo['sortable'];
            $th_attributes = $columnInfo['th_attributes'];
            $td_attributes = $columnInfo['td_attributes'];

            if (!empty($this->columnsToHide)) {
                if (isset($this->columnsToHide[$column])) {
                    continue;
                }
            }

            $column = $counter;
            $param['direction'] = 'ASC';
            if ($this->column == $column && 'ASC' == $this->direction) {
                $param['direction'] = 'DESC';
            }

            $param['page_nr'] = $this->page_nr;
            $param['per_page'] = $this->per_page;
            $param['column'] = $column;
            $link = $label;
            if ($sortable) {
                $link = '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;';
                foreach ($param as $key => &$value) {
                    $link .= $this->param_prefix.$key.'='.urlencode($value).'&amp;';
                }
                $link .= $this->get_additional_url_paramstring();
                $link .= '">'.$label.'</a>';
                if ($this->column == $column) {
                    $link .= 'ASC' == $this->direction ? ' &#8595;' : ' &#8593;';
                }
            }
            $this->setHeaderContents(0, $column, $link);

            if (!is_null($td_attributes)) {
                $this->td_attributes[$column] = $td_attributes;
            }
            if (!is_null($th_attributes)) {
                $this->th_attributes[$column] = $th_attributes;
            }

            $counter++;
        }
    }

    /**
     * Set the header-label.
     *
     * @param int    $column        The column number
     * @param string $label         The label
     * @param bool   $sortable      Is the table sortable by this column? (defatult
     *                              = true)
     * @param string $th_attributes Additional attributes for the th-tag of the
     *                              table header
     * @param string $td_attributes Additional attributes for the td-tags of the
     *                              column
     */
    public function set_header(
        $column,
        $label,
        $sortable = true,
        $th_attributes = ['class' => 'th-header'],
        $td_attributes = null
    ) {
        $this->headers[$column] = [
            'label' => $label,
            'sortable' => $sortable,
            'th_attributes' => $th_attributes,
            'td_attributes' => $td_attributes,
        ];
    }

    /**
     * Get the parameter-string with additional parameters to use in the URLs
     * generated by this SortableTable.
     */
    public function get_additional_url_paramstring()
    {
        $result = '';
        if (is_array($this->additional_parameters)) {
            $result = http_build_query($this->additional_parameters);
        }
        foreach ($this->other_tables as $index => &$tablename) {
            $param = [];
            if (isset($_GET[$tablename.'_direction'])) {
                $my_get_direction = $_GET[$tablename.'_direction'];
                if (!in_array($my_get_direction, ['ASC', 'DESC'])) {
                    $param[$tablename.'_direction'] = 'ASC';
                } else {
                    $param[$tablename.'_direction'] = $my_get_direction;
                }
            }
            if (isset($_GET[$tablename.'_page_nr'])) {
                $param[$tablename.'_page_nr'] = intval($_GET[$tablename.'_page_nr']);
            }
            if (isset($_GET[$tablename.'_per_page'])) {
                $param[$tablename.'_per_page'] = intval($_GET[$tablename.'_per_page']);
            }
            if (isset($_GET[$tablename.'_column'])) {
                $param[$tablename.'_column'] = intval($_GET[$tablename.'_column']);
            }
            if (count($param) > 0) {
                $result .= (strlen($result) > 0 ? '&' : '').http_build_query($param);
            }
        }

        return $result;
    }

    /**
     * Get the parameter-string with the SortableTable-related parameters to use
     * in URLs.
     */
    public function get_sortable_table_param_string()
    {
        $param[$this->param_prefix.'direction'] = $this->direction;
        $param[$this->param_prefix.'page_nr'] = $this->page_nr;
        $param[$this->param_prefix.'per_page'] = $this->per_page;
        $param[$this->param_prefix.'column'] = $this->column;
        $param_string_parts = [];
        foreach ($param as $key => &$value) {
            $param_string_parts[] = urlencode($key).'='.urlencode($value);
        }
        $res = implode('&amp;', $param_string_parts);

        return $res;
    }

    /**
     * Add a filter to a column. If another filter was already defined for the
     * given column, it will be overwritten.
     *
     * @param int            $column   The number of the column
     * @param string|Closure $function The name of the filter-function. This should be a
     *                                 function wich requires 1 parameter and returns the filtered value.
     */
    public function set_column_filter($column, $function)
    {
        $this->column_filters[$column] = $function;
    }

    /**
     * List of columns to hide.
     *
     * @param int $column
     */
    public function setHideColumn($column)
    {
        $this->columnsToHide[$column] = $column;
    }

    /**
     * Define a list of actions which can be performed on the table-date.
     * If you define a list of actions, the first column of the table will be
     * converted into checkboxes.
     *
     * @param array  $actions       A list of actions. The key is the name of the
     *                              action. The value is the label to show in the select-box
     * @param string $checkbox_name The name of the generated checkboxes. The
     *                              value of the checkbox will be the value of the first column.
     */
    public function set_form_actions($actions, $checkbox_name = 'id')
    {
        $this->form_actions = $actions;
        $this->checkbox_name = $checkbox_name;
    }

    /**
     * Define a list of additional parameters to use in the generated URLs
     * <code>$parameters['action'] = 'test'; will be convert in
     * <input type="hidden" name="action" value="test"></code>.
     *
     * @param array $parameters
     */
    public function set_additional_parameters($parameters)
    {
        $this->additional_parameters = $parameters;
    }

    /**
     * Set other tables on the same page.
     * If you have other sortable tables on the page displaying this sortable
     * tables, you can define those other tables with this function. If you
     * don't define the other tables, there sorting and pagination will return
     * to their default state when sorting this table.
     *
     * @param array $tablenames an array of table names
     */
    public function set_other_tables($tablenames)
    {
        $this->other_tables = $tablenames;
    }

    /**
     * Transform all data in a table-row, using the filters defined by the
     * function set_column_filter(...) defined elsewhere in this class.
     * If you've defined actions, the first element of the given row will be
     * converted into a checkbox.
     *
     * @param array $row a row from the table
     *
     * @return array
     */
    public function filter_data($row)
    {
        $url_params = $this->get_sortable_table_param_string().'&'.$this->get_additional_url_paramstring();
        foreach ($this->column_filters as $column => &$function) {
            $firstParam = isset($row[$column]) ? $row[$column] : 0;
            $row[$column] = call_user_func($function, $firstParam, $url_params, $row);
        }
        if (count($this->form_actions) > 0) {
            if (strlen($row[0]) > 0) {
                $row[0] = '<input type="checkbox" name="'.$this->checkbox_name.'[]" value="'.$row[0].'"';
                if (isset($_GET[$this->param_prefix.'selectall'])) {
                    $row[0] .= ' checked="checked"';
                }
                $row[0] .= '/>';
            }
        }
        if (is_array($row)) {
            foreach ($row as &$value) {
                if (empty($value)) {
                    $value = '-';
                }
            }
        }

        return $row;
    }

    /**
     * Get the total number of items. This function calls the function given as
     * 2nd argument in the constructor of a SortableTable. Make sure your
     * function has the same parameters as defined here.
     */
    public function get_total_number_of_items()
    {
        if ($this->total_number_of_items == -1 && !is_null($this->get_total_number_function)) {
            $this->total_number_of_items = call_user_func(
                $this->get_total_number_function,
                $this->getDataFunctionParams()
            );
        }

        return $this->total_number_of_items;
    }

    /**
     * @param int $value
     */
    public function setTotalNumberOfItems($value)
    {
        $this->total_number_of_items = (int) $value;
    }

    /**
     * Get the data to display.  This function calls the function given as
     * 2nd argument in the constructor of a SortableTable. Make sure your
     * function has the same parameters as defined here.
     *
     * @param int    $from      index of the first item to return
     * @param int    $per_page  The number of items to return
     * @param int    $column    The number of the column on which the data should be
     * @param bool   $sort      Whether to sort or not
     *                          sorted
     * @param string $direction In which order should the data be sorted (ASC
     *                          or DESC)
     *
     * @return array
     */
    public function get_table_data(
        $from = null,
        $per_page = null,
        $column = null,
        $direction = null,
        $sort = null
    ) {
        $data = [];
        if (null !== $this->get_data_function) {
            $data = call_user_func(
                $this->get_data_function,
                $from,
                $this->per_page,
                $this->column,
                $this->direction,
                $this->dataFunctionParams
            );
        }

        return $data;
    }

    /**
     * @param array $data
     */
    public function setTableData($data)
    {
        $this->table_data = $data;
    }
}
