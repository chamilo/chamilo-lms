<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Class GradebookDataGenerator
 * Class to select, sort and transform object data into array data,
 * used for the general gradebook view.
 *
 * @author Bert Steppé
 */
class GradebookDataGenerator
{
    // Sorting types constants
    public const GDG_SORT_TYPE = 1;
    public const GDG_SORT_NAME = 2;
    public const GDG_SORT_DESCRIPTION = 4;
    public const GDG_SORT_WEIGHT = 8;
    public const GDG_SORT_DATE = 16;
    public const GDG_SORT_ASC = 32;
    public const GDG_SORT_DESC = 64;
    public const GDG_SORT_ID = 128;
    public $userId;
    public $hidePercentage = false;

    public $items;
    public $preLoadDataKey;
    public $exportToPdf;
    private $evals_links;

    /**
     * @param array $cats
     * @param array $evals
     * @param array $links
     */
    public function __construct($cats = [], $evals = [], $links = [])
    {
        $allcats = isset($cats) ? $cats : [];
        $allevals = isset($evals) ? $evals : [];
        $alllinks = isset($links) ? $links : [];
        $this->exportToPdf = false;

        // if we are in the root category and if there are sub categories
        // display only links depending of the root category and not link that belongs
        // to a sub category https://support.chamilo.org/issues/6602
        $tabLinkToDisplay = $alllinks;
        if (count($allcats) > 0) {
            // get sub categories id
            $tabCategories = [];
            for ($i = 0; $i < count($allcats); $i++) {
                $tabCategories[] = $allcats[$i]->get_id();
            }
            // dont display links that belongs to a sub category
            $tabLinkToDisplay = [];
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
        $this->hidePercentage = api_get_configuration_value('hide_gradebook_percentage_user_result');
    }

    /**
     * Get total number of items (rows).
     *
     * @return int
     */
    public function get_total_items_count()
    {
        return count($this->items);
    }

    /**
     * Get actual array data.
     *
     * @param int $count
     *
     * @return array 2-dimensional array - each array contains the elements:
     *               0: cat/eval/link object
     *               1: item name
     *               2: description
     *               3: weight
     *               4: date
     *               5: student's score (if student logged in)
     */
    public function get_data(
        $sorting = 0,
        $start = 0,
        $count = null,
        $ignore_score_color = false,
        $studentList = [],
        $loadStats = true
    ) {
        // do some checks on count, redefine if invalid value
        if (!isset($count)) {
            $count = count($this->items) - $start;
        }
        if ($count < 0) {
            $count = 0;
        }

        $allitems = $this->items;
        usort($allitems, ['GradebookDataGenerator', 'sort_by_name']);
        $userId = $this->userId;
        $visibleItems = array_slice($allitems, $start, $count);
        $userCount = !empty($studentList) ? count($studentList) : 0;
        // Generate the data to display
        $data = [];
        $allowStats = api_get_configuration_value('allow_gradebook_stats');
        $scoreDisplay = ScoreDisplay::instance();
        $defaultData = Session::read($this->preLoadDataKey);
        $model = ExerciseLib::getCourseScoreModel();
        $useExerciseScoreInTotal = api_get_configuration_value('gradebook_use_exercise_score_settings_in_total');

        /** @var GradebookItem $item */
        foreach ($visibleItems as $item) {
            $row = [];
            $row[] = $item;
            $row[] = Security::remove_XSS($item->get_name());
            // display the 2 first line of description and all description
            // on mouseover (https://support.chamilo.org/issues/6588)
            $row[] = '<span title="'.api_remove_tags_with_space($item->get_description()).'">'.
                api_get_short_text_from_html($item->get_description(), 160).'</span>';
            $row[] = $item->get_weight();
            $item->setStudentList($studentList);
            $itemType = get_class($item);

            switch ($itemType) {
                case 'Evaluation':
                    // Items inside a category.
                    $resultColumn = $this->build_result_column(
                        $userId,
                        $item,
                        $ignore_score_color,
                        false,
                        $useExerciseScoreInTotal
                    );
                    $row[] = $resultColumn['display'];
                    $row['result_score'] = $resultColumn['score'];
                    $row['result_score_weight'] = $resultColumn['score_weight'];

                    // Best
                    if (isset($defaultData[$item->get_id()]) && isset($defaultData[$item->get_id()]['best'])) {
                        $best = $defaultData[$item->get_id()]['best'];
                    } else {
                        $best = $this->buildBestResultColumn($item, $useExerciseScoreInTotal);
                    }

                    if (empty($model)) {
                        $row['best'] = $best['display'];
                        $row['best_score'] = $best['score'];
                        // Average
                        if (isset($defaultData[$item->get_id()]) && isset($defaultData[$item->get_id()]['average'])) {
                            $average = $defaultData[$item->get_id()]['average'];
                        } else {
                            $average = $this->buildBestResultColumn($item, $useExerciseScoreInTotal);
                        }

                        $row['average'] = $average['display'];
                        $row['average_score'] = $average['score'];

                        // Ranking
                        $ranking = $this->buildRankingColumn($item, $userId, $userCount);
                        $row['ranking'] = $ranking['display'];
                        $row['ranking_score'] = $ranking['score'];
                    }
                    $row[] = $item;
                    break;
                case 'ExerciseLink':
                    /** @var ExerciseLink $item */
                    // Category.
                    $result = $this->build_result_column(
                        $userId,
                        $item,
                        $ignore_score_color,
                        true,
                        $useExerciseScoreInTotal
                    );
                    $row[] = $result['display'];
                    $row['result_score'] = $result['score'];
                    $row['result_score_weight'] = $result['score'];

                    if (empty($model)) {
                        // Best
                        if (isset($defaultData[$item->get_id()]) && isset($defaultData[$item->get_id()]['best'])) {
                            $best = $defaultData[$item->get_id()]['best'];
                        } else {
                            $best = $this->buildBestResultColumn($item);
                        }

                        $row['best'] = $best['display'];
                        $row['best_score'] = $best['score'];

                        $rankingStudentList = [];
                        $invalidateResults = false;
                        // Average
                        if (isset($defaultData[$item->get_id()]) && isset($defaultData[$item->get_id()]['average'])) {
                            $average = $defaultData[$item->get_id()]['average'];
                        } else {
                            $average = $this->buildAverageResultColumn($item);
                        }

                        $row['average'] = $average['display'];
                        $row['average_score'] = $average['score'];

                        // Ranking.
                        if ($allowStats) {
                            // Ranking
                            if (isset($defaultData[$item->get_id()]) &&
                                isset($defaultData[$item->get_id()]['ranking'])
                            ) {
                                $rankingStudentList = $defaultData[$item->get_id()]['ranking'];
                                $invalidateResults = $defaultData[$item->get_id()]['ranking_invalidate'];
                                $score = AbstractLink::getCurrentUserRanking($userId, $rankingStudentList);
                            } else {
                                if (!empty($studentList)) {
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
                                    $defaultData[$item->get_id()]['ranking'] = $rankingStudentList;
                                    $defaultData[$item->get_id()]['ranking_invalidate'] = $invalidateResults;
                                    Session::write($this->preLoadDataKey, $defaultData);
                                }
                                $score = AbstractLink::getCurrentUserRanking($userId, $rankingStudentList);
                            }
                        } else {
                            if (!empty($studentList)) {
                                $session_id = api_get_session_id();
                                $evals = [];
                                $links = [];
                                if ('C' === $item->get_item_type()) {
                                    $evals = $item->get_evaluations(null);
                                    $links = $item->get_links(null);

                                    foreach ($studentList as $user) {
                                        $ressum = 0;
                                        $weightsum = 0;
                                        $bestResult = 0;
                                        if (!empty($evals)) {
                                            foreach ($evals as $eval) {
                                                $evalres = $eval->calc_score($user['user_id'], null);
                                                $eval->setStudentList($studentList);

                                                if (isset($evalres) && 0 != $eval->get_weight()) {
                                                    $evalweight = $eval->get_weight();
                                                    $weightsum += $evalweight;
                                                    if (!empty($evalres[1])) {
                                                        $ressum += $evalres[0] / $evalres[1] * $evalweight;
                                                    }

                                                    if ($ressum > $bestResult) {
                                                        $bestResult = $ressum;
                                                    }
                                                } else {
                                                    if (0 != $eval->get_weight()) {
                                                        $evalweight = $eval->get_weight();
                                                        $weightsum += $evalweight;
                                                    }
                                                }
                                            }
                                        }
                                        if (!empty($links)) {
                                            foreach ($links as $link) {
                                                $link->setStudentList($studentList);
                                                if ($session_id) {
                                                    $link->set_session_id($session_id);
                                                }
                                                $linkres = $link->calc_score($user['user_id'], null);
                                                if (!empty($linkres) && 0 != $link->get_weight()) {
                                                    $linkweight = $link->get_weight();
                                                    $link_res_denom = 0 == $linkres[1] ? 1 : $linkres[1];

                                                    $weightsum += $linkweight;
                                                    $ressum += $linkres[0] / $link_res_denom * $linkweight;
                                                    if ($ressum > $bestResult) {
                                                        $bestResult = $ressum;
                                                    }
                                                } else {
                                                    // Adding if result does not exists
                                                    if (0 != $link->get_weight()) {
                                                        $linkweight = $link->get_weight();
                                                        $weightsum += $linkweight;
                                                    }
                                                }
                                            }
                                        }

                                        if (!empty($ressum)) {
                                            $invalidateResults = false;
                                        }
                                        $rankingStudentList[$user['user_id']] = $ressum;
                                    }
                                }

                                if (empty($rankingStudentList)) {
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

                                        $rankingStudentList[$user['user_id']] = 0;
                                        if ($score['score']) {
                                            $rankingStudentList[$user['user_id']] = $score['score'][0];
                                        }
                                    }
                                }
                            }

                            $score = AbstractLink::getCurrentUserRanking($userId, $rankingStudentList);
                        }

                        $row['ranking'] = $scoreDisplay->display_score(
                            $score,
                            SCORE_DIV,
                            SCORE_BOTH,
                            true,
                            true
                        );
                        if ($invalidateResults) {
                            $row['ranking'] = null;
                        }
                    }
                    break;
                default:
                    // Category.
                    $result = $this->build_result_column(
                        $userId,
                        $item,
                        $ignore_score_color,
                        true,
                        $useExerciseScoreInTotal
                    );
                    $row[] = $result['display'];
                    $row['result_score'] = $result['score'];
                    $row['result_score_weight'] = $result['score'];

                    if (empty($model)) {
                        // Best
                        if (isset($defaultData[$item->get_id()]) && isset($defaultData[$item->get_id()]['best'])) {
                            $best = $defaultData[$item->get_id()]['best'];
                            if ($useExerciseScoreInTotal) {
                                $bestScore = $best['score'];
                                $best['display'] = ExerciseLib::show_score($bestScore[0], $bestScore[1], true);
                            } else {
                                $best = $defaultData[$item->get_id()]['best'];
                            }
                        } else {
                            $best = $this->buildBestResultColumn($item, $useExerciseScoreInTotal);
                        }

                        $row['best'] = $best['display'];
                        $row['best_score'] = $best['score'];
                        $rankingStudentList = [];
                        $invalidateResults = true;
                        // Average
                        if (isset($defaultData[$item->get_id()]) && isset($defaultData[$item->get_id()]['average'])) {
                            $average = $defaultData[$item->get_id()]['average'];
                            if ($useExerciseScoreInTotal) {
                                $averageScore = $average['score'];
                                $average['display'] = ExerciseLib::show_score($averageScore[0], $averageScore[1], true);
                            }
                        } else {
                            $average = $this->buildAverageResultColumn($item, $useExerciseScoreInTotal);
                        }
                        $row['average'] = $average['display'];
                        $row['average_score'] = $average['score'];

                        // Ranking
                        if (isset($defaultData[$item->get_id()]) && isset($defaultData[$item->get_id()]['ranking'])) {
                            $rankingStudentList = $defaultData[$item->get_id()]['ranking'];
                            $invalidateResults = $defaultData[$item->get_id()]['ranking_invalidate'];
                            $invalidateResults = false;
                            $score = AbstractLink::getCurrentUserRanking($userId, $rankingStudentList);
                        } else {
                            if (!empty($studentList)) {
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
                            }
                            $score = AbstractLink::getCurrentUserRanking($userId, $rankingStudentList);
                        }

                        $row['ranking'] = $scoreDisplay->display_score(
                            $score,
                            SCORE_DIV,
                            SCORE_BOTH,
                            true,
                            true,
                            true
                        );

                        if ($invalidateResults) {
                            $row['ranking'] = null;
                        }
                    }
                    break;
            }
            $data[] = $row;
        }

        return $data;
    }

