<?php
/* For licensing terms, see /license.txt */
/**
 * This script exports the PDF reports from a test for several students at once.
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

// Setting the section (for the tabs).
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script(true);

$sessionId = isset($_REQUEST['session_id']) ? (int) $_REQUEST['session_id'] : null;
$courseId = isset($_GET['selected_course']) ? (int) $_GET['selected_course'] : null;
$exerciseId = isset($_REQUEST['exerciseId']) ? (int) $_REQUEST['exerciseId'] : null;
$courseIdChanged = isset($_GET['course_id_changed']) ? (int) $_GET['course_id_changed'] : null;
$exerciseIdChanged = isset($_GET['exercise_id_changed']) ? (int) $_GET['exercise_id_changed'] : null;

// Get the session list
$sessionList = SessionManager::get_sessions_by_user(api_get_user_id(), api_is_platform_admin());

// Course list, get course list of session, or for course where user is admin
$courseList = [];
if (!empty($sessionId) && $sessionId != '-1' && !empty($sessionList)) {
    $sessionInfo = [];
    foreach ($sessionList as $session) {
        if ($session['session_id'] == $sessionId) {
            $sessionInfo = $session;
        }
    }
    $courseList = $sessionInfo['courses'];
} else {
    if (api_is_platform_admin()) {
        $courseList = CourseManager::get_courses_list(0, 0, 'title');
    } else {
        $courseList = CourseManager::get_course_list_of_user_as_course_admin(api_get_user_id());
    }
}

$courseInfo = [];
if (empty($courseId)) {
    $exerciseId = 0;
} else {
    $courseInfo = api_get_course_info_by_id($courseId);
}

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];

$confirmYourChoice = addslashes(get_lang('ConfirmYourChoice'));
$htmlHeadXtra[] = "
<script>
    function submit_form(obj) {
        document.export_all_results_form.submit();
    }

    function mark_course_id_changed() {
        $('#course_id_changed').val('0');
    }

    function mark_exercise_id_changed() {
        $('#exercise_id_changed').val('0');
    }

    function confirm_your_choice() {
        return confirm('$confirmYourChoice');
    }
</script>";

$sessionSelectList = [0 => get_lang('Select')];
foreach ($sessionList as $item) {
    $sessionSelectList[$item['session_id']] = $item['session_name'];
}

$courseSelectList = [0 => get_lang('Select')];
foreach ($courseList as $item) {
    $courseItemId = $item['real_id'];
    $courseInfo = api_get_course_info_by_id($courseItemId);
    $courseSelectList[$courseItemId] = '';
    if ($courseItemId == $courseId) {
        $courseSelectList[$courseItemId] = '>&nbsp;&nbsp;&nbsp;&nbsp;';
    }
    $courseSelectList[$courseItemId] = $courseInfo['title'];
}

// If course has changed, reset the menu default
if (!empty($courseSelectList) && !in_array($courseId, array_keys($courseSelectList))) {
    $courseId = 0;
}

$courseInfo = api_get_course_info_by_id($courseId);

// Get exercise list for this course
$exerciseList = ExerciseLib::get_all_exercises_for_course_id(
    $courseInfo,
    $sessionId,
    $courseId,
    false
);

$exerciseSelectList = [];
$exerciseSelectList = [0 => get_lang('Select')];
if (is_array($exerciseList)) {
    foreach ($exerciseList as $row) {
        $exerciseTitle = $row['title'];
        $exerciseSelectList[$row['iid']] = $exerciseTitle;
    }
}

$url = api_get_self().'?'.api_get_cidreq().'&'.http_build_query(
        [
            'session_id' => $sessionId,
            'selected_course' => $courseId,
            'exerciseId' => $exerciseId,
            'course_id_changed' => $courseIdChanged,
            'exercise_id_changed' => $exerciseIdChanged,
        ]
    );

// Form
$form = new FormValidator('export_all_results_form', 'GET', $url);
$form->addHeader(get_lang('ExportExerciseAllResults'));
$form
    ->addSelect(
        'session_id',
        get_lang('Session'),
        $sessionSelectList,
        ['onchange' => 'submit_form(this)', 'id' => 'session_id']
    )
    ->setSelected($sessionId);
$form
    ->addSelect(
        'selected_course',
        get_lang('Course'),
        $courseSelectList,
        ['onchange' => 'mark_course_id_changed(); submit_form(this);', 'id' => 'selected_course']
    )
    ->setSelected($courseId);
$form
    ->addSelect(
        'exerciseId',
        get_lang('Exercise'),
        $exerciseSelectList
    )
    ->setSelected($exerciseId);

$form->addDateTimePicker('start_date', get_lang('StartDate'));
$form->addDateTimePicker('end_date', get_lang('EndDate'));
$form->addRule('start_date', get_lang('InvalidDate'), 'datetime');
$form->addRule('end_date', get_lang('InvalidDate'), 'datetime');

$form->addRule(
    ['start_date', 'end_date'],
    get_lang('StartDateShouldBeBeforeEndDate'),
    'date_compare',
    'lte'
);

$form->addHidden('course_id_changed', '0');
$form->addHidden('exercise_id_changed', '0');
$form->addButtonExport(get_lang('Export'), 'name');

if ($form->validate()) {
    $values = $form->getSubmitValues();

    if (!empty($values['exerciseId']) && !empty($values['selected_course'])) {
        $sessionId = (int) $values['session_id'];
        $courseId = (int) $values['selected_course'];
        $exerciseId = (int) $values['exerciseId'];
        $filterDates = [
            'start_date' => (!empty($values['start_date']) ? $values['start_date'] : ''),
            'end_date' => (!empty($values['end_date']) ? $values['end_date'] : ''),
        ];
        ExerciseLib::exportExerciseAllResultsZip($sessionId, $courseId, $exerciseId, $filterDates);
    }
}

Display::display_header(get_lang('ExportExerciseAllResults'));

echo Display::return_message(
    get_lang('PleaseWaitThisCouldTakeAWhile'),
    'normal',
    false,
);

echo $form->display();

Display::display_footer();
