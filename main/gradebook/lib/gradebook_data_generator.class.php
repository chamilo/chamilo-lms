<?php
/* For licensing terms, see /license.txt */

/**
 * Class GradebookDataGenerator
 * Class to select, sort and transform object data into array data,
 * used for the general gradebook view
 * @author Bert Steppé
 * @package chamilo.gradebook
 */
class GradebookDataGenerator
{
    // Sorting types constants
    const GDG_SORT_TYPE = 1;
    const GDG_SORT_NAME = 2;
    const GDG_SORT_DESCRIPTION = 4;
    const GDG_SORT_WEIGHT = 8;
    const GDG_SORT_DATE = 16;
    const GDG_SORT_ASC = 32;
    const GDG_SORT_DESC = 64;
    const GDG_SORT_ID = 128;
    public $userId;

    private $items;
    private $evals_links;

    /**
     * @param array $cats
     * @param array $evals
     * @param array $links
     */
    public function __construct($cats = array(), $evals = array(), $links = array())
    {
        $allcats = isset($cats) ? $cats : array();
        $allevals = isset($evals) ? $evals : array();
        $alllinks = isset($links) ? $links : array();

        // if we are in the root category and if there are sub categories
        // display only links depending of the root category and not link that belongs
        // to a sub category https://support.chamilo.org/issues/6602
        $tabLinkToDisplay = $alllinks;
        if (count($allcats) > 0) {
            // get sub categories id
            $tabCategories = array();
            for ($i = 0; $i < count($allcats); $i++) {
                $tabCategories[] = $allcats[$i]->get_id();
            }
            // dont display links that belongs to a sub category
            $tabLinkToDisplay = array();
            for ($i = 0; $i < count($alllinks); $i++) {
                if (!in_array($alllinks[$i]->get_category_id(), $tabCategories)) {
                    $tabLinkToDisplay[] = $alllinks[$i];
                }
            }
        }

        // merge categories, evaluations and links
        $this->items = array_merge($allcats, $allevals, $tabLinkToDisplay);
        $this->evals_links = array_merge($allevals, $tabLinkToDisplay);
        $this->userId = api_get_user_id();
    }

    /**
     * Get total number of items (rows)
     * @return int
     */
    public function get_total_items_count()
    {
        return count($this->items);
    }

    /**
     * Get actual array data
     * @param integer $count
     * @return array 2-dimensional array - each array contains the elements:
     * 0: cat/eval/link object
     * 1: item name
     * 2: description
     * 3: weight
     * 4: date
     * 5: student's score (if student logged in)
     */
    public function get_data(
        $sorting = 0,
        $start = 0,
        $count = null,
        $ignore_score_color = false,
        $studentList = array()
    ) {
        //$status = CourseManager::get_user_in_course_status(api_get_user_id(), api_get_course_id());
        // do some checks on count, redefine if invalid value
        if (!isset($count)) {
            $count = count($this->items) - $start;
        }
        if ($count < 0) {
            $count = 0;
        }

        $allitems = $this->items;
        usort($allitems, array('GradebookDataGenerator', 'sort_by_name'));

        $userId = $this->userId;

        // Get selected items
        $visibleItems = array_slice($allitems, $start, $count);
        $userCount = count($studentList);

        // Generate the data to display
        $data = array();
        $totalWeight = 0;

        /** @var GradebookItem $item */
        foreach ($visibleItems as $item) {
            $row = array();
            $row[] = $item;
            $row[] = $item->get_name();
            // display the 2 first line of description, and all description on mouseover (https://support.chamilo.org/issues/6588)
            $row[] = '<span title="'.api_remove_tags_with_space($item->get_description()).'">'.
                api_get_short_text_from_html($item->get_description(), 160).'</span>';
            $totalWeight += $item->get_weight();
            $row[] = $item->get_weight();
            $item->setStudentList($studentList);

            //if (count($this->evals_links) > 0) {
            if (get_class($item) == 'Evaluation') {
                // Items inside a category.
                if (1) {
                    $resultColumn = $this->build_result_column(
                        $userId,
                        $item,
                        $ignore_score_color
                    );

                    $row[] = $resultColumn['display'];
                    $row['result_score'] = $resultColumn['score'];
                    $row['result_score_weight'] = $resultColumn['score_weight'];

                    // Best
                    $best = $this->buildBestResultColumn($item);
                    $row['best'] = $best['display'];
                    $row['best_score'] = $best['score'];

                    // Average
                    $average = $this->buildAverageResultColumn($item);
                    $row['average'] = $average['display'];
                    $row['average_score'] = $average['score'];

                    // Ranking
                    $ranking = $this->buildRankingColumn($item, $userId, $userCount);

                    $row['ranking'] = $ranking['display'];
                    $row['ranking_score'] = $ranking['score'];

                    $row[] = $item;
                }
            } else {
                // Category.
                $result = $this->build_result_column($userId, $item, $ignore_score_color, true);
                $row[] = $result['display'];
                $row['result_score'] = $result['score'];
                $row['result_score_weight'] = $result['score'];

                // Best
                $best = $this->buildBestResultColumn($item);
                $row['best'] = $best['display'];
                $row['best_score'] = $best['score'];

                // Average
                $average = $this->buildAverageResultColumn($item);
                $row['average'] = $average['display'];
                $row['average_score'] = $average['score'];

                // Ranking
                $rankingStudentList = array();
                $invalidateResults = true;
                foreach ($studentList as $user) {
                    $score = $this->build_result_column(
                        $user['user_id'],
                        $item,
                        $ignore_score_color,
                        true
                    );

                    if (!empty($score['score'][0])) {
                        $invalidateResults = false;
                    }
                    $rankingStudentList[$user['user_id']] = $score['score'][0];
                }

                $scoreDisplay = ScoreDisplay::instance();
                $score = AbstractLink::getCurrentUserRanking($userId, $rankingStudentList);
                $row['ranking'] = $scoreDisplay->display_score($score, SCORE_DIV, SCORE_BOTH, true);
                if ($invalidateResults) {
                    $row['ranking'] = null;
                }
            }
            $data[] = $row;
        }

        return $data;
    }

