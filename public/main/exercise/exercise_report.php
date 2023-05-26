<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\TrackEAttemptRecording;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CLp;

/**
 * Exercise list: This script shows the list of exercises for administrators and students.
 *
 * @author Julio Montoya <gugli100@gmail.com> jqgrid integration
 * Modified by hubert.borderiou (question category)
 *
 * @todo fix excel export
 */
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;

$htmlHeadXtra[] = api_get_jqgrid_js();

$filter_user = isset($_REQUEST['filter_by_user']) ? (int) $_REQUEST['filter_by_user'] : null;
$isBossOfStudent = false;
if (api_is_student_boss() && !empty($filter_user)) {
    // Check if boss has access to user info.
    if (UserManager::userIsBossOfStudent(api_get_user_id(), $filter_user)) {
        $isBossOfStudent = true;
    } else {
        api_not_allowed(true);
    }
} else {
    api_protect_course_script(true, false, true);
}

$limitTeacherAccess = ('true' === api_get_setting('exercise.limit_exercise_teacher_access'));
$allowClean = Exercise::allowAction('clean_results');

if ($limitTeacherAccess && !api_is_platform_admin()) {
    api_not_allowed(true);
}

$_course = api_get_course_info();

// document path
//$documentPath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
$origin = api_get_origin();
$is_allowedToEdit = api_is_allowed_to_edit(null, true) ||
    api_is_drh() ||
    api_is_student_boss() ||
    api_is_session_admin();
$is_tutor = api_is_allowed_to_edit(true);

$TBL_TRACK_EXERCISES = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$TBL_TRACK_ATTEMPT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
$TBL_TRACK_ATTEMPT_RECORDING = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
$TBL_LP_ITEM_VIEW = Database::get_course_table(TABLE_LP_ITEM_VIEW);
$allowCoachFeedbackExercises = 'true' === api_get_setting('allow_coach_feedback_exercises');
$course_id = api_get_course_int_id();
$exercise_id = isset($_REQUEST['exerciseId']) ? (int) $_REQUEST['exerciseId'] : 0;
$locked = api_resource_is_locked_by_gradebook($exercise_id, LINK_EXERCISE);
$sessionId = api_get_session_id();

if (empty($exercise_id)) {
    api_not_allowed(true);
}

$blockPage = true;
if (empty($sessionId)) {
    if ($is_allowedToEdit) {
        $blockPage = false;
    }
} else {
    if ($allowCoachFeedbackExercises && api_is_coach($sessionId, $course_id)) {
        $blockPage = false;
    } else {
        if ($is_allowedToEdit) {
            $blockPage = false;
        }
    }
}

if ($blockPage) {
    api_not_allowed(true);
}

if (!empty($exercise_id)) {
    $parameters['exerciseId'] = $exercise_id;
}

if (!empty($_GET['path'])) {
    $parameters['path'] = Security::remove_XSS($_GET['path']);
}

if (!empty($_REQUEST['export_report']) && '1' == $_REQUEST['export_report']) {
    if (api_is_platform_admin() || api_is_course_admin() ||
        api_is_course_tutor() || api_is_session_general_coach()
    ) {
        $loadExtraData = false;
        if (isset($_REQUEST['extra_data']) && 1 == $_REQUEST['extra_data']) {
            $loadExtraData = true;
        }

        $includeAllUsers = false;
        if (isset($_REQUEST['include_all_users']) &&
            1 == $_REQUEST['include_all_users']
        ) {
            $includeAllUsers = true;
        }

        $onlyBestAttempts = false;
        if (isset($_REQUEST['only_best_attempts']) &&
            1 == $_REQUEST['only_best_attempts']
        ) {
            $onlyBestAttempts = true;
        }

        require_once 'exercise_result.class.php';
        $export = new ExerciseResult();
        $export->setIncludeAllUsers($includeAllUsers);
        $export->setOnlyBestAttempts($onlyBestAttempts);

        switch ($_GET['export_format']) {
            case 'xls':
                $export->exportCompleteReportXLS(
                    '',
                    null,
                    $loadExtraData,
                    null,
                    $exercise_id
                );
                exit;

                break;
            case 'csv':
            default:
                $export->exportCompleteReportCSV(
                    '',
                    null,
                    $loadExtraData,
                    null,
                    $exercise_id
                );
                exit;

                break;
        }
    } else {
        api_not_allowed(true);
    }
}

