<?php
/* For licensing terms, see /license.txt */
/**
*	Exercise result
*	This script gets informations from the script "exercise_submit.php",
*	through the session, and calculates the score of the student for
*	that exercise.
*	Then it shows the results on the screen.
*	@package chamilo.exercise
*	@author Olivier Brouckaert, main author
*	@author Roan Embrechts, some refactoring
* 	@author Julio Montoya Armas switchable fill in blank option added
*
*	@todo	split more code up in functions, move functions to library?
*/
/**
 * Code
 */
require_once 'exercise.class.php';
require_once 'question.class.php';
require_once 'answer.class.php';

// Name of the language file that needs to be included
$language_file = 'exercice';

require_once '../inc/global.inc.php';
require_once 'exercise.lib.php';

if ($_GET['origin']=='learnpath') {
	require_once '../newscorm/learnpath.class.php';
	require_once '../newscorm/learnpathItem.class.php';
	require_once '../newscorm/scorm.class.php';
	require_once '../newscorm/scormItem.class.php';
	require_once '../newscorm/aicc.class.php';
	require_once '../newscorm/aiccItem.class.php';
}
require_once api_get_path(LIBRARY_PATH).'exercise_show_functions.lib.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';

$this_section = SECTION_COURSES;

/* 	ACCESS RIGHTS  */
api_protect_course_script(true);

// Database table definitions
$main_admin_table       = Database::get_main_table(TABLE_MAIN_ADMIN);

if($debug>0){error_log('Entered exercise_result.php: '.print_r($_POST,1));}

// general parameters passed via POST/GET
if ( empty ( $origin ) ) {                  $origin                 = Security::remove_XSS($_REQUEST['origin']);}
if ( empty ( $objExercise ) ) {             $objExercise            = $_SESSION['objExercise'];}
if ( empty ( $remind_list ) ) {             $remind_list            = $_REQUEST['remind_list'];}

$exe_id = isset($_REQUEST['exe_id']) ? intval($_REQUEST['exe_id']) : 0;

if (empty($objExercise)) {
	//Redirect to the exercise overview
	//Check if the exe_id exists
	$objExercise = new Exercise();
	$exercise_stat_info = $objExercise->get_stat_track_exercise_info_by_exe_id($exe_id);
	if (!empty($exercise_stat_info) && isset($exercise_stat_info['exe_exo_id'])) {
		header("Location: overview.php?exerciseId=".$exercise_stat_info['exe_exo_id']);
		exit;
	}	
    api_not_allowed();
}

$gradebook = '';
if (isset($_SESSION['gradebook'])) {
	$gradebook=	$_SESSION['gradebook'];
}
if (!empty($gradebook) && $gradebook=='view') {
	$interbreadcrumb[]= array ('url' => '../gradebook/'.$_SESSION['gradebook_dest'], 'name' => get_lang('ToolGradebook'));
}

$nameTools = get_lang('Exercice');

$interbreadcrumb[]= array("url" => "exercice.php?gradebook=$gradebook","name" => get_lang('Exercices'));

if ($origin != 'learnpath') {
	//so we are not in learnpath tool
	Display::display_header($nameTools,get_lang('Exercise'));
} else {
    Display::display_reduced_header();
}

//Hide results
$show_results     = false;
$show_only_score  = false;

if ($objExercise->results_disabled == 0) {
    $show_results = true;	
}

if ($objExercise->results_disabled == 2) {
    $show_only_score = true;
}

/* DISPLAY AND MAIN PROCESS */

// I'm in a preview mode as course admin. Display the action menu.
if (api_is_course_admin() && $origin != 'learnpath') {
	echo '<div class="actions">';
	echo '<a href="admin.php?'.api_get_cidreq().'&exerciseId='.$objExercise->id.'">'.Display::return_icon('back.png', get_lang('GoBackToQuestionList'), array(), 32).'</a>';
	echo '<a href="exercise_admin.php?'.api_get_cidreq().'&modifyExercise=yes&exerciseId='.$objExercise->id.'">'.Display::return_icon('edit.png', get_lang('ModifyExercise'), array(), 32).'</a>';
	echo '</div>';
}

$feedback_type           = $objExercise->feedbacktype;
$exercise_stat_info      = $objExercise->get_stat_track_exercise_info_by_exe_id($exe_id);

if (!empty($exercise_stat_info['data_tracking'])) {
	$question_list		= explode(',', $exercise_stat_info['data_tracking']);
}

$safe_lp_id              = $exercise_stat_info['orig_lp_id'];
$safe_lp_item_id         = $exercise_stat_info['orig_lp_item_id'];
$safe_lp_item_view_id    = $exercise_stat_info['orig_lp_item_view_id'];

if ($origin == 'learnpath') {		
?>
	<form method="get" action="exercice.php?<?php echo api_get_cidreq() ?>">
	<input type="hidden" name="origin" 					value="<?php echo $origin; ?>" />
    <input type="hidden" name="learnpath_id" 			value="<?php echo $safe_lp_id; ?>" />
    <input type="hidden" name="learnpath_item_id" 		value="<?php echo $safe_lp_item_id; ?>" />
    <input type="hidden" name="learnpath_item_view_id"  value="<?php echo $safe_lp_item_view_id; ?>" />
<?php
}

$i = $total_score = $total_weight = 0;