    /**
     * Get best result of an item
     * @param GradebookItem $item
     * @return string
     */
    private function buildBestResultColumn(GradebookItem $item)
    {
        $score = $item->calc_score(
            null,
            'best',
            api_get_course_id(),
            api_get_session_id()
        );

        $scoreDisplay = ScoreDisplay :: instance();
        $display = $scoreDisplay->display_score($score, SCORE_DIV_PERCENT_WITH_CUSTOM, SCORE_BOTH, true);
        $type = $item->get_item_type();
        if ($type == 'L' && get_class($item) == 'ExerciseLink') {
            $display  = ExerciseLib::show_score($score[0], $score[1], false);
        }

        return array(
            'display' => $display,
            'score' => $score
        );
    }

    /**
     * @param GradebookItem $item
     *
     * @return string
     */
    private function buildAverageResultColumn(GradebookItem $item)
    {
        $score = $item->calc_score(null, 'average');
        $scoreDisplay = ScoreDisplay :: instance();
        $display = $scoreDisplay->display_score($score, SCORE_DIV_PERCENT_WITH_CUSTOM, SCORE_BOTH, true);
        $type = $item->get_item_type();

        if ($type == 'L' && get_class($item) == 'ExerciseLink') {
            $display  = ExerciseLib::show_score($score[0], $score[1], false);
        }

        return array(
            'display' => $display,
            'score' => $score
        );
    }

    /**
     * @param GradebookItem $item
     * @param int $userId
     * @param int $userCount
     *
     * @return string
     */
    private function buildRankingColumn(GradebookItem $item, $userId = null, $userCount = 0)
    {
        $score = $item->calc_score($userId, 'ranking');
        $score[1] = $userCount;

        $scoreDisplay = null;
        if (isset($score[0])) {
            $scoreDisplay = ScoreDisplay::instance();
            $scoreDisplay = $scoreDisplay->display_score($score, SCORE_DIV, SCORE_BOTH);
        }

        return array(
            'display' => $scoreDisplay,
            'score' => $score
        );
    }

