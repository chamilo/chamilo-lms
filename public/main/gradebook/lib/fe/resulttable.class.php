<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\ToolIcon;

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
     * @param Evaluation   $evaluation
     * @param ?array       $results
     * @param ?string      $iscourse
     * @param ?array       $addparams
     * @param ?bool        $forprint
     */
    public function __construct(
        Evaluation $evaluation,
        ?array $results = [],
        ?string $iscourse = '0',
        ?array $addparams = [],
        ?bool $forprint = false
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
        if ('1' == $this->iscourse) {
            $this->set_header($column++, '', false);
            $this->set_form_actions([
                    'delete' => get_lang('Delete'),
            ]);
        }
        if (api_is_western_name_order()) {
            $this->set_header($column++, get_lang('First name'));
            $this->set_header($column++, get_lang('Last name'));
        } else {
            $this->set_header($column++, get_lang('Last name'));
            $this->set_header($column++, get_lang('First name'));
        }

        $model = ExerciseLib::getCourseScoreModel();
        if (empty($model)) {
            $this->set_header($column++, get_lang('Score'));
        }

        if ($scoredisplay->is_custom()) {
            $this->set_header($column++, get_lang('Ranking'));
        }
        if (!$this->forprint) {
            $this->set_header($column++, get_lang('Edit'), false);
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
        $perPage = null,
        $column = null,
        $direction = null,
        $sort = null
    ) {
        $isWesternNameOrder = api_is_western_name_order();
        $scoredisplay = ScoreDisplay::instance();

        // determine sorting type
        $col_adjust = '1' == $this->iscourse ? 1 : 0;

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

        if ('DESC' === $this->direction) {
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
                $row[] = $this->renderScoreCell($item);
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

    private function renderScoreCell(array $item): string
    {
        $raw = $item['score'] ?? '';
        $text = html_entity_decode(strip_tags((string) $raw));
        $text = trim(preg_replace('/\s+/', ' ', $text));

        // Extract numeric parts from legacy strings like: "5 % (5 / 100)"
        preg_match_all('/\d+(?:[\\.,]\d+)?/', $text, $m);
        $numbers = $m[0] ?? [];

        $score = $numbers[0] ?? '';
        $max = !empty($numbers) ? end($numbers) : '';

        $score = str_replace(',', '.', (string) $score);
        $max = str_replace(',', '.', (string) $max);

        $percent = $item['percentage_score'] ?? null;
        $percentLabel = is_numeric($percent) ? api_number_format((float) $percent, 0) : '';

        // If we don't have a numeric score, just show the legacy text nicely.
        if ($score === '' && $text !== '') {
            return '<div class="max-w-xs text-body-2 text-gray-50 italic">'.Security::remove_XSS($text).'</div>';
        }

        $fraction = ($score !== '' && $max !== '') ? '('.$score.'/'.$max.')' : '';
        $left = $score !== '' ? Security::remove_XSS($score) : '—';

        // Choose semantic styling based on percentage.
        //  - success: >= 80
        //  - warning: 50-79
        //  - danger : < 50
        $toneBg = 'bg-support-1';
        $toneText = 'text-gray-90';
        $toneBarBg = 'bg-gray-20';
        $toneBarFill = 'bg-primary';
        $tonePillBg = 'bg-support-1';
        $tonePillText = 'text-support-4';

        if (is_numeric($percent)) {
            $p = (float) $percent;

            if ($p >= 80) {
                $toneBg = 'bg-support-2';
                $toneText = 'text-gray-90';
                $toneBarBg = 'bg-gray-20';
                $toneBarFill = 'bg-success';
                $tonePillBg = 'bg-support-2';
                $tonePillText = 'text-success';
            } elseif ($p >= 50) {
                $toneBg = 'bg-support-6';
                $toneText = 'text-gray-90';
                $toneBarBg = 'bg-gray-20';
                $toneBarFill = 'bg-warning';
                $tonePillBg = 'bg-support-6';
                $tonePillText = 'text-warning';
            } else {
                $toneBg = 'bg-support-6';
                $toneText = 'text-gray-90';
                $toneBarBg = 'bg-gray-20';
                $toneBarFill = 'bg-danger';
                $tonePillBg = 'bg-support-6';
                $tonePillText = 'text-danger';
            }
        }

        // Progress bar (uses theme colors)
        $bar = '';
        if (is_numeric($percent)) {
            $p = max(0, min(100, (float) $percent));
            $bar = '
            <div class="mt-2 h-2 w-full rounded-full '.$toneBarBg.' overflow-hidden">
                <div class="h-2 rounded-full '.$toneBarFill.'" style="width: '.$p.'%"></div>
            </div>
        ';
        }

        // Percent pill (uses theme colors)
        $pill = '';
        if ($percentLabel !== '') {
            $pill = '<span class="inline-flex items-center rounded-full '.$tonePillBg.' px-2 py-0.5 text-tiny '.$tonePillText.'">'
                .Security::remove_XSS($percentLabel).'%</span>';
        }

        $fractionHtml = '';
        if ($fraction !== '') {
            $fractionHtml = '<div class="mt-1 text-tiny text-gray-50">'.Security::remove_XSS($fraction).'</div>';
        }

        return '
        <div class="min-w-[10rem] max-w-xs rounded-xl border border-gray-25 px-3 py-2 '.$toneBg.'">
            <div class="flex items-center gap-2">
                <span class="font-semibold '.$toneText.'">'.$left.'</span>
                '.$pill.'
            </div>
            '.$fractionHtml.'
            '.$bar.'
        </div>
    ';
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
        $htmlTable->setHeaderContents(0, 2, get_lang('Created at'));

        if (!empty($url)) {
            $htmlTable->setHeaderContents(0, 3, get_lang('Detail'));
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
                        Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')),
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
        $allowMultipleAttempts = ('true' === api_get_setting('gradebook.gradebook_multiple_evaluation_attempts'));
        $baseUrl = api_get_self().'?selecteval='.$this->evaluation->get_id().'&'.api_get_cidreq();
        $editColumn = '';
        if (api_is_allowed_to_edit(null, true) && 0 == $locked_status) {
            if ($allowMultipleAttempts) {
                if (!empty($item['percentage_score'])) {
                    $editColumn .=
                        Display::url(
                            Display::getMdiIcon(ActionIcon::ADD, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Add attempt')),
                            $baseUrl.'&action=add_attempt&editres='.$item['result_id']
                        );
                } else {
                    $editColumn .= '<a href="'.api_get_self().'?editres='.$item['result_id'].'&selecteval='.$this->evaluation->get_id().'&'.api_get_cidreq().'">'.
                        Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')).'</a>';
                }
            } else {
                $editColumn .= '<a href="'.api_get_self().'?editres='.$item['result_id'].'&selecteval='.$this->evaluation->get_id().'&'.api_get_cidreq().'">'.
                    Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')).'</a>';
            }
            $editColumn .= ' <a href="'.api_get_self().'?delete_mark='.$item['result_id'].'&selecteval='.$this->evaluation->get_id().'&'.api_get_cidreq().'">'.
                Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete')).'</a>';
        }

        if (null == $this->evaluation->getCourseId()) {
            $editColumn .= '&nbsp;<a href="'.api_get_self().'?resultdelete='.$item['result_id'].'&selecteval='.$this->evaluation->get_id().'" onclick="return confirmationuser();">';
            $editColumn .= Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Delete'));
            $editColumn .= '</a>';
            $editColumn .= '&nbsp;<a href="user_stats.php?userid='.$item['id'].'&selecteval='.$this->evaluation->get_id().'&'.api_get_cidreq().'">';
            $editColumn .= Display::getMdiIcon(ToolIcon::TRACKING, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Statistics'));
            $editColumn .= '</a>';
        }

        // Evaluation's origin is a link
        if ($this->evaluation->get_category_id() < 0) {
            $link = LinkFactory::get_evaluation_link($this->evaluation->get_id());
            $doc_url = $link->get_view_url($item['id']);

            if (null != $doc_url) {
                $editColumn .= '&nbsp;<a href="'.$doc_url.'" target="_blank">';
                $editColumn .= Display::getMdiIcon(ToolIcon::LINK, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Open document')).'</a>';
            }
        }

        return $editColumn;
    }
}
