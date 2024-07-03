<?php
/* For licensing terms, see /license.txt */

/**
 * Class FlatViewDataGenerator
 * Class to select, sort and transform object data into array data,
 * used for the teacher's flat view.
 *
 * @author Bert Steppé
 */
class FlatViewDataGenerator
{
    // Sorting types constants
    public const FVDG_SORT_LASTNAME = 1;
    public const FVDG_SORT_FIRSTNAME = 2;
    public const FVDG_SORT_ASC = 4;
    public const FVDG_SORT_DESC = 8;
    public $params;
    /** @var Category */
    public $category;

    private $users;
    private $evals;
    private $links;
    private $evals_links;
    private $mainCourseCategory;

    /**
     * @param array         $users
     * @param array         $evals
     * @param array         $links
     * @param array         $params
     * @param Category|null $mainCourseCategory
     */
    public function __construct(
        $users = [],
        $evals = [],
        $links = [],
        $params = [],
        $mainCourseCategory = null
    ) {
        $this->users = isset($users) ? $users : [];
        $this->evals = isset($evals) ? $evals : [];
        $this->links = isset($links) ? $links : [];
        $this->evals_links = array_merge($this->evals, $this->links);
        $this->params = $params;
        $this->mainCourseCategory = $mainCourseCategory;
    }

    /**
     * @return Category
     */
    public function getMainCourseCategory()
    {
        return $this->mainCourseCategory;
    }

    /**
     * Get total number of users (rows).
     *
     * @return int
     */
    public function get_total_users_count()
    {
        return count($this->users);
    }

    /**
     * Get total number of evaluations/links (columns) (the 2 users columns not included).
     *
     * @return int
     */
    public function get_total_items_count()
    {
        return count($this->evals_links);
    }

