<?php

/* For licensing terms, see license.txt */

use ChamiloSession as Session;
use CpChart\Cache as pCache;
use CpChart\Data as pData;
use CpChart\Image as pImage;

/**
 * GradebookTable Class
 * Table to display categories, evaluations and links.
 *
 * @author Stijn Konings
 * @author Bert Steppé (refactored, optimised)
 */
class GradebookTable extends SortableTable
{
    public $cats;
    public $exportToPdf;
    public $teacherView;
    public $userId;
    public $studentList = [];
    private $currentcat;
    private $datagen;
    private $evals_links;
    private $dataForGraph;
    /**
     * @var array Indicates which columns should be shown in gradebook
     *
     * @example [1] To add Ranking column
     *          [2] To add Best Score column
     *          [3] To add Average column
     */
    private $loadStats = [];

    /**
     * GradebookTable constructor.
     *
     * @param Category $currentcat
     * @param array    $cats
     * @param array    $evals
     * @param array    $links
     * @param null     $addparams
     * @param bool     $exportToPdf
     * @param null     $showTeacherView
     * @param int      $userId
     * @param array    $studentList
     */
    public function __construct(
        $currentcat,
        $cats = [],
        $evals = [],
        $links = [],
        $addparams = null,
        $exportToPdf = false,
        $showTeacherView = null,
        $userId = null,
        $studentList = [],
        array $loadStats = []
    ) {
        $this->teacherView = is_null($showTeacherView) ? api_is_allowed_to_edit(null, true) : $showTeacherView;
        $this->userId = is_null($userId) ? api_get_user_id() : $userId;
        $this->exportToPdf = $exportToPdf;
        $this->studentList = $studentList;

        parent::__construct(
            'gradebooklist',
            null,
            null,
            api_is_allowed_to_edit() ? 1 : 0,
            1000,
            'ASC',
            'gradebook_list'
        );

        $this->evals_links = array_merge($evals, $links);
        $this->currentcat = $currentcat;
        $this->cats = $cats;
        $this->loadStats = $loadStats;
        $this->datagen = new GradebookDataGenerator($cats, $evals, $links);
        $this->datagen->exportToPdf = $this->exportToPdf;
        $this->datagen->preLoadDataKey = $this->getPreloadDataKey();
        $this->datagen->hidePercentage = api_get_configuration_value('hide_gradebook_percentage_user_result');

        if (!empty($userId)) {
            $this->datagen->userId = $userId;
        }

        if (isset($addparams)) {
            $this->set_additional_parameters($addparams ?: []);
        }

        $column = 0;
        if ($this->teacherView) {
            if (false == $this->exportToPdf) {
                $this->set_header($column++, '', '', 'width="25px"');
            }
        }

        $styleTextRight = ['style' => 'text-align: right;'];
        $styleTextRight100 = ['style' => 'text-align: right; width: 100px;'];
        $styleTextRight120 = ['style' => 'text-align: right; width: 120px;'];
        $styleCenterRight = ['style' => 'text-align: center;'];

        $this->set_header($column++, get_lang('Type'), '', 'width="20px"');
        $this->set_header($column++, get_lang('Name'), false);
        if (false == $this->exportToPdf) {
            $this->set_header($column++, get_lang('Description'), false);
        }

        $model = ExerciseLib::getCourseScoreModel();
        $settings = api_get_configuration_value('gradebook_pdf_export_settings');
        $showWeight = true;
        if ($this->exportToPdf && isset($settings['hide_score_weight']) && $settings['hide_score_weight']) {
            $showWeight = false;
        }
        if ($showWeight) {
            $this->set_header($column++, get_lang('Weight'), false, $styleTextRight100, $styleTextRight100);
        }

        if (!$this->teacherView) {
            $this->set_header($column++, get_lang('Result'), false, $styleTextRight, $styleTextRight);
        }

        if (empty($model)) {
            if (in_array(1, $this->loadStats)) {
                $this->set_header($column++, get_lang('Ranking'), false, $styleTextRight100, $styleTextRight100);
            }
            if (in_array(2, $this->loadStats)) {
                $this->set_header($column++, get_lang('BestScore'), false, $styleTextRight120, $styleTextRight120);
            }
            if (in_array(3, $this->loadStats)) {
                $this->set_header($column++, get_lang('Average'), false, $styleTextRight100, $styleTextRight100);
            }
        }

        if ($this->teacherView) {
        } else {
            if (!empty($cats)) {
                if ($this->exportToPdf == false) {
                    $this->set_header($column++, get_lang('Actions'), false, $styleCenterRight, $styleCenterRight);
                }
            }
        }

        // Deactivates the odd/even alt rows in order that the +/- buttons work see #4047
        $this->odd_even_rows_enabled = false;

        // Admins get an edit column.
        if ($this->teacherView) {
            $this->set_header($column++, get_lang('Modify'), false, 'width="195px"');
            // Actions on multiple selected documents.
            $this->set_form_actions(
                [
                    'setvisible' => get_lang('SetVisible'),
                    'setinvisible' => get_lang('SetInvisible'),
                    'deleted' => get_lang('DeleteSelected'),
                ]
            );
        } else {
            if (empty($_GET['selectcat']) && !$this->teacherView) {
                if ($this->exportToPdf == false) {
                    $this->set_header(
                        $column++,
                        get_lang('Certificates'),
                        false
                    );
                }
            }
        }
    }

