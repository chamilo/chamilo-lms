<?php

/* For licensing terms, see /license.txt */

/**
 * Exercise list: This script shows the list of exercises for administrators and students.
 *
 * @author Julio Montoya <gugli100@gmail.com> jqgrid integration
 * Modified by hubert.borderiou (question category)
 *
 * @todo fix excel export
 */
require_once __DIR__.'/../inc/global.inc.php';

// Setting the tabs
$this_section = SECTION_COURSES;
$htmlHeadXtra[] = api_get_jqgrid_js();

$filter_user = isset($_REQUEST['filter_by_user']) ? (int) $_REQUEST['filter_by_user'] : null;
$isBossOfStudent = false;
if (!empty($filter_user) && api_is_student_boss()) {
    // Check if boss has access to user info.
    if (UserManager::userIsBossOfStudent(api_get_user_id(), $filter_user)) {
        $isBossOfStudent = true;
    } else {
        api_not_allowed(true);
    }
} else {
    api_protect_course_script(true, false, true);
}

$limitTeacherAccess = api_get_configuration_value('limit_exercise_teacher_access');
$allowClean = Exercise::allowAction('clean_results');

if ($limitTeacherAccess && !api_is_platform_admin()) {
    api_not_allowed(true);
}

require_once 'hotpotatoes.lib.php';

$_course = api_get_course_info();

// document path
$documentPath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
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
$allowCoachFeedbackExercises = api_get_setting('allow_coach_feedback_exercises') === 'true';
$course_id = api_get_course_int_id();
$exercise_id = isset($_REQUEST['exerciseId']) ? (int) $_REQUEST['exerciseId'] : 0;
$locked = api_resource_is_locked_by_gradebook($exercise_id, LINK_EXERCISE);
$sessionId = api_get_session_id();
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;

