<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Exercise\FinalExamAccessRule;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;

/**
 * Exercise preview.
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_QUIZ;
Exercise::cleanSessionVariables();
$this_section = SECTION_COURSES;

//$js = '<script>'.api_get_language_translate_html().'</script>';
//$htmlHeadXtra[] = $js;

// Notice for unauthorized people.
api_protect_course_script(true);
$courseId = isset($_REQUEST['cid']) ? (int) $_REQUEST['cid'] : api_get_course_int_id();
$sessionId = isset($_REQUEST['sid']) ? (int) $_REQUEST['sid'] : api_get_session_id();
$exercise_id = isset($_REQUEST['exerciseId']) ? (int) $_REQUEST['exerciseId'] : 0;

$objExercise = new Exercise($courseId);
$result = $objExercise->read($exercise_id, true);

if (!$result) {
    api_not_allowed(true);
}

$plugin = Positioning::create();
if ($plugin->isEnabled()) {
    if ($plugin->blockFinalExercise(api_get_user_id(), $exercise_id, $courseId, $sessionId)) {
        api_not_allowed(true);
    }
}

if (isset($_POST['final_exam_user_identifier_save'])) {
    header('Content-Type: application/json; charset='.api_get_system_encoding());

    if (!Security::check_token('post')) {
        http_response_code(403);
        echo json_encode(['success' => false]);
        exit;
    }

    $studentId = trim((string) ($_POST['student_id'] ?? ''));
    $success = FinalExamAccessRule::saveUserIdentifier(
        api_get_user_id(),
        $exercise_id,
        $studentId
    );

    Security::clear_token();
    echo json_encode(['success' => $success]);
    exit;
}

$learnpath_id = isset($_REQUEST['learnpath_id']) ? (int) $_REQUEST['learnpath_id'] : null;
$learnpath_item_id = isset($_REQUEST['learnpath_item_id']) ? (int) $_REQUEST['learnpath_item_id'] : null;
$learnpathItemViewId = isset($_REQUEST['learnpath_item_view_id']) ? (int) $_REQUEST['learnpath_item_view_id'] : null;
$origin = api_get_origin();
if (empty($origin) && !empty($learnpath_id)) {
    $origin = 'learnpath';
}

$logInfo = [
    'tool' => TOOL_QUIZ,
    'tool_id' => $exercise_id,
    'action' => isset($_REQUEST['learnpath_id']) ? 'learnpath_id' : '',
    'action_details' => isset($_REQUEST['learnpath_id']) ? (int) $_REQUEST['learnpath_id'] : '',
];
Event::registerLog($logInfo);

$interbreadcrumb[] = [
    'url' => 'exercise.php?'.api_get_cidreq(),
    'name' => get_lang('Tests'),
];
$interbreadcrumb[] = ['url' => '#', 'name' => $objExercise->selectTitle(true)];

$time_control = false;
$clock_expired_time = ExerciseLib::get_session_time_control_key($objExercise->id, $learnpath_id, $learnpath_item_id);

if (0 != $objExercise->expired_time && !empty($clock_expired_time)) {
    $time_control = true;
}

$htmlHeadXtra[] = api_get_build_js('legacy_exercise.js');
if ($time_control) {
    // Get time left for expiring time
    $time_left = api_strtotime($clock_expired_time, 'UTC') - time();
    /*$htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/epiclock/stylesheet/jquery.epiclock.css');
    $htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/epiclock/renderers/minute/epiclock.minute.css');
    $htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.dateformat.min.js');
    $htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.epiclock.min.js');
    $htmlHeadXtra[] = api_get_js('epiclock/renderers/minute/epiclock.minute.js');*/
    $htmlHeadXtra[] = $objExercise->showTimeControlJS($time_left);
}

$useBlankExerciseLayout = in_array($origin, ['learnpath', 'embeddable'], true);

if (!in_array($origin, ['learnpath', 'embeddable', 'mobileapp'], true)) {
    SessionManager::addFlashSessionReadOnly();
    Display::display_header();
} else {
    $htmlHeadXtra[] = '
    <style>
    body { background: none;}
    </style>
    ';

    if ($useBlankExerciseLayout) {
        ob_start();
        Display::$legacyTemplate = '@ChamiloCore/Layout/blank.html.twig';
    } else {
        Display::display_reduced_header();
    }
}