    /**
     * Get array containing column header names (incl user columns).
     *
     * @param int  $items_start Start item offset
     * @param int  $items_count Number of items to get
     * @param bool $show_detail whether to show the details or not
     *
     * @return array List of headers
     */
    public function get_header_names($items_start = 0, $items_count = null, $show_detail = false)
    {
        $headers = [];
        if (isset($this->params['show_official_code']) && $this->params['show_official_code']) {
            $headers[] = get_lang('OfficialCode');
        }

        if (isset($this->params['join_firstname_lastname']) && $this->params['join_firstname_lastname']) {
            if (api_is_western_name_order()) {
                $headers[] = get_lang('FirstnameAndLastname');
            } else {
                $headers[] = get_lang('LastnameAndFirstname');
            }
        } else {
            if (api_is_western_name_order()) {
                $headers[] = get_lang('FirstName');
                $headers[] = get_lang('LastName');
            } else {
                $headers[] = get_lang('LastName');
                $headers[] = get_lang('FirstName');
            }
        }

        $headers[] = get_lang('Username');

        $this->addExtraFieldColumnsHeaders($headers);

        if (!isset($items_count)) {
            $items_count = count($this->evals_links) - $items_start;
        }

        $parent_id = $this->category->get_parent_id();
        if (0 == $parent_id ||
            isset($this->params['only_subcat']) &&
            $this->params['only_subcat'] == $this->category->get_id()
        ) {
            $main_weight = $this->category->get_weight();
        } else {
            $main_cat = Category::load($parent_id, null, null);
            $main_weight = $main_cat[0]->get_weight();
        }

        //@todo move these in a function
        $sum_categories_weight_array = [];
        $mainCategoryId = null;
        $mainCourseCategory = $this->getMainCourseCategory();

        if (!empty($mainCourseCategory)) {
            $mainCategoryId = $mainCourseCategory->get_id();
        }

        // No category was added
        $course_code = api_get_course_id();
        $session_id = api_get_session_id();
        $model = ExerciseLib::getCourseScoreModel();
        $allcat = $this->category->get_subcategories(
            null,
            $course_code,
            $session_id,
            'ORDER BY id'
        );

        $evaluationsAdded = [];
        if (0 == $parent_id && !empty($allcat)) {
            // Means there are any subcategory
            /** @var Category $sub_cat */
            foreach ($allcat as $sub_cat) {
                $sub_cat_weight = round(100 * $sub_cat->get_weight() / $main_weight, 1);
                $add_weight = " $sub_cat_weight %";

                $mainHeader = Display::url(
                    Security::remove_XSS($sub_cat->get_name()),
                    api_get_self().'?selectcat='.$sub_cat->get_id().'&'.api_get_cidreq()
                ).$add_weight;

                if ('true' === api_get_setting('gradebook_detailed_admin_view')) {
                    $links = $sub_cat->get_links();
                    $evaluations = $sub_cat->get_evaluations();

                    $linkNameList = [];
                    /** @var ExerciseLink $link */
                    foreach ($links as $link) {
                        $linkNameList[] = Display::returnHeaderWithPercentage(
                            $link->get_name(),
                            $link->get_weight()
                        );
                    }

                    $evalNameList = [];
                    /** @var Evaluation $evaluation */
                    foreach ($evaluations as $evaluation) {
                        $linkNameList[] = Display::returnHeaderWithPercentage(
                            $evaluation->get_name(),
                            $evaluation->get_weight()
                        );
                    }

                    $finalList = array_merge($linkNameList, $evalNameList);

                    if (!empty($finalList)) {
                        $finalList[] = '<span class="small">'.get_lang('SubTotal').'</span>';
                    }

                    $list = [];
                    $list['items'] = $finalList;
                    $list['header'] = '<span class="text-center">'.$mainHeader.'</span>';
                    $headers[] = $list;
                } else {
                    $headers[] = '<span class="text-center">'.$mainHeader.'</span>';
                }
            }
        } else {
            if (!isset($this->params['only_total_category']) ||
                (isset($this->params['only_total_category']) &&
                    false == $this->params['only_total_category'])
            ) {
                for ($count = 0; ($count < $items_count) && ($items_start + $count < count($this->evals_links)); $count++) {
                    /** @var AbstractLink $item */
                    $item = $this->evals_links[$count + $items_start];
                    $weight = round(100 * $item->get_weight() / $main_weight, 1);
                    $label = $item->get_name().' '.$weight.' % ';
                    if (!empty($model)) {
                        $label = $item->get_name();
                    }

                    /*if ($item instanceof ExerciseLink) {
                        $label .= '<br /><br />'.$item->getLpListToString();
                    }*/

                    $headers[] = $label;
                    $evaluationsAdded[] = $item->get_id();
                }
            }
        }

        if (!empty($mainCategoryId)) {
            for ($count = 0; ($count < $items_count) && ($items_start + $count < count($this->evals_links)); $count++) {
                /** @var AbstractLink $item */
                $item = $this->evals_links[$count + $items_start];
                if ($mainCategoryId == $item->get_category_id() &&
                    !in_array($item->get_id(), $evaluationsAdded)
                ) {
                    $weight = round(100 * $item->get_weight() / $main_weight, 1);
                    $label = $item->get_name().' '.$weight.' % ';
                    if (!empty($model)) {
                        $label = $item->get_name();
                    }

                    /*if ($item instanceof ExerciseLink) {
                        $label .= '<br /><br />'.$item->getLpListToString();
                    }*/

                    $headers[] = $label;
                }
            }
        }

        $headers[] = '<span class="text-center">'.api_strtoupper(get_lang('GradebookQualificationTotal')).'</span>';

        if (api_get_configuration_value('gradebook_score_display_custom_standalone')
            && ScoreDisplay::instance()->is_custom()
        ) {
            $headers[] = get_lang('GradebookScoreDisplayCustomValues');
        }

        return $headers;
    }

    /**
     * @param int $id
     *
     * @return int
     */
    public function get_max_result_by_link($id)
    {
        $max = 0;
        foreach ($this->users as $user) {
            $item = $this->evals_links[$id];
            $score = $item->calc_score($user[0]);
            if ($score[0] > $max) {
                $max = $score[0];
            }
        }

        return $max;
    }

    /**
     * Get array containing evaluation items.
     *
     * @return array
     */
    public function get_evaluation_items($items_start = 0, $items_count = null)
    {
        $headers = [];
        if (!isset($items_count)) {
            $items_count = count($this->evals_links) - $items_start;
        }
        for ($count = 0; ($count < $items_count) && ($items_start + $count < count($this->evals_links)); $count++) {
            $item = $this->evals_links[$count + $items_start];
            $headers[] = $item->get_name();
        }

        return $headers;
    }

