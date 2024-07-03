<?php
/* For licensing terms, see /license.txt */

/**
 * Class ResultTable
 * Table to display results for an evaluation.
 *
 * @author Stijn Konings
 * @author Bert Steppé
 */
class ResultTable extends SortableTable
{
    private $datagen;
    private $evaluation;
    private $allresults;
    private $iscourse;

    /**
     * ResultTable constructor.
     *
     * @param string      $evaluation
     * @param array       $results
     * @param string|null $iscourse
     * @param array       $addparams
     * @param bool        $forprint
     */
    public function __construct(
        $evaluation,
        $results,
        $iscourse,
        $addparams = [],
        $forprint = false
    ) {
        parent::__construct(
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
        if ('1' == $this->iscourse) {
            $this->set_header($column++, '', false);
            $this->set_form_actions([
                    'delete' => get_lang('Delete'),
            ]);
        }
        if (api_is_western_name_order()) {
            $this->set_header($column++, get_lang('FirstName'));
            $this->set_header($column++, get_lang('LastName'));
        } else {
            $this->set_header($column++, get_lang('LastName'));
            $this->set_header($column++, get_lang('FirstName'));
        }

        $model = ExerciseLib::getCourseScoreModel();
        if (empty($model)) {
            $this->set_header($column++, get_lang('Score'));
        }

        if ($scoredisplay->is_custom()) {
            $this->set_header($column++, get_lang('Display'));
        }
        if (!$this->forprint) {
            $this->set_header($column++, get_lang('Modify'), false);
        }
    }

    /**
     * Function used by SortableTable to get total number of items in the table.
     */
    public function get_total_number_of_items()
    {
        return $this->datagen->get_total_results_count();
    }

    /**
     * Function used by SortableTable to generate the data to display.
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
            case 0 + $col_adjust:
                if ($isWesternNameOrder) {
                    $sorting = ResultsDataGenerator::RDG_SORT_FIRSTNAME;
                } else {
                    $sorting = ResultsDataGenerator::RDG_SORT_LASTNAME;
                }
                break;
                // first name or last name
            case 1 + $col_adjust:
                if ($isWesternNameOrder) {
                    $sorting = ResultsDataGenerator::RDG_SORT_LASTNAME;
                } else {
                    $sorting = ResultsDataGenerator::RDG_SORT_FIRSTNAME;
                }
                break;
                // Score
            case 2 + $col_adjust:
                $sorting = ResultsDataGenerator::RDG_SORT_SCORE;
                break;
            case 3 + $col_adjust:
                $sorting = ResultsDataGenerator::RDG_SORT_MASK;
                break;
        }

        if ($this->direction === 'DESC') {
            $sorting |= ResultsDataGenerator::RDG_SORT_DESC;
        } else {
            $sorting |= ResultsDataGenerator::RDG_SORT_ASC;
        }

        $data_array = $this->datagen->get_data($sorting, $from, $this->per_page);

        $model = ExerciseLib::getCourseScoreModel();

        // generate the data to display
        $sortable_data = [];
        foreach ($data_array as $item) {
            $row = [];
            if ('1' == $this->iscourse) {
                $row[] = $item['result_id'];
            }
            if ($isWesternNameOrder) {
                $row[] = $item['firstname'];
                $row[] = $item['lastname'];
            } else {
                $row[] = $item['lastname'];
                $row[] = $item['firstname'];
            }

            if (empty($model)) {
                $row[] = Display::bar_progress(
                    $item['percentage_score'],
                    false,
                    $item['score']
                );
            }

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
     * @param Result $result
     * @param string $url
     *
     * @return string
     */
    public static function getResultAttemptTable($result, $url = '')
    {
        if (empty($result)) {
            return '';
        }

        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_RESULT_ATTEMPT);

        $sql = "SELECT * FROM $table WHERE result_id = ".$result->get_id().' ORDER BY created_at DESC';
        $resultQuery = Database::query($sql);
        $list = Database::store_result($resultQuery);

        $htmlTable = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
        $htmlTable->setHeaderContents(0, 0, get_lang('Score'));
        $htmlTable->setHeaderContents(0, 1, get_lang('Comment'));
        $htmlTable->setHeaderContents(0, 2, get_lang('CreatedAt'));

        if (!empty($url)) {
            $htmlTable->setHeaderContents(0, 3, get_lang('Actions'));
        }

        $row = 1;
        foreach ($list as $data) {
            $htmlTable->setCellContents($row, 0, $data['score']);
            $htmlTable->setCellContents($row, 1, $data['comment']);
            $htmlTable->setCellContents($row, 2, Display::dateToStringAgoAndLongDate($data['created_at']));
            if (!empty($url)) {
                $htmlTable->setCellContents(
                    $row,
                    3,
                    Display::url(
                        Display::return_icon('delete.png', get_lang('Delete')),
                        $url.'&action=delete_attempt&result_attempt_id='.$data['id']
                    )
                );
            }
            $row++;
        }

        return $htmlTable->toHtml();
    }

