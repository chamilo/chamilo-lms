<?php

/* For licensing terms, see /license.txt */

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
$courseInfo = api_get_course_info_by_id($courseId);
$courseCode = $courseInfo['code'];
$exercise_id = isset($_REQUEST['exerciseId']) ? (int) $_REQUEST['exerciseId'] : 0;

if (!function_exists('ricky_rescue_get_final_exam_time_rules')) {
    /**
     * Ricky Rescue legal course timing rules for Final Exam access.
     * Format: course code => [total course minutes, final exam minutes].
     */
    function ricky_rescue_get_final_exam_time_rules(): array
    {
        return [
            '2120' => [2400, 60],
            '2720' => [2400, 60],
            '2770' => [2400, 60],
            '1505' => [2400, 60],
            '1810' => [2400, 60],
            '2810' => [2400, 60],
            '2811' => [2400, 60],
            '2541' => [2400, 60],
            '1740' => [1920, 60],
            '17402021' => [1920, 60],
            '1510' => [1920, 60],
            '2521' => [1920, 60],
            '2706' => [1920, 60],
            '2741' => [720, 20],
            '27412021' => [720, 20],
            'NFPA' => [540, 120],
            '9641' => [2400, 60],
            '9516' => [2400, 60],
            '1540' => [2400, 60],
            '1302' => [1440, 60],
            '1301' => [2400, 60],
            '6742' => [2400, 60],
            '6741' => [2400, 60],
            'NFPA2018' => [540, 120],
            'COURSEDELIVERY' => [1920, 60],
            'COURSEDESIGN' => [720, 20],
            'CROWDMANAGERTRAINING' => [120, 60],
            '2111' => [2400, 60],
            'AERIALDRIVEROPERATOR' => [1920, 60],
            'NFPA2021' => [540, 120],
            'RN3842' => [480, 60],
            'NFPA2024' => [480, 240],
        ];
    }

    function ricky_rescue_minutes_to_label(int $minutes): string
    {
        $minutes = max(0, $minutes);
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return sprintf('%02d hours %02d minutes', $hours, $remainingMinutes);
    }

    function ricky_rescue_is_final_exam_learnpath(?int $learnpathId): bool
    {
        if (empty($learnpathId)) {
            return false;
        }

        $tableLp = Database::get_course_table(TABLE_LP_MAIN);
        $sql = "SELECT title FROM $tableLp WHERE iid = ".(int) $learnpathId." LIMIT 1";
        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);

        return isset($row['title']) && 'Final Exam' === trim((string) $row['title']);
    }

    function ricky_rescue_get_fire_college_extra_field_id(): int
    {
        static $fieldId = null;

        if (null !== $fieldId) {
            return $fieldId;
        }

        $fieldId = 0;
        $extraFieldTable = Database::get_main_table('extra_field');
        $candidateVariables = [
            'fcdice_or_acadis_student_id',
            'firetraq_id',
            'fire_traq_id',
            'florida_state_fire_college_id',
            'florida_fire_college_id',
            'official_code',
            'student_id',
            'fsfc_id',
        ];

        foreach ($candidateVariables as $variable) {
            $variable = Database::escape_string($variable);
            $sql = "SELECT id FROM $extraFieldTable WHERE item_type = 1 AND variable = '$variable' LIMIT 1";
            $result = Database::query($sql);
            $row = Database::fetch_assoc($result);
            if (!empty($row['id'])) {
                $fieldId = (int) $row['id'];

                return $fieldId;
            }
        }

        // Legacy Ricky used field_id = 24 for this value. Keep it only as a compatibility fallback.
        $sql = "SELECT id FROM $extraFieldTable WHERE id = 24 AND item_type = 1 LIMIT 1";
        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);
        if (!empty($row['id'])) {
            $fieldId = (int) $row['id'];
        }

        return $fieldId;
    }

    function ricky_rescue_get_fire_college_id(int $userId): string
    {
        $fieldId = ricky_rescue_get_fire_college_extra_field_id();
        if (empty($fieldId) || empty($userId)) {
            return '';
        }

        $extraFieldValueTable = Database::get_main_table('extra_field_values');
        $sql = "SELECT field_value FROM $extraFieldValueTable
                WHERE item_id = ".(int) $userId." AND field_id = ".(int) $fieldId."
                LIMIT 1";
        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);

        return isset($row['field_value']) ? trim((string) $row['field_value']) : '';
    }

    function ricky_rescue_save_fire_college_id(int $userId, string $studentId): bool
    {
        $fieldId = ricky_rescue_get_fire_college_extra_field_id();
        if (empty($fieldId) || empty($userId)) {
            return false;
        }

        $studentId = trim($studentId);
        if ('NONE' !== $studentId && !preg_match('/^\d{3,}$/', $studentId)) {
            return false;
        }

        $extraFieldValueTable = Database::get_main_table('extra_field_values');
        $studentId = Database::escape_string($studentId);
        $now = Database::escape_string(api_get_utc_datetime());
        $sql = "SELECT id FROM $extraFieldValueTable
                WHERE item_id = ".(int) $userId." AND field_id = ".(int) $fieldId."
                LIMIT 1";
        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);

        if (!empty($row['id'])) {
            $sql = "UPDATE $extraFieldValueTable
                    SET field_value = '$studentId', updated_at = '$now'
                    WHERE id = ".(int) $row['id'];
            Database::query($sql);

            return true;
        }

        $sql = "INSERT INTO $extraFieldValueTable (field_id, field_value, item_id, created_at, updated_at)
                VALUES (".(int) $fieldId.", '$studentId', ".(int) $userId.", '$now', '$now')";
        Database::query($sql);

        return true;
    }

    function ricky_rescue_render_fire_college_gate(bool $isCrowdManagerTraining): string
    {
        $html = '<br><br>'.Display::return_message(
            'To ensure you receive credit for this course, your Florida State Fire College ID is required to take the final exam.',
            'warning',
            false
        );

        if ($isCrowdManagerTraining) {
            $html .= '<p>Would you like your course completion submitted to the Florida Bureau of Fire Standards and Training?</p>';
            $html .= '<p><button type="button" class="btn btn--primary btn-primary" onclick="rickyRescueUseFireCollegeId(true)">Yes</button> ';
            $html .= '<button type="button" class="btn btn--plain btn-default" onclick="rickyRescueUseFireCollegeId(false)">No</button></p>';
        }

        $disabled = $isCrowdManagerTraining ? ' disabled="disabled"' : '';
        $html .= '<p>Florida State Fire College ID: ';
        $html .= '<input type="text" name="studentId" id="studentId" placeholder="Input Numbers Only" onkeypress="return rickyRescueIsNumberKey(event)"'.$disabled.'> ';
        $html .= '<button type="button" class="btn btn--primary btn-primary" onclick="rickyRescueSaveFireCollegeId()">Save</button></p>';
        $html .= '<div id="ricky-rescue-save-message"></div>';
        $securityToken = json_encode(
            Security::get_token(),
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        );
        $html .= <<<JS
<script>
function rickyRescueIsNumberKey(evt) {
    var charCode = (evt.which) ? evt.which : evt.keyCode;
    return !(charCode > 31 && (charCode < 48 || charCode > 57));
}
function rickyRescueUseFireCollegeId(useId) {
    var studentId = jQuery('#studentId');
    if (useId) {
        studentId.prop('disabled', false).val('');
        studentId.focus();
        return;
    }
    studentId.prop('disabled', true).val('NONE');
    rickyRescueSaveFireCollegeId();
}
function rickyRescueSaveFireCollegeId() {
    var studentId = jQuery('#studentId').val();
    if (studentId !== 'NONE' && !/^\d{3,}$/.test(studentId)) {
        alert('Please enter FireTRAQ ID');
        return false;
    }
    jQuery.ajax({
        url: window.location.href,
        type: 'POST',
        dataType: 'json',
        data: {
            ricky_fire_college_id_save: 1,
            student_id: studentId,
            sec_token: {$securityToken}
        },
        success: function (response) {
            if (response && response.success) {
                jQuery('#ricky-rescue-save-message').html('<span class="text-success">Student ID saved successfully. Reloading...</span>');
                window.setTimeout(function () { window.location.reload(); }, 1000);
                return;
            }
            jQuery('#ricky-rescue-save-message').html('<span class="text-danger">Unable to save the Student ID.</span>');
        },
        error: function () {
            jQuery('#ricky-rescue-save-message').html('<span class="text-danger">Unable to save the Student ID.</span>');
        }
    });
    return false;
}
</script>
JS;

        return $html;
    }

    function ricky_rescue_get_final_exam_gate(
        int $userId,
        int $courseId,
        string $courseCode,
        int $sessionId,
        ?int $learnpathId
    ): array {
        $rules = ricky_rescue_get_final_exam_time_rules();
        if (!isset($rules[$courseCode]) || !ricky_rescue_is_final_exam_learnpath($learnpathId)) {
            return ['allow_start' => true, 'html' => ''];
        }

        [$totalMinutes, $examMinutes] = $rules[$courseCode];
        $requiredMinutes = max(0, (int) $totalMinutes - (int) $examMinutes);
        $timeSpentSeconds = (int) Tracking::get_time_spent_on_the_course($userId, $courseId, $sessionId);
        $timeSpentMinutes = (int) floor($timeSpentSeconds / 60);
        $html = '';

        if ($timeSpentMinutes < $requiredMinutes) {
            $shortage = $requiredMinutes - $timeSpentMinutes;
            $html .= Display::return_message(
                'You have not met the minimum time requirement. You must spend an additional <strong>'.ricky_rescue_minutes_to_label($shortage).'</strong> reviewing course content. Please return to the course and reexamine the material.<br><br><strong>Please Note</strong>: This course is timed to ensure compliance with the standard set by your state/region certification agency.',
                'warning',
                false
            );
        }

        $fireCollegeId = ricky_rescue_get_fire_college_id($userId);
        if ('' === $fireCollegeId) {
            $html .= ricky_rescue_render_fire_college_gate('CROWDMANAGERTRAINING' === $courseCode);
        }

        return [
            'allow_start' => $timeSpentMinutes >= $requiredMinutes && '' !== $fireCollegeId,
            'html' => $html,
        ];
    }
}

if (isset($_POST['ricky_fire_college_id_save'])) {
    header('Content-Type: application/json; charset='.api_get_system_encoding());

    if (!Security::check_token('post')) {
        http_response_code(403);
        echo json_encode(['success' => false]);
        exit;
    }

    Security::clear_token();
    $studentId = isset($_POST['student_id']) ? trim((string) $_POST['student_id']) : '';
    $success = ricky_rescue_save_fire_college_id(api_get_user_id(), $studentId);
    echo json_encode(['success' => $success]);
    exit;
}

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
    $rickyRescueFinalExamGate = ricky_rescue_get_final_exam_gate(
        api_get_user_id(),
        $courseId,
        $courseCode,
        $sessionId,
        $learnpath_id
    );
    $html .= $rickyRescueFinalExamGate['html'];

    if ($rickyRescueFinalExamGate['allow_start']) {
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