    /**
     * Get actual array data.
     *
     * @param int  $users_sorting
     * @param int  $users_start
     * @param null $users_count
     * @param int  $items_start
     * @param null $items_count
     * @param bool $ignore_score_color
     * @param bool $show_all
     *
     * @return array 2-dimensional array - each array contains the elements:
     *               0: user id
     *               1: user lastname
     *               2: user firstname
     *               3+: evaluation/link scores
     */
    public function get_data(
        $users_sorting = 0,
        $users_start = 0,
        $users_count = null,
        $items_start = 0,
        $items_count = null,
        $ignoreScoreColor = false,
        $show_all = false,
        $onlyScore = false
    ) {
        // Do some checks on users/items counts, redefine if invalid values
        if (!isset($users_count)) {
            $users_count = count($this->users) - $users_start;
        }
        if ($users_count < 0) {
            $users_count = 0;
        }
        if (!isset($items_count)) {
            $items_count = count($this->evals) + count($this->links) - $items_start;
        }
        if ($items_count < 0) {
            $items_count = 0;
        }

        $userTable = [];
        foreach ($this->users as $user) {
            $userTable[] = $user;
        }

        // sort users array
        if ($users_sorting & self::FVDG_SORT_LASTNAME) {
            usort($userTable, ['FlatViewDataGenerator', 'sort_by_last_name']);
        } elseif ($users_sorting & self::FVDG_SORT_FIRSTNAME) {
            usort($userTable, ['FlatViewDataGenerator', 'sort_by_first_name']);
        }

        if ($users_sorting & self::FVDG_SORT_DESC) {
            $userTable = array_reverse($userTable);
        }

        // Select the requested users
        $selected_users = array_slice($userTable, $users_start, $users_count);

        // Generate actual data array
        $scoreDisplay = ScoreDisplay::instance();

        $data = [];
        //@todo move these in a function
        $sum_categories_weight_array = [];
        $mainCategoryId = null;
        $mainCourseCategory = $this->getMainCourseCategory();
        if (!empty($mainCourseCategory)) {
            $mainCategoryId = $mainCourseCategory->get_id();
        }

        if (isset($this->category) && !empty($this->category)) {
            $categories = Category::load(
                null,
                null,
                null,
                $this->category->get_id()
            );
            if (!empty($categories)) {
                foreach ($categories as $category) {
                    $sum_categories_weight_array[$category->get_id()] = $category->get_weight();
                }
            } else {
                $sum_categories_weight_array[$this->category->get_id()] = $this->category->get_weight();
            }
        }

        $parent_id = $this->category->get_parent_id();
        if (0 == $parent_id ||
            (isset($this->params['only_subcat']) && $this->params['only_subcat'] == $this->category->get_id())
        ) {
            $main_weight = $this->category->get_weight();
        } else {
            $main_cat = Category::load($parent_id, null, null);
            $main_weight = $main_cat[0]->get_weight();
        }

        $export_to_pdf = false;
        if (isset($this->params['export_pdf']) && $this->params['export_pdf']) {
            $export_to_pdf = true;
        }

        $course_code = api_get_course_id();
        $session_id = api_get_session_id();
        $model = ExerciseLib::getCourseScoreModel();

        foreach ($selected_users as $user) {
            $row = [];
            // User id
            if ($export_to_pdf) {
                $row['user_id'] = $user_id = $user[0];
            } else {
                $row[] = $user_id = $user[0];
            }

            // Official code
            if (isset($this->params['show_official_code']) &&
                $this->params['show_official_code']
            ) {
                if ($export_to_pdf) {
                    $row['official_code'] = $user[4];
                } else {
                    $row[] = $user[4];
                }
            }

            // Last name
            if (isset($this->params['join_firstname_lastname']) &&
                $this->params['join_firstname_lastname']
            ) {
                if ($export_to_pdf) {
                    $row['name'] = api_get_person_name($user[3], $user[2]);
                } else {
                    $row[] = api_get_person_name($user[3], $user[2]);
                }
            } else {
                if ($export_to_pdf) {
                    if (api_is_western_name_order()) {
                        $row['firstname'] = $user[3];
                        $row['lastname'] = $user[2];
                    } else {
                        $row['lastname'] = $user[2];
                        $row['firstname'] = $user[3];
                    }
                } else {
                    if (api_is_western_name_order()) {
                        $row[] = $user[3]; //first name
                        $row[] = $user[2]; //last name
                    } else {
                        $row[] = $user[2]; //last name
                        $row[] = $user[3]; //first name
                    }
                }
            }

            $row[] = $user[1];
            $this->addExtraFieldColumnsData($row, $user[0]);
            $item_value_total = 0;
            $item_total = 0;
            $allcat = $this->category->get_subcategories(
                null,
                $course_code,
                $session_id,
                'ORDER BY id'
            );

            $evaluationsAdded = [];
            $detailAdminView = 'true' === api_get_setting('gradebook_detailed_admin_view');
            $style = api_get_configuration_value('gradebook_report_score_style');
            $defaultStyle = SCORE_DIV_SIMPLE_WITH_CUSTOM;
            if (!empty($style)) {
                $defaultStyle = (int) $style;
            }

            if ($onlyScore) {
                $defaultStyle = SCORE_PERCENT;
            }

            if (0 == $parent_id && !empty($allcat)) {
                /** @var Category $sub_cat */
                foreach ($allcat as $sub_cat) {
                    $score = $sub_cat->calc_score($user_id);
                    if ($detailAdminView) {
                        $links = $sub_cat->get_links();
                        /** @var ExerciseLink $link */
                        $linkScoreList = [];
                        foreach ($links as $link) {
                            $linkScore = $link->calc_score($user_id);
                            $linkScoreList[] = $scoreDisplay->display_score(
                                $linkScore,
                                $defaultStyle,
                                null,
                                $ignoreScoreColor
                            );
                        }

                        $evaluations = $sub_cat->get_evaluations();
                        $evalScoreList = [];
                        /** @var Evaluation $evaluation */
                        foreach ($evaluations as $evaluation) {
                            $evalScore = $evaluation->calc_score($user_id);
                            $evalScoreList[] = $scoreDisplay->display_score(
                                $evalScore,
                                $defaultStyle,
                                null,
                                $ignoreScoreColor
                            );
                        }
                    }

                    $real_score = $score;
                    $divide = 0 == $score[1] ? 1 : $score[1];
                    $sub_cat_percentage = $sum_categories_weight_array[$sub_cat->get_id()];
                    $item_value = $score[0] / $divide * $main_weight;

                    // Fixing total when using one or multiple gradebooks
                    $percentage = $sub_cat->get_weight() / ($sub_cat_percentage) * $sub_cat_percentage / $this->category->get_weight();
                    $item_value = $percentage * $item_value;
                    $item_total += $sub_cat->get_weight();

                    if ('false' === api_get_setting('gradebook_show_percentage_in_reports')) {
                        $defaultShowPercentageValue = SCORE_SIMPLE;
                        if (!empty($style)) {
                            $defaultShowPercentageValue = $style;
                        }
                        if ($onlyScore) {
                            $defaultShowPercentageValue = SCORE_PERCENT;
                        }
                        $real_score = $scoreDisplay->display_score(
                            $real_score,
                            $defaultShowPercentageValue,
                            true,
                            $ignoreScoreColor
                        );
                        $temp_score = $scoreDisplay->display_score(
                            $score,
                            SCORE_DIV_SIMPLE_WITH_CUSTOM,
                            null,
                            $ignoreScoreColor
                        );
                        $temp_score = Display::tip($real_score, $temp_score);
                    } else {
                        $real_score = $scoreDisplay->display_score(
                            $real_score,
                            SCORE_DIV_PERCENT,
                            SCORE_ONLY_SCORE,
                            $ignoreScoreColor
                        );
                        $temp_score = $scoreDisplay->display_score(
                            $score,
                            $defaultStyle,
                            null,
                            $ignoreScoreColor
                        );
                        $temp_score = Display::tip($temp_score, $real_score, 'div');
                    }

                    if (!isset($this->params['only_total_category']) ||
                        (isset($this->params['only_total_category']) &&
                            false == $this->params['only_total_category'])
                    ) {
                        if (!$show_all) {
                            if ($detailAdminView) {
                                $finalList = array_merge($linkScoreList, $evalScoreList);
//                                if (empty($finalList)) {
//                                    $average = 0;
//                                } else {
//                                    $average = $item_value / count($finalList);
//                                }
//                                $finalList[] = round($average, 2);
                                foreach ($finalList as $finalValue) {
                                    $row[] = $finalValue;
                                }
//                            } else {
                            }
                            $row[] = $temp_score.' ';
//                            }
                        } else {
                            $row[] = $temp_score;
                        }
                    }
                    $item_value_total += $item_value;
                }
            } else {
                // All evaluations
                $result = $this->parseEvaluations(
                    $user_id,
                    $sum_categories_weight_array,
                    $items_count,
                    $items_start,
                    $show_all,
                    $row,
                    null,
                    [],
                    $ignoreScoreColor,
                    $onlyScore
                );
                $item_value_total += $result['item_value_total'];
                $evaluationsAdded = $result['evaluations_added'];
                $item_total = $main_weight;
            }

            // All evaluations
            $result = $this->parseEvaluations(
                $user_id,
                $sum_categories_weight_array,
                $items_count,
                $items_start,
                $show_all,
                $row,
                $mainCategoryId,
                $evaluationsAdded,
                $ignoreScoreColor,
                $onlyScore
            );

            $item_total += $result['item_total'];
            $item_value_total += $result['item_value_total'];
            $total_score = [$item_value_total, $item_total];
            $style = api_get_configuration_value('gradebook_report_score_style');
            $customDisplayIsStandalone =
                api_get_configuration_value('gradebook_score_display_custom_standalone') &&
                $scoreDisplay->is_custom();

            if (!$show_all) {
                //$defaultStyle = empty($style) ? SCORE_DIV_PERCENT : (int) $style;
                $displayScore = $scoreDisplay->display_score($total_score, $defaultStyle, null, $ignoreScoreColor);
                if (!empty($model)) {
                    $displayScore = ExerciseLib::show_score($total_score[0], $total_score[1]);
                }
                if ($export_to_pdf) {
                    $row['total'] = $displayScore;
                } else {
                    $row[] = $displayScore;
                }
            } else {
                /*$defaultStyle = empty($style) ? SCORE_DIV_SIMPLE_WITH_CUSTOM_LETTERS : (int) $style;
                if ($ignoreScoreColor) {
                    $defaultStyle = SCORE_DIV_PERCENT;
                }*/
                $displayScore = $scoreDisplay->display_score($total_score, $defaultStyle, null, $ignoreScoreColor);
                if (!empty($model)) {
                    $displayScore = ExerciseLib::show_score($total_score[0], $total_score[1]);
                }
                if ($export_to_pdf) {
                    $row['total'] = $displayScore;
                } else {
                    $row[] = $displayScore;
                }
            }

            if ($customDisplayIsStandalone) {
                if ($export_to_pdf) {
                    $row['display_custom'] = $scoreDisplay->display_custom($total_score);
                } else {
                    $row[] = $scoreDisplay->display_custom($total_score);
                }
            }

            unset($score);
            $data[] = $row;
        }

        return $data;
    }