    /**
     * @return GradebookDataGenerator
     */
    public function get_data()
    {
        return $this->datagen;
    }

    /**
     * Function used by SortableTable to get total number of items in the table.
     *
     * @return int
     */
    public function get_total_number_of_items()
    {
        return $this->datagen->get_total_items_count();
    }

    /**
     * @return string
     */
    public function getPreloadDataKey()
    {
        return 'default_data_'.api_get_course_id().'_'.api_get_session_id();
    }

    public function preloadData()
    {
        $allitems = $this->datagen->items;
        usort($allitems, ['GradebookDataGenerator', 'sort_by_name']);
        $visibleItems = array_merge($this->datagen->items, $this->evals_links);
        $defaultDataFromSession = Session::read($this->getPreloadDataKey());
        if (empty($defaultDataFromSession)) {
            $defaultData = [];
            /** @var GradebookItem $item */
            foreach ($visibleItems as $item) {
                $item->setStudentList($this->studentList);
                $itemType = get_class($item);
                switch ($itemType) {
                    case 'Evaluation':
                        // Best
                        $best = $this->datagen->buildBestResultColumn($item);
                        $defaultData[$item->get_id()]['best'] = $best;
                        // Average
                        $average = $this->datagen->buildAverageResultColumn($item);
                        $defaultData[$item->get_id()]['average'] = $average;
                        break;
                    case 'ExerciseLink':
                        /** @var ExerciseLink $item */
                        // Best
                        $best = $this->datagen->buildBestResultColumn($item);
                        $defaultData[$item->get_id()]['best'] = $best;
                        // Average
                        $average = $this->datagen->buildAverageResultColumn($item);
                        $defaultData[$item->get_id()]['average'] = $average;
                        // Ranking
                        /*if (!empty($this->studentList)) {
                            $invalidateRanking = true;
                            foreach ($this->studentList as $user) {
                                $score = $this->datagen->build_result_column(
                                    $user['user_id'],
                                    $item,
                                    false,
                                    true
                                );
                                if (!empty($score['score'])) {
                                    $invalidateRanking = false;
                                }
                                $rankingStudentList[$user['user_id']] = $score['score'][0];
                                $defaultData[$item->get_id()]['ranking'] = $rankingStudentList;
                                $defaultData[$item->get_id()]['ranking_invalidate'] = $invalidateRanking;
                            }
                        }*/
                        break;
                    default:
                        // Best
                        $best = $this->datagen->buildBestResultColumn($item);
                        $defaultData[$item->get_id()]['best'] = $best;

                        // Average
                        $average = $this->datagen->buildAverageResultColumn($item);
                        $defaultData[$item->get_id()]['average'] = $average;

                        // Ranking
                        if (!empty($this->studentList)) {
                            $invalidateRanking = true;
                            foreach ($this->studentList as $user) {
                                $score = $this->datagen->build_result_column(
                                    $user['user_id'],
                                    $item,
                                    false,
                                    true
                                );
                                if (!empty($score['score'])) {
                                    $invalidateRanking = false;
                                }
                                $rankingStudentList[$user['user_id']] = $score['score'][0];
                                $defaultData[$item->get_id()]['ranking'] = $rankingStudentList;
                                $defaultData[$item->get_id()]['ranking_invalidate'] = $invalidateRanking;
                            }
                        }
                        break;
                }
            }
            Session::write($this->getPreloadDataKey(), $defaultData);
        } else {
            $defaultData = $defaultDataFromSession;
        }

        return $defaultData;
    }