    /**
     * Returns the link to the certificate generation, if the score is enough, otherwise
     * returns an empty string. This only works with categories.
     *
     * @param    object Item
     *
     * @return string
     */
    public function get_certificate_link($item)
    {
        if (is_a($item, 'Category')) {
            if ($item->is_certificate_available(api_get_user_id())) {
                $link = '<a
                href="'.Category::getUrl().'export_certificate=1&cat='.$item->get_id().'&user='.api_get_user_id().'">'.
                    get_lang('Certificate').'</a>';

                return $link;
            }
        }

        return '';
    }

    /**
     * @param GradebookItem $item1
     * @param GradebookItem $item2
     *
     * @return int
     */
    public static function sort_by_name($item1, $item2)
    {
        return api_strnatcmp($item1->get_name(), $item2->get_name());
    }

    /**
     * @param GradebookItem $item1
     * @param GradebookItem $item2
     *
     * @return int
     */
    public function sort_by_id($item1, $item2)
    {
        return api_strnatcmp($item1->get_id(), $item2->get_id());
    }

    /**
     * @param GradebookItem $item1
     * @param GradebookItem $item2
     *
     * @return int
     */
    public function sort_by_type($item1, $item2)
    {
        if ($item1->get_item_type() == $item2->get_item_type()) {
            return $this->sort_by_name($item1, $item2);
        }

        return $item1->get_item_type() < $item2->get_item_type() ? -1 : 1;
    }