    /**
     * Parse evaluations.
     *
     * @param int   $user_id
     * @param array $sum_categories_weight_array
     * @param int   $items_count
     * @param int   $items_start
     * @param int   $show_all
     * @param int   $parentCategoryIdFilter      filter by category id if set
     *
     * @return array
     */
    public function parseEvaluations(
        $user_id,
        $sum_categories_weight_array,
        $items_count,
        $items_start,
        $show_all,
        &$row,
        $parentCategoryIdFilter = null,
        $evaluationsAlreadyAdded = [],
        $ignoreScoreDecorations = false,
        $onlyScore = false
    ) {
        // Generate actual data array
        $scoreDisplay = ScoreDisplay::instance();
        $item_total = 0;
        $item_value_total = 0;
        $evaluationsAdded = [];
        $model = ExerciseLib::getCourseScoreModel();
        $style = api_get_configuration_value('gradebook_report_score_style');

        $defaultStyle = SCORE_DIV_SIMPLE_WITH_CUSTOM;
        if (!empty($style)) {
            $defaultStyle = (int) $style;
        }
        if ($onlyScore) {
            $defaultStyle = SCORE_PERCENT;
        }

        $showPercentage = api_get_setting('gradebook_show_percentage_in_reports');
        for ($count = 0; $count < $items_count && ($items_start + $count < count($this->evals_links)); $count++) {
            /** @var AbstractLink $item */
            $item = $this->evals_links[$count + $items_start];

            if (!empty($evaluationsAlreadyAdded)) {
                if (in_array($item->get_id(), $evaluationsAlreadyAdded)) {
                    continue;
                }
            }

            if (!empty($parentCategoryIdFilter)) {
                if ($item->get_category_id() != $parentCategoryIdFilter) {
                    continue;
                }
            }

            $evaluationsAdded[] = $item->get_id();
            $score = $item->calc_score($user_id);
            $real_score = $score;
            $divide = isset($score[1]) && !empty($score[1]) && $score[1] > 0 ? $score[1] : 1;
            // Sub cat weight
            $item_value = isset($score[0]) ? $score[0] / $divide : null;
            // Fixing total when using one or multiple gradebooks.
            if (empty($parentCategoryIdFilter)) {
                if (0 == $this->category->get_parent_id()) {
                    if (isset($score[0])) {
                        $item_value = $score[0] / $divide * $item->get_weight();
                    } else {
                        $item_value = null;
                    }
                } else {
                    if (null !== $item_value) {
                        $item_value = $item_value * $item->get_weight();
                    }
                }
            } else {
                if ($score) {
                    $item_value = $score[0] / $divide * $item->get_weight();
                }
            }

            $item_total += $item->get_weight();
            $totalType = SCORE_DIV_PERCENT;
            if ($onlyScore) {
                $totalType = SCORE_PERCENT;
            }
            $complete_score = $scoreDisplay->display_score(
                $score,
                $totalType,
                SCORE_ONLY_SCORE,
                $ignoreScoreDecorations
            );

            if ('false' === $showPercentage) {
                $defaultShowPercentageValue = SCORE_SIMPLE;
                if (!empty($style)) {
                    $defaultShowPercentageValue = $style;
                }
                if ($onlyScore) {
                    $defaultShowPercentageValue = SCORE_PERCENT;
                }

                $real_score = $scoreDisplay->display_score(
                    $real_score,
                    $defaultShowPercentageValue,
                    null,
                    $ignoreScoreDecorations
                );
                $temp_score = $scoreDisplay->display_score(
                    [$item_value, null],
                    $defaultStyle,
                    null,
                    $ignoreScoreDecorations
                );
                $temp_score = Display::tip($real_score, $temp_score);
            } else {
                $temp_score = $scoreDisplay->display_score(
                    $real_score,
                    $defaultStyle,
                    null,
                    $ignoreScoreDecorations
                );
                $temp_score = Display::tip($temp_score, $complete_score);
            }

            if (!empty($model)) {
                $scoreToShow = '';
                if (isset($score[0]) && isset($score[1])) {
                    $scoreToShow = ExerciseLib::show_score($score[0], $score[1]);
                }
                $temp_score = $scoreToShow;
            }

            if (!isset($this->params['only_total_category']) ||
                (isset($this->params['only_total_category']) && false == $this->params['only_total_category'])
            ) {
                if (!$show_all) {
                    if (in_array(
                        $item->get_type(),
                        [
                            LINK_EXERCISE,
                            LINK_DROPBOX,
                            LINK_STUDENTPUBLICATION,
                            LINK_LEARNPATH,
                            LINK_FORUM_THREAD,
                            LINK_ATTENDANCE,
                            LINK_SURVEY,
                            LINK_HOTPOTATOES,
                        ]
                    )
                    ) {
                        if (isset($score[0])) {
                            $row[] = $temp_score.' ';
                        } else {
                            $row[] = '';
                        }
                    } else {
                        if (isset($score[0])) {
                            $row[] = $temp_score.' ';
                        } else {
                            $row[] = '';
                        }
                    }
                } else {
                    $row[] = $temp_score;
                }
            }
            $item_value_total += $item_value;
        }

        return [
            'item_total' => $item_total,
            'item_value_total' => $item_value_total,
            'evaluations_added' => $evaluationsAdded,
        ];
    }

