<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Shows the exercise results
 *
 * @author Julio Montoya - Simple exercise result page
 *
 */
require_once __DIR__.'/../inc/global.inc.php';

$id = isset($_REQUEST['id']) ? intval($_GET['id']) : null; //exe id
$show_headers = isset($_REQUEST['show_headers']) ? intval($_REQUEST['show_headers']) : null; //exe id
$origin = api_get_origin();

if ($origin == 'learnpath') {
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

// Only users can see their own results
if (!$is_allowedToEdit) {
    if ($student_id != $current_user_id) {
        api_not_allowed($show_headers);
    }
}

$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/css/hotspot.css">';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/js/hotspot.js"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'annotation/js/annotation.js"></script>';

if ($show_headers) {
    $interbreadcrumb[] = array(
        "url" => "exercise.php?".api_get_cidreq(),
        "name" => get_lang('Exercises')
    );
    $interbreadcrumb[] = array("url" => "#", "name" => get_lang('Result'));
    $this_section = SECTION_COURSES;
    Display::display_header();
} else {
    $htmlHeadXtra[] = '<style>
        body { background: none;}
    </style>';
    Display::display_reduced_header();
}

$message = Session::read('attempt_remaining');
Session::erase('attempt_remaining');

ExerciseLib::displayQuestionListByAttempt(
    $objExercise,
    $id,
    false,
    $message
);

if ($show_headers) {
    Display::display_footer();
}