    /**
     * @param int $userId
     * @param GradebookItem $item
     * @param boolean $ignore_score_color
     * @return null|string
     */
    private function build_result_column(
        $userId,
        $item,
        $ignore_score_color,
        $forceSimpleResult = false
    ) {
        $scoredisplay = ScoreDisplay::instance();

        $score = $item->calc_score($userId);

        if (!empty($score)) {
            switch ($item->get_item_type()) {
                // category
                case 'C':
                    if ($score != null) {
                        if ($forceSimpleResult) {
                            return
                                array(
                                    'display' => $scoredisplay->display_score(
                                        $score,
                                        SCORE_DIV
                                    ),
                                    'score' => $score,
                                    'score_weight' => $score
                                );
                        }

                        return array(
                            'display' => $scoredisplay->display_score($score, SCORE_DIV),
                            'score' => $score,
                            'score_weight' => $score
                        );
                    } else {
                        return array(
                            'display' => null,
                            'score' => $score,
                            'score_weight' => $score
                        );
                    }
                    break;
                // evaluation and link
                case 'E':
                case 'L':
                    //if ($parentId == 0) {
                        $scoreWeight = [
                            ($score[1] > 0) ? $score[0] / $score[1] * $item->get_weight() : 0,
                            $item->get_weight()
                        ];
                    //}

                $display = $scoredisplay->display_score($score, SCORE_DIV_PERCENT_WITH_CUSTOM);

                $type = $item->get_item_type();
                if ($type == 'L' && get_class($item) == 'ExerciseLink') {
                    $display  = ExerciseLib::show_score($score[0], $score[1], false);
                }

                return array(
                    'display' => $display,
                    'score' => $score,
                    'score_weight' => $scoreWeight,
                );
            }
        }

        return array(
            'display' => null,
            'score' => null,
            'score_weight' => null
        );
    }

    /**
     * @param GradebookItem $item
     * @return string
     */
    private function build_date_column($item)
    {
        $date = $item->get_date();
        if (!isset($date) || empty($date)) {
            return '';
        } else {
            if (is_int($date)) {
                return api_convert_and_format_date($date);
            } else {
                return api_format_date($date);
            }
        }
    }

    /**
     * Returns the link to the certificate generation, if the score is enough, otherwise
     * returns an empty string. This only works with categories.
     * @param    object Item
     */
    public function get_certificate_link($item)
    {
        if (is_a($item, 'Category')) {
            if ($item->is_certificate_available(api_get_user_id())) {
                $link = '<a href="'.Security::remove_XSS($_SESSION['gradebook_dest']).'?export_certificate=1&cat='.$item->get_id().'&user='.api_get_user_id().'">'.
                    get_lang('Certificate').'</a>';
                return $link;
            }
        }
        return '';
    }

    /**
     * @param GradebookItem $item1
     * @param GradebookItem $item2
     * @return int
     */
    public function sort_by_name($item1, $item2)
    {
        return api_strnatcmp($item1->get_name(), $item2->get_name());
    }

    /**
     * @param GradebookItem $item1
     * @param GradebookItem $item2
     * @return int
     */
    public function sort_by_id($item1, $item2)
    {
        return api_strnatcmp($item1->get_id(), $item2->get_id());
    }

    /**
     * @param GradebookItem $item1
     * @param GradebookItem $item2
     * @return int
     */
    public function sort_by_type($item1, $item2)
    {
        if ($item1->get_item_type() == $item2->get_item_type()) {
            return $this->sort_by_name($item1,$item2);
        } else {
            return ($item1->get_item_type() < $item2->get_item_type() ? -1 : 1);
        }
    }

    /**
     * @param GradebookItem $item1
     * @param GradebookItem $item2
     * @return int
     */
    public function sort_by_description($item1, $item2)
    {
        $result = api_strcmp($item1->get_description(), $item2->get_description());
        if ($result == 0) {
            return $this->sort_by_name($item1,$item2);
        }
        return $result;
    }

    /**
     * @param GradebookItem $item1
     * @param GradebookItem $item2
     * @return int
     */
    public function sort_by_weight($item1, $item2)
    {
        if ($item1->get_weight() == $item2->get_weight()) {
            return $this->sort_by_name($item1,$item2);
        } else {
            return ($item1->get_weight() < $item2->get_weight() ? -1 : 1);
        }
    }

    /**
     * @param GradebookItem $item1
     * @param GradebookItem $item2
     * @return int
     */
    public function sort_by_date($item1, $item2)
    {
        if (is_int($item1->get_date())) {
            $timestamp1 = $item1->get_date();
        } else {
            $date = $item1->get_date();
            if (!empty($date)) {
                $timestamp1 = api_strtotime($date, 'UTC');
            } else {
                $timestamp1 = null;
            }
        }

        if (is_int($item2->get_date())) {
            $timestamp2 = $item2->get_date();
        } else {
            $timestamp2 = api_strtotime($item2->get_date(), 'UTC');
        }

        if ($timestamp1 == $timestamp2) {
            return $this->sort_by_name($item1, $item2);
        } else {
            return ($timestamp1 < $timestamp2 ? -1 : 1);
        }
    }
}