    /**
     * Function used by SortableTable to generate the data to display.
     *
     * @param int    $from
     * @param int    $per_page
     * @param int    $column
     * @param string $direction
     * @param int    $sort
     *
     * @return array|mixed
     */
    public function get_table_data($from = 1, $per_page = null, $column = null, $direction = null, $sort = null)
    {
        //variables load in index.php
        global $certificate_min_score;

        $isAllowedToEdit = api_is_allowed_to_edit();
        $hideLinkForStudent = api_get_configuration_value('gradebook_hide_link_to_item_for_student') ?? false;
        // determine sorting type
        $col_adjust = $isAllowedToEdit ? 1 : 0;
        // By id
        $this->column = 5;

        switch ($this->column) {
            // Type
            case 0 + $col_adjust:
                $sorting = GradebookDataGenerator::GDG_SORT_TYPE;
                break;
            case 1 + $col_adjust:
                $sorting = GradebookDataGenerator::GDG_SORT_NAME;
                break;
            case 2 + $col_adjust:
                $sorting = GradebookDataGenerator::GDG_SORT_DESCRIPTION;
                break;
            case 3 + $col_adjust:
                $sorting = GradebookDataGenerator::GDG_SORT_WEIGHT;
                break;
            case 4 + $col_adjust:
                $sorting = GradebookDataGenerator::GDG_SORT_DATE;
                break;
            case 5 + $col_adjust:
                $sorting = GradebookDataGenerator::GDG_SORT_ID;
                break;
        }

        if ('DESC' === $this->direction) {
            $sorting |= GradebookDataGenerator::GDG_SORT_DESC;
        } else {
            $sorting |= GradebookDataGenerator::GDG_SORT_ASC;
        }

        // Status of user in course.
        $user_id = $this->userId;
        $course_code = api_get_course_id();
        $session_id = api_get_session_id();

        $statusToFilter = 0;
        if (empty($session_id)) {
            $statusToFilter = STUDENT;
        }

        if (empty($this->studentList) && $this->loadStats) {
            $studentList = CourseManager::get_user_list_from_course_code(
                $course_code,
                $session_id,
                null,
                null,
                $statusToFilter
            );
            $this->studentList = $studentList;
        }

        $this->datagen->userId = $this->userId;
        $data_array = $this->datagen->get_data(
            $sorting,
            $from,
            $this->per_page,
            false,
            $this->studentList,
            $this->loadStats
        );

        // generate the data to display
        $sortable_data = [];
        $weight_total_links = 0;
        $main_cat = Category::load(
            null,
            null,
            $course_code,
            null,
            null,
            $session_id,
            'ORDER BY id'
        );

        $total_categories_weight = 0;
        $scoredisplay = ScoreDisplay::instance();
        $totalBest = [0, 0];
        $totalAverage = [0, 0];

        $type = 'detail';
        if ($this->exportToPdf) {
            $type = 'simple';
        }

        $model = ExerciseLib::getCourseScoreModel();
        $userExerciseScoreInCategory = api_get_configuration_value(
            'gradebook_use_exercise_score_settings_in_categories'
        );
        $useExerciseScoreInTotal = api_get_configuration_value('gradebook_use_exercise_score_settings_in_total');
        $course_code = api_get_course_id();
        $session_id = api_get_session_id();
        $defaultData = Session::read($this->getPreloadDataKey());
        $settings = api_get_configuration_value('gradebook_pdf_export_settings');
        $showWeight = true;
        if ($this->exportToPdf && isset($settings['hide_score_weight']) && $settings['hide_score_weight']) {
            $showWeight = false;
        }
        $totalAverageList = [];
        // Categories.
        if (!empty($data_array)) {
            foreach ($data_array as $data) {
                // list of items inside the gradebook (exercises, lps, forums, etc)
                $row = [];
                /** @var AbstractLink $item */
                $item = $data[0];
                // If the item is invisible, wrap it in a span with class invisible
                $invisibility_span_open = $isAllowedToEdit && $item->is_visible() == '0' ? '<span class="text-muted">' : '';
                $invisibility_span_close = $isAllowedToEdit && $item->is_visible() == '0' ? '</span>' : '';

                if ($this->teacherView) {
                    if (false == $this->exportToPdf) {
                        $row[] = $this->build_id_column($item);
                    }
                }

                // Type.
                $row[] = $this->build_type_column($item);

                // Name.
                if ('Category' === get_class($item)) {
                    $row[] = $invisibility_span_open.
                        '<strong>'.Security::remove_XSS($item->get_name()).'</strong>'.$invisibility_span_close;
                    $main_categories[$item->get_id()]['name'] = $item->get_name();
                } else {

                    // If the item type is 'Evaluation', or the user is not a student,
                    // or 'gradebook_hide_link_to_item_for_student' it's true, make links
                    if ($item->get_item_type() === 'E' || $isAllowedToEdit || !$hideLinkForStudent) {
                        $name = Security::remove_XSS($this->build_name_link($item, $type));
                    } else {
                        $name = Security::remove_XSS(
                            $item->get_name().' '.Display::label($item->get_type_name(), 'info')
                        );
                    }

                    $row[] = $invisibility_span_open.$name.$invisibility_span_close;
                    $main_categories[$item->get_id()]['name'] = $name;
                }

                $this->dataForGraph['categories'][] = $item->get_name();
                $main_categories[$item->get_id()]['weight'] = $item->get_weight();
                $total_categories_weight += $item->get_weight();

                // Description.
                if (false == $this->exportToPdf) {
                    $row[] = $invisibility_span_open.$data[2].$invisibility_span_close;
                }

                // Weight.
                $weight = $scoredisplay->display_score(
                    [
                        $data['3'],
                        $this->currentcat->get_weight(),
                    ],
                    SCORE_SIMPLE,
                    SCORE_BOTH,
                    true
                );

                if ($showWeight) {
                    if ($this->teacherView) {
                        $row[] = $invisibility_span_open.
                            Display::tag('p', $weight, ['class' => 'score']).
                            $invisibility_span_close;
                    } else {
                        $row[] = $invisibility_span_open.$weight.$invisibility_span_close;
                    }
                }

                $category_weight = $item->get_weight();
                if ($this->teacherView) {
                    $weight_total_links += $data[3];
                }

                // Edit (for admins).
                if ($this->teacherView) {
                    $cat = new Category();
                    $show_message = $cat->show_message_resource_delete($item->get_course_code());
                    if ($show_message === false) {
                        $row[] = $this->build_edit_column($item);
                    }
                } else {
                    // Students get the results and certificates columns
                    $value_data = isset($data[4]) ? $data[4] : null;
                    $best = isset($data['best']) ? $data['best'] : null;
                    $average = isset($data['average']) ? $data['average'] : null;
                    $ranking = isset($data['ranking']) ? $data['ranking'] : null;

                    $totalResult = [
                        $data['result_score'][0] ?? null,
                        $data['result_score'][1] ?? null,
                    ];

                    if (empty($model)) {
                        $totalBest = [
                            $scoredisplay->format_score($totalBest[0] + (empty($data['best_score'][0]) ? 0 : $data['best_score'][0])),
                            $scoredisplay->format_score($totalBest[1] + (empty($data['best_score'][1]) ? 0 : $data['best_score'][1])),
                        ];
                        $totalAverage = [0, 0];
                        if (isset($data['average_score']) && !empty($data['average_score'])) {
                            $totalAverage = [
                                $data['average_score'][0],
                                $data['average_score'][1],
                            ];
                        }
                    }

                    // Score
                    if (empty($model)) {
                        $row[] = $value_data;
                    } else {
                        $row[] = ExerciseLib::show_score(
                            $data['result_score'][0],
                            $data['result_score'][1]
                        );
                    }

                    $totalAverageList[$item->get_id()] = $totalAverage;
                    $mode = SCORE_AVERAGE;
                    if ($userExerciseScoreInCategory) {
                        $mode = SCORE_SIMPLE;
                        $result = ExerciseLib::convertScoreToPlatformSetting($totalAverage[0], $totalAverage[1]);
                        $totalAverage[0] = $result['score'];
                        $totalAverage[1] = $result['weight'];

                        $result = ExerciseLib::convertScoreToPlatformSetting($totalResult[0], $totalResult[1]);
                        $totalResult[0] = $result['score'];
                        $totalResult[1] = $result['weight'];

                        $result = ExerciseLib::convertScoreToPlatformSetting(
                            $data['result_score'][0],
                            $data['result_score'][1]
                        );
                        $data['my_result_no_float'][0] = $result['score'];
                    }

                    $totalResultAverageValue = strip_tags(
                        $scoredisplay->display_score($totalResult, $mode, null, false, false, true)
                    );
                    $totalAverageValue = strip_tags(
                        $scoredisplay->display_score($totalAverage, $mode, null, false, false, true)
                    );

                    $this->dataForGraph['my_result'][] = floatval($totalResultAverageValue);
                    $this->dataForGraph['average'][] = floatval($totalAverageValue);
                    $this->dataForGraph['my_result_no_float'][] = $data['result_score'][0] ?? null;

                    if (empty($model)) {
                        // Ranking
                        if (in_array(1, $this->loadStats)) {
                            $row[] = $ranking;
                        }

                        // Best
                        if (in_array(2, $this->loadStats)) {
                            $row[] = $best;
                        }

                        // Average
                        if (in_array(3, $this->loadStats)) {
                            $row[] = $average;
                        }
                    }

                    if ('Category' === get_class($item)) {
                        if (false == $this->exportToPdf) {
                            $row[] = $this->build_edit_column($item);
                        }
                    }
                }

                // Category added.
                $sortable_data[] = $row;

                // Loading children
                if ('Category' === get_class($item)) {
                    $parent_id = $item->get_id();
                    $cats = Category::load(
                        $parent_id,
                        null,
                        null,
                        null,
                        null,
                        null
                    );

                    if (isset($cats[0])) {
                        /** @var Category $subCategory */
                        $subCategory = $cats[0];
                        $allcat = $subCategory->get_subcategories($this->userId, $course_code, $session_id);
                        $alleval = $subCategory->get_evaluations($this->userId);
                        $alllink = $subCategory->get_links($this->userId);

                        $sub_cat_info = new GradebookDataGenerator($allcat, $alleval, $alllink);
                        $sub_cat_info->exportToPdf = $this->exportToPdf;
                        $sub_cat_info->preLoadDataKey = $this->getPreloadDataKey();
                        $sub_cat_info->userId = $user_id;

                        $data_array2 = $sub_cat_info->get_data(
                            $sorting,
                            $from,
                            $this->per_page,
                            false,
                            $this->studentList
                        );
                        $total_weight = 0;

                        // Links.
                        foreach ($data_array2 as $data) {
                            $row = [];
                            $item = $data[0];
                            // if the item is invisible, wrap it in a span with class invisible
                            $invisibility_span_open = $isAllowedToEdit && $item->is_visible() == '0' ? '<span class="text-muted">' : '';
                            $invisibility_span_close = $isAllowedToEdit && $item->is_visible() == '0' ? '</span>' : '';

                            if (isset($item)) {
                                $main_categories[$parent_id]['children'][$item->get_id()]['name'] = $item->get_name();
                                $main_categories[$parent_id]['children'][$item->get_id()]['weight'] = $item->get_weight();
                            }

                            if ($this->teacherView) {
                                if (false == $this->exportToPdf) {
                                    $row[] = $this->build_id_column($item);
                                }
                            }

                            // Type
                            $row[] = $this->build_type_column($item, ['style' => 'padding-left:5px']);
                            // Name.
                            $row[] = $invisibility_span_open.'&nbsp;&nbsp;&nbsp; '.
                                Security::remove_XSS($this->build_name_link($item, $type, 4)).$invisibility_span_close;

                            // Description.
                            if (false == $this->exportToPdf) {
                                $row[] = $invisibility_span_open.$data[2].$invisibility_span_close;
                            }

                            $weight = $data[3];
                            $total_weight += $weight;

                            // Weight
                            if ($showWeight) {
                                $row[] = $invisibility_span_open.$weight.$invisibility_span_close;
                            }

                            // Admins get an edit column.
                            if (api_is_allowed_to_edit(null, true) &&
                                isset($_GET['user_id']) == false &&
                                (isset($_GET['action']) && $_GET['action'] != 'export_all' || !isset($_GET['action']))
                            ) {
                                $cat = new Category();
                                $show_message = $cat->show_message_resource_delete($item->get_course_code());
                                if ($show_message === false) {
                                    if ($this->exportToPdf == false) {
                                        $row[] = $this->build_edit_column($item);
                                    }
                                }
                            } else {
                                // Students get the results and certificates columns
                                $eval_n_links = array_merge($alleval, $alllink);
                                if (count($eval_n_links) > 0) {
                                    $value_data = isset($data[4]) ? $data[4] : null;
                                    if (!is_null($value_data)) {
                                        // Result
                                        $row[] = $value_data;
                                        $best = isset($data['best']) ? $data['best'] : null;
                                        $average = isset($data['average']) ? $data['average'] : null;
                                        $ranking = isset($data['ranking']) ? $data['ranking'] : null;
                                        if (empty($model)) {
                                            // Ranking
                                            if (in_array(1, $this->loadStats)) {
                                                $row[] = $ranking;
                                            }

                                            // Best
                                            if (in_array(2, $this->loadStats)) {
                                                $row[] = $best;
                                            }

                                            // Average
                                            if (in_array(3, $this->loadStats)) {
                                                $row[] = $average;
                                            }
                                        }
                                    }
                                }

                                if (!empty($cats)) {
                                    if (false == $this->exportToPdf) {
                                        $row[] = null;
                                    }
                                }
                            }

                            if (false == $this->exportToPdf) {
                                $row['child_of'] = $parent_id;
                            }
                            $sortable_data[] = $row;
                        }

                        // "Warning row"
                        if (!empty($data_array)) {
                            if ($this->teacherView) {
                                // Compare the category weight to the sum of all weights inside the category
                                if (intval($total_weight) == $category_weight) {
                                    $label = null;
                                    $total = GradebookUtils::score_badges(
                                        [
                                            $total_weight.' / '.$category_weight,
                                            '100',
                                        ]
                                    );
                                } else {
                                    $label = Display::return_icon(
                                        'warning.png',
                                        sprintf(get_lang('TotalWeightMustBeX'), $category_weight)
                                    );
                                    $total = Display::badge($total_weight.' / '.$category_weight, 'warning');
                                }
                                $row = [
                                    null,
                                    null,
                                    "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<h5>".get_lang('SubTotal').'</h5>',
                                    null,
                                    $total.' '.$label,
                                    'child_of' => $parent_id,
                                ];
                                $sortable_data[] = $row;
                            }
                        }
                    }
                }
            }
        } //end looping categories

        $main_weight = 0;
        if (count($main_cat) > 1) {
            /** @var Category $myCat */
            foreach ($main_cat as $myCat) {
                $myParentId = $myCat->get_parent_id();
                if (0 == $myParentId) {
                    $main_weight = (int) $myCat->get_weight();
                }
            }
        }

        if ($this->teacherView) {
            // Total for teacher.
            if (count($main_cat) > 1) {
                if (intval($total_categories_weight) == $main_weight) {
                    $total = GradebookUtils::score_badges(
                        [
                            $total_categories_weight.' / '.$main_weight,
                            '100',
                        ]
                    );
                } else {
                    $total = Display::badge($total_categories_weight.' / '.$main_weight, 'warning');
                }
                $row = [
                    null,
                    null,
                    '<strong>'.get_lang('Total').'</strong>',
                    null,
                    $total,
                ];
                $sortable_data[] = $row;
            }
        } else {
            $showPercentage = false === $this->datagen->hidePercentage;
            // Total for student.
            if (count($main_cat) > 1) {
                $main_weight = (int) $main_cat[0]->get_weight();
                $global = null;
                $average = null;
                $myTotal = 0;
                if (!empty($this->dataForGraph)) {
                    foreach ($this->dataForGraph['my_result_no_float'] as $result) {
                        $myTotal += $result;
                    }
                }

                $totalResult[0] = $myTotal;
                // Overwrite main weight
                $totalResult[1] = $main_weight;

                if (!empty($model)) {
                    $totalResult = ExerciseLib::show_score(
                        $totalResult[0],
                        $totalResult[1]
                    );
                } else {
                    $totalResult = $scoredisplay->display_score(
                        $totalResult,
                        SCORE_DIV,
                        null,
                        false,
                        false,
                        true
                    );

                    if ($useExerciseScoreInTotal) {
                        $totalResult = ExerciseLib::show_score($myTotal, $main_weight, false);
                    }
                }

                $row = [
                    null,
                    '<strong>'.get_lang('Total').'</strong>',
                ];

                if (!$this->exportToPdf) {
                    $row[] = null;
                }

                if ($showWeight) {
                    $row[] = $main_weight;
                }

                $row[] = $totalResult;
                $categoryId = $main_cat[0]->get_id();

                if (empty($model)) {
                    if (in_array(1, $this->loadStats)) {
                        if (isset($defaultData[$categoryId]) && isset($defaultData[$categoryId]['ranking'])) {
                            $totalRanking = $defaultData[$categoryId]['ranking'];
                            $invalidateRanking = $defaultData[$categoryId]['ranking_invalidate'];
                            $average = 0;
                            foreach ($totalRanking as $ranking) {
                                $average += $ranking;
                            }
                        } else {
                            $totalRanking = [];
                            $invalidateRanking = true;
                            $average = 0;
                            $main_cat[0]->setStudentList($this->studentList);
                            foreach ($this->studentList as $student) {
                                $score = $main_cat[0]->calc_score(
                                    $student['user_id'],
                                    null,
                                    $course_code,
                                    $session_id
                                );
                                if (!empty($score[0])) {
                                    $invalidateRanking = false;
                                }
                                $totalRanking[$student['user_id']] = $score[0];
                                $average += $score[0];
                            }
                            $defaultData[$categoryId]['ranking'] = $totalRanking;
                            $defaultData[$categoryId]['ranking_invalidate'] = $invalidateRanking;
                            Session::write($this->getPreloadDataKey(), $defaultData);
                        }

                        $totalRanking = AbstractLink::getCurrentUserRanking($user_id, $totalRanking);
                        $totalRanking = $scoredisplay->display_score(
                            $totalRanking,
                            SCORE_DIV,
                            SCORE_BOTH,
                            true,
                            true,
                            true
                        );

                        if ($invalidateRanking) {
                            $totalRanking = null;
                        }
                        $row[] = $totalRanking;
                    }

                    if (in_array(2, $this->loadStats)) {
                        if (isset($defaultData[$categoryId]) && isset($defaultData[$categoryId]['best'])) {
                            $totalBest = $defaultData[$categoryId]['best'];
                        } else {
                            // Overwrite main weight
                            $totalBest[1] = $main_weight;
                            $defaultData[$categoryId]['best'] = $totalBest;
                        }

                        if ($useExerciseScoreInTotal) {
                            if (isset($totalBest['score'])) {
                                $totalBestScore = $totalBest['score'];
                            } else {
                                $totalBestScore = $totalBest;
                            }

                            $totalBest = ExerciseLib::show_score($totalBestScore[0], $totalBestScore[1], $showPercentage);
                        } else {
                            $totalBest = $scoredisplay->display_score(
                                $totalBest,
                                SCORE_DIV,
                                SCORE_BOTH,
                                true,
                                false,
                                true
                            );
                        }
                        $row[] = $totalBest;
                    }

                    if (in_array(3, $this->loadStats)) {
                        if (isset($defaultData[$categoryId]) && isset($defaultData[$categoryId]['average'])) {
                            $totalAverage = $defaultData[$categoryId]['average'];
                        } else {
                            $averageWeight = 0;
                            $categoryAverage = 0;
                            foreach ($totalAverageList as $averageScore) {
                                $categoryAverage += $averageScore[0];
                                $averageWeight += $averageScore[1];
                            }
                            $categoryAverage = $categoryAverage / count($totalAverageList);
                            //$averageWeight = $averageWeight ($totalAverageList);

                            // Overwrite main weight
                            //$totalAverage[0] = $average / count($this->studentList);
                            //$totalAverage[1] = $main_weight;
                            $totalAverage[0] = $categoryAverage;
                            $totalAverage[1] = $averageWeight;
                            //$defaultData[$categoryId]['average'] = $totalBest;
                        }

                        if ($useExerciseScoreInTotal) {
                            if (isset($totalAverage['score'])) {
                                $totalAverageScore = $totalAverage['score'];
                            } else {
                                $totalAverageScore = $totalAverage;
                            }

                            $totalAverage = ExerciseLib::show_score($totalAverageScore[0], $totalAverageScore[1], $showPercentage);
                        } else {
                            $totalAverage = $scoredisplay->display_score(
                                $totalAverage,
                                SCORE_DIV,
                                SCORE_BOTH,
                                true,
                                false,
                                true
                            );
                        }

                        $row[] = $totalAverage;
                    }
                }

                if (!empty($row)) {
                    $sortable_data[] = $row;
                }
            }
        }

        Session::write('default_data', $defaultData);

        // Warning messages
        $view = isset($_GET['view']) ? $_GET['view'] : null;
        if ($this->teacherView) {
            if (isset($_GET['selectcat']) &&
                $_GET['selectcat'] > 0 &&
                $view !== 'presence'
            ) {
                $id_cat = (int) $_GET['selectcat'];
                $category = Category::load($id_cat);
                $weight_category = (int) $this->build_weight($category[0]);
                $course_code = $this->build_course_code($category[0]);
                $weight_total_links = round($weight_total_links);

                if ($weight_total_links > $weight_category ||
                    $weight_total_links < $weight_category ||
                    $weight_total_links > $weight_category
                ) {
                    $warning_message = sprintf(get_lang('TotalWeightMustBeX'), $weight_category);
                    $modify_icons =
                        '<a
                        href="gradebook_edit_cat.php?editcat='.$id_cat.'&cidReq='.$course_code.'&id_session='.api_get_session_id().'">'.
                        Display::return_icon('edit.png', $warning_message, [], ICON_SIZE_SMALL).'</a>';
                    $warning_message .= $modify_icons;
                    echo Display::return_message($warning_message, 'warning', false);
                }

                $content_html = DocumentManager::replace_user_info_into_html(
                    api_get_user_id(),
                    $course_code,
                    api_get_session_id()
                );

                if (!empty($content_html)) {
                    $new_content = explode('</head>', $content_html['content']);
                }

                if (empty($new_content[0])) {
                    // Set default certificate
                    $courseData = api_get_course_info($course_code);
                    DocumentManager::generateDefaultCertificate($courseData);
                }
            }

            if (empty($_GET['selectcat'])) {
                $categories = Category::load();
                $weight_categories = $certificate_min_scores = $course_codes = [];
                foreach ($categories as $category) {
                    $course_code_category = $this->build_course_code($category);
                    if (!empty($course_code)) {
                        if ($course_code_category == $course_code) {
                            $weight_categories[] = intval($this->build_weight($category));
                            $certificate_min_scores[] = intval($this->build_certificate_min_score($category));
                            $course_codes[] = $course_code;
                            break;
                        }
                    } else {
                        $weight_categories[] = intval($this->build_weight($category));
                        $certificate_min_scores[] = intval($this->build_certificate_min_score($category));
                        $course_codes[] = $course_code_category;
                    }
                }

                if (is_array($weight_categories) &&
                    is_array($certificate_min_scores) &&
                    is_array($course_codes)
                ) {
                    $warning_message = '';
                    for ($x = 0; $x < count($weight_categories); $x++) {
                        $weight_category = intval($weight_categories[$x]);
                        $certificate_min_score = intval($certificate_min_scores[$x]);
                        $course_code = $course_codes[$x];

                        if (empty($certificate_min_score) ||
                            ($certificate_min_score > $weight_category)
                        ) {
                            $warning_message .= $course_code.
                                '&nbsp;-&nbsp;'.get_lang('CertificateMinimunScoreIsRequiredAndMustNotBeMoreThan').
                                '&nbsp;'.$weight_category.'<br />';
                        }
                    }

                    if (!empty($warning_message)) {
                        echo Display::return_message($warning_message, 'warning', false);
                    }
                }
            }
        }

        return $sortable_data;
    }