if ('export_all_exercises_results' !== $action) {
    if (empty($exercise_id)) {
        api_not_allowed(true);
    }
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

if (!empty($_REQUEST['export_report']) && $_REQUEST['export_report'] == '1') {
    if (api_is_platform_admin() || api_is_course_admin() ||
        api_is_course_tutor() || api_is_session_general_coach()
    ) {
        $loadExtraData = false;
        if (isset($_REQUEST['extra_data']) && $_REQUEST['extra_data'] == 1) {
            $loadExtraData = true;
        }

        $includeAllUsers = false;
        if (isset($_REQUEST['include_all_users']) &&
            $_REQUEST['include_all_users'] == 1
        ) {
            $includeAllUsers = true;
        }

        $onlyBestAttempts = false;
        if (isset($_REQUEST['only_best_attempts']) &&
            $_REQUEST['only_best_attempts'] == 1
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
                    $documentPath,
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
                    $documentPath,
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

switch ($action) {
    case 'export_all_results':
        $sessionId = api_get_session_id();
        $courseId = api_get_course_int_id();
        ExerciseLib::exportExerciseAllResultsZip($sessionId, $courseId, $exercise_id);

        break;
}

//Send student email @todo move this code in a class, library
if (isset($_REQUEST['comments']) &&
    $_REQUEST['comments'] === 'update' &&
    ($is_allowedToEdit || $is_tutor || $allowCoachFeedbackExercises)
) {
    // Filtered by post-condition
    $id = (int) $_GET['exeid'];
    $track_exercise_info = ExerciseLib::get_exercise_track_exercise_info($id);
    if (empty($track_exercise_info)) {
        api_not_allowed();
    }
    $student_id = (int) $track_exercise_info['exe_user_id'];
    $session_id = $track_exercise_info['session_id'];
    $lp_id = $track_exercise_info['orig_lp_id'];
    $lpItemId = $track_exercise_info['orig_lp_item_id'];
    $lp_item_view_id = (int) $track_exercise_info['orig_lp_item_view_id'];
    $exerciseId = $track_exercise_info['exe_exo_id'];
    $exeWeighting = $track_exercise_info['exe_weighting'];

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
        if ($my_post_info[0] === 'comments') {
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
        $marksFromDatabase = $questionListData[$questionId]['marks'];
        if (in_array($question->type, [FREE_ANSWER, ORAL_EXPRESSION, ANNOTATION, UPLOAD_ANSWER])) {
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

        $params = [
            'exe_id' => $id,
            'question_id' => $questionId,
            'marks' => $marks,
            'insert_date' => api_get_utc_datetime(),
            'author' => api_get_user_id(),
            'teacher_comment' => $my_comments,
        ];
        Database::insert($TBL_TRACK_ATTEMPT_RECORDING, $params);
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
        while ($row = Database::fetch_array($res, 'ASSOC')) {
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
            SET exe_result = '".$totalScore."'
            WHERE exe_id = ".$id;
    Database::query($sql);

    // See BT#18165
    $remedialMessage = RemedialCoursePlugin::create()->getRemedialCourseList(
        $objExerciseTmp,
        $student_id,
        api_get_session_id(),
        true,
        $lp_id ?: 0,
        $lpItemId ?: 0
    );
    if (null != $remedialMessage) {
        Display::addFlash(
            Display::return_message($remedialMessage, 'warning', false)
        );
    }
    $advancedMessage = RemedialCoursePlugin::create()->getAdvancedCourseList(
        $objExerciseTmp,
        $student_id,
        api_get_session_id(),
        $lp_id ?: 0,
        $lpItemId ?: 0
    );
    if (!empty($advancedMessage)) {
        $message = Display::return_message(
            $advancedMessage,
            'info',
            false
        );
    }
    if (isset($_POST['send_notification'])) {
        //@todo move this somewhere else
        $subject = get_lang('ExamSheetVCC');
        $message = isset($_POST['notification_content']) ? $_POST['notification_content'] : '';
        MessageManager::send_message_simple(
            $student_id,
            $subject,
            $message,
            api_get_user_id()
        );
        if ($allowCoachFeedbackExercises) {
            Display::addFlash(
                Display::return_message(get_lang('MessageSent'))
            );
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
            api_get_configuration_value('quiz_results_answers_report'),
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
            api_get_configuration_value('quiz_results_answers_report'),
            false
        );
        ob_end_clean();
        $objExerciseTmp->forceShowExpectedChoiceColumn = false;
        $objExerciseTmp->results_disabled = $oldResultDisabled;

        $attemptCount = Event::getAttemptPosition(
            $track_exercise_info['exe_id'],
            $student_id,
            $objExerciseTmp->iid,
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
        $statusCondition = '';
        $item = new learnpathItem($lpItemId, api_get_user_id(), api_get_course_int_id());
        if ($item) {
            $prereqId = $item->get_prereq_string();
            $minScore = $item->getPrerequisiteMinScore();
            $maxScore = $item->getPrerequisiteMaxScore();
            $passed = false;
            $lp = new learnpath(api_get_course_id(), $lp_id, $student_id);
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
            Display::addFlash(Display::return_message(get_lang('LearnpathUpdated')));
        }

        $sql = "UPDATE $TBL_LP_ITEM_VIEW
                SET score = '".(float) $tot."'
                $statusCondition
                WHERE iid = $lp_item_view_id";
        Database::query($sql);

        header('Location: '.api_get_path(WEB_CODE_PATH).'exercise/exercise_show.php?id='.$id.'&student='.$student_id.'&'.api_get_cidreq());
        exit;
    }
}

$actions = null;
$hideIp = api_get_configuration_value('exercise_hide_ip');
if ($is_allowedToEdit && $origin !== 'learnpath') {
    // the form
    if (api_is_platform_admin() || api_is_course_admin() ||
        api_is_course_tutor() || api_is_session_general_coach()
    ) {
        $actions .= '<a href="exercise.php?'.api_get_cidreq().'">'.
            Display::return_icon('back.png', get_lang('GoBackToQuestionList'), '', ICON_SIZE_MEDIUM).'</a>';
        $actions .= '<a href="live_stats.php?'.api_get_cidreq().'&exerciseId='.$exercise_id.'">'.
            Display::return_icon('activity_monitor.png', get_lang('LiveResults'), '', ICON_SIZE_MEDIUM).'</a>';
        $actions .= '<a href="stats.php?'.api_get_cidreq().'&exerciseId='.$exercise_id.'">'.
            Display::return_icon('statistics.png', get_lang('ReportByQuestion'), '', ICON_SIZE_MEDIUM).'</a>';
        $actions .= '<a href="stats_attempts.php?'.api_get_cidreq().'&exerciseId='.$exercise_id.'">'.
            Display::return_icon('survey_reporting_complete.png', get_lang('ReportByAttempts'), '', ICON_SIZE_MEDIUM).'</a>';
        $actions .= '<a id="export_opener" href="'.api_get_self().'?export_report=1&exerciseId='.$exercise_id.'" >'.
        Display::return_icon('save.png', get_lang('Export'), '', ICON_SIZE_MEDIUM).'</a>';
        $actions .= Display::url(
            Display::return_icon('reload.png', get_lang('RecalculateResults'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'exercise/recalculate_all.php?'.api_get_cidreq()."&exercise=$exercise_id"
        );

        $actions .= Display::url(
            Display::return_icon('export_pdf.png', get_lang('ExportExerciseAllResults'), [], ICON_SIZE_MEDIUM),
            api_get_self().'?'.api_get_cidreq().'&action=export_all_results&exerciseId='.$exercise_id
        );

        // clean result before a selected date icon
        if ($allowClean) {
            $actions .= Display::url(
                Display::return_icon(
                    'clean_before_date.png',
                    get_lang('CleanStudentsResultsBeforeDate'),
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
                    get_lang('SelectADateOnTheCalendar'),
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

        $actions .= Display::url(
            get_lang('QuestionStats'),
            'question_stats.php?'.api_get_cidreq().'&id='.$exercise_id,
            ['class' => 'btn btn-default']
        );

        $actions .= Display::url(
            get_lang('ComparativeGroupReport'),
            'comparative_group_report.php?'.api_get_cidreq().'&id='.$exercise_id,
            ['class' => 'btn btn-default']
        );
    }
} else {
    $actions .= '<a href="exercise.php">'.
        Display::return_icon(
            'back.png',
            get_lang('GoBackToQuestionList'),
            '',
            ICON_SIZE_MEDIUM
        ).
    '</a>';
}

// Deleting an attempt
if (($is_allowedToEdit || $is_tutor || api_is_coach()) &&
    isset($_GET['delete']) && $_GET['delete'] === 'delete' &&
    !empty($_GET['did']) && $locked == false
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
        'name' => get_lang('Exercises'),
    ];

    $nameTools = get_lang('StudentScore');
    if ($exerciseExists) {
        $interbreadcrumb[] = [
            'url' => '#',
            'name' => $objExerciseTmp->selectTitle(true),
        ];
    }
} else {
    $interbreadcrumb[] = [
        'url' => 'exercise.php?'.api_get_cidreq(),
        'name' => get_lang('Exercises'),
    ];
    if ($exerciseExists) {
        $nameTools = get_lang('Results').': '.$objExerciseTmp->selectTitle(true);
    }
}

if (($is_allowedToEdit || $is_tutor || api_is_coach()) &&
    isset($_GET['a']) && $_GET['a'] === 'close' &&
    !empty($_GET['id']) && $locked == false
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
    isset($_GET['delete_before_date']) && $locked == false
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
                sprintf(get_lang('XResultsCleaned'), $count),
                'confirm'
            );
        }
    }
}

// Security token to protect deletion
$token = Security::get_token();
$actions = Display::div($actions, ['class' => 'actions']);

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

$extra .= '<div id="dialog-confirm" title="'.get_lang('ConfirmYourChoice').'">';
$form = new FormValidator(
    'report',
    'post',
    null,
    null,
    ['class' => 'form-vertical']
);
$form->addElement(
    'radio',
    'export_format',
    null,
    get_lang('ExportAsCSV'),
    'csv',
    ['id' => 'export_format_csv_label']
);
$form->addElement(
    'radio',
    'export_format',
    null,
    get_lang('ExportAsXLS'),
    'xls',
    ['id' => 'export_format_xls_label']
);
$form->addElement(
    'checkbox',
    'load_extra_data',
    null,
    get_lang('LoadExtraData'),
    '0',
    ['id' => 'export_format_xls_label']
);
$form->addElement(
    'checkbox',
    'include_all_users',
    null,
    get_lang('IncludeAllUsers'),
    '0'
);
$form->addElement(
    'checkbox',
    'only_best_attempts',
    null,
    get_lang('OnlyBestAttempts'),
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
    'group_none:'.get_lang('None'),
];

foreach ($group_list as $group) {
    $group_parameters[] = $group['id'].':'.$group['name'];
}
if (!empty($group_parameters)) {
    $group_parameters = implode(';', $group_parameters);
}

$officialCodeInList = api_get_setting('show_official_code_exercise_result_list');

if ($is_allowedToEdit || $is_tutor) {
    // The order is important you need to check the the $column variable in the model.ajax.php file
    $columns = [
        get_lang('FirstName'),
        get_lang('LastName'),
        get_lang('LoginName'),
        get_lang('Group'),
        get_lang('Duration').' ('.get_lang('MinMinute').')',
        get_lang('StartDate'),
        get_lang('EndDate'),
        get_lang('Score'),
        get_lang('IP'),
        get_lang('Status'),
        get_lang('ToolLearnpath'),
        get_lang('Actions'),
    ];
    $indexIp = 8;
    if ($officialCodeInList === 'true') {
        $indexIp = 9;
        $columns = array_merge([get_lang('OfficialCode')], $columns);
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
            'hidden' => api_get_configuration_value('exercise_attempts_report_show_username') ? 'false' : 'true',
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
        ['name' => 'score', 'index' => 'exe_result', 'width' => '50', 'align' => 'center', 'search' => 'true'],
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
                'value' => ':'.get_lang('All').';1:'.get_lang('Validated').';0:'.get_lang('NotValidated'),
            ],
            //for the top bar
            'editoptions' => [
                'value' => ':'.get_lang('All').';1:'.get_lang('Validated').';0:'.get_lang(
                        'NotValidated'
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

    if ($hideIp) {
        // It removes the 9th column related to IP
        unset($columns[$indexIp]);
        unset($column_model[$indexIp]);
        $columns = array_values($columns);
        $column_model = array_values($column_model);
    }

    $action_links = '
    // add username as title in lastname filed - ref 4226
    function action_formatter(cellvalue, options, rowObject) {
        // rowObject is firstname,lastname,login,... get the third word
        var loginx = "'.api_htmlentities(sprintf(get_lang('LoginX'), ':::'), ENT_QUOTES).'";
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
            if (confirm("<?php echo convert_double_quote_to_single(get_lang('AreYouSureDeleteTestResultBeforeDateD')).' '; ?>" + selectedDate)) {
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
