<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Shows the exercise results.
 *
 * @author Julio Montoya - Simple exercise result page
 */
require_once __DIR__.'/../inc/global.inc.php';

$id = isset($_REQUEST['id']) ? (int) $_GET['id'] : 0; // exe id
$show_headers = isset($_REQUEST['show_headers']) ? (int) $_REQUEST['show_headers'] : null;
$origin = api_get_origin();

if (in_array($origin, ['learnpath', 'embeddable'])) {
    $show_headers = false;
}

api_protect_course_script($show_headers);

if (empty($id)) {
    api_not_allowed($show_headers);
}

$is_allowedToEdit = api_is_allowed_to_edit(null, true) || $is_courseTutor;

// Getting results from the exe_id. This variable also contain all the information about the exercise
$track_exercise_info = ExerciseLib::get_exercise_track_exercise_info($id);

// No track info
if (empty($track_exercise_info)) {
    api_not_allowed($show_headers);
}

$exercise_id = $track_exercise_info['exe_exo_id'];
$student_id = $track_exercise_info['exe_user_id'];
$current_user_id = api_get_user_id();

$objExercise = new Exercise();
if (!empty($exercise_id)) {
    $objExercise->read($exercise_id);
}

if (empty($objExercise)) {
    api_not_allowed($show_headers);
}

// Only users can see their own results
if (!$is_allowedToEdit) {
    if ($student_id != $current_user_id) {
        api_not_allowed($show_headers);
    }
}

$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/css/hotspot.css">';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/js/hotspot.js"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'annotation/js/annotation.js"></script>';

if (!empty($objExercise->getResultAccess())) {
    $htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/epiclock/renderers/minute/epiclock.minute.css');
    $htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.dateformat.min.js');
    $htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.epiclock.min.js');
    $htmlHeadXtra[] = api_get_js('epiclock/renderers/minute/epiclock.minute.js');
}

if ($show_headers) {
    $interbreadcrumb[] = [
        'url' => 'exercise.php?'.api_get_cidreq(),
        'name' => get_lang('Exercises'),
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Result')];
    $this_section = SECTION_COURSES;
} else {
    $htmlHeadXtra[] = '<style>
        body { background: none;}
    </style>';
}

$message = Session::read('attempt_remaining');
Session::erase('attempt_remaining');

ob_start();
ExerciseLib::displayQuestionListByAttempt(
    $objExercise,
    $id,
    false,
    $message
);
$pageContent = ob_get_contents();
ob_end_clean();

$template = new Template('', $show_headers, $show_headers);
$template->assign('page_content', $pageContent);
$layout = $template->fetch(
    $template->get_template('exercise/result.tpl')
);
$template->assign('content', $layout);
$template->display_one_col_template();

if ($show_headers) {
    Display::display_footer();
} else {
    Display::display_reduced_footer();
}
