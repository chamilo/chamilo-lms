<?php
/* For licensing terms, see /license.txt */

/**
 * Class ResultTable
 * Table to display results for an evaluation
 * @author Stijn Konings
 * @author Bert Steppé
 * @package chamilo.gradebook
 */
class ResultTable extends SortableTable
{
    private $datagen;
    private $evaluation;
    private $allresults;
    private $iscourse;

    /**
     * ResultTable constructor.
     * @param string $evaluation
     * @param array $results
     * @param null|string $iscourse
     * @param array $addparams
     * @param bool $forprint
     */
    public function __construct(
        $evaluation,
        $results = [],
        $iscourse,
        $addparams = [],
        $forprint = false
    ) {
        parent:: __construct(
            'resultlist',
            null,
            null,
            api_is_western_name_order() ? 1 : 2
        );

        $this->datagen = new ResultsDataGenerator($evaluation, $results, true);

        $this->evaluation = $evaluation;
        $this->iscourse = $iscourse;
        $this->forprint = $forprint;

        if (isset($addparams)) {
            $this->set_additional_parameters($addparams);
        }
        $scoredisplay = ScoreDisplay::instance();
        $column = 0;
        if ($this->iscourse == '1') {
            $this->set_header($column++, '', false);
            $this->set_form_actions([
                    'delete' => get_lang('Delete')
            ]);
        }
        if (api_is_western_name_order()) {
            $this->set_header($column++, get_lang('FirstName'));
            $this->set_header($column++, get_lang('LastName'));
        } else {
            $this->set_header($column++, get_lang('LastName'));
            $this->set_header($column++, get_lang('FirstName'));
        }
        $this->set_header($column++, get_lang('Score'));
        if ($scoredisplay->is_custom()) {
            $this->set_header($column++, get_lang('Display'));
        }
        if (!$this->forprint) {
            $this->set_header($column++, get_lang('Modify'), false);
        }
    }

    /**
     * Function used by SortableTable to get total number of items in the table
     */
    public function get_total_number_of_items()
    {
        return $this->datagen->get_total_results_count();
    }

    /**
     * Function used by SortableTable to generate the data to display
     */
    public function get_table_data(
        $from = 1,
        $per_page = null,
        $column = null,
        $direction = null,
        $sort = null
    ) {
        $isWesternNameOrder = api_is_western_name_order();
        $scoredisplay = ScoreDisplay::instance();

        // determine sorting type
        $col_adjust = $this->iscourse == '1' ? 1 : 0;

        switch ($this->column) {
            // first name or last name
            case (0 + $col_adjust):
                if ($isWesternNameOrder) {
                    $sorting = ResultsDataGenerator::RDG_SORT_FIRSTNAME;
                } else {
                    $sorting = ResultsDataGenerator::RDG_SORT_LASTNAME;
                }
                break;
                // first name or last name
            case (1 + $col_adjust):
                if ($isWesternNameOrder) {
                    $sorting = ResultsDataGenerator::RDG_SORT_LASTNAME;
                } else {
                    $sorting = ResultsDataGenerator::RDG_SORT_FIRSTNAME;
                }
                break;
                // Score
            case (2 + $col_adjust):
                $sorting = ResultsDataGenerator::RDG_SORT_SCORE;
                break;
            case (3 + $col_adjust):
                $sorting = ResultsDataGenerator::RDG_SORT_MASK;
                break;
        }

        if ($this->direction == 'DESC') {
            $sorting |= ResultsDataGenerator::RDG_SORT_DESC;
        } else {
            $sorting |= ResultsDataGenerator::RDG_SORT_ASC;
        }

        $data_array = $this->datagen->get_data($sorting, $from, $this->per_page);

        // generate the data to display
        $sortable_data = [];
        foreach ($data_array as $item) {
            $row = [];
            if ($this->iscourse == '1') {
                $row[] = $item['result_id'];
            }
            if ($isWesternNameOrder) {
                $row[] = $item['firstname'];
                $row[] = $item['lastname'];
            } else {
                $row[] = $item['lastname'];
                $row[] = $item['firstname'];
            }

            $row[] = Display::bar_progress(
                $item['percentage_score'],
                false,
                $item['score']
            );

            if ($scoredisplay->is_custom()) {
                $row[] = $item['display'];
            }
            if (!$this->forprint) {
                $row[] = $this->build_edit_column($item);
            }
            $sortable_data[] = $row;
        }

        return $sortable_data;
    }

    /**
     * @param array $item
     * @return string
     */
    private function build_edit_column($item)
    {
        $locked_status = $this->evaluation->get_locked();
        if (api_is_allowed_to_edit(null, true) && $locked_status == 0) {
            //api_is_course_admin()
            $edit_column = '<a href="'.api_get_self().'?editres='.$item['result_id'].'&selecteval='.$this->evaluation->get_id().'&'.api_get_cidreq().'">'.
                Display::return_icon('edit.png', get_lang('Modify'), '', '22').'</a>';
            $edit_column .= ' <a href="'.api_get_self().'?delete_mark='.$item['result_id'].'&selecteval='.$this->evaluation->get_id().'&'.api_get_cidreq().'">'.
                Display::return_icon('delete.png', get_lang('Delete'), '', '22').'</a>';
        }

        if ($this->evaluation->get_course_code() == null) {
            $edit_column .= '&nbsp;<a href="'.api_get_self().'?resultdelete='.$item['result_id'].'&selecteval='.$this->evaluation->get_id().'" onclick="return confirmationuser();">';
            $edit_column .= Display::return_icon('delete.png', get_lang('Delete'));
            $edit_column .= '</a>';
            $edit_column .= '&nbsp;<a href="user_stats.php?userid='.$item['id'].'&selecteval='.$this->evaluation->get_id().'&'.api_get_cidreq().'">';
            $edit_column .= Display::return_icon('statistics.gif', get_lang('Statistics'));
            $edit_column .= '</a>';
        }

        // Evaluation's origin is a link
        if ($this->evaluation->get_category_id() < 0) {
            $link = LinkFactory::get_evaluation_link($this->evaluation->get_id());
            $doc_url = $link->get_view_url($item['id']);

            if ($doc_url != null) {
                $edit_column .= '&nbsp;<a href="'.$doc_url.'" target="_blank">';
                $edit_column .= Display::return_icon('link.gif', get_lang('OpenDocument')).'</a>';
            }
        }

        return $edit_column;
    }
}
