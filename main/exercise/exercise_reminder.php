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
/** @var Exercise $objExercise */
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
    $objExercise->iid,
    $learnpath_id,
    $learnpath_item_id
);

if ($objExercise->expired_time != 0 && !empty($clock_expired_time)) {
    $time_control = true;
}

if ($time_control) {
    // Get time left for expiring time
    $time_left = api_strtotime($clock_expired_time, 'UTC') - time();
    $htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/epiclock/stylesheet/jquery.epiclock.css');
    $htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/epiclock/renderers/minute/epiclock.minute.css');
    $htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.dateformat.min.js');
    $htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.epiclock.min.js');
    $htmlHeadXtra[] = api_get_js('epiclock/renderers/minute/epiclock.minute.js');
    $htmlHeadXtra[] = $objExercise->showTimeControlJS($time_left);
}

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

$nameTools = get_lang('Exercises');
$interbreadcrumb[] = ['url' => 'exercise.php?'.api_get_cidreq(), 'name' => get_lang('Exercises')];
$hideHeaderAndFooter = in_array($origin, ['learnpath', 'embeddable', 'iframe']);

if (!$hideHeaderAndFooter) {
    Display::display_header($nameTools, get_lang('Exercise'));
} else {
    Display::display_reduced_header();
}

// I'm in a preview mode as course admin. Display the action menu.
if (!$hideHeaderAndFooter && api_is_course_admin()) {
    echo '<div class="actions">';
    echo '<a href="admin.php?'.api_get_cidreq().'&exerciseId='.$objExercise->iid.'">'.
        Display::return_icon('back.png', get_lang('GoBackToQuestionList'), [], 32).'</a>';
    echo '<a href="exercise_admin.php?'.api_get_cidreq().'&modifyExercise=yes&exerciseId='.$objExercise->iid.'">'.
        Display::return_icon('edit.png', get_lang('ModifyExercise'), [], 32).'</a>';
    echo '</div>';
}
echo Display::page_header(get_lang('QuestionsToReview'));

if ($time_control) {
    echo $objExercise->returnTimeLeftDiv();
}

$selectionType = $objExercise->getQuestionSelectionType();
if (api_get_configuration_value('block_category_questions') &&
    ONE_PER_PAGE == $objExercise->type &&
    EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_RANDOM == $selectionType
) {
    $extraFieldValue = new ExtraFieldValue('exercise');
    $extraFieldData = $extraFieldValue->get_values_by_handler_and_field_variable($objExercise->iid, 'block_category');
    if ($extraFieldData && isset($extraFieldData['value']) && 1 === (int) $extraFieldData['value']) {
        // get last category question list
        $categoryList = Session::read('categoryList');
        $question_list = end($categoryList);
    }
}

echo $objExercise->getReminderTable($question_list, $exercise_stat_info);

$exerciseActions = Display::url(
    get_lang('ReviewQuestions'),
    'javascript://',
    ['onclick' => 'reviewQuestions();', 'class' => 'btn btn-primary']
);

$exerciseActions .= '&nbsp;'.Display::url(
    get_lang('SelectAll'),
    'javascript://',
    ['onclick' => 'changeOptionStatus(1);', 'class' => 'btn btn-default']
);

$exerciseActions .= '&nbsp;'.Display::url(
    get_lang('UnSelectAll'),
    'javascript://',
    ['onclick' => 'changeOptionStatus(0);', 'class' => 'btn btn-default']
);

$exerciseActions .= '&nbsp;'.Display::url(
    get_lang('EndTest'),
    'javascript://',
    ['onclick' => 'final_submit();', 'class' => 'btn btn-warning']
);

echo Display::div('', ['class' => 'clear']);
echo Display::div($exerciseActions, ['class' => 'form-actions']);

if (!$hideHeaderAndFooter) {
    // We are not in learnpath tool or embeddable quiz
    Display::display_footer();
} else {
    Display::display_reduced_footer();
}