    /**
     * @param GradebookItem $item1
     * @param GradebookItem $item2
     *
     * @return int
     */
    public function sort_by_description($item1, $item2)
    {
        $result = api_strcmp($item1->get_description(), $item2->get_description());
        if (0 == $result) {
            return $this->sort_by_name($item1, $item2);
        }

        return $result;
    }

    /**
     * @param GradebookItem $item1
     * @param GradebookItem $item2
     *
     * @return int
     */
    public function sort_by_weight($item1, $item2)
    {
        if ($item1->get_weight() == $item2->get_weight()) {
            return $this->sort_by_name($item1, $item2);
        }

        return $item1->get_weight() < $item2->get_weight() ? -1 : 1;
    }

    /**
     * @param GradebookItem $item1
     * @param GradebookItem $item2
     *
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
        }

        return $timestamp1 < $timestamp2 ? -1 : 1;
    }

    /**
     * Get best result of an item.
     *
     * @return array
     */
    public function buildBestResultColumn(GradebookItem $item, $userExerciseSettings = false)
    {
        $score = $item->calc_score(
            null,
            'best',
            api_get_course_id(),
            api_get_session_id()
        );

        if (empty($score)) {
            return [
                'display' => '',
                'score' => '',
            ];
        }

        $scoreMode = SCORE_DIV_PERCENT_WITH_CUSTOM;
        $showPercentage = true;
        if ($this->hidePercentage) {
            $scoreMode = SCORE_DIV;
            $showPercentage = false;
        }
        $scoreDisplay = ScoreDisplay::instance();
        $display = $scoreDisplay->display_score(
            $score,
            $scoreMode,
            SCORE_BOTH,
            true,
            false,
            true
        );
        $type = $item->get_item_type();
        if ('L' === $type && 'ExerciseLink' === get_class($item)) {
            $display = ExerciseLib::show_score(
                $score[0],
                $score[1],
                $showPercentage
            );
        }

        if ($userExerciseSettings) {
            $display = ExerciseLib::show_score(
                $score[0],
                $score[1],
                $showPercentage
            );
        }

        return [
            'display' => $display,
            'score' => $score,
        ];
    }