    /**
     * @return string
     */
    public function getGraph()
    {
        $data = $this->getDataForGraph();
        if (!empty($data) &&
            isset($data['categories']) &&
            isset($data['my_result']) &&
            isset($data['average'])
        ) {
            $dataSet = new pData();
            $dataSet->addPoints($data['my_result'], get_lang('Me'));
            // In order to generate random values
            // $data['average'] = array(rand(0,50), rand(0,50));
            $dataSet->addPoints($data['average'], get_lang('Average'));
            $dataSet->addPoints($data['categories'], 'categories');
            $dataSet->setAbscissa('categories');
            $xSize = 700;
            $ySize = 500;
            $pChart = new pImage($xSize, $ySize, $dataSet);
            /* Turn of Antialiasing */
            $pChart->Antialias = false;

            /* Add a border to the picture */
            $pChart->drawRectangle(
                0,
                0,
                $xSize - 1,
                $ySize - 1,
                ["R" => 0, "G" => 0, "B" => 0]
            );
            $pChart->drawText(
                80,
                16,
                get_lang('Results'),
                ["FontSize" => 11, "Align" => TEXT_ALIGN_BOTTOMMIDDLE]
            );
            $pChart->setGraphArea(50, 30, $xSize - 50, $ySize - 70);
            $pChart->setFontProperties(
                [
                    'FontName' => api_get_path(SYS_FONTS_PATH).'Harmattan/Harmattan-Regular.ttf',
                    /*'FontName' => api_get_path(SYS_FONTS_PATH).'opensans/OpenSans-Regular.ttf',*/
                    'FontSize' => 10,
                ]
            );

            /* Draw the scale */
            $scaleSettings = [
                "XMargin" => AUTO,
                "YMargin" => 10,
                "Floating" => true,
                "GridR" => 200,
                "GridG" => 200,
                "GridB" => 200,
                "DrawSubTicks" => true,
                "CycleBackground" => true,
                'LabelRotation' => 10,
            ];
            $pChart->drawScale($scaleSettings);

            /* Draw the line chart */
            $pChart->drawLineChart();
            $pChart->drawPlotChart(
                [
                    "DisplayValues" => true,
                    "PlotBorder" => true,
                    "BorderSize" => 2,
                    "Surrounding" => -60,
                    "BorderAlpha" => 80,
                ]
            );

            /* Write the chart legend */
            $pChart->drawLegend(
                $xSize - 180,
                9,
                [
                    "Style" => LEGEND_NOBORDER,
                    "Mode" => LEGEND_HORIZONTAL,
                    "FontR" => 0,
                    "FontG" => 0,
                    "FontB" => 0,
                ]
            );

            $cachePath = api_get_path(SYS_ARCHIVE_PATH);
            $myCache = new pCache(['CacheFolder' => substr($cachePath, 0, strlen($cachePath) - 1)]);
            $chartHash = $myCache->getHash($dataSet);

            $myCache->writeToCache($chartHash, $pChart);
            $imgSysPath = api_get_path(SYS_ARCHIVE_PATH).$chartHash;
            $myCache->saveFromCache($chartHash, $imgSysPath);
            $imgWebPath = api_get_path(WEB_ARCHIVE_PATH).$chartHash;

            if (file_exists($imgSysPath)) {
                $result = '<br /><div id="contentArea" style="text-align: center;" >';
                $result .= '<img src="'.$imgWebPath.'" >';
                $result .= '</div>';

                return $result;
            }
        }

        return '';
    }

