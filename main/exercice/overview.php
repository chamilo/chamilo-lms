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

use \ChamiloSession as Session;

$language_file = 'exercice';
require_once 'exercise.class.php';
require_once '../inc/global.inc.php';
require_once 'exercise.lib.php';
$current_course_tool  = TOOL_QUIZ;

// Clear the exercise session just in case
if (isset ($_SESSION['objExercise'])) {
	Session::erase('objExercise');
}

$this_section = SECTION_COURSES;

// Notice for unauthorized people.
api_protect_course_script(true);
$sessionId = api_get_session_id();
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
$clock_expired_time = get_session_time_control_key($objExercise->id, $learnpath_id, $learnpath_item_id);

if ($objExercise->expired_time != 0 && !empty($clock_expired_time)) {
	$time_control = true;
}

if ($time_control) {
    // Get time left for expiring time
    $time_left = api_strtotime($clock_expired_time,'UTC') - time();

	$htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/epiclock/stylesheet/jquery.epiclock.css');
    $htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/epiclock/renderers/minute/epiclock.minute.css');
    $htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.dateformat.min.js');
    $htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.epiclock.min.js');
    $htmlHeadXtra[] = api_get_js('epiclock/renderers/minute/epiclock.minute.js');
	$htmlHeadXtra[] = $objExercise->show_time_control_js($time_left);
}

if ($origin != 'learnpath') {
	Display::display_header();
} else {
	Display::display_reduced_header();
}

$html = '';
$message = '';

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
$edit_link = '';
if ($is_allowed_to_edit && $objExercise->sessionId == $sessionId) {
	$edit_link = Display::url(Display::return_icon('edit.png', get_lang('Edit'), array(), ICON_SIZE_SMALL), api_get_path(WEB_CODE_PATH).'exercice/admin.php?'.api_get_cidreq().'&id_session='.api_get_session_id().'&exerciseId='.$objExercise->id);
}

//Exercise name
$html .= Display::page_header($objExercise->name.' '.$edit_link);

//Exercise description
if (!empty($objExercise->description)) {
    $html .= Display::div($objExercise->description, array('class'=>'exercise_description'));
}

$extra_params = '';
if (isset($_GET['preview'])) {
	$extra_params = '&preview=1';
}

$exercise_stat_info = $objExercise->get_stat_track_exercise_info($learnpath_id, $learnpath_item_id, 0);
$attempt_list = null;
if (isset($exercise_stat_info['exe_id'])) {
	$attempt_list = get_all_exercise_event_by_exe_id($exercise_stat_info['exe_id']);
}

//1. Check if this is a new attempt or a previous
//$countNotFinished = isset($exercise_stat_info['num_exe']) ? $exercise_stat_info['num_exe'] : null;
$label = get_lang('StartTest');
if ($time_control && !empty($clock_expired_time) || !empty($attempt_list)) {
	$label = get_lang('ContinueTest');
}

if (!empty($attempt_list)) {
	$message = Display::return_message(get_lang('YouTriedToResolveThisExerciseEarlier'));
}

// 2. Exercise button
// Notice we not add there the lp_item_view_id because is not already generated
$exercise_url = api_get_path(WEB_CODE_PATH).'exercice/exercise_submit.php?'.api_get_cidreq().'&exerciseId='.$objExercise->id.'&origin='.$origin.'&learnpath_id='.$learnpath_id.'&learnpath_item_id='.$learnpath_item_id.$extra_params;
$exercise_url_button = Display::url($label, $exercise_url, array('class'=>'btn btn-primary btn-large'));

//3. Checking visibility of the exercise (overwrites the exercise button)
$visible_return = $objExercise->is_visible(
    $learnpath_id,
    $learnpath_item_id,
    null,
    false
);

// Exercise is not visible remove the button
if ($visible_return['value'] == false) {
    if ($is_allowed_to_edit) {
        $message = Display::return_message(get_lang('ThisItemIsInvisibleForStudentsButYouHaveAccessAsTeacher'), 'warning');
    } else {
        $message = $visible_return['message'];
        $exercise_url_button = null;
    }
}

$attempts = get_exercise_results_by_user(api_get_user_id(), $objExercise->id, api_get_course_id(), api_get_session_id(), $learnpath_id, $learnpath_item_id, 'desc');
$counter = count($attempts);

$my_attempt_array = array();
$table_content = '';