    /**
     * @return array
     */
    public function buildAverageResultColumn(GradebookItem $item, $userExerciseSettings = false)
    {
        $score = $item->calc_score(null, 'average');

        if (empty($score)) {
            return [
                'display' => '',
                'score' => '',
            ];
        }

        $scoreDisplay = ScoreDisplay::instance();

        $scoreMode = SCORE_DIV_PERCENT_WITH_CUSTOM;
        $showPercentage = true;
        if ($this->hidePercentage) {
            $scoreMode = SCORE_DIV;
            $showPercentage = false;
        }

        $display = $scoreDisplay->display_score(
            $score,
            $scoreMode,
            SCORE_BOTH,
            true,
            false,
            true
        );
        $type = $item->get_item_type();

        if ('L' === $type && 'ExerciseLink' === get_class($item)) {
            $display = ExerciseLib::show_score($score[0], $score[1], $showPercentage);
            $result = ExerciseLib::convertScoreToPlatformSetting($score[0], $score[1]);
            $score[0] = $result['score'];
            $score[1] = $result['weight'];
        } else {
            if ($userExerciseSettings) {
                $display = ExerciseLib::show_score(
                    $score[0],
                    $score[1],
                    $showPercentage
                );
            }
        }

        return [
            'display' => $display,
            'score' => $score,
        ];
    }

