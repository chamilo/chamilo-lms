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
require_once 'exercise.class.php';
require_once 'exercise.lib.php';
$language_file = 'exercice';

require_once '../inc/global.inc.php';

$this_section = SECTION_COURSES;

// Notice for unauthorized people.
api_protect_course_script(true);

if (empty ($exerciseId)) {
	$exercise_id = intval($_REQUEST['exerciseId']);
}

$objExercise = new Exercise();
$result = $objExercise->read($exercise_id);
if (!$result) {
	api_not_allowed(true);
}

$gradebook 			= isset($_GET['gradebook']) 			? Security :: remove_XSS($_GET['gradebook']) : null;
$learnpath_id       = isset($_REQUEST['learnpath_id']) 		? intval($_REQUEST['learnpath_id']) : null;
$learnpath_item_id  = isset($_REQUEST['learnpath_item_id']) ? intval($_REQUEST['learnpath_item_id']) : null;
$origin  			= isset($_REQUEST['origin']) 			? Security::remove_XSS($_REQUEST['origin']) : null;

$interbreadcrumb[] = array ("url" => "exercice.php?gradebook=$gradebook", "name" => get_lang('Exercices'));
$interbreadcrumb[] = array ("url" => "#","name" => $objExercise->name);

$htmlHeadXtra[] = api_get_jquery_ui_js();

$htmlHeadXtra[] = '<script language="javascript">

$(function() {  
	$(".exercise_opener").live("click", function() {	
		var url = this.href;
		var dialog = $("#dialog");                
        if ($("#dialog").length == 0) {
            dialog = $(\'<div id="dialog" style="display:hidden"></div> \').appendTo(\'body\');
        }
         // load remote content
        dialog.load(
                url,
                {},
                function(responseText, textStatus, XMLHttpRequest) {                
                    dialog.dialog({
	                    width:	720, 
	                    height:	550, 
	                    modal:	true,
	                });
				}
		);
        //prevent the browser to follow the link
        return false;
	});

});
</script>'; //jQuery

if ($origin != 'learnpath') {
	Display::display_header();
} else {
	Display::display_reduced_header();
}

$html = '';

$is_allowed_to_edit = api_is_allowed_to_edit(null,true);

$html .= Display::tag('h1', $objExercise->name);
$html .= Display::div($objExercise->description, array('class'=>'exercise_description'));

//Buttons
//Notice we not add there the lp_item_view__id because is not already generated 
$exercise_url = api_get_path(WEB_CODE_PATH).'exercice/exercise_submit.php?'.api_get_cidreq().'&id_session='.api_get_session_id().'&exerciseId='.$objExercise->id.'&origin='.$origin.'&learnpath_id='.$learnpath_id.'&learnpath_item_id='.$learnpath_item_id;
$exercise_url = Display::url(get_lang('TakeTheExam'), $exercise_url, array('class'=>'a_button orange big round'));

if (!$objExercise->is_visible()) {
	$exercise_url = Display::url(get_lang('TakeTheExam'), '#', array('class'=>'a_button white big round'));
}

$options = Display::div($exercise_url, array('class'=>'left_option'));

$attempts = get_exercise_results_by_user(api_get_user_id(), $objExercise->id, api_get_course_id(), api_get_session_id(), $learnpath_id, $learnpath_item_id);

$my_attempt_array = array();
$counter = 0;
$table_content = '';

if (!empty($attempts)) {
	foreach($attempts as $attempt_result) {	
		$counter++;
		$score = show_score($attempt_result['exe_result'], $attempt_result['exe_weighting']);
		$attempt_url = api_get_path(WEB_CODE_PATH).'exercice/result.php?'.api_get_cidreq().'&id='.$attempt_result['exe_id'].'&id_session='.api_get_session_id();
		$attempt_link = Display::url(Display::return_icon('quiz.png', get_lang('Result'), array(), 22), $attempt_url, array('class'=>'exercise_opener'));
		if (!$is_allowed_to_edit && $attempt_result['attempt_revised'] == 0) {
			$attempt_link = get_lang('NoResult');
			$attempt_link = Display::return_icon('quiz_na.png', get_lang('NoResult'), array(), 22);
		}
		$my_attempt_array[] = array('count'	 		=> $counter, 
									'date'	 		=> api_convert_and_format_date($attempt_result['start_date'], DATE_TIME_FORMAT_LONG), 
									'result' 		=> $score,
									'attempt_link'	=> $attempt_link,
									);
	}
	
	$table = new HTML_Table(array('class' => 'data_table'));
	$header_names = array(get_lang('Attempt'), get_lang('Date'), get_lang('Score'), get_lang('Details'));
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
		$options.= Display::div(get_lang('MaxAttempts').' '.$objExercise->selectAttempts(), array('class'=>'right_option'));
	} else {
		$options.= Display::div(get_lang('AttemptsLeft').' '.$counter.' / '.$objExercise->selectAttempts(), array('class'=>'right_option'));
	}
}
$html.=  Display::div($options, array('class'=>'exercise_overview_options'));

$html .= $table_content;

echo Display::div($html, array('class'=>'rounded_div', 'style'=>'width:60%'));

if ($origin != 'learnpath') {
	Display::display_footer();
}