<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_QUIZ;

if (false === api_get_configuration_value('block_category_questions')) {
    api_not_allowed(true);
}

$this_section = SECTION_COURSES;
api_protect_course_script(true);
$origin = api_get_origin();
$learnpath_id = isset($_REQUEST['learnpath_id']) ? (int) $_REQUEST['learnpath_id'] : 0;
$learnpath_item_id = isset($_REQUEST['learnpath_item_id']) ? (int) $_REQUEST['learnpath_item_id'] : 0;
$learnpath_item_view_id = isset($_REQUEST['learnpath_item_view_id']) ? (int) $_REQUEST['learnpath_item_view_id'] : 0;
$exerciseId = isset($_REQUEST['exerciseId']) ? (int) $_REQUEST['exerciseId'] : 0;
$currentQuestion = isset($_REQUEST['num']) ? (int) $_REQUEST['num'] : 1;
$exeId = isset($_REQUEST['exe_id']) ? (int) $_REQUEST['exe_id'] : 0;
$questionCategoryId = isset($_REQUEST['category_id']) ? (int) $_REQUEST['category_id'] : 0;
$validateCategory = isset($_REQUEST['validate']) && 1 === (int) $_REQUEST['validate'];

/** @var Exercise $objExercise */
$objExercise = null;
$exerciseInSession = Session::read('objExercise');
if (!empty($exerciseInSession)) {
    $objExercise = $exerciseInSession;
}

$category = new TestCategory();
$categoryObj = $category->getCategory($questionCategoryId);

if (empty($objExercise) || empty($questionCategoryId) || empty($exeId) || empty($categoryObj)) {
    api_not_allowed(true);
}

$categoryId = (int) $categoryObj->iid;
$params = "exe_id=$exeId&exerciseId=$exerciseId&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id&learnpath_item_view_id=$learnpath_item_view_id&".api_get_cidreq();
$url = api_get_path(WEB_CODE_PATH).'exercise/exercise_submit.php?'.$params;
$validateUrl = api_get_path(WEB_CODE_PATH).'exercise/exercise_question_reminder.php?'.
    $params.'&category_id='.$categoryId.'&validate=1';

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

$trackInfo = $objExercise->get_stat_track_exercise_info_by_exe_id($exeId);
if (empty($trackInfo)) {
    api_not_allowed();
}
$blockedCategories = [];
if (isset($trackInfo['blocked_categories']) && !empty($trackInfo['blocked_categories'])) {
    $blockedCategories = explode(',', $trackInfo['blocked_categories']);
}

if ($validateCategory) {
    $blockedCategories[] = $categoryId;
    $blockedCategories = array_unique($blockedCategories);
    $value = implode(',', $blockedCategories);
    $value = Database::escape_string($value);
    $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
    $sql = "UPDATE $table
            SET blocked_categories = '$value'
            WHERE exe_id = $exeId";
    Database::query($sql);

    // Cleaning old remind list.
    $objExercise->removeAllQuestionToRemind($exeId);
    api_location($url.'&num='.$currentQuestion);
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
echo Display::page_header($categoryObj->name);
echo '<p>'.Security::remove_XSS($categoryObj->description).'</p>';
echo '<p>'.get_lang('BlockCategoryExplanation').'</p>';

$categoryList = Session::read('categoryList');
$disableAllQuestions = '';
$questionList = [];
if (isset($categoryList[$categoryId])) {
    $questionList = $categoryList[$categoryId];
}
if ($objExercise->review_answers) {
    $disableAllQuestions = 'changeOptionStatus(0);';
    echo $objExercise->getReminderTable($questionList, $trackInfo);
}

if ($time_control) {
    echo $objExercise->returnTimeLeftDiv();
}

echo Display::div('', ['id' => 'message']);
$previousQuestion = $currentQuestion - 1;

$nextQuestion = $currentQuestion + 1;
if (!empty($questionList)) {
    $firstQuestionOfCategory = end($questionList);
    $dataTracking = explode(',', $trackInfo['data_tracking']);
    $index = 0;
    foreach ($dataTracking as $index => $question) {
        if ($firstQuestionOfCategory == $question) {
            break;
        }
    }
    $nextQuestion = $index + 1;
}

echo '<script>
    function goBack() {
        window.location = "'.$url.'&num='.$previousQuestion.'";
    }

    function continueExercise() {
        '.$disableAllQuestions.'
        window.location = "'.$validateUrl.'&num='.$nextQuestion.'";
    }

    function final_submit() {
        window.location = "'.api_get_path(WEB_CODE_PATH).'exercise/exercise_result.php?'.api_get_cidreq().'&exe_id='.$exeId.'&" + lp_data;
    }
</script>';

$exercise_result = $objExercise->getUserAnswersSavedInExercise($exeId);
echo '<div class="clear"></div><br />';
$table = '';
$counter = 0;
echo Display::div($table, ['class' => 'question-check-test']);

$exerciseActions = '';
if (!in_array($categoryId, $blockedCategories)) {
    $exerciseActions = '&nbsp;'.Display::url(
        get_lang('GoBack'),
        'javascript://',
        ['onclick' => 'goBack();', 'class' => 'btn btn-default']
    );
}

if ($objExercise->review_answers) {
    $exerciseActions .= Display::url(
        get_lang('ReviewQuestions'),
        'javascript://',
        ['onclick' => 'reviewQuestions();', 'class' => 'btn btn-primary']
    );

    $exerciseActions .= '&nbsp;'.Display::url(
        get_lang('SelectAll'),
        'javascript://',
        ['onclick' => 'selectAll();', 'class' => 'btn btn-default']
    );

    $exerciseActions .= '&nbsp;'.Display::url(
        get_lang('UnSelectAll'),
        'javascript://',
        ['onclick' => 'changeOptionStatus(0);', 'class' => 'btn btn-default']
    );
}

end($categoryList);

// This is the last category
if (key($categoryList) === $categoryId) {
    $exerciseActions .= '&nbsp;'.Display::url(
        get_lang('EndTest'),
        'javascript://',
        ['onclick' => 'final_submit();', 'class' => 'btn btn-warning']
    );
} else {
    $exerciseActions .= '&nbsp;'.Display::url(
        get_lang('ContinueTest'),
        'javascript://',
        ['onclick' => 'continueExercise();', 'class' => 'btn btn-primary']
    );
}

echo Display::div('', ['class' => 'clear']);
echo Display::div($exerciseActions, ['class' => 'form-actions']);

if (!$hideHeaderAndFooter) {
    // We are not in learnpath tool or embeddable quiz
    Display::display_footer();
} else {
    Display::display_reduced_footer();
}