    /**
     * @param int $userId
     * @param int $userCount
     *
     * @return array
     */
    public function buildRankingColumn(GradebookItem $item, $userId = null, $userCount = 0)
    {
        $score = $item->calc_score($userId, 'ranking');
        $score[1] = $userCount;
        $scoreDisplay = null;
        if (isset($score[0])) {
            $scoreDisplay = ScoreDisplay::instance();
            $scoreDisplay = $scoreDisplay->display_score(
                $score,
                SCORE_DIV,
                SCORE_BOTH,
                true,
                true,
                true
            );
        }

        return [
            'display' => $scoreDisplay,
            'score' => $score,
        ];
    }

    /**
     * @param int           $userId
     * @param GradebookItem $item
     * @param bool          $ignore_score_color
     *
     * @return string|null
     */
    public function build_result_column(
        $userId,
        $item,
        $ignore_score_color,
        $forceSimpleResult = false,
        $useExerciseScoreInTotal = false
    ) {
        $scoreDisplay = ScoreDisplay::instance();
        $score = $item->calc_score($userId);
        $model = ExerciseLib::getCourseScoreModel();
        if (!empty($score)) {
            switch ($item->get_item_type()) {
                // category
                case 'C':
                    if (null != $score) {
                        if (empty($model)) {
                            if ($useExerciseScoreInTotal) {
                                $display = ExerciseLib::show_score($score[0], $score[1], false);
                            } else {
                                $display = $scoreDisplay->display_score(
                                    $score,
                                    SCORE_DIV,
                                    null,
                                    false,
                                    false,
                                    true
                                );
                            }

                            return [
                                'display' => $display,
                                'score' => $score,
                                'score_weight' => $score,
                            ];
                        } else {
                            $display = ExerciseLib::show_score(
                                $score[0],
                                $score[1],
                                false
                            );

                            return [
                                'display' => $display,
                                'score' => $score,
                                'score_weight' => $score,
                            ];
                        }
                    } else {
                        return [
                            'display' => null,
                            'score' => $score,
                            'score_weight' => $score,
                        ];
                    }
                    break;
                case 'E':
                case 'L':
                    $scoreWeight = [
                        ($score[1] > 0) ? $score[0] / $score[1] * $item->get_weight() : 0,
                        $item->get_weight(),
                    ];

                    if (empty($model)) {
                        if ($useExerciseScoreInTotal) {
                            $display = ExerciseLib::show_score($score[0], $score[1], false);
                        } else {
                            $display = $scoreDisplay->display_score(
                                $score,
                                SCORE_DIV_PERCENT_WITH_CUSTOM,
                                null,
                                false,
                                false,
                                true
                            );
                        }
                        /*$type = $item->get_item_type();
                        if ('L' === $type && 'ExerciseLink' === get_class($item)) {
                            $display = ExerciseLib::show_score(
                                $score[0],
                                $score[1],
                                false,
                                true,
                                false,
                                false,
                                null,
                                null,
                                false,
                                true
                            );
                        }*/
                    } else {
                        $display = ExerciseLib::show_score(
                            $score[0],
                            $score[1],
                            false
                        );
                    }

                    return [
                        'display' => $display,
                        'score' => $score,
                        'score_weight' => $scoreWeight,
                    ];
                }
        }

        return [
            'display' => null,
            'score' => null,
            'score_weight' => null,
        ];
    }

    /**
     * @param GradebookItem $item
     *
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
            }

            return api_format_date($date);
        }
    }
}