    /**
     * Get actual array data evaluation/link scores.
     *
     * @param int $session_id
     *
     * @return array
     */
    public function getEvaluationSummaryResults($session_id = null)
    {
        $usertable = [];
        foreach ($this->users as $user) {
            $usertable[] = $user;
        }
        $selected_users = $usertable;

        // generate actual data array for all selected users
        $data = [];

        foreach ($selected_users as $user) {
            $row = [];
            for ($count = 0; $count < count($this->evals_links); $count++) {
                $item = $this->evals_links[$count];
                $score = $item->calc_score($user[0]);
                $porcent_score = isset($score[1]) && $score[1] > 0 ? ($score[0] * 100) / $score[1] : 0;
                $row[$item->get_name()] = $porcent_score;
            }
            $data[$user[0]] = $row;
        }

        // get evaluations for every user by item
        $data_by_item = [];
        foreach ($data as $uid => $items) {
            $tmp = [];
            foreach ($items as $item => $value) {
                $tmp[] = $item;
                if (in_array($item, $tmp)) {
                    $data_by_item[$item][$uid] = $value;
                }
            }
        }

        /* Get evaluation summary results
           (maximum, minimum and average of evaluations for all students)
        */
        $result = [];
        foreach ($data_by_item as $k => $v) {
            $average = round(array_sum($v) / count($v));
            arsort($v);
            $maximum = array_shift($v);
            $minimum = array_pop($v);

            if (is_null($minimum)) {
                $minimum = 0;
            }

            $summary = [
                'max' => $maximum,
                'min' => $minimum,
                'avg' => $average,
            ];
            $result[$k] = $summary;
        }

        return $result;
    }

