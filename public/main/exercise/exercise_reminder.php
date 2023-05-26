<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Exercise reminder overview
 * Then it shows the results on the screen.
 *
 * @author Julio Montoya switchable fill in blank option added
 */
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_QUIZ;

$this_section = SECTION_COURSES;

api_protect_course_script(true);
$origin = api_get_origin();

$learnpath_id = isset($_REQUEST['learnpath_id']) ? (int) $_REQUEST['learnpath_id'] : 0;
$learnpath_item_id = isset($_REQUEST['learnpath_item_id']) ? (int) $_REQUEST['learnpath_item_id'] : 0;
$learnpath_item_view_id = isset($_REQUEST['learnpath_item_view_id']) ? (int) $_REQUEST['learnpath_item_view_id'] : 0;
$exerciseId = isset($_REQUEST['exerciseId']) ? (int) $_REQUEST['exerciseId'] : 0;

$objExercise = null;
$exerciseInSession = Session::read('objExercise');
if (!empty($exerciseInSession)) {
    $objExercise = $exerciseInSession;
}

if (!$objExercise) {
    // Redirect to the exercise overview
    // Check if the exe_id exists
    header('Location: '.api_get_path(WEB_CODE_PATH).'exercise/overview.php?exerciseId='.$exerciseId.'&'.api_get_cidreq());
    exit;
}

$time_control = false;
$clock_expired_time = ExerciseLib::get_session_time_control_key(
    $objExercise->getId(),
    $learnpath_id,
    $learnpath_item_id
);

if (0 != $objExercise->expired_time && !empty($clock_expired_time)) {
    $time_control = true;
}

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

$htmlHeadXtra[] = api_get_build_js('exercise.js');
$htmlHeadXtra[] = api_get_css_asset('pretty-checkbox/dist/pretty-checkbox.min.css');

$exe_id = 0;
if (isset($_GET['exe_id'])) {
    $exe_id = (int) $_GET['exe_id'];
    Session::write('exe_id', $exe_id);
}

$exe_id = (int) Session::read('exe_id');

$exercise_stat_info = $objExercise->get_stat_track_exercise_info_by_exe_id($exe_id);
$question_list = [];
if (!empty($exercise_stat_info['data_tracking'])) {
    $question_list = explode(',', $exercise_stat_info['data_tracking']);
}

if (empty($exercise_stat_info) || empty($question_list)) {
    api_not_allowed();
}

$nameTools = get_lang('Tests');
$interbreadcrumb[] = ['url' => 'exercise.php?'.api_get_cidreq(), 'name' => get_lang('Tests')];

$hideHeaderAndFooter = in_array($origin, ['learnpath', 'embeddable']);

if (!$hideHeaderAndFooter) {
    Display::display_header($nameTools, get_lang('Test'));
} else {
    Display::display_reduced_header();
}

/* DISPLAY AND MAIN PROCESS */

// I'm in a preview mode as course admin. Display the action menu.
if (!$hideHeaderAndFooter && api_is_course_admin()) {
    $actions = '<a href="admin.php?'.api_get_cidreq().'&exerciseId='.$objExercise->getId().'">'.
        Display::return_icon('back.png', get_lang('Go back to the questions list'), [], 32).'</a>';
    $actions .= '<a href="exercise_admin.php?'.api_get_cidreq().'&modifyTest=yes&exerciseId='.$objExercise->getId().'">'.
        Display::return_icon('edit.png', get_lang('ModifyTest'), [], 32).'</a>';
    echo Display::toolbarAction('toolbar', [$actions]);
}
echo Display::page_header(get_lang('Questions to be reviewed'));

if ($time_control) {
    echo $objExercise->returnTimeLeftDiv();
}

$selectionType = $objExercise->getQuestionSelectionType();
if (('true' === api_get_setting('exercise.block_category_questions')) &&
    ONE_PER_PAGE == $objExercise->type &&
    EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_RANDOM == $selectionType
) {
    $extraFieldValue = new ExtraFieldValue('exercise');
    $extraFieldData = $extraFieldValue->get_values_by_handler_and_field_variable($objExercise->iId, 'block_category');
    if ($extraFieldData && isset($extraFieldData['value']) && 1 === (int) $extraFieldData['value']) {
        $categoryList = Session::read('categoryList');
        $question_list = end($categoryList);
    }
}

echo $objExercise->getReminderTable($question_list, $exercise_stat_info);

$exerciseActions = Display::url(
    get_lang('Review selected questions'),
    'javascript://',
    ['onclick' => 'review_questions();', 'class' => 'btn btn--primary']
);

$exerciseActions .= '&nbsp;'.Display::url(
    get_lang('Select all'),
    'javascript://',
    ['onclick' => 'changeOptionStatus(1);', 'class' => 'btn btn--plain']
);

$exerciseActions .= '&nbsp;'.Display::url(
    get_lang('UnSelect all'),
    'javascript://',
    ['onclick' => 'changeOptionStatus(0);', 'class' => 'btn btn--plain']
);

$exerciseActions .= '&nbsp;'.Display::url(
    get_lang('End test'),
    'javascript://',
    ['onclick' => 'final_submit();', 'class' => 'btn btn--warning']
);

echo Display::div('', ['class' => 'clear']);
echo Display::div($exerciseActions, ['class' => 'form-actions']);

if (!$hideHeaderAndFooter) {
    // We are not in learnpath tool or embeddable quiz
    Display::display_footer();
} else {
    Display::display_reduced_footer();
}
