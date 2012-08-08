<?php
/* For licensing terms, see /license.txt */
/**
 *  Shows the exercise results 
 *
 * @author Julio Montoya Armas  - Simple exercise result page
 * 
 */

/**
 * Code
 */
// name of the language file that needs to be included
$language_file = array('exercice');

// including additional libraries
require_once 'exercise.class.php';
require_once 'question.class.php';
require_once 'answer.class.php';

require_once '../inc/global.inc.php';
require_once 'exercise.lib.php';

if (empty($origin)) {
    $origin = $_REQUEST['origin'];
}

if ($origin == 'learnpath')
    api_protect_course_script();
else
	api_protect_course_script(true);

$id 	       = isset($_REQUEST['id']) 	  ? intval($_GET['id']) : null; //exe id
$show_headers  = isset($_GET['show_headers']) ? intval($_GET['show_headers']) : null; //exe id

if ($origin == 'learnpath') {
	$show_headers = false;
}

if (empty($id)) {
	api_not_allowed();
}

$is_allowedToEdit   = api_is_allowed_to_edit(null,true) || $is_courseTutor;

//Getting results from the exe_id. This variable also contain all the information about the exercise
$track_exercise_info = get_exercise_track_exercise_info($id);

//No track info
if (empty($track_exercise_info)) {
    api_not_allowed(false);
}
$exercise_id        = $track_exercise_info['id'];
$exercise_date      = $track_exercise_info['start_date'];
$student_id         = $track_exercise_info['exe_user_id'];
$learnpath_id       = $track_exercise_info['orig_lp_id'];
$learnpath_item_id  = $track_exercise_info['orig_lp_item_id'];    
$lp_item_view_id    = $track_exercise_info['orig_lp_item_view_id'];
$course_code        = api_get_course_id();
$current_user_id    = api_get_user_id();

if (empty($objExercise)) {
	$objExercise = new Exercise();
    $objExercise->read($exercise_id);
}

//Only users can see their own results 
if (!$is_allowedToEdit) {
    if ($student_id != $current_user_id) {
    	api_not_allowed();
    }
}

if ($show_headers) {
	$interbreadcrumb[] = array("url" => "exercice.php","name" => get_lang('Exercices'));
	$interbreadcrumb[] = array("url" => "#","name" => get_lang('Result'));
	$this_section = SECTION_COURSES;
	
	Display::display_header();
} else {
	Display::display_reduced_header();
}	

display_question_list_by_attempt($objExercise, $id, false);

if ($show_headers) {
	Display::display_footer();
}