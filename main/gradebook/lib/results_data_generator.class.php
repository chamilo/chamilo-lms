<?php
/* For licensing terms, see /license.txt */

/**
 * ResultsDataGenerator Class
 * Class to select, sort and transform object data into array data,
 * used for the teacher's evaluation results view.
 *
 * @author Bert Steppé
 */
class ResultsDataGenerator
{
    // Sorting types constants
    public const RDG_SORT_LASTNAME = 1;
    public const RDG_SORT_FIRSTNAME = 2;
    public const RDG_SORT_SCORE = 4;
    public const RDG_SORT_MASK = 8;

    public const RDG_SORT_ASC = 16;
    public const RDG_SORT_DESC = 32;
    private $evaluation;
    private $results;
    private $is_course_ind;
    private $include_edit;

    /**
     * Constructor.
     */
    public function __construct(
        $evaluation,
        $results = [],
        $include_edit = false
    ) {
        $this->evaluation = $evaluation;
        $this->results = isset($results) ? $results : [];
    }

    /**
     * Get total number of results (rows).
     */
    public function get_total_results_count()
    {
        return count($this->results);
    }

    /**
     * Get actual array data.
     *
     * @param int $count
     *
     * @return array 2-dimensional array - each array contains the elements:
     *               0 ['id']        : user id
     *               1 ['result_id'] : result id
     *               2 ['lastname']  : user lastname
     *               3 ['firstname'] : user firstname
     *               4 ['score']     : student's score
     *               5 ['display']   : custom score display (only if custom scoring enabled)
     */
    public function get_data(
        $sorting = 0,
        $start = 0,
        $count = null,
        $ignore_score_color = false,
        $pdf = false
    ) {
        // do some checks on count, redefine if invalid value
        $number_decimals = api_get_setting('gradebook_number_decimals');
        if (!isset($count)) {
            $count = count($this->results) - $start;
        }
        if ($count < 0) {
            $count = 0;
        }

        $model = ExerciseLib::getCourseScoreModel();

        $scoreDisplay = ScoreDisplay::instance();
        // generate actual data array
        $table = [];
        foreach ($this->results as $result) {
            $user = [];
            $info = api_get_user_info($result->get_user_id());
            $user['id'] = $result->get_user_id();
            if ($pdf) {
                $user['username'] = $info['username'];
            }
            $user['result_id'] = $result->get_id();
            $user['lastname'] = $info['lastname'];
            $user['firstname'] = $info['firstname'];
            if ($pdf) {
                $user['score'] = $result->get_score();
            } else {
                $user['score'] = $this->get_score_display(
                    $result->get_score(),
                    true,
                    $ignore_score_color
                );
            }

            $user['percentage_score'] = (int) $scoreDisplay->display_score(
                [$result->get_score(), $this->evaluation->get_max()],
                SCORE_PERCENT,
                SCORE_BOTH,
                true
            );

            if ($pdf && null == $number_decimals) {
                $user['scoreletter'] = $result->get_score();
            }
            if ($scoreDisplay->is_custom()) {
                $user['display'] = $this->get_score_display(
                    $result->get_score(),
                    false,
                    $ignore_score_color
                );
                if (!empty($model)) {
                    $user['display'] .= '&nbsp;'.
                        ExerciseLib::show_score(
                            $result->get_score(),
                            $this->evaluation->get_max()
                        )
                    ;
                }
            }
            $table[] = $user;
        }

        // sort array
        if ($sorting & self::RDG_SORT_LASTNAME) {
            usort($table, ['ResultsDataGenerator', 'sort_by_last_name']);
        } elseif ($sorting & self::RDG_SORT_FIRSTNAME) {
            usort($table, ['ResultsDataGenerator', 'sort_by_first_name']);
        } elseif ($sorting & self::RDG_SORT_SCORE) {
            usort($table, ['ResultsDataGenerator', 'sort_by_score']);
        } elseif ($sorting & self::RDG_SORT_MASK) {
            usort($table, ['ResultsDataGenerator', 'sort_by_mask']);
        }
        if ($sorting & self::RDG_SORT_DESC) {
            $table = array_reverse($table);
        }
        $return = array_slice($table, $start, $count);

        return $return;
    }

    // Sort functions - used internally

    /**
     * @param array $item1
     * @param array $item2
     *
     * @return int
     */
    public function sort_by_last_name($item1, $item2)
    {
        return api_strcmp($item1['lastname'], $item2['lastname']);
    }

    /**
     * @param array $item1
     * @param array $item2
     *
     * @return int
     */
    public function sort_by_first_name($item1, $item2)
    {
        return api_strcmp($item1['firstname'], $item2['firstname']);
    }

    /**
     * @param array $item1
     * @param array $item2
     *
     * @return int
     */
    public function sort_by_score($item1, $item2)
    {
        if ($item1['percentage_score'] == $item2['percentage_score']) {
            return 0;
        } else {
            return $item1['percentage_score'] < $item2['percentage_score'] ? -1 : 1;
        }
    }

    /**
     * @param array $item1
     * @param array $item2
     *
     * @return int
     */
    public function sort_by_mask($item1, $item2)
    {
        $score1 = (isset($item1['score']) ? [$item1['score'], $this->evaluation->get_max()] : null);
        $score2 = (isset($item2['score']) ? [$item2['score'], $this->evaluation->get_max()] : null);

        return ScoreDisplay::compare_scores_by_custom_display($score1, $score2);
    }

    /**
     * Re-formats the score to show percentage ("2/4 (50 %)") or letters ("A").
     *
     * @param float Current absolute score (max score is taken from $this->evaluation->get_max()
     * @param bool  Whether we want the real score (2/4 (50 %)) or the transformation (A, B, C, etc)
     * @param bool  Whether we want to ignore the score color
     * @param bool $realscore
     *
     * @return string The score as we want to show it
     */
    private function get_score_display(
        $score,
        $realscore,
        $ignore_score_color = false
    ) {
        if (null != $score) {
            $scoreDisplay = ScoreDisplay::instance();
            $type = SCORE_CUSTOM;
            if (true === $realscore) {
                $type = SCORE_DIV_PERCENT;
            }

            return $scoreDisplay->display_score(
                [$score, $this->evaluation->get_max()],
                $type,
                SCORE_BOTH,
                $ignore_score_color
            );
        }

        return '';
    }
}
