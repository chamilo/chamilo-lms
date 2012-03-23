<?php
/* For licensing terms, see /license.txt */
/**
*	Exercise preview
*
*	@package chamilo.exercise
* 	@author Julio Montoya <gugli100@gmail.com>
*/
/**
 * Code
 */
$language_file = 'exercice';
require_once 'exercise.class.php';
require_once '../inc/global.inc.php';
require_once 'exercise.lib.php';

// Clear the exercise session just in case
if (isset ($_SESSION['objExercise'])) {
	api_session_unregister('objExercise');
}

$this_section = SECTION_COURSES;

// Notice for unauthorized people.
api_protect_course_script(true);

$exercise_id = isset($_REQUEST['exerciseId']) ? intval($_REQUEST['exerciseId']) : 0;

$objExercise = new Exercise();
$result = $objExercise->read($exercise_id);
if (!$result) {
	api_not_allowed(true);
}

$gradebook 			= isset($_GET['gradebook'])             ? Security :: remove_XSS($_GET['gradebook']) : null;
$learnpath_id       = isset($_REQUEST['learnpath_id']) 		? intval($_REQUEST['learnpath_id']) : null;
$learnpath_item_id  = isset($_REQUEST['learnpath_item_id']) ? intval($_REQUEST['learnpath_item_id']) : null;
$origin  			= isset($_REQUEST['origin']) 			? Security::remove_XSS($_REQUEST['origin']) : null;

$interbreadcrumb[] = array ("url" => "exercice.php?gradebook=$gradebook", "name" => get_lang('Exercices'));
$interbreadcrumb[] = array ("url" => "#","name" => $objExercise->name);

$time_control = false;
if ($objExercise->expired_time != 0 && $origin != 'learnpath') {
	$time_control = true;
}

$clock_expired_time = get_session_time_control_key($objExercise->id);

// Get time left for exipiring time
$time_left = api_strtotime($clock_expired_time,'UTC') - time();

if ($time_control) {
	$htmlHeadXtra[] = api_get_js('jquery.epiclock.min.js');
	$htmlHeadXtra[] = $objExercise->show_time_control_js($time_left);
}

if ($origin != 'learnpath') {
	Display::display_header();
} else {
	Display::display_reduced_header();
}

$html = '';

$is_allowed_to_edit = api_is_allowed_to_edit(null,true);
$edit_link = '';
if ($is_allowed_to_edit ) {
	$edit_link = Display::url(Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL), api_get_path(WEB_CODE_PATH).'exercice/admin.php?'.api_get_cidreq().'&id_session='.api_get_session_id().'&exerciseId='.$objExercise->id);
}

//Exercise name
$html .= Display::page_header( $objExercise->name .' '.$edit_link);

//Exercise description
$html .= Display::div($objExercise->description, array('class'=>'exercise_description'));

$extra_params = '';
if (isset($_GET['preview'])) {
	$extra_params = '&preview=1';	
}

//Exercise button
//Notice we not add there the lp_item_view__id because is not already generated 
$exercise_url = api_get_path(WEB_CODE_PATH).'exercice/exercise_submit.php?'.api_get_cidreq().'&exerciseId='.$objExercise->id.'&origin='.$origin.'&learnpath_id='.$learnpath_id.'&learnpath_item_id='.$learnpath_item_id.$extra_params;
$label = get_lang('StartTest');
if ($time_control && !empty($clock_expired_time)) {
	$label = get_lang('ContinueTest');
}
$exercise_stat_info = $objExercise->get_stat_track_exercise_info($learnpath_id, $learnpath_item_id, 0);
$attempt_list = null;
if (isset($exercise_stat_info['exe_id'])) {
	$attempt_list = get_all_exercise_event_by_exe_id($exercise_stat_info['exe_id']);
}

$exercise_url_button = Display::url($label, $exercise_url, array('class'=>'btn btn-primary btn-large'));

$visible_return = $objExercise->is_visible($learnpath_id, $learnpath_item_id, null, false);
        
if ($visible_return['value'] == false) {
	$exercise_url = api_get_path(WEB_CODE_PATH).'exercice/exercise_report.php?'.api_get_cidreq().'&exerciseId='.$objExercise->id;	
	$exercise_url_button = $visible_return['message'];    
}


//Message "you already try this exercise"

$message = '';
if (!empty($attempt_list)) {
	$message = Display::return_message(get_lang('YouTriedToResolveThisExerciseEarlier'));
	$label = get_lang('ContinueTest');
}
$html .= $message;

