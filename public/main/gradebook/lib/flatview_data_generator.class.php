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
    const FVDG_SORT_LASTNAME = 1;
    const FVDG_SORT_FIRSTNAME = 2;
    const FVDG_SORT_ASC = 4;
    const FVDG_SORT_DESC = 8;
    public $params;
    /** @var Category */
    public $category;

    private $users;
    private $evals;
    private $links;
    private $evals_links;
    private $mainCourseCategory;

    /**
     * @param ?array         $users
     * @param ?array         $evals
     * @param ?array         $links
     * @param ?array         $params
     * @param ?Category $mainCourseCategory
     */
    public function __construct(
        ?array $users = [],
        ?array $evals = [],
        ?array $links = [],
        ?array $params = [],
        ?Category $mainCourseCategory = null
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
     * @throws \Doctrine\DBAL\Exception
     */
    public function get_header_names($items_start = 0, $items_count = null, $show_detail = false): array
    {
        $headers = [];
        if (isset($this->params['show_official_code']) && $this->params['show_official_code']) {
            $headers[] = get_lang('Code');
        }

        if (isset($this->params['join_firstname_lastname']) && $this->params['join_firstname_lastname']) {
            if (api_is_western_name_order()) {
                $headers[] = get_lang('First Name and Last Name');
            } else {
                $headers[] = get_lang('Last Name and First Name');
            }
        } else {
            if (api_is_western_name_order()) {
                $headers[] = get_lang('First name');
                $headers[] = get_lang('Last name');
            } else {
                $headers[] = get_lang('Last name');
                $headers[] = get_lang('First name');
            }
        }

        $headers[] = get_lang('Username');

        $this->addExtraFieldColumnsHeaders($headers);

        if (!isset($items_count)) {
            $items_count = count($this->evals_links) - $items_start;
        }

        $parent_id = $this->category->get_parent_id();
        if (
            0 == $parent_id ||
            (isset($this->params['only_subcat']) && $this->params['only_subcat'] == $this->category->get_id())
        ) {
            $main_weight = $this->category->get_weight();
        } else {
            $main_cat = Category::load($parent_id, null, 0);
            $main_weight = $main_cat[0]->get_weight();
        }

        // Avoid division by zero in header weight calculations.
        if (0 == $main_weight) {
            $main_weight = 1;
        }

        $mainWeightRaw = (float) $main_weight;
        $mainWeightForItems = $this->normalizeMainWeightForItems($mainWeightRaw, $this->getAllEvalLinkWeights());
        if (0.0 == $mainWeightForItems) {
            $mainWeightForItems = 1.0;
        }

        // @todo move these in a function
        $sum_categories_weight_array = [];
        $mainCategoryId = null;
        $mainCourseCategory = $this->getMainCourseCategory();

        if (!empty($mainCourseCategory)) {
            $mainCategoryId = $mainCourseCategory->get_id();
        }

        // No category was added
        $session_id = api_get_session_id();
        $model = ExerciseLib::getCourseScoreModel();
        $allcat = $this->category->get_subcategories(
            null,
            api_get_course_int_id(),
            $session_id,
            'ORDER BY id'
        );

        $evaluationsAdded = [];
        if (0 == $parent_id && !empty($allcat)) {
            // Means there are subcategories.
            /** @var Category $sub_cat */
            foreach ($allcat as $sub_cat) {
                // Skip zero-weight or invisible subcategories in flat view header
                $isVisible = true;
                if (method_exists($sub_cat, 'is_visible')) {
                    $isVisible = (bool) $sub_cat->is_visible();
                } elseif (method_exists($sub_cat, 'get_visible')) {
                    $isVisible = (bool) $sub_cat->get_visible();
                }

                if (!$isVisible || $sub_cat->get_weight() <= 0) {
                    continue;
                }

                // Normalize main weight against subcategory weights too (same mismatch can happen here).
                $subCatWeights = [];
                foreach ($allcat as $tmpCat) {
                    $isVisibleTmp = true;
                    if (method_exists($tmpCat, 'is_visible')) {
                        $isVisibleTmp = (bool) $tmpCat->is_visible();
                    } elseif (method_exists($tmpCat, 'get_visible')) {
                        $isVisibleTmp = (bool) $tmpCat->get_visible();
                    }

                    if ($isVisibleTmp && (float) $tmpCat->get_weight() > 0) {
                        $subCatWeights[] = (float) $tmpCat->get_weight();
                    }
                }

                $mainWeightForSubcats = $this->normalizeMainWeightForItems($mainWeightRaw, $subCatWeights);
                if (0.0 == $mainWeightForSubcats) {
                    $mainWeightForSubcats = 1.0;
                }

                $sub_cat_weight = round(100 * $sub_cat->get_weight() / $mainWeightForSubcats, 1);
                $add_weight = ' ' . $sub_cat_weight . ' %';

                $mainHeader = Display::url(
                        $sub_cat->get_name(),
                        api_get_self() . '?selectcat=' . $sub_cat->get_id() . '&' . api_get_cidreq()
                    ) . $add_weight;

                if ('true' === api_get_setting('gradebook_detailed_admin_view')) {
                    $links = $sub_cat->get_links();
                    $evaluations = $sub_cat->get_evaluations();

                    /** @var ExerciseLink $link */
                    $linkNameList = [];
                    foreach ($links as $link) {
                        $linkNameList[] = $link->get_name();
                    }

                    $evalNameList = [];
                    foreach ($evaluations as $evaluation) {
                        $evalNameList[] = $evaluation->get_name();
                    }

                    $finalList = array_merge($linkNameList, $evalNameList);

                    if (!empty($finalList)) {
                        $finalList[] = get_lang('Average');
                    }

                    $list = [];
                    $list['items'] = $finalList;
                    $list['header'] = '<span class="text-center">' . $mainHeader . '</span>';
                    $headers[] = $list;
                } else {
                    $headers[] = '<span class="text-center">' . $mainHeader . '</span>';
                }
            }
        } else {
            if (
                !isset($this->params['only_total_category']) ||
                (isset($this->params['only_total_category']) && false == $this->params['only_total_category'])
            ) {
                for (
                    $count = 0;
                    ($count < $items_count) && ($items_start + $count < count($this->evals_links));
                    $count++
                ) {
                    /** @var AbstractLink $item */
                    $item = $this->evals_links[$count + $items_start];
                    $weight = round(100 * $item->get_weight() / $mainWeightForItems, 1);
                    $label = $item->get_name() . ' ' . $weight . ' % ';
                    // When a course score model is active, show only the item name.
                    if (!empty($model)) {
                        $label = $item->get_name();
                    }
                    $headers[] = $label;
                    $evaluationsAdded[] = $item->get_id();
                }
            }
        }

        if (!empty($mainCategoryId)) {
            for (
                $count = 0;
                ($count < $items_count) && ($items_start + $count < count($this->evals_links));
                $count++
            ) {
                /** @var AbstractLink $item */
                $item = $this->evals_links[$count + $items_start];
                if (
                    $mainCategoryId == $item->get_category_id() &&
                    !in_array($item->get_id(), $evaluationsAdded)
                ) {
                    $weight = round(100 * $item->get_weight() / $mainWeightForItems, 1);
                    $label = $item->get_name() . ' ' . $weight . ' % ';
                    if (!empty($model)) {
                        $label = $item->get_name();
                    }
                    $headers[] = $label;
                }
            }
        }

        $headers[] = '<span class="text-center">' . api_strtoupper(get_lang('Total')) . '</span>';

        if (
            'true' === api_get_setting('gradebook.gradebook_score_display_custom_standalone') &&
            ScoreDisplay::instance()->is_custom()
        ) {
            $headers[] = get_lang('Competence levels custom values');
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
        $ignore_score_color = false,
        $show_all = false
    ) {
        // Basic bounds checks for users/items.
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

        // Copy users into a sortable table.
        $userTable = [];
        foreach ($this->users as $user) {
            $userTable[] = $user;
        }

        // Sort users array.
        if ($users_sorting & self::FVDG_SORT_LASTNAME) {
            usort($userTable, ['FlatViewDataGenerator', 'sort_by_last_name']);
        } elseif ($users_sorting & self::FVDG_SORT_FIRSTNAME) {
            usort($userTable, ['FlatViewDataGenerator', 'sort_by_first_name']);
        }

        if ($users_sorting & self::FVDG_SORT_DESC) {
            $userTable = array_reverse($userTable);
        }

        // Select the requested users.
        $selected_users = array_slice($userTable, $users_start, $users_count);

        // Generate actual data array.
        $scoreDisplay = ScoreDisplay::instance();

        $data = [];
        // @todo move these in a function
        $sum_categories_weight_array = [];
        $mainCategoryId = null;
        $mainCourseCategory = $this->getMainCourseCategory();
        if (!empty($mainCourseCategory)) {
            $mainCategoryId = $mainCourseCategory->get_id();
        }

        if (!empty($this->category)) {
            $categories = Category::load(
                null,
                null,
                0,
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

        if (
            0 == $parent_id ||
            (isset($this->params['only_subcat']) &&
                $this->params['only_subcat'] == $this->category->get_id())
        ) {
            $main_weight = $this->category->get_weight();
        } else {
            $main_cat = Category::load($parent_id, null, 0);
            $main_weight = $main_cat[0]->get_weight();
        }

        // Avoid division by zero for safety.
        if (0 == $main_weight) {
            $main_weight = 1;
        }

        // Normalize main weight scale to avoid totals like 100/1 => 10000%.
        $mainWeightRaw = (float) $main_weight;
        $mainWeightForItems = $this->normalizeMainWeightForItems($mainWeightRaw, $this->getAllEvalLinkWeights());
        if (0.0 == $mainWeightForItems) {
            $mainWeightForItems = 1.0;
        }

        $export_to_pdf = false;
        if (isset($this->params['export_pdf']) && $this->params['export_pdf']) {
            $export_to_pdf = true;
        }

        $session_id = api_get_session_id();
        $model = ExerciseLib::getCourseScoreModel();
        $hasScoreModel = !empty($model);

        foreach ($selected_users as $user) {
            $row = [];

            // User id.
            if ($export_to_pdf) {
                $row['user_id'] = $user_id = $user[0];
            } else {
                $row[] = $user_id = $user[0];
            }

            // Official code.
            if (isset($this->params['show_official_code']) && $this->params['show_official_code']) {
                if ($export_to_pdf) {
                    $row['official_code'] = $user[4];
                } else {
                    $row[] = $user[4];
                }
            }

            // Last name / first name.
            if (isset($this->params['join_firstname_lastname']) && $this->params['join_firstname_lastname']) {
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
                        $row[] = $user[3]; // first name
                        $row[] = $user[2]; // last name
                    } else {
                        $row[] = $user[2]; // last name
                        $row[] = $user[3]; // first name
                    }
                }
            }

            // Username column.
            $row[] = $user[1];

            // Extra fields.
            $this->addExtraFieldColumnsData($row, $user[0]);

            $item_value_total = 0;
            $item_total = 0;

            $allcat = $this->category->get_subcategories(
                null,
                api_get_course_int_id(),
                $session_id,
                'ORDER BY id'
            );

            $evaluationsAdded = [];

            // Case 1: root category with visible subcategories → one column per subcategory.
            if (0 == $parent_id && !empty($allcat)) {
                /** @var Category $sub_cat */
                foreach ($allcat as $sub_cat) {
                    // Skip zero-weight or invisible subcategories in flat view totals (see #3504).
                    $isVisible = true;
                    if (method_exists($sub_cat, 'is_visible')) {
                        $isVisible = (bool) $sub_cat->is_visible();
                    } elseif (method_exists($sub_cat, 'get_visible')) {
                        $isVisible = (bool) $sub_cat->get_visible();
                    }

                    $subCatWeight = (float) $sub_cat->get_weight();
                    if (!$isVisible || $subCatWeight <= 0) {
                        continue;
                    }

                    $score = $sub_cat->calc_score($user_id);

                    // Is there a meaningful score? (avoid 0/0 artefacts)
                    $hasValidScore = isset($score[1]) && $score[1] > 0 && isset($score[0]);

                    // Safe denominator.
                    $divide = $hasValidScore ? $score[1] : 1;

                    // Base score ratio (0..1).
                    $ratio = isset($score[0]) ? $score[0] / $divide : 0;

                    // Contribution = score ratio * sub-category weight.
                    $item_value = $ratio * $subCatWeight;

                    // Accumulate sub-category weight into total.
                    $item_total += $subCatWeight;

                    // -----------------------------------------------------------------
                    // Build the visible cell content for this subcategory.
                    // If score model is enabled → label (Good, VeryBad, etc.).
                    // If not → show plain numeric value (percent).
                    // -----------------------------------------------------------------
                    if ($hasScoreModel && $hasValidScore) {
                        // Use course score model label.
                        $temp_score = ExerciseLib::show_score($score[0], $score[1]);
                    } else {
                        if (!$hasValidScore) {
                            // No attempts yet → keep cell empty.
                            $temp_score = '';
                        } else {
                            // Numeric fallback: show percent (or can be SCORE_SIMPLE if you prefer).
                            $temp_score = $scoreDisplay->display_score(
                                $score,
                                SCORE_DIV_PERCENT,
                                true
                            );
                        }
                    }

                    if (
                        !isset($this->params['only_total_category']) ||
                        (isset($this->params['only_total_category']) &&
                            false == $this->params['only_total_category'])
                    ) {
                        if (!$show_all) {
                            // Normal flat view: one column per subcategory.
                            $row[] = $temp_score . ' ';
                        } else {
                            $row[] = $temp_score;
                        }
                    }
                    $item_value_total += $item_value;
                }
            } else {
                // Case 2: no subcategories → all evaluations/links handled by parseEvaluations().
                $result = $this->parseEvaluations(
                    $user_id,
                    $sum_categories_weight_array,
                    $items_count,
                    $items_start,
                    $show_all,
                    $row
                );
                $item_value_total += $result['item_value_total'];
                $evaluationsAdded = $result['evaluations_added'];
                $item_total = $mainWeightForItems;
            }

            // Additional evaluations (for main course category if defined).
            $result = $this->parseEvaluations(
                $user_id,
                $sum_categories_weight_array,
                $items_count,
                $items_start,
                $show_all,
                $row,
                $mainCategoryId,
                $evaluationsAdded
            );

            $item_total += $result['item_total'];
            $item_value_total += $result['item_value_total'];
            $total_score = [$item_value_total, $item_total];

            // -----------------------------------------------------------------
            // Total column:
            //  - with model: label using ExerciseLib::show_score()
            //  - without model: numeric percent
            // -----------------------------------------------------------------
            if ($hasScoreModel) {
                $displayScore = ExerciseLib::show_score($total_score[0], $total_score[1]);
            } else {
                $displayScore = $scoreDisplay->display_score(
                    $total_score,
                    SCORE_DIV_PERCENT,
                    true
                );
            }

            if ($export_to_pdf) {
                $row['total'] = $displayScore;
            } else {
                $row[] = $displayScore;
            }

            // Extra “custom standalone” column (unchanged).
            $style = api_get_setting('gradebook.gradebook_report_score_style');
            $customDisplayIsStandalone =
                ('true' === api_get_setting('gradebook.gradebook_score_display_custom_standalone')) &&
                $scoreDisplay->is_custom();

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
        $evaluationsAlreadyAdded = []
    ) {
        // Generate actual data array
        $scoreDisplay = ScoreDisplay::instance();
        $item_total = 0;
        $item_value_total = 0;
        $evaluationsAdded = [];
        $model = ExerciseLib::getCourseScoreModel();
        $style = api_get_setting('gradebook.gradebook_report_score_style');

        $defaultStyle = SCORE_DIV_SIMPLE_WITH_CUSTOM;
        if (!empty($style)) {
            $defaultStyle = (int) $style;
        }
        //$showPercentage = api_get_setting('gradebook_show_percentage_in_reports');
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

            $complete_score = $scoreDisplay->display_score(
                $score,
                SCORE_DIV_PERCENT,
                SCORE_ONLY_SCORE
            );

            //if ('false' === $showPercentage) {
                $defaultShowPercentageValue = SCORE_SIMPLE;
                if (!empty($style)) {
                    $defaultShowPercentageValue = $style;
                }
                $real_score = $scoreDisplay->display_score(
                    $real_score,
                    $defaultShowPercentageValue
                );
                $temp_score = $scoreDisplay->display_score(
                    [$item_value, null],
                    SCORE_DIV_SIMPLE_WITH_CUSTOM
                );
                $temp_score = Display::tip($real_score, $temp_score);
            /*} else {
                $temp_score = $scoreDisplay->display_score(
                    $real_score,
                    $defaultStyle
                );
                $temp_score = Display::tip($temp_score, $complete_score);
            }*/

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
        // Copy users into a sortable table.
        $usertable = [];
        foreach ($this->users as $user) {
            $usertable[] = $user;
        }

        // Sort users array.
        usort($usertable, ['FlatViewDataGenerator', 'sort_by_first_name']);

        $data = [];

        $selected_users = $usertable;
        foreach ($selected_users as $user) {
            $row = [];
            $row[] = $user[0]; // user id
            $item_value = 0;
            $item_total = 0;

            for ($count = 0; $count < count($this->evals_links); $count++) {
                /** @var AbstractLink $item */
                $item = $this->evals_links[$count];
                $score = $item->calc_score($user[0]);

                $scoreDenom = (isset($score[1]) && $score[1] > 0) ? $score[1] : 1;
                $scoreNum = isset($score[0]) ? $score[0] : 0;

                $item_value += $scoreNum / $scoreDenom * $item->get_weight();
                $item_total += $item->get_weight();

                $score_final = $scoreDenom > 0 ? ($scoreNum / $scoreDenom) * 100 : 0;
                $row[] = $score_final;
            }

            // Avoid division by zero when no weighted items are present.
            $score_final = $item_total > 0 ? ($item_value / $item_total) * 100 : 0;

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
        $session_id = api_get_session_id();

        // Copy users into a sortable table.
        $usertable = [];
        foreach ($this->users as $user) {
            $usertable[] = $user;
        }

        // Sort users array.
        usort($usertable, ['FlatViewDataGenerator', 'sort_by_first_name']);

        // Generate actual data array.
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
                api_get_course_int_id(),
                $session_id,
                'ORDER BY id'
            );
            $parent_id = $this->category->get_parent_id();

            // Case 1: root category with subcategories.
            if (0 == $parent_id && !empty($allcat)) {
                foreach ($allcat as $sub_cat) {
                    // Skip zero-weight or invisible subcategories in graph data
                    $isVisible = true;
                    if (method_exists($sub_cat, 'is_visible')) {
                        $isVisible = (bool) $sub_cat->is_visible();
                    } elseif (method_exists($sub_cat, 'get_visible')) {
                        $isVisible = (bool) $sub_cat->get_visible();
                    }

                    $subCatWeight = (float) $sub_cat->get_weight();
                    if (!$isVisible || $subCatWeight <= 0) {
                        continue;
                    }

                    $score = $sub_cat->calc_score($user[0]);
                    $real_score = $score;

                    $main_weight = $this->category->get_weight();
                    if (0 == $main_weight) {
                        $main_weight = 1;
                    }

                    $divide = (isset($score[1]) && $score[1] > 0) ? $score[1] : 1;

                    // Contribution value scaled by main weight.
                    $item_value = (isset($score[0]) ? $score[0] / $divide : 0) * $main_weight;
                    $item_total += $subCatWeight;

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
                    $final_score += isset($score[0]) ? $score[0] : 0;
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
                // Case 2: no subcategories → work directly with evals/links.
                for ($count = 0; $count < count($this->evals_links); $count++) {
                    /** @var AbstractLink $item */
                    $item = $this->evals_links[$count];
                    $score = $item->calc_score($user[0]);
                    $score_final = null;
                    $displayScore = null;

                    if (null !== $score) {
                        $scoreDenom = (isset($score[1]) && $score[1] > 0) ? $score[1] : 1;
                        $scoreNum = isset($score[0]) ? $score[0] : 0;

                        $item_value += $scoreNum / $scoreDenom * $item->get_weight();
                        $item_total += $item->get_weight();
                        $score_final = $scoreDenom > 0 ? ($scoreNum / $scoreDenom) * 100 : 0;

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
                $score_final = $item_total > 0 ? ($item_value / $item_total) * 100 : 0;

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
        $extraFieldColumns = api_get_setting('gradebook.gradebook_flatview_extrafields_columns', true);

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
        $extraFieldColumns = api_get_setting('gradebook.gradebook_flatview_extrafields_columns', true);

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

    /**
     * Collect all weights from evals/links currently loaded in this flat view.
     * Used to detect the scale (ratio 0..1 vs percent-like 0..100).
     */
    private function getAllEvalLinkWeights(): array
    {
        $weights = [];

        foreach ($this->evals_links as $item) {
            if ($item && method_exists($item, 'get_weight')) {
                $weights[] = (float) $item->get_weight();
            }
        }

        return $weights;
    }

    /**
     * Normalize the category/main weight to match the scale used by item weights.
     *
     * Why:
     * - Some installs store category weight as 1 (meaning 100%),
     *   while item/link weights are stored as 100 (meaning 100%).
     * - This causes 100 * 100 / 1 => 10000% in headers and totals.
     *
     * Heuristic:
     * - If mainWeight <= 1 and items contain values > 1 => treat mainWeight as ratio and upscale to percent.
     * - If mainWeight > 1 and items look fractional (0 < w < 1 or decimals) => treat items as ratio and downscale mainWeight.
     */
    private function normalizeMainWeightForItems(float $mainWeight, array $itemWeights): float
    {
        if ($mainWeight <= 0.0) {
            return 1.0;
        }

        $max = 0.0;
        $hasFractionalRatio = false;

        foreach ($itemWeights as $w) {
            $wf = (float) $w;
            if ($wf > $max) {
                $max = $wf;
            }

            // Detect ratio-style weights like 0.25, 0.5, 0.33...
            if ($wf > 0.0 && ($wf < 1.0 || abs($wf - floor($wf)) > 0.00001)) {
                $hasFractionalRatio = true;
            }
        }

        // main weight stored as ratio (<=1) but items stored as percent-like (>1)
        if ($mainWeight <= 1.0 && $max > 1.0) {
            return $mainWeight * 100.0;
        }

        // main weight stored as percent-like (>1) but items stored as ratios (fractional <=1)
        if ($mainWeight > 1.0 && $max <= 1.0 && $hasFractionalRatio) {
            return $mainWeight / 100.0;
        }

        return $mainWeight;
    }
}