/* Make a special case for IE, which doesn't seem to be able to handle the
 * results popup -> send it to the full results page */
require_once api_get_path(LIBRARY_PATH).'browser/Browser.php';
$browser = new Browser();
$current_browser = $browser->getBrowser();
$url_suffix = '';
$btn_class = 'ajax ';
if ($current_browser == 'Internet Explorer') {
    $url_suffix = '&amp;show_headers=1';
    $btn_class = '';
}

if (!empty($attempts)) {
    $i = $counter;
    foreach ($attempts as $attempt_result) {

        $score = show_score(
            $attempt_result['exe_result'],
            $attempt_result['exe_weighting']
        );
        $attempt_url = api_get_path(
                WEB_CODE_PATH
            ) . 'exercice/result.php?' . api_get_cidreq(
            ) . '&amp;id=' . $attempt_result['exe_id'] . '&amp;id_session=' . api_get_session_id(
            ) . '&amp;height=500&amp;width=950' . $url_suffix;
        $attempt_link = Display::url(
            get_lang('Show'),
            $attempt_url,
            array('class' => $btn_class . 'btn')
        );

        $teacher_revised = Display::label(get_lang('Validated'), 'success');
        //$attempt_link = get_lang('NoResult');
        //$attempt_link = Display::return_icon('quiz_na.png', get_lang('NoResult'), array(), ICON_SIZE_SMALL);
        if ($attempt_result['attempt_revised'] == 0) {
            $teacher_revised = Display::label(get_lang('NotValidated'), 'info');
        }
        $row = array(
            'count' => $i,
            'date' => api_convert_and_format_date(
                $attempt_result['start_date'],
                DATE_TIME_FORMAT_LONG
            )
        );
        $attempt_link .= "&nbsp;&nbsp;&nbsp;" . $teacher_revised;

        if (in_array(
            $objExercise->results_disabled,
            array(
                RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS,
                RESULT_DISABLE_SHOW_SCORE_ONLY,
                RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES
            )
        )
        ) {
            $row['result'] = $score;
        }

        if (in_array(
                $objExercise->results_disabled,
                array(
                    RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS,
                    RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES
                )
            )
            || ($objExercise->results_disabled == RESULT_DISABLE_SHOW_SCORE_ONLY && $objExercise->feedback_type == EXERCISE_FEEDBACK_TYPE_END)) {
            $row['attempt_link'] = $attempt_link;
        }
        $my_attempt_array[] = $row;
        $i--;
	}

	$table = new HTML_Table(array('class' => 'data_table'));

	//Hiding score and answer
	switch ($objExercise->results_disabled) {
		case RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS:
        case RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES:
			$header_names = array(get_lang('Attempt'), get_lang('StartDate'), get_lang('Score'), get_lang('Details'));
			break;
		case RESULT_DISABLE_NO_SCORE_AND_EXPECTED_ANSWERS:
			$header_names = array(get_lang('Attempt'), get_lang('StartDate'));
			break;
		case RESULT_DISABLE_SHOW_SCORE_ONLY:
            if ($objExercise->feedback_type != EXERCISE_FEEDBACK_TYPE_END) {
			    $header_names = array(get_lang('Attempt'), get_lang('StartDate'), get_lang('Score'));
            }
            else {
                $header_names = array(get_lang('Attempt'), get_lang('StartDate'), get_lang('Score'), get_lang('Details'));
            }
			break;
	}
	$column = 0;
	foreach ($header_names as $item) {
		$table->setHeaderContents(0, $column, $item);
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
    $attempt_message = get_lang('Attempts').' '.$counter.' / '.$objExercise->selectAttempts();

	if ($counter == $objExercise->selectAttempts()) {
		$attempt_message = Display::return_message($attempt_message, 'error');
	} else {
        $attempt_message = Display::return_message($attempt_message, 'info');
    }
    if ($visible_return['value'] == true) {
        $message .=   $attempt_message;
    }
}

if ($time_control) {
    $html.= $objExercise->return_time_left_div();
}

$html .= $message;

if (!empty($exercise_url_button)) {
    $html .= Display::div(
        Display::div(
            $exercise_url_button,
            array('class' => 'exercise_overview_options span12')
        ),
        array('class' => ' row')
    );
}

$html .= $table_content;

echo $html;

if ($origin != 'learnpath') {
	Display::display_footer();
}