if ('mobileapp' === $origin) {
    $actions = '<a href="javascript:window.history.go(-1);">'.
        Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Go back to the questions list')).'</a>';
    echo Display::toolbarAction('toolbar', [$actions]);
}

$html = '';
$message = '';
$html .= '<div class="exercise-overview">';
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
$editLink = '';
if ($is_allowed_to_edit) {
    if ($objExercise->sessionId == $sessionId) {
        $editLink = Display::url(
            Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')),
            api_get_path(WEB_CODE_PATH).'exercise/admin.php?'.api_get_cidreq().'&exerciseId='.$objExercise->id
        );
    }
    $editLink .= Display::url(
        Display::getMdiIcon('chart-box', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Results and feedback')),
        api_get_path(WEB_CODE_PATH).'exercise/exercise_report.php?'.api_get_cidreq().'&exerciseId='.$objExercise->id,
        ['title' => get_lang('Results and feedback')]
    );
}

$iconExercise = Display::getMdiIcon('order-bool-ascending-variant', 'ch-tool-icon-gradient', null, ICON_SIZE_MEDIUM, get_lang('Test'));

// Exercise name.
if ('true' === api_get_setting('editor.save_titles_as_html')) {
    $html .= Display::div(
        $objExercise->get_formated_title().PHP_EOL.$editLink
    );
} else {
    $html .= Display::page_header(
        $iconExercise.PHP_EOL.$objExercise->selectTitle().PHP_EOL.$editLink
    );
}

// Exercise description.
if (!empty($objExercise->description)) {
    $html .= Display::div($objExercise->description, ['class' => 'exercise_description wysiwyg']);
}

$extra_params = '';
if (isset($_GET['preview'])) {
    $extra_params = '&preview=1';
}

$exercise_stat_info = $objExercise->get_stat_track_exercise_info(
    $learnpath_id,
    $learnpath_item_id,
    0
);

if ($time_control && !empty($exercise_stat_info['exe_id']) && !empty($clock_expired_time)) {
    $time_left_check = api_strtotime($clock_expired_time, 'UTC') - time();
    if ($time_left_check <= 0) {
        $result_url = api_get_path(WEB_CODE_PATH).'exercise/result.php?'
            .api_get_cidreq().'&'.http_build_query([
                'id' => $exercise_stat_info['exe_id'],
                'show_headers' => in_array($origin, ['learnpath', 'embeddable', 'mobileapp']) ? 0 : 1,
                'origin' => $origin,
                'learnpath_id' => $learnpath_id,
                'learnpath_item_id' => $learnpath_item_id,
                'learnpath_item_view_id' => $learnpathItemViewId,
            ]);
        api_location($result_url);
    }
}

//1. Check if this is a new attempt or a previous
$label = get_lang('Start test');
if ($time_control && !empty($clock_expired_time) || isset($exercise_stat_info['exe_id'])) {
    $label = get_lang('Proceed with the test');
}

if (isset($exercise_stat_info['exe_id'])) {
    $message = Display::return_message(get_lang('You have tried to resolve this exercise earlier'));
}

// 2. Exercise button
// Notice we not add there the lp_item_view_id because is not already generated
$exercise_url = api_get_path(WEB_CODE_PATH).'exercise/exercise_submit.php?'.
    api_get_cidreq().'&'.http_build_query([
        'exerciseId' => $objExercise->id,
        'learnpath_id' => $learnpath_id,
        'learnpath_item_id' => $learnpath_item_id,
        'learnpath_item_view_id' => $learnpathItemViewId,
        'origin' => $origin,
    ]).$extra_params;
$exercise_url_button = Display::url(
    $label,
    $exercise_url,
    ['class' => 'btn btn--success btn-large']
);

$btnCheck = '';
$quizCheckButtonEnabled = ('true' === api_get_setting('exercise.quiz_check_button_enable'));
if ($quizCheckButtonEnabled) {
    $btnCheck = Display::button(
            'quiz_check_request_button',
            Display::getMdiIcon('loading', 'animate-spin hidden').' '.get_lang('Test your browser'),
            [
                'type' => 'button',
                'role' => 'button',
                'id' => 'quiz-check-request-button',
                'class' => 'btn btn--plain',
                'data-loading-text' => get_lang('Loading'),
                'autocomplete' => 'off',
            ]
        ).PHP_EOL.'<strong id="quiz-check-request-text"></strong>';
}

// 3. Checking visibility of the exercise (overwrites the exercise button).
$visible_return = $objExercise->is_visible(
    $learnpath_id,
    $learnpath_item_id,
    null,
    true
);

// Exercise is not visible remove the button
if (false == $visible_return['value']) {
    if ($is_allowed_to_edit) {
        $message = Display::return_message(get_lang('This item is invisible for learner but you have access as teacher.'), 'warning');
    } else {
        $message = $visible_return['message'];
        $exercise_url_button = null;
    }
}

if (!api_is_allowed_to_session_edit()) {
    $exercise_url_button = null;
}

$attempts = Event::getExerciseResultsByUser(
    api_get_user_id(),
    $objExercise->id,
    $courseId,
    $sessionId,
    $learnpath_id,
    $learnpath_item_id,
    'desc'
);
$counter = count($attempts);
$my_attempt_array = [];
$table_content = '';
$hideAttemptsTableOnStartPage = ('true' === api_get_setting('exercise.quiz_hide_attempts_table_on_start_page'));

/* Make a special case for IE, which doesn't seem to be able to handle the
 * results popup -> send it to the full results page */

$url_suffix = '';
$btn_class = ' ';
$blockShowAnswers = false;
if (in_array(
    $objExercise->results_disabled,
    [
        RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT,
        //RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK,
        RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK,
    ])
) {
    if (count($attempts) < $objExercise->attempts) {
        $blockShowAnswers = true;
    }
}

$certificateBlock = '';

if (!empty($attempts)) {
    $i = $counter;
    foreach ($attempts as $attempt_result) {
        if (empty($certificateBlock)) {
            $certificateBlock = ExerciseLib::generateAndShowCertificateBlock(
                $attempt_result['score'],
                $attempt_result['max_score'],
                $objExercise,
                $attempt_result['exe_user_id'],
                $courseId,
                $sessionId
            );
        }

        $score = ExerciseLib::show_score($attempt_result['score'], $attempt_result['max_score']);
        $attempt_url = api_get_path(WEB_CODE_PATH).'exercise/result.php?';
        $attempt_url .= api_get_cidreq().'&'.http_build_query([
            'id' => $attempt_result['exe_id'],
            'show_headers' => in_array($origin, ['learnpath', 'embeddable', 'mobileapp']) ? 0 : 1,
            'origin' => $origin,
            'learnpath_id' => $learnpath_id,
            'learnpath_item_id' => $learnpath_item_id,
            'learnpath_item_view_id' => $learnpathItemViewId,
        ]);
        $attempt_url .= $url_suffix;

        $attempt_link = Display::url(
            get_lang('Show'),
            $attempt_url,
            [
                'class' => $btn_class.'btn btn--plain',
                'data-title' => get_lang('Show'),
                'data-size' => 'lg',
            ]
        );

        $teacher_revised = Display::label(get_lang('Validated'), 'success');
        if (0 == $attempt_result['attempt_revised']) {
            $teacher_revised = Display::label(get_lang('Not validated'), 'info');
        }
        $row = [
            'count' => $i,
            'date' => api_convert_and_format_date($attempt_result['start_date'], DATE_TIME_FORMAT_LONG),
            'userIp' => $attempt_result['user_ip'],
        ];
        $attempt_link .= PHP_EOL.$teacher_revised;

        if (in_array(
            $objExercise->results_disabled,
            [
                RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS,
                RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
                RESULT_DISABLE_SHOW_SCORE_ONLY,
                RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES,
                RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT,
                RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK,
                RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK,
                RESULT_DISABLE_RANKING,
                RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
            ]
        )) {
            $row['result'] = $score;
        }

        if (in_array(
            $objExercise->results_disabled,
            [
                RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS,
                RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
                RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES,
                RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT,
                RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK,
                RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK,
                RESULT_DISABLE_RANKING,
                RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
            ]
        ) || (
            RESULT_DISABLE_SHOW_SCORE_ONLY == $objExercise->results_disabled &&
            EXERCISE_FEEDBACK_TYPE_END == $objExercise->getFeedbackType()
        )
        ) {
            if ($blockShowAnswers &&
                RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK != $objExercise->results_disabled &&
                RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT != $objExercise->results_disabled &&
                RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK != $objExercise->results_disabled
            ) {
                $attempt_link = '';
            }
            if (true == $blockShowAnswers &&
                RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK == $objExercise->results_disabled
            ) {
                if (isset($row['result'])) {
                    unset($row['result']);
                }
            }

            if (!empty($objExercise->getResultAccess())) {
                if (!$objExercise->hasResultsAccess($attempt_result)) {
                    $attempt_link = '';
                }
            }
            $row['attempt_link'] = $attempt_link;
        }
        $my_attempt_array[] = $row;
        $i--;
    }

    $header_names = [];
    $table = new HTML_Table(['class' => 'table table-striped table-hover']);
    // Hiding score and answer.
    switch ($objExercise->results_disabled) {
        case RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK:
            if ($blockShowAnswers) {
                $header_names = [get_lang('Attempt'), get_lang('Start Date'), get_lang('IP'), get_lang('Details')];
            } else {
                $header_names = [
                    get_lang('Attempt'),
                    get_lang('Start Date'),
                    get_lang('IP'),
                    get_lang('Score'),
                    get_lang('Details'),
                ];
            }

            break;
        case RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK:
        case RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT:
            $header_names = [
                get_lang('Attempt'),
                get_lang('Start Date'),
                get_lang('IP'),
                get_lang('Score'),
                get_lang('Details'),
            ];

            break;
        case RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS:
        case RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING:
        case RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES:
        case RESULT_DISABLE_RANKING:
        case RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER:
            $header_names = [
                get_lang('Attempt'),
                get_lang('Start Date'),
                get_lang('IP'),
                get_lang('Score'),
                get_lang('Details'),
            ];

            break;
        case RESULT_DISABLE_NO_SCORE_AND_EXPECTED_ANSWERS:
            $header_names = [get_lang('Attempt'), get_lang('Start Date'), get_lang('IP')];

            break;
        case RESULT_DISABLE_SHOW_SCORE_ONLY:
            if (EXERCISE_FEEDBACK_TYPE_END != $objExercise->getFeedbackType()) {
                $header_names = [get_lang('Attempt'), get_lang('Start Date'), get_lang('IP'), get_lang('Score')];
            } else {
                $header_names = [
                    get_lang('Attempt'),
                    get_lang('Start Date'),
                    get_lang('IP'),
                    get_lang('Score'),
                    get_lang('Details'),
                ];
            }

            break;
    }
    $column = 0;
    foreach ($header_names as $item) {
        $table->setHeaderContents(0, $column, $item);
        $column++;
    }
    $row = 1;
    if (!empty($my_attempt_array)) {
        foreach ($my_attempt_array as $data) {
            $column = 0;
            $table->setCellContents($row, $column, $data);
            $table->setRowAttributes($row, null, true);
            $column++;
            $row++;
        }
    }
    $table_content = $table->toHtml();
}

$selectAttempts = $objExercise->selectAttempts();
if ($selectAttempts) {
    $attempt_message = get_lang('Attempts').' '.$counter.' / '.$selectAttempts;
    if ($counter == $selectAttempts) {
        $attempt_message = Display::return_message($attempt_message, 'error');
    } else {
        $attempt_message = Display::return_message($attempt_message, 'info');
    }
    if (true == $visible_return['value']) {
        $message .= $attempt_message;
    }
}

if ($time_control) {
   // $html .= $objExercise->returnTimeLeftDiv();
}

$html .= $message;

$disable = ('true' === api_get_setting('exercise.exercises_disable_new_attempts'));
if ($disable && empty($exercise_stat_info)) {
    $exercise_url_button = Display::return_message(get_lang('The portal do not allowed to start new test for the moment, please come back later.'));
}

$isLimitReached = ExerciseLib::isQuestionsLimitPerDayReached(
    api_get_user_id(),
    count($objExercise->get_validated_question_list()),
    $courseId,
    $sessionId
);

if (!empty($exercise_url_button) && !$isLimitReached) {
    $finalExamAccess = $is_allowed_to_edit
        ? ['applies' => false, 'allowed' => true]
        : FinalExamAccessRule::evaluate(
            api_get_user_id(),
            $courseId,
            $sessionId,
            $exercise_id
        );

    if ($finalExamAccess['applies']) {
        if (!$finalExamAccess['time_requirement_met']) {
            $html .= Display::return_message(
                'You have not met the minimum time requirement. You must spend an additional <strong>'.
                FinalExamAccessRule::formatMinutes($finalExamAccess['remaining_minutes']).
                '</strong> reviewing course content. Please return to the course and reexamine the material.'.
                '<br><br><strong>Please Note</strong>: This course is timed to ensure compliance with the standard set by your state/region certification agency.',
                'warning',
                false
            );
        }

        if (!$finalExamAccess['user_identifier_present']) {
            $identifierLabel = htmlspecialchars(
                $finalExamAccess['user_identifier_label'],
                ENT_QUOTES,
                api_get_system_encoding()
            );
            $html .= '<br><br>'.Display::return_message(
                'To ensure you receive credit for this course, your '.$identifierLabel.' is required to take the final exam.',
                'warning',
                false
            );

            if ($finalExamAccess['allow_user_identifier_opt_out']) {
                $optOutPrompt = htmlspecialchars(
                    $finalExamAccess['user_identifier_opt_out_prompt'],
                    ENT_QUOTES,
                    api_get_system_encoding()
                );
                $html .= '<p>'.$optOutPrompt.'</p>';
                $html .= '<p><button type="button" class="btn btn--primary btn-primary" onclick="finalExamUseUserIdentifier(true)">Yes</button> ';
                $html .= '<button type="button" class="btn btn--plain btn-default" onclick="finalExamUseUserIdentifier(false)">No</button></p>';
            }

            $disabled = $finalExamAccess['allow_user_identifier_opt_out'] ? ' disabled="disabled"' : '';
            $html .= '<p>'.$identifierLabel.': ';
            $html .= '<input type="text" name="final_exam_user_identifier" id="final-exam-user-identifier" placeholder="Input Numbers Only" onkeypress="return finalExamIsNumberKey(event)"'.$disabled.'> ';
            $html .= '<button type="button" class="btn btn--primary btn-primary" onclick="finalExamSaveUserIdentifier()">Save</button></p>';
            $html .= '<div id="final-exam-user-identifier-message"></div>';

            $securityToken = json_encode(
                Security::get_token(),
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
            );
            $html .= <<<JS
<script>
function finalExamIsNumberKey(evt) {
    var charCode = (evt.which) ? evt.which : evt.keyCode;
    return !(charCode > 31 && (charCode < 48 || charCode > 57));
}
function finalExamUseUserIdentifier(useIdentifier) {
    var input = jQuery('#final-exam-user-identifier');
    if (useIdentifier) {
        input.prop('disabled', false).val('').focus();
        return;
    }
    input.prop('disabled', true).val('NONE');
    finalExamSaveUserIdentifier();
}
function finalExamSaveUserIdentifier() {
    var studentId = jQuery('#final-exam-user-identifier').val();
    if (studentId !== 'NONE' && !/^\d{3,}$/.test(studentId)) {
        alert('Please enter a valid student ID');
        return false;
    }
    jQuery.ajax({
        url: window.location.href,
        type: 'POST',
        dataType: 'json',
        data: {
            final_exam_user_identifier_save: 1,
            student_id: studentId,
            sec_token: {$securityToken}
        },
        success: function (response) {
            if (response && response.success) {
                jQuery('#final-exam-user-identifier-message').html('<span class="text-success">Student ID saved successfully. Reloading...</span>');
                window.setTimeout(function () { window.location.reload(); }, 1000);
                return;
            }
            jQuery('#final-exam-user-identifier-message').html('<span class="text-danger">Unable to save the Student ID.</span>');
        },
        error: function () {
            jQuery('#final-exam-user-identifier-message').html('<span class="text-danger">Unable to save the Student ID.</span>');
        }
    });
    return false;
}
</script>
JS;
        }
    }

    if ($finalExamAccess['allowed']) {
        if ($quizCheckButtonEnabled) {
            $html .= Display::div(
                $btnCheck,
                ['class' => 'exercise_overview_options']
            );
            $html .= '<br>';
        }

        $html .= Display::div(
            Display::div(
                $exercise_url_button,
                ['class' => 'exercise_overview_options']
            ),
            ['class' => 'options']
        );
    }
}

if ($isLimitReached) {
    $maxQuestionsAnswered = (int) api_get_course_setting('quiz_question_limit_per_day');

    $html .= Display::return_message(
        sprintf(get_lang('Sorry, you have reached the maximum number of questions (%s) for the day. Please try again tomorrow.'), $maxQuestionsAnswered),
        'warning',
        false
    );
}

if (!$hideAttemptsTableOnStartPage && !empty($table_content)) {
    $html .= Display::tag(
        'div',
        $table_content,
        ['class' => 'table-responsive']
    );
}
$html .= '</div>';

if ($certificateBlock) {
    $html .= PHP_EOL.$certificateBlock;
}

if ($quizCheckButtonEnabled) {
    $quizCheckRequestUrl = api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?'.api_get_cidreq().'&a=browser_test';
    $params = http_build_query(
        [
            'exe_id' => 1,
            'exerciseId' => $exercise_id,
            'learnpath_id' => $learnpath_id,
            'learnpath_item_id' => $learnpath_item_id,
            'learnpath_item_view_id' => $learnpathItemViewId,
            'reminder' => '0',
            'type' => 'simple',
            'question_id' => 23,
            'choice[23]' => 45,
        ]
    ).'&'.api_get_cidreq();

    $html .= "<script>
        $(function () {
            var btnTest = $('#quiz-check-request-button'),
                iconBtnTest = btnTest.children('.animate-spin');

            btnTest.on('click', function (e) {
                e.preventDefault();

                btnTest.prop('disabled', true).removeClass('btn--success btn--danger').addClass('btn--plain');
                iconBtnTest.removeClass('hidden');

                var txtResult = $('#quiz-check-request-text').removeClass('text-success text-error').hide();

                $
                    .when(
                        $.ajax({
                            url: '$quizCheckRequestUrl',
                            type: 'post',
                            data: '$params'
                        }),
                        $.ajax({
                            url: '$quizCheckRequestUrl',
                            type: 'post',
                            data: '$params&sleep=1'
                        })
                    )
                    .then(
                        function (xhr1, xhr2) {
                            var xhr1IsOk = !!xhr1 && xhr1[1] === 'success' && !!xhr1[0] && 'ok' === xhr1[0];
                            var xhr2IsOk = !!xhr2 && xhr2[1] === 'success' && !!xhr2[0] && 'ok' === xhr2[0];

                            if (xhr1IsOk && xhr2IsOk) {
                                btnTest.removeClass('btn--plain btn--danger').addClass('btn--success');
                                txtResult.text(\"".get_lang('Your browser has been verified. You can safely proceed.')."\").addClass('text-success').show();
                            } else {
                                btnTest.removeClass('btn--plain btn--success').addClass('btn--danger');
                                txtResult.text(\"".get_lang('Your browser could not be verified. Please try again, or try another browser or device before starting your test.')."\").addClass('text-error').show();
                            }
                        },
                        function () {
                            txtResult.text(\"".get_lang('Your browser could not be verified. Please try again, or try another browser or device before starting your test.')."\").addClass('text-error').show();
                            btnTest.removeClass('btn--plain btn--success').addClass('btn--danger');
                        }
                    )
                    .always(function () {
                        btnTest.prop('disabled', false);
                        iconBtnTest.addClass('hidden');
                    });
            });
        });
        </script>";
}

echo $html;

if ($useBlankExerciseLayout) {
    Display::display_footer();
} elseif ('mobileapp' === $origin) {
    Display::display_reduced_footer();
} else {
    Display::display_footer();
}