    /**
     * @param array $item
     *
     * @return string
     */
    private function build_edit_column($item)
    {
        $locked_status = $this->evaluation->get_locked();
        $allowMultipleAttempts = api_get_configuration_value('gradebook_multiple_evaluation_attempts');
        $baseUrl = api_get_self().'?selecteval='.$this->evaluation->get_id().'&'.api_get_cidreq();
        $editColumn = '';
        if (api_is_allowed_to_edit(null, true) && $locked_status == 0) {
            if ($allowMultipleAttempts) {
                if (!empty($item['percentage_score'])) {
                    $editColumn .=
                        Display::url(
                            Display::return_icon('add.png', get_lang('AddAttempt'), '', '22'),
                            $baseUrl.'&action=add_attempt&editres='.$item['result_id']
                        );
                } else {
                    $editColumn .= '<a href="'.api_get_self().'?editres='.$item['result_id'].'&selecteval='.$this->evaluation->get_id().'&'.api_get_cidreq().'">'.
                        Display::return_icon('edit.png', get_lang('Modify'), '', '22').'</a>';
                }
            } else {
                $editColumn .= '<a href="'.api_get_self().'?editres='.$item['result_id'].'&selecteval='.$this->evaluation->get_id().'&'.api_get_cidreq().'">'.
                    Display::return_icon('edit.png', get_lang('Modify'), '', '22').'</a>';
            }
            $editColumn .= ' <a href="'.api_get_self().'?delete_mark='.$item['result_id'].'&selecteval='.$this->evaluation->get_id().'&'.api_get_cidreq().'">'.
                Display::return_icon('delete.png', get_lang('Delete'), '', '22').'</a>';
        }

        if ($this->evaluation->get_course_code() == null) {
            $editColumn .= '&nbsp;<a href="'.api_get_self().'?resultdelete='.$item['result_id'].'&selecteval='.$this->evaluation->get_id().'" onclick="return confirmationuser();">';
            $editColumn .= Display::return_icon('delete.png', get_lang('Delete'));
            $editColumn .= '</a>';
            $editColumn .= '&nbsp;<a href="user_stats.php?userid='.$item['id'].'&selecteval='.$this->evaluation->get_id().'&'.api_get_cidreq().'">';
            $editColumn .= Display::return_icon('statistics.gif', get_lang('Statistics'));
            $editColumn .= '</a>';
        }

        // Evaluation's origin is a link
        if ($this->evaluation->get_category_id() < 0) {
            $link = LinkFactory::get_evaluation_link($this->evaluation->get_id());
            $doc_url = $link->get_view_url($item['id']);

            if ($doc_url != null) {
                $editColumn .= '&nbsp;<a href="'.$doc_url.'" target="_blank">';
                $editColumn .= Display::return_icon('link.gif', get_lang('OpenDocument')).'</a>';
            }
        }

        return $editColumn;
    }
}