$objExerciseTmp = new Exercise();
$exerciseExists = $objExerciseTmp->read($exercise_id);

//Send student email @todo move this code in a class, library
if (isset($_REQUEST['comments']) &&
    'update' === $_REQUEST['comments'] &&
    ($is_allowedToEdit || $is_tutor || $allowCoachFeedbackExercises)
) {
    // Filtered by post-condition
    $id = (int) $_GET['exeid'];
    $track_exercise_info = ExerciseLib::get_exercise_track_exercise_info($id);

    if (empty($track_exercise_info)) {
        api_not_allowed();
    }

    $student_id = $track_exercise_info['exe_user_id'];
    $session_id = $track_exercise_info['session_id'];
    $lp_id = $track_exercise_info['orig_lp_id'];
    $lpItemId = $track_exercise_info['orig_lp_item_id'];
    $lp_item_view_id = (int) $track_exercise_info['orig_lp_item_view_id'];
    $exerciseId = $track_exercise_info['exe_exo_id'];
    $exeWeighting = $track_exercise_info['max_score'];

    $attemptData = Event::get_exercise_results_by_attempt($id);
    $questionListData = [];
    if ($attemptData && $attemptData[$id] && $attemptData[$id]['question_list']) {
        $questionListData = $attemptData[$id]['question_list'];
    }
    $post_content_id = [];
    $comments_exist = false;

    $questionListInPost = [];
    foreach ($_POST as $key_index => $key_value) {
        $my_post_info = explode('_', $key_index);
        $post_content_id[] = isset($my_post_info[1]) ? $my_post_info[1] : null;

        if ('comments' === $my_post_info[0]) {
            $comments_exist = true;
            $questionListInPost[] = $my_post_info[1];
        }
    }

    foreach ($questionListInPost as $questionId) {
        $marks = $_POST['marks_'.$questionId] ?? 0;
        $my_comments = $_POST['comments_'.$questionId] ?? '';
        $params = [
            'teacher_comment' => $my_comments,
        ];
        $question = Question::read($questionId);
        if (false === $question) {
            continue;
        }

        // From the database.
        $marksFromDatabase = 0;
        if (isset($questionListData[$questionId])) {
            $marksFromDatabase = $questionListData[$questionId]['marks'];
        }

        if (in_array($question->type, [FREE_ANSWER, ORAL_EXPRESSION, ANNOTATION])) {
            // From the form.
            $params['marks'] = $marks;
            if ($marksFromDatabase != $marks) {
                Event::addEvent(
                    LOG_QUESTION_SCORE_UPDATE,
                    LOG_EXERCISE_ATTEMPT_QUESTION_ID,
                    [
                        'exe_id' => $id,
                        'question_id' => $questionId,
                        'old_marks' => $marksFromDatabase,
                        'new_marks' => $marks,
                    ]
                );
            }
        } else {
            $marks = $marksFromDatabase;
        }

        Database::update(
            $TBL_TRACK_ATTEMPT,
            $params,
            ['question_id = ? AND exe_id = ?' => [$questionId, $id]]
        );

        $recording = new TrackEAttemptRecording();
        $recording
            ->setExeId($id)
            ->setQuestionId($questionId)
            ->setAuthor(api_get_user_id())
            ->setTeacherComment($my_comments)
            ->setExeId($id)
            ->setMarks($marks)
            ->setSessionId(api_get_session_id())
        ;

        $em = Database::getManager();
        $em->persist($recording);
        $em->flush();
    }

    $useEvaluationPlugin = false;
    $pluginEvaluation = QuestionOptionsEvaluationPlugin::create();

    if ('true' === $pluginEvaluation->get(QuestionOptionsEvaluationPlugin::SETTING_ENABLE)) {
        $formula = $pluginEvaluation->getFormulaForExercise($exerciseId);

        if (!empty($formula)) {
            $useEvaluationPlugin = true;
        }
    }

    if (!$useEvaluationPlugin) {
        $qry = 'SELECT DISTINCT question_id, marks
                FROM '.$TBL_TRACK_ATTEMPT.' WHERE exe_id = '.$id.'
                GROUP BY question_id';
        $res = Database::query($qry);
        $tot = 0;
        while ($row = Database :: fetch_array($res, 'ASSOC')) {
            $marks = $row['marks'];
            if (!$objExerciseTmp->propagate_neg && $marks < 0) {
                continue;
            }
            $tot += $marks;
        }
    } else {
        $tot = $pluginEvaluation->getResultWithFormula($id, $formula);
    }

    $totalScore = (float) $tot;

    $sql = "UPDATE $TBL_TRACK_EXERCISES
            SET score = '".$totalScore."'
            WHERE exe_id = ".$id;
    Database::query($sql);

    if (isset($_POST['send_notification'])) {
        //@todo move this somewhere else
        $subject = get_lang('Examsheet viewed/corrected/commented by the trainer');
        $message = isset($_POST['notification_content']) ? $_POST['notification_content'] : '';

        MessageManager::send_message_simple(
            $student_id,
            $subject,
            $message,
            api_get_user_id()
        );

        if ($allowCoachFeedbackExercises) {
            Display::addFlash(
                Display::return_message(get_lang('Message Sent'))
            );
            header('Location: '.api_get_self().'?'.api_get_cidreq().'&exerciseId='.$exerciseId);
            exit;
        }
    }

    $notifications = api_get_configuration_value('exercise_finished_notification_settings');
    if ($notifications) {
        $oldResultDisabled = $objExerciseTmp->results_disabled;
        $objExerciseTmp->results_disabled = RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS;
        ob_start();
        $stats = ExerciseLib::displayQuestionListByAttempt(
            $objExerciseTmp,
            $track_exercise_info['exe_id'],
            false,
            false,
            false,
            ('true' === api_get_setting('exercise.quiz_results_answers_report')),
            false
        );
        $objExerciseTmp->results_disabled = $oldResultDisabled;
        ob_end_clean();
        // Show all for teachers.
        $oldResultDisabled = $objExerciseTmp->results_disabled;
        $objExerciseTmp->results_disabled = RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS;
        $objExerciseTmp->forceShowExpectedChoiceColumn = true;
        ob_start();
        $statsTeacher = ExerciseLib::displayQuestionListByAttempt(
            $objExerciseTmp,
            $track_exercise_info['exe_id'],
            false,
            false,
            false,
            ('true' === api_get_setting('exercise.quiz_results_answers_report')),
            false
        );
        ob_end_clean();
        $objExerciseTmp->forceShowExpectedChoiceColumn = false;
        $objExerciseTmp->results_disabled = $oldResultDisabled;

        $attemptCount = Event::getAttemptPosition(
            $track_exercise_info['exe_id'],
            $student_id,
            $objExerciseTmp->id,
            $lp_id,
            $lpItemId,
            $lp_item_view_id
        );

        ExerciseLib::sendNotification(
            $student_id,
            $objExerciseTmp,
            $track_exercise_info,
            api_get_course_info(),
            $attemptCount,
            $stats,
            $statsTeacher
        );
    }

    // Updating LP score here
    if (!empty($lp_id) && !empty($lpItemId)) {
        $lpRepo = Container::getLpRepository();
        $lp = null;
        if (!empty($lpId)) {
            /** @var CLp $lp */
            $lp = $lpRepo->find($lpId);
        }
        $statusCondition = '';
        $item = new learnpathItem($lpItemId);
        if ($item) {
            $prereqId = $item->get_prereq_string();
            $minScore = $item->getPrerequisiteMinScore();
            $maxScore = $item->getPrerequisiteMaxScore();
            $passed = false;
            $lp = new learnpath($lp, api_get_course_info(), $student_id);
            $prereqCheck = $lp->prerequisites_match($lpItemId);
            if ($prereqCheck) {
                $passed = true;
            }
            if (false === $passed) {
                if (!empty($objExerciseTmp->pass_percentage)) {
                    $passed = ExerciseLib::isSuccessExerciseResult(
                        $tot,
                        $exeWeighting,
                        $objExerciseTmp->pass_percentage
                    );
                } else {
                    $passed = false;
                }
            }

            if ($passed) {
                $statusCondition = ', status = "completed" ';
            } else {
                $statusCondition = ', status = "failed" ';
            }
            Display::addFlash(Display::return_message(get_lang('Learning path updated')));
        }

        $sql = "UPDATE $TBL_LP_ITEM_VIEW
                SET score = '".(float) $tot."'
                $statusCondition
                WHERE c_id = $course_id AND id = $lp_item_view_id";
        Database::query($sql);

        header('Location: '.api_get_path(WEB_CODE_PATH).'exercise/exercise_show.php?id='.$id.'&student='.$student_id.'&'.api_get_cidreq());
        exit;
    }
}