    /**
     * @return array
     */
    public function get_data_to_graph()
    {
        // do some checks on users/items counts, redefine if invalid values
        $usertable = [];
        foreach ($this->users as $user) {
            $usertable[] = $user;
        }
        // sort users array
        usort($usertable, ['FlatViewDataGenerator', 'sort_by_first_name']);

        $data = [];

        $selected_users = $usertable;
        foreach ($selected_users as $user) {
            $row = [];
            $row[] = $user[0]; // user id
            $item_value = 0;
            $item_total = 0;

            for ($count = 0; $count < count($this->evals_links); $count++) {
                $item = $this->evals_links[$count];
                $score = $item->calc_score($user[0]);

                $divide = (0 == ($score[1])) ? 1 : $score[1];
                $item_value += $score[0] / $divide * $item->get_weight();
                $item_total += $item->get_weight();

                $score_denom = (0 == $score[1]) ? 1 : $score[1];
                $score_final = ($score[0] / $score_denom) * 100;
                $row[] = $score_final;
            }
            $score_final = ($item_value / $item_total) * 100;

            $row[] = $score_final;
            $data[] = $row;
        }

        return $data;
    }

    /**
     * This is a function to show the generated data.
     *
     * @param bool $displayWarning
     *
     * @return array
     */
    public function get_data_to_graph2($displayWarning = true)
    {
        $course_code = api_get_course_id();
        $session_id = api_get_session_id();
        // do some checks on users/items counts, redefine if invalid values
        $usertable = [];
        foreach ($this->users as $user) {
            $usertable[] = $user;
        }
        // sort users array
        usort($usertable, ['FlatViewDataGenerator', 'sort_by_first_name']);

        // generate actual data array
        $scoreDisplay = ScoreDisplay::instance();
        $data = [];
        $selected_users = $usertable;
        foreach ($selected_users as $user) {
            $row = [];
            $row[] = $user[0]; // user id
            $item_value = 0;
            $item_total = 0;
            $final_score = 0;
            $item_value_total = 0;
            $allcat = $this->category->get_subcategories(
                null,
                $course_code,
                $session_id,
                'ORDER BY id'
            );
            $parent_id = $this->category->get_parent_id();
            if (0 == $parent_id && !empty($allcat)) {
                foreach ($allcat as $sub_cat) {
                    $score = $sub_cat->calc_score($user[0]);
                    $real_score = $score;
                    $main_weight = $this->category->get_weight();
                    $divide = 0 == $score[1] ? 1 : $score[1];

                    //$sub_cat_percentage = $sum_categories_weight_array[$sub_cat->get_id()];
                    $item_value = $score[0] / $divide * $main_weight;
                    $item_total += $sub_cat->get_weight();

                    $row[] = [
                        $item_value,
                        trim(
                            $scoreDisplay->display_score(
                                $real_score,
                                SCORE_CUSTOM,
                                null,
                                true
                            )
                        ),
                    ];
                    $item_value_total += $item_value;
                    $final_score += $score[0];
                }
                $total_score = [$final_score, $item_total];
                $row[] = [
                    $final_score,
                    trim(
                        $scoreDisplay->display_score(
                            $total_score,
                            SCORE_CUSTOM,
                            null,
                            true
                        )
                    ),
                ];
            } else {
                for ($count = 0; $count < count($this->evals_links); $count++) {
                    $item = $this->evals_links[$count];
                    $score = $item->calc_score($user[0]);
                    $score_final = null;
                    $displayScore = null;

                    if (null !== $score) {
                        $divide = 0 == $score[1] ? 1 : $score[1];
                        $item_value += $score[0] / $divide * $item->get_weight();
                        $item_total += $item->get_weight();
                        $score_denom = (0 == $score[1]) ? 1 : $score[1];
                        $score_final = ($score[0] / $score_denom) * 100;
                        $displayScore = trim(
                            $scoreDisplay->display_score(
                                $score,
                                SCORE_CUSTOM,
                                null,
                                true
                            )
                        );
                    }

                    $row[] = [
                        $score_final,
                        $displayScore,
                    ];
                }
                $total_score = [$item_value, $item_total];
                $score_final = 0;
                if (!empty($item_total)) {
                    $score_final = ($item_value / $item_total) * 100;
                }
                if ($displayWarning) {
                    echo Display::return_message($total_score[1], 'warning');
                }
                $row[] = [
                    $score_final,
                    trim(
                        $scoreDisplay->display_score(
                            $total_score,
                            SCORE_CUSTOM,
                            null,
                            true
                        )
                    ),
                ];
            }

            $data[] = $row;
        }

        return $data;
    }