$attempts = get_exercise_results_by_user(api_get_user_id(), $objExercise->id, api_get_course_id(), api_get_session_id(), $learnpath_id, $learnpath_item_id, 'desc');
$counter = count($attempts);

$options = '';
if (!empty($exercise_url_button)) {
	$options .= $exercise_url_button;
}
$options  = Display::div($options, array('class'=>'offset4 span3'));



$my_attempt_array = array();
$table_content = '';

if (!empty($attempts)) {
    $i = $counter;
	foreach ($attempts as $attempt_result) {		
        
		$score = show_score($attempt_result['exe_result'], $attempt_result['exe_weighting']);
		$attempt_url 	= api_get_path(WEB_CODE_PATH).'exercice/result.php?'.api_get_cidreq().'&id='.$attempt_result['exe_id'].'&id_session='.api_get_session_id().'&height=500&width=750';
		$attempt_link 	= Display::url(get_lang('Show'), $attempt_url, array('class'=>'ajax btn'));
		
		$teacher_revised = Display::span(get_lang('Validated'), array('class'=>'label_tag success'));
			//$attempt_link = get_lang('NoResult');
			//$attempt_link = Display::return_icon('quiz_na.png', get_lang('NoResult'), array(), ICON_SIZE_SMALL);
		if ($attempt_result['attempt_revised'] == 0) {
			$teacher_revised = Display::span(get_lang('NotValidated'), array('class'=>'label_tag notice'));
		}				
		$row = array('count'	 	=> $i,
					 'date'	 		=> api_convert_and_format_date($attempt_result['start_date'], DATE_TIME_FORMAT_LONG)
				);
		$attempt_link .= "&nbsp;&nbsp;&nbsp;".$teacher_revised;
		 
		if ($objExercise->results_disabled == EXERCISE_FEEDBACK_TYPE_END || $objExercise->results_disabled == EXERCISE_FEEDBACK_TYPE_EXAM) {
			$row['result'] = $score;
		}
		
		if ($objExercise->results_disabled == EXERCISE_FEEDBACK_TYPE_END) {
			$row['attempt_link'] = $attempt_link;			
		}									
		$my_attempt_array[] = $row;
        $i--;
	}
	
	$table = new HTML_Table(array('class' => 'data_table'));
	
	//Hiding score and answer
	switch($objExercise->results_disabled) {
		case EXERCISE_FEEDBACK_TYPE_END:
			$header_names = array(get_lang('Attempt'), get_lang('Date'), get_lang('Score'), get_lang('Details'));
			break;
		case EXERCISE_FEEDBACK_TYPE_DIRECT:
			$header_names = array(get_lang('Attempt'), get_lang('Date'));
			break;
		case EXERCISE_FEEDBACK_TYPE_EXAM:
			$header_names = array(get_lang('Attempt'), get_lang('Date'), get_lang('Score'));
			break;			
	}
	
	$row = 0;
	$column = 0;
	foreach ($header_names as $item) {
		$table->setHeaderContents($row, $column, $item);
		$column++;
	}
	$row = 1;
	if (!empty($my_attempt_array)) {
		foreach ($my_attempt_array as $data) {
			$column = 0;
			$table->setCellContents($row, $column, $data);
			//$table->setRowAttributes($row, 'style="text-align:center"');
			$class = 'class="row_odd"';
			if($row % 2) {
				$class = 'class="row_even"';
			}
			$table->setRowAttributes($row, $class, true);
			$column++;
			$row++;
		}
	}
	$table_content = $table->toHtml();	
}

if ($objExercise->selectAttempts()) {
	if ($is_allowed_to_edit) {
		//$options.= Display::div(get_lang('ExerciseAttempts').' '.$objExercise->selectAttempts(), array('class'=>'right_option'));
	}	
    $attempt_message = get_lang('Attempts').' '.$counter.' / '.$objExercise->selectAttempts();
    
	if ($counter == $objExercise->selectAttempts()) {
		$attempt_message = Display::return_message($attempt_message, 'error');
	} else {
        $attempt_message = Display::return_message($attempt_message, 'info');
    }
	$options.= Display::div($attempt_message, array('class'=>"offset2 span2"));	
}

if ($time_control) {
	$html.=  '<div align="left" id="wrapper-clock"><div id="square" class="rounded"><div id="text-content" align="center" class="count_down"></div></div></div>';
}

$html.=  Display::div(Display::div($options, array('class'=>'exercise_overview_options span12')), array('class'=>' row'));


$html .= $table_content;

echo $html;

if ($origin != 'learnpath') {
	Display::display_footer();
}