$actions = null;
if ($is_allowedToEdit && 'learnpath' != $origin) {
    // the form
    if (api_is_platform_admin() || api_is_course_admin() ||
        api_is_course_tutor() || api_is_session_general_coach()
    ) {
        $actions .= '<a href="exercise.php?'.api_get_cidreq().'">'.
            Display::return_icon('back.png', get_lang('Go back to the questions list'), '', ICON_SIZE_MEDIUM).'</a>';
        $actions .= '<a href="live_stats.php?'.api_get_cidreq().'&exerciseId='.$exercise_id.'">'.
            Display::return_icon('activity_monitor.png', get_lang('Live results'), '', ICON_SIZE_MEDIUM).'</a>';
        $actions .= '<a href="stats.php?'.api_get_cidreq().'&exerciseId='.$exercise_id.'">'.
            Display::return_icon('statistics.png', get_lang('Report by question'), '', ICON_SIZE_MEDIUM).'</a>';
        $actions .= '<a id="export_opener" href="'.api_get_self().'?export_report=1&exerciseId='.$exercise_id.'" >'.
        Display::return_icon('save.png', get_lang('Export'), '', ICON_SIZE_MEDIUM).'</a>';
        $actions .= Display::url(
            Display::return_icon('reload.png', get_lang('RecalculateResults'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'exercise/recalculate_all.php?'.api_get_cidreq()."&exercise=$exercise_id"
        );

        // clean result before a selected date icon
        if ($allowClean) {
            $actions .= Display::url(
                Display::return_icon(
                    'clean_before_date.png',
                    get_lang('Clean all results before a selected date'),
                    '',
                    ICON_SIZE_MEDIUM
                ),
                '#',
                ['onclick' => 'javascript:display_date_picker()']
            );
            // clean result before a selected date datepicker popup
            $actions .= Display::span(
                Display::input(
                    'input',
                    'datepicker_start',
                    get_lang('Select a date from the calendar'),
                    [
                        'onmouseover' => 'datepicker_input_mouseover()',
                        'id' => 'datepicker_start',
                        'onchange' => 'datepicker_input_changed()',
                        'readonly' => 'readonly',
                    ]
                ).
                Display::button(
                    'delete',
                    get_lang('Delete'),
                    ['onclick' => 'submit_datepicker()']
                ),
                ['style' => 'display:none', 'id' => 'datepicker_span']
            );
        }
        $actions .= '<a class="btn btn--plain" href="question_stats.php?'.api_get_cidreq().'&id='.$exercise_id.'">'.
            get_lang('QuestionStats').'</a>';
    }
} else {
    $actions .= '<a href="exercise.php">'.
        Display::return_icon(
            'back.png',
            get_lang('Go back to the questions list'),
            '',
            ICON_SIZE_MEDIUM
        ).
    '</a>';
}

// Deleting an attempt
if (($is_allowedToEdit || $is_tutor || api_is_coach()) &&
    isset($_GET['delete']) && 'delete' === $_GET['delete'] &&
    !empty($_GET['did']) && false == $locked
) {
    $exe_id = (int) $_GET['did'];
    if (!empty($exe_id)) {
        ExerciseLib::deleteExerciseAttempt($exe_id);
        header('Location: exercise_report.php?'.api_get_cidreq().'&exerciseId='.$exercise_id);
        exit;
    }
}

if ($is_allowedToEdit || $is_tutor) {
    $interbreadcrumb[] = [
        'url' => 'exercise.php?'.api_get_cidreq(),
        'name' => get_lang('Tests'),
    ];

    $nameTools = get_lang('Learner score');
    if ($exerciseExists) {
        $interbreadcrumb[] = [
            'url' => '#',
            'name' => $objExerciseTmp->selectTitle(true),
        ];
    }
} else {
    $interbreadcrumb[] = [
        'url' => 'exercise.php?'.api_get_cidreq(),
        'name' => get_lang('Tests'),
    ];
    if ($exerciseExists) {
        $nameTools = get_lang('Results and feedback').': '.$objExerciseTmp->selectTitle(true);
    }
}

if (($is_allowedToEdit || $is_tutor || api_is_coach()) &&
    isset($_GET['a']) && 'close' === $_GET['a'] &&
    !empty($_GET['id']) && false == $locked
) {
    // Close the user attempt otherwise left pending
    $exe_id = (int) $_GET['id'];
    $sql = "UPDATE $TBL_TRACK_EXERCISES SET status = ''
            WHERE exe_id = $exe_id AND status = 'incomplete'";
    Database::query($sql);
}

Display::display_header($nameTools);

// Clean all results for this test before the selected date
if (($is_allowedToEdit || $is_tutor || api_is_coach()) &&
    isset($_GET['delete_before_date']) && false == $locked
) {
    // ask for the date
    $check = Security::check_token('get');
    if ($check && $allowClean) {
        $objExerciseTmp = new Exercise();
        if ($objExerciseTmp->read($exercise_id)) {
            $count = $objExerciseTmp->cleanResults(
                true,
                $_GET['delete_before_date'].' 23:59:59'
            );
            echo Display::return_message(
                sprintf(get_lang('XResults and feedbackCleaned'), $count),
                'confirm'
            );
        }
    }
}

// Security token to protect deletion
$token = Security::get_token();
$actions = Display::toolbarAction('exercise_report', [$actions]);

$extra = '<script>
    $(function() {
        $( "#dialog:ui-dialog" ).dialog( "destroy" );
        $( "#dialog-confirm" ).dialog({
                autoOpen: false,
                show: "blind",
                resizable: false,
                height:300,
                modal: true
         });

        $("#export_opener").click(function() {
            var targetUrl = $(this).attr("href");
            $( "#dialog-confirm" ).dialog({
                width:400,
                height:300,
                buttons: {
                    "'.addslashes(get_lang('Download')).'": function() {
                        var export_format = $("input[name=export_format]:checked").val();
                        var extra_data  = $("input[name=load_extra_data]:checked").val();
                        var includeAllUsers  = $("input[name=include_all_users]:checked").val();
                        var attempts = $("input[name=only_best_attempts]:checked").val();
                        location.href = targetUrl+"&export_format="+export_format+"&extra_data="+extra_data+"&include_all_users="+includeAllUsers+"&only_best_attempts="+attempts;
                        $( this ).dialog( "close" );
                    }
                }
            });
            $( "#dialog-confirm" ).dialog("open");
            return false;
        });
    });
    </script>';

$extra .= '<div id="dialog-confirm" title="'.get_lang('Please confirm your choice').'">';
$form = new FormValidator(
    'report',
    'post',
    null,
    null,
    ['class' => 'form-vertical']
);
$form->addRadio(
    'export_format',
    get_lang('CSV export'),
    ['csv'],
    ['id' => 'export_format_csv_label']
);
$form->addRadio(
    'export_format',
    get_lang('Excel export'),
    ['xls'],
    ['id' => 'export_format_xls_label']
);
$form->addCheckBox(
    'load_extra_data',
    get_lang('Load extra user fields data (have to be marked as \'Filter\' to appear).'),
    '0',
    ['id' => 'export_format_xls_label']
);
$form->addCheckBox(
    'include_all_users',
    get_lang('Include all users'),
);
$form->addCheckBox(
    'only_best_attempts',
    get_lang('Only best attempts'),
    '0'
);
$form->setDefaults(['export_format' => 'csv']);
$extra .= $form->returnForm();
$extra .= '</div>';

if ($is_allowedToEdit) {
    echo $extra;
}

echo $actions;
$url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_exercise_results&exerciseId='.$exercise_id.'&filter_by_user='.$filter_user.'&'.api_get_cidreq();
$action_links = '';
// Generating group list
$group_list = GroupManager::get_group_list();
$group_parameters = [
    'group_all:'.get_lang('All'),
    'group_none:'.get_lang('none'),
];

foreach ($group_list as $group) {
    $group_parameters[] = $group['iid'].':'.$group['name'];
}
if (!empty($group_parameters)) {
    $group_parameters = implode(';', $group_parameters);
}

$officialCodeInList = api_get_setting('show_official_code_exercise_result_list');

if ($is_allowedToEdit || $is_tutor) {
    // The order is important you need to check the the $column variable in the model.ajax.php file
    $columns = [
        get_lang('First name'),
        get_lang('Last name'),
        get_lang('Login'),
        get_lang('Group'),
        get_lang('Duration').' ('.get_lang('minute').')',
        get_lang('Start Date'),
        get_lang('End Date'),
        get_lang('Score'),
        get_lang('IP'),
        get_lang('Status'),
        get_lang('Learning path'),
        get_lang('Detail'),
    ];

    if ('true' === $officialCodeInList) {
        $columns = array_merge([get_lang('Code')], $columns);
    }

    // Column config
    $column_model = [
        ['name' => 'firstname', 'index' => 'firstname', 'width' => '50', 'align' => 'left', 'search' => 'true'],
        [
            'name' => 'lastname',
            'index' => 'lastname',
            'width' => '50',
            'align' => 'left',
            'formatter' => 'action_formatter',
            'search' => 'true',
        ],
        [
            'name' => 'login',
            'index' => 'username',
            'width' => '40',
            'align' => 'left',
            'search' => 'true',
            'hidden' => ('true' === api_get_setting('exercise.exercise_attempts_report_show_username')) ? 'false' : 'true',
        ],
        [
            'name' => 'group_name',
            'index' => 'group_id',
            'width' => '40',
            'align' => 'left',
            'search' => 'true',
            'stype' => 'select',
            //for the bottom bar
            'searchoptions' => [
                'defaultValue' => 'group_all',
                'value' => $group_parameters,
            ],
            //for the top bar
            'editoptions' => ['value' => $group_parameters],
        ],
        ['name' => 'duration', 'index' => 'exe_duration', 'width' => '30', 'align' => 'left', 'search' => 'true'],
        ['name' => 'start_date', 'index' => 'start_date', 'width' => '60', 'align' => 'left', 'search' => 'true'],
        ['name' => 'exe_date', 'index' => 'exe_date', 'width' => '60', 'align' => 'left', 'search' => 'true'],
        ['name' => 'score', 'index' => 'score', 'width' => '50', 'align' => 'center', 'search' => 'true'],
        ['name' => 'ip', 'index' => 'user_ip', 'width' => '40', 'align' => 'center', 'search' => 'true'],
        [
            'name' => 'status',
            'index' => 'revised',
            'width' => '40',
            'align' => 'left',
            'search' => 'true',
            'stype' => 'select',
            //for the bottom bar
            'searchoptions' => [
                'defaultValue' => '',
                'value' => ':'.get_lang('All').';1:'.get_lang('Validated').';0:'.get_lang('Not validated'),
            ],
            //for the top bar
            'editoptions' => [
                'value' => ':'.get_lang('All').';1:'.get_lang('Validated').';0:'.get_lang(
                        'Not validated'
                    ),
            ],
        ],
        ['name' => 'lp', 'index' => 'orig_lp_id', 'width' => '60', 'align' => 'left', 'search' => 'false'],
        ['name' => 'actions', 'index' => 'actions', 'width' => '60', 'align' => 'left', 'search' => 'false', 'sortable' => 'false'],
    ];

    if ('true' === $officialCodeInList) {
        $officialCodeRow = ['name' => 'official_code', 'index' => 'official_code', 'width' => '50', 'align' => 'left', 'search' => 'true'];
        $column_model = array_merge([$officialCodeRow], $column_model);
    }

    $action_links = '
    // add username as title in lastname filed - ref 4226
    function action_formatter(cellvalue, options, rowObject) {
        // rowObject is firstname,lastname,login,... get the third word
        var loginx = "'.api_htmlentities(sprintf(get_lang('Login: %s'), ':::'), ENT_QUOTES).'";
        var tabLoginx = loginx.split(/:::/);
        // tabLoginx[0] is before and tabLoginx[1] is after :::
        // may be empty string but is defined
        return "<span title=\""+tabLoginx[0]+rowObject[2]+tabLoginx[1]+"\">"+cellvalue+"</span>";
    }';
}

$extra_params['autowidth'] = 'true';
$extra_params['height'] = 'auto';
$extra_params['gridComplete'] = "
    defaultGroupId = Cookies.get('default_group_".$exercise_id."');
    if (typeof defaultGroupId !== 'undefined') {
        $('#gs_group_name').val(defaultGroupId);
    }
";

$extra_params['beforeRequest'] = "
var defaultGroupId = $('#gs_group_name').val();

// Load from group menu
if (typeof defaultGroupId !== 'undefined') {
    Cookies.set('default_group_".$exercise_id."', defaultGroupId);
} else {
    // get from cookies
    defaultGroupId = Cookies.get('default_group_".$exercise_id."');
    $('#gs_group_name').val(defaultGroupId);
}

if (typeof defaultGroupId !== 'undefined') {
    var posted_data = $(\"#results\").jqGrid('getGridParam', 'postData');
    var extraFilter = ',{\"field\":\"group_id\",\"op\":\"eq\",\"data\":\"'+ defaultGroupId +'\"}]}';
    var filters = posted_data.filters;
    var stringObj = new String(filters);
    stringObj.replace(']}', extraFilter);

    posted_data['group_id_in_toolbar'] = defaultGroupId;
    $(this).jqGrid('setGridParam', 'postData', posted_data);
}
";

$gridJs = Display::grid_js(
    'results',
    $url,
    $columns,
    $column_model,
    $extra_params,
    [],
    $action_links,
    true
);

?>
<script>
    function exportExcel()
    {
        var mya = $("#results").getDataIDs();  // Get All IDs
        var data = $("#results").getRowData(mya[0]);     // Get First row to get the labels
        var colNames = new Array();
        var ii = 0;
        for (var i in data) {
            colNames[ii++] = i;
        }
        var html = "";
        for (i = 0; i < mya.length; i++) {
            data = $("#results").getRowData(mya[i]); // get each row
            for (j = 0; j < colNames.length; j++) {
                html = html + data[colNames[j]] + ","; // output each column as tab delimited
            }
            html = html + "\n";  // output each row with end of line
        }
        html = html + "\n";  // end of line at the end
        var form = $("#export_report_form");
        $("#csvBuffer").attr('value', html);
        form.target='_blank';
        form.submit();
    }

    $(function() {
        $("#datepicker_start").datepicker({
            defaultDate: "",
            changeMonth: false,
            numberOfMonths: 1
        });
        <?php
        echo $gridJs;

        if ($is_allowedToEdit || $is_tutor) {
            ?>
            $("#results").jqGrid(
                'navGrid',
                '#results_pager', {
                    view:true, edit:false, add:false, del:false, excel:false
                },
                {height:280, reloadAfterSubmit:false}, // view options
                {height:280, reloadAfterSubmit:false}, // edit options
                {height:280, reloadAfterSubmit:false}, // add options
                {reloadAfterSubmit: false}, // del options
                {width:500}, // search options
            );

            var sgrid = $("#results")[0];

            // Update group
            var defaultGroupId = Cookies.get('default_group_<?php echo $exercise_id; ?>');
            $('#gs_group_name').val(defaultGroupId);
            // Adding search options
            var options = {
                'stringResult': true,
                'autosearch' : true,
                'searchOnEnter': false,
                afterSearch: function () {
                    $('#gs_group_name').on('change', function() {
                        var defaultGroupId = $('#gs_group_name').val();
                        // Save default group id
                        Cookies.set('default_group_<?php echo $exercise_id; ?>', defaultGroupId);
                    });
                }
            }
            jQuery("#results").jqGrid('filterToolbar', options);
            sgrid.triggerToolbar();
            $('#results').on('click', 'a.exercise-recalculate', function (e) {
                e.preventDefault();
                if (!$(this).data('user') || !$(this).data('exercise') || !$(this).data('id')) {
                    return;
                }
                var url = '<?php echo api_get_path(WEB_CODE_PATH); ?>exercise/recalculate.php?<?php echo api_get_cidreq(); ?>';
                var recalculateXhr = $.post(url, $(this).data());
                $.when(recalculateXhr).done(function (response) {
                    $('#results').trigger('reloadGrid');
                });
            });
        <?php
        }
        ?>
        });
    // datepicker functions
    var datapickerInputModified = false;
    /**
     * return true if the datepicker input has been modified
     */
    function datepicker_input_changed() {
        datapickerInputModified = true;
    }

    /**
    * disply the datepicker calendar on mouse over the input
    */
    function datepicker_input_mouseover() {
        $('#datepicker_start').datepicker( "show" );
    }

    /**
    * display or hide the datepicker input, calendar and button
    */
    function display_date_picker() {
        if (!$('#datepicker_span').is(":visible")) {
            $('#datepicker_span').show();
            $('#datepicker_start').datepicker( "show" );
        } else {
            $('#datepicker_start').datepicker( "hide" );
            $('#datepicker_span').hide();
        }
    }

    /**
    * confirm deletion
    */
    function submit_datepicker() {
        if (datapickerInputModified) {
            var dateTypeVar = $('#datepicker_start').datepicker('getDate');
            var dateForBDD = $.datepicker.formatDate('yy-mm-dd', dateTypeVar);
            // Format the date for confirm box
            var dateFormat = $( "#datepicker_start" ).datepicker( "option", "dateFormat" );
            var selectedDate = $.datepicker.formatDate(dateFormat, dateTypeVar);
            if (confirm("<?php echo addslashes(get_lang('Are you sure you want to clean results for this test before the selected date ?')).' '; ?>" + selectedDate)) {
                self.location.href = "exercise_report.php?<?php echo api_get_cidreq(); ?>&exerciseId=<?php echo $exercise_id; ?>&delete_before_date="+dateForBDD+"&sec_token=<?php echo $token; ?>";
            }
        }
    }
</script>
<form id="export_report_form" method="post" action="exercise_report.php?<?php echo api_get_cidreq(); ?>">
    <input type="hidden" name="csvBuffer" id="csvBuffer" value="" />
    <input type="hidden" name="export_report" id="export_report" value="1" />
    <input type="hidden" name="exerciseId" id="exerciseId" value="<?php echo $exercise_id; ?>" />
</form>

<?php
echo Display::grid_html('results');
Display::display_footer();
