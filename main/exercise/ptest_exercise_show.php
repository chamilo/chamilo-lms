<?php
/* For licensing terms, see /licence.txt */

use ChamiloSession as Session;

/**
 *  Shows the exercise results.
 *
 * @author Jose Angel Ruiz (NOSOLORED)
 *
 * @package chamilo.exercise
 */
require_once __DIR__.'/../inc/global.inc.php';

$origin = api_get_origin();
$currentUserId = api_get_user_id();
$printHeaders = $origin === 'learnpath';
$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0; //exe id

if (empty($id)) {
    api_not_allowed(true);
}

// Getting results from the exe_id. This variable also contain all the information about the exercise
$trackExerciseInfo = ExerciseLib::get_exercise_track_exercise_info($id);

//No track info
if (empty($trackExerciseInfo)) {
    api_not_allowed($printHeaders);
}

$exerciseId = $trackExerciseInfo['id'];
$studentId = $trackExerciseInfo['exe_user_id'];
$isBossOfStudent = false;
if (api_is_student_boss()) {
    // Check if boss has access to user info.
    if (UserManager::userIsBossOfStudent($currentUserId, $studentId)) {
        $isBossOfStudent = true;
    } else {
        api_not_allowed($printHeaders);
    }
} else {
    api_protect_course_script($printHeaders, false, true);
}

if (empty($formSent)) {
    $formSent = isset($_REQUEST['formSent']) ? $_REQUEST['formSent'] : null;
}
if (empty($exerciseResult)) {
    $exerciseResult = Session::read('exerciseResult');
}

if (empty($choiceDegreeCertainty)) {
    $choiceDegreeCertainty = isset($_REQUEST['choiceDegreeCertainty']) ? $_REQUEST['choiceDegreeCertainty'] : null;
}
$questionId = isset($_REQUEST['questionId']) ? (int) $_REQUEST['questionId'] : null;

if (empty($choice)) {
    $choice = isset($_REQUEST['choice']) ? $_REQUEST['choice'] : null;
}
if (empty($questionNum)) {
    $questionNum = isset($_REQUEST['num']) ? $_REQUEST['num'] : null;
}
if (empty($nbrQuestions)) {
    $nbrQuestions = isset($_REQUEST['nbrQuestions']) ? $_REQUEST['nbrQuestions'] : null;
}
if (empty($questionList)) {
    $questionList = Session::read('questionList');
}
if (empty($objExercise)) {
    $objExercise = Session::read('objExercise');
}
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;

$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();

$isAllowedToEdit =
    api_is_allowed_to_edit(null, true) ||
    api_is_course_tutor() ||
    api_is_session_admin() ||
    api_is_drh() ||
    api_is_student_boss();

if (!empty($sessionId) && !$isAllowedToEdit) {
    if (api_is_course_session_coach(
        $currentUserId,
        api_get_course_int_id(),
        $sessionId
    )) {
        if (!api_coach_can_edit_view_results(api_get_course_int_id(), $sessionId)) {
            api_not_allowed($printHeaders);
        }
    }
} else {
    if (!$isAllowedToEdit) {
        api_not_allowed($printHeaders);
    }
}

if (api_is_excluded_user_type(true, $studentId)) {
    api_not_allowed($printHeaders);
}

$locked = api_resource_is_locked_by_gradebook($exerciseId, LINK_EXERCISE);

if (empty($objExercise)) {
    $objExercise = new Exercise();
    $objExercise->read($exerciseId);
}

// Only users can see their own results
if (!$isAllowedToEdit) {
    if ($studentId != $currentUserId) {
        api_not_allowed($printHeaders);
    }
}

$js = '<script>'.api_get_language_translate_html().'</script>';
$htmlHeadXtra[] = $js;

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('ToolGradebook'),
    ];
}

$interbreadcrumb[] = [
    'url' => 'exercise.php?'.api_get_cidreq(),
    'name' => get_lang('Exercises'),
];
$interbreadcrumb[] = [
    'url' => 'ptest_exercise_report.php?exerciseId='.$exerciseId.'&'.api_get_cidreq(),
    'name' => $objExercise->selectTitle(true),
];
$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Result')];

$this_section = SECTION_COURSES;

$htmlHeadXtra[] = api_get_js('chartjs/Chart.min.js');

if ($objExercise->selectPtType() == EXERCISE_PT_TYPE_PTEST) {
    Display::display_header('');

    $message = Session::read('attempt_remaining');
    Session::erase('attempt_remaining');

    ExerciseLib::displayQuestionListByAttempt(
        $objExercise,
        $id,
        false,
        $message
    );
    Display::display_footer();
    exit;
}
