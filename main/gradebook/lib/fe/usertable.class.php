<?php
/* For licensing terms, see /license.txt */

/**
 * Class UserTable
 * Table to display flat view of a student's evaluations and links.
 *
 * @author Stijn Konings
 * @author Bert Steppé (refactored, optimised, use of caching, datagenerator class)
 *
 * @package chamilo.gradebook
 */
class UserTable extends SortableTable
{
    private $userid;
    private $datagen;

    /**
     * Constructor.
     */
    public function __construct($userid, $evals = [], $links = [], $addparams = null)
    {
        parent::__construct('userlist', null, null, 0);
        $this->userid = $userid;
        $this->datagen = new UserDataGenerator($userid, $evals, $links);
        if (isset($addparams)) {
            $this->set_additional_parameters($addparams);
        }
        $column = 0;
        $this->set_header($column++, get_lang('Type'));
        $this->set_header($column++, get_lang('Score'));
        $this->set_header($column++, get_lang('Course'));
        $this->set_header($column++, get_lang('Category'));
        $this->set_header($column++, get_lang('ScoreAverage'));
        $this->set_header($column++, get_lang('Result'));

        $scoredisplay = ScoreDisplay::instance();
        if ($scoredisplay->is_custom()) {
            $this->set_header($column++, get_lang('Ranking'));
        }
    }

    /**
     * Function used by SortableTable to get total number of items in the table.
     */
    public function get_total_number_of_items()
    {
        return $this->datagen->get_total_items_count();
    }

    /**
     * Function used by SortableTable to generate the data to display.
     */
    public function get_table_data($from = 1, $per_page = null, $column = null, $direction = null, $sort = null)
    {
        $scoredisplay = ScoreDisplay::instance();

        // determine sorting type
        switch ($this->column) {
            // Type
            case 0:
                $sorting = UserDataGenerator::UDG_SORT_TYPE;
                break;
            case 1:
                $sorting = UserDataGenerator::UDG_SORT_NAME;
                break;
            case 2:
                $sorting = UserDataGenerator::UDG_SORT_COURSE;
                break;
            case 3:
                $sorting = UserDataGenerator::UDG_SORT_CATEGORY;
                break;
            case 4:
                $sorting = UserDataGenerator::UDG_SORT_AVERAGE;
                break;
            case 5:
                $sorting = UserDataGenerator::UDG_SORT_SCORE;
                break;
            case 6:
                $sorting = UserDataGenerator::UDG_SORT_MASK;
                break;
        }
        if ($this->direction === 'DESC') {
            $sorting |= UserDataGenerator::UDG_SORT_DESC;
        } else {
            $sorting |= UserDataGenerator::UDG_SORT_ASC;
        }
        $data_array = $this->datagen->get_data($sorting, $from, $this->per_page);
        // generate the data to display
        $sortable_data = [];
        foreach ($data_array as $data) {
            if ($data[2] != '') {
                // filter by course removed
                $row = [];
                $row[] = $this->build_type_column($data[0]);
                $row[] = $this->build_name_link($data[0]);
                $row[] = $data[2];
                $row[] = $data[3];
                $row[] = $data[4];
                $row[] = $data[5];
                if ($scoredisplay->is_custom()) {
                    $row[] = $data[6];
                }
                $sortable_data[] = $row;
            }
        }

        return $sortable_data;
    }

    /**
     * @param $item
     *
     * @return string
     */
    private function build_type_column($item)
    {
        return GradebookUtils::build_type_icon_tag($item->get_icon_name());
    }

    /**
     * @param $item
     *
     * @return string
     */
    private function build_name_link($item)
    {
        switch ($item->get_item_type()) {
            // evaluation
            case 'E':
                return '&nbsp;'
                    .'<a href="gradebook_view_result.php?selecteval='.$item->get_id().'&'.api_get_cidreq().'">'
                    .$item->get_name()
                    .'</a>';
            // link
            case 'L':
                return '&nbsp;<a href="'.$item->get_link().'">'
                    .$item->get_name()
                    .'</a>'
                    .'&nbsp;['.$item->get_type_name().']';
        }
    }
}