    public static function getExtraStatsColumnsToDisplay(): array
    {
        if (api_get_configuration_value('gradebook_enable_best_score') === true) {
            return [2];
        }

        $gradebookDisplayExtraStats = api_get_configuration_value('gradebook_display_extra_stats');

        /** @see GradebookTable::$loadStats */
        return $gradebookDisplayExtraStats['columns'] ?? [1, 2, 3];
    }

    /**
     * @return array
     */
    private function getDataForGraph()
    {
        return $this->dataForGraph;
    }

    /**
     * @param $item
     *
     * @return mixed
     */
    private function build_certificate_min_score($item)
    {
        return $item->getCertificateMinScore();
    }

    /**
     * @param $item
     *
     * @return mixed
     */
    private function build_weight($item)
    {
        return $item->get_weight();
    }

    /**
     * @param $item
     *
     * @return mixed
     */
    private function build_course_code($item)
    {
        return $item->get_course_code();
    }

    /**
     * @param $item
     *
     * @return string
     */
    private function build_id_column($item)
    {
        switch ($item->get_item_type()) {
            // category
            case 'C':
                return 'CATE'.$item->get_id();
            // evaluation
            case 'E':
                return 'EVAL'.$item->get_id();
            // link
            case 'L':
                return 'LINK'.$item->get_id();
        }
    }