    /**
     * @param $item1
     * @param $item2
     *
     * @return int
     */
    public function sort_by_last_name($item1, $item2)
    {
        return api_strcmp($item1[2], $item2[2]);
    }

    /**
     * @param $item1
     * @param $item2
     *
     * @return int
     */
    public function sort_by_first_name($item1, $item2)
    {
        return api_strcmp($item1[3], $item2[3]);
    }

    /**
     * Add columns heders according to gradebook_flatview_extrafields_columns conf setting.
     */
    private function addExtraFieldColumnsHeaders(array &$headers)
    {
        $extraFieldColumns = api_get_configuration_value('gradebook_flatview_extrafields_columns');

        if (!$extraFieldColumns || !is_array($extraFieldColumns)) {
            return;
        }

        foreach ($extraFieldColumns['variables'] as $extraFieldVariable) {
            $extraField = new ExtraField('user');
            $extraFieldInfo = $extraField->get_handler_field_info_by_field_variable($extraFieldVariable);

            $headers[] = $extraFieldInfo['display_text'];
        }
    }

    /**
     * Add columns data according to gradebook_flatview_extrafields_columns conf setting.
     *
     * @param int $userId
     */
    private function addExtraFieldColumnsData(array &$row, $userId)
    {
        $extraFieldColumns = api_get_configuration_value('gradebook_flatview_extrafields_columns');

        if (!$extraFieldColumns || !is_array($extraFieldColumns)) {
            return;
        }

        foreach ($extraFieldColumns['variables'] as $extraFieldVariable) {
            $extraFieldValue = new ExtraFieldValue('user');
            $extraFieldValueInfo = $extraFieldValue->get_values_by_handler_and_field_variable(
                $userId,
                $extraFieldVariable
            );

            $row[] = $extraFieldValueInfo ? $extraFieldValueInfo['value'] : null;
        }
    }
}