//We check if the user attempts before sending to the exercise_result.php                
if ($objExercise->selectAttempts() > 0) {
    $attempt_count = get_attempt_count(api_get_user_id(), $objExercise->id, $safe_lp_id, $safe_lp_item_id, $safe_lp_item_view_id);                
    if ($attempt_count >= $objExercise->selectAttempts()) {        
        Display :: display_warning_message(sprintf(get_lang('ReachedMaxAttempts'), $objExercise->selectTitle(), $objExercise->selectAttempts()), false);
        if ($origin != 'learnpath') {
            //we are not in learnpath tool
            Display::display_footer();
        }                                      
        exit;
    }
}


$user_info   = api_get_user_info(api_get_user_id());     
if ($show_results || $show_only_score) {
    echo $exercise_header = $objExercise->show_exercise_result_header(api_get_person_name($user_info['firstName'], $user_info['lastName']));
}

Display :: display_confirmation_message(get_lang('Saved').'<br />',false);

// Display text when test is finished #4074
// Don't display the text when finished message if we are from a LP #4227
// but display it from page exercice_show.php
$end_of_message = $objExercise->selectTextWhenFinished();
if (!empty($end_of_message) && ($origin != 'learnpath')) {
    Display::display_normal_message($end_of_message, false);
    echo "<div class='clear'>&nbsp;</div>";
}

$counter = 1;
// Loop over all question to show results for each of them, one by one
if (!empty($question_list)) {
	foreach ($question_list as $questionId) {
	    // destruction of the Question object
		unset($objQuestionTmp);
		
		// gets the student choice for this question
		$choice                = $exerciseResult[$questionId];
	    
		// creates a temporary Question object
		$objQuestionTmp        = Question :: read($questionId);
			
		//this variable commes from exercise_submit_modal.php	
	
		//$hotspot_delineation_result = $_SESSION['hotspot_delineation_result'][$objExercise->selectId()][$quesId]; 
		
		if ($show_results) {
		    // show category
		    Testcategory::displayCategoryAndTitle($objQuestionTmp->id);
	    	// show titles    	
	    	echo $objQuestionTmp->return_header($objExercise->feedback_type, $counter);
	    	$counter++;    	
		}
		
	    // We're inside *one* question. Go through each possible answer for this question
	    $result = $objExercise->manage_answer($exercise_stat_info['exe_id'], $questionId, null ,'exercise_result', array(), false, true, $show_results, $objExercise->selectPropagateNeg(), $hotspot_delineation_result);	    
	   	
	    $total_score     += $result['score'];    
	    $total_weight    += $result['weight'];    
	} // end foreach() block that loops over all questions
}

if ($origin != 'learnpath') {
    if ($show_results || $show_only_score) {
        echo '<div id="question_score">';
        echo get_lang('YourTotalScore')." ";	
        if ($objExercise->selectPropagateNeg() == 0 && $total_score < 0) {
    	    $total_score = 0;
        }     
        echo show_score($total_score, $total_weight, false);	
        echo '</div>';
    }
    /* <button type="submit" class="save"><?php echo get_lang('Finish');?></button> */
}

// Tracking of results

//	Updates the empty exercise

$quizDuration = (!empty($_SESSION['quizStartTime']) ? time() - $_SESSION['quizStartTime'] : 0);

$feed = $objExercise->feedbacktype; 
if (api_is_allowed_to_session_edit()) {	
	update_event_exercice($exercise_stat_info['exe_id'], $objExercise->selectId(), $total_score, $total_weight, api_get_session_id(), $safe_lp_id, $safe_lp_item_id, $safe_lp_item_view_id, $quiz_duration, $question_list, '');	
}


//If is not valid
$session_control_key = get_session_time_control_key($objExercise->id);
if (isset($session_control_key) && !exercise_time_control_is_valid($objExercise->id)) {
	$TBL_TRACK_ATTEMPT		= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
	$sql_fraud = "UPDATE $TBL_TRACK_ATTEMPT SET answer = 0, marks=0, position=0 WHERE exe_id = $exe_id ";
	Database::query($sql_fraud);
}

//Unset session for clock time
exercise_time_control_delete($objExercise->id);

if ($origin != 'learnpath') {	
	Display::display_footer();
} else {
	$lp_mode =  $_SESSION['lp_mode'];
	$url = '../newscorm/lp_controller.php?cidReq='.api_get_course_id().'&action=view&lp_id='.$safe_lp_id.'&lp_item_id='.$safe_lp_item_id.'&exeId='.$exercise_stat_info['exe_id'].'&fb_type='.$feed;
	//echo $total_score.','.$total_weight;	exit;	
	$href = ($lp_mode == 'fullscreen')?' window.opener.location.href="'.$url.'" ':' top.location.href="'.$url.'" ';
	echo '<script language="javascript" type="text/javascript">'.$href.'</script>'."\n";
	//record the results in the learning path, using the SCORM interface (API)
	echo '<script language="javascript" type="text/javascript">window.parent.API.void_save_asset('.$total_score.','.$total_weight.');</script>'."\n";
	echo '</body></html>';
}

// Send notification..
if (!api_is_allowed_to_edit(null,true)) {	
    $objExercise->send_notification($arrques, $arrans, $origin);	
}
if (api_is_allowed_to_session_edit()) {
	api_session_unregister('objExercise');
	api_session_unregister('exe_id');
}