    /**
     * @param $item
     * @param array $attributes
     *
     * @return string
     */
    private function build_type_column($item, $attributes = [])
    {
        return GradebookUtils::build_type_icon_tag($item->get_icon_name(), $attributes);
    }

    /**
     * Generate name column.
     *
     * @param GradebookItem $item
     * @param string        $type simple|detail
     *
     * @return string
     */
    private function build_name_link($item, $type = 'detail', $spaces = 0)
    {
        $view = isset($_GET['view']) ? Security::remove_XSS($_GET['view']) : null;
        $categoryId = $item->getCategory()->get_id();

        $cat = new Category();

        switch ($item->get_item_type()) {
            case 'C':
                // Category
                $prms_uri = '?selectcat='.$item->get_id().'&view='.$view;
                $isStudentView = api_is_student_view_active();
                if (isset($is_student) || $isStudentView) {
                    $prms_uri = $prms_uri.'&amp;isStudentView=studentview';
                }
                $show_message = $cat->show_message_resource_delete($item->get_course_code());

                return '&nbsp;<a href="'.Category::getUrl().$prms_uri.'">'
                    .$item->get_name()
                    .'</a>'
                    .($item->is_course() ? ' &nbsp;['.$item->get_course_code().']'.$show_message : '');
            case 'E':
                // Evaluation
                $course_id = CourseManager::get_course_by_category($categoryId);
                $show_message = $cat->show_message_resource_delete($course_id);
                $skills = $item->getSkillsFromItem();

                // course/platform admin can go to the view_results page
                if (api_is_allowed_to_edit() && $show_message === false) {
                    if ($item->get_type() == 'presence') {
                        return '&nbsp;'
                            .'<a href="gradebook_view_result.php?cidReq='.$course_id.'&amp;selecteval='.$item->get_id().'">'
                            .$item->get_name()
                            .'</a>';
                    } else {
                        $extra = Display::label(get_lang('Evaluation'));
                        if ('simple' === $type) {
                            $extra = '';
                        }
                        $extra .= $skills;

                        return '&nbsp;'
                            .'<a href="gradebook_view_result.php?'.api_get_cidreq().'&selecteval='.$item->get_id().'">'
                            .$item->get_name()
                            .'</a>&nbsp;'.$extra;
                    }
                } elseif (ScoreDisplay::instance()->is_custom() && $show_message === false) {
                    // students can go to the statistics page (if custom display enabled)
                    return '&nbsp;'
                        .'<a href="gradebook_statistics.php?'.api_get_cidreq().'&selecteval='.$item->get_id().'">'
                        .$item->get_name()
                        .'</a>'.$skills;
                } elseif ($show_message === false && !api_is_allowed_to_edit() && !ScoreDisplay::instance()->is_custom()) {
                    return '&nbsp;'
                        .'<a href="gradebook_statistics.php?'.api_get_cidreq().'&selecteval='.$item->get_id().'">'
                        .$item->get_name()
                        .'</a>'.$skills;
                } else {
                    return '['.get_lang('Evaluation').']&nbsp;&nbsp;'.$item->get_name().$show_message.$skills;
                }
                // no break because of return
            case 'L':
                // Link
                $course_id = CourseManager::get_course_by_category($categoryId);
                $show_message = $cat->show_message_resource_delete($course_id);

                $url = $item->get_link();
                $text = $item->get_name();
                if (isset($url) && false === $show_message) {
                    $text = '&nbsp;<a href="'.$item->get_link().'">'
                        .$item->get_name()
                        .'</a>';
                }

                $extra = Display::label($item->get_type_name(), 'info');
                if ('simple' === $type) {
                    $extra = '';
                }
                $extra .= $item->getSkillsFromItem();
                $text .= "&nbsp;".$extra.$show_message;

                /*if ($item instanceof ExerciseLink) {
                    $spaces = str_repeat('&nbsp;', $spaces);
                    $text .= '<br /><br />'.$spaces.$item->getLpListToString();
                }*/

                $cc = $this->currentcat->get_course_code();
                if (empty($cc)) {
                    $text .= '&nbsp;[<a href="'.api_get_path(REL_COURSE_PATH).$item->get_course_code().'/">'.$item->get_course_code().'</a>]';
                }

                return $text;
        }
    }

    /**
     * @param AbstractLink $item
     *
     * @return string|null
     */
    private function build_edit_column($item)
    {
        switch ($item->get_item_type()) {
            case 'C':
                // Category
                return GradebookUtils::build_edit_icons_cat($item, $this->currentcat);
            case 'E':
                // Evaluation
                return GradebookUtils::build_edit_icons_eval($item, $this->currentcat->get_id());
            case 'L':
                // Link
                return GradebookUtils::build_edit_icons_link($item, $this->currentcat->get_id());
        }
    }
}
