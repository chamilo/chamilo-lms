<?php
/* For licensing terms, see /license.txt */
/**
*	Exercise list: This script shows the list of exercises for administrators and students.
*	@package chamilo.exercise
*	@author Olivier Brouckaert, original author
*	@author Denes Nagy, HotPotatoes integration
*	@author Wolfgang Schneider, code/html cleanup
*/
/**
 * Code
 */
/**
 * name of the language file that needs to be included
 */
$language_file='exercice';

require_once '../inc/global.inc.php';
$this_section=SECTION_COURSES;
api_protect_course_script(true);

$show=(isset($_GET['show']) && $_GET['show'] == 'result')?'result':'test'; // moved down to fix bug: http://www.dokeos.com/forum/viewtopic.php?p=18609#18609

/**
 * Libraries
 */

/* 	Constants and variables */
$is_allowedToEdit = api_is_allowed_to_edit(null,true);
$is_tutor = api_is_allowed_to_edit(true);

if(!$is_allowedToEdit){
	header('Location: /main/exercice/exercice.php?cidReq='.Security::remove_XSS($_GET['cidReq']));
	exit;
}

$interbreadcrumb[]= array ('url' => 'exercise_report.php','name' => get_lang('Exercices'));
$interbreadcrumb[]= array ('url' => 'exercise_report.php'.'?filter=2','name' => get_lang('StudentScore'));
$interbreadcrumb[]= array ('url' => 'exercise_history.php'.'?exe_id='.intval($_GET['exe_id']), 'name' => get_lang('Details'));

$TBL_USER          	    = Database::get_main_table(TABLE_MAIN_USER);
$TBL_EXERCICES			= Database::get_course_table(TABLE_QUIZ_TEST);
$TBL_EXERCICES_QUESTION	= Database::get_course_table(TABLE_QUIZ_QUESTION);
$TBL_TRACK_EXERCICES	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$TBL_TRACK_ATTEMPT_RECORDING= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
Display::display_header($nameTools,get_lang('Exercise'));

if (isset($_GET['message'])) {
	if (in_array($_GET['message'], array('ExerciseEdited'))) {
		$my_message_history=Security::remove_XSS($_GET['message']);
		Display::display_confirmation_message(get_lang($my_message_history));
	}
}

echo '<div class="actions">';
echo '<a href="exercise_report.php?' . api_get_cidreq() . '&filter=2">' . Display :: return_icon('back.png', get_lang('BackToResultList'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

?>

<table class="data_table">
	<tr class="row_odd">
		<th><?php echo get_lang('Question'); ?></th>
		<th width="50px"><?php echo get_lang('Value'); ?></th>
		<th><?php echo get_lang('Feedback'); ?></th>		  
		<th><?php echo get_lang('Author'); ?></th>
		<th width="160px"><?php echo get_lang('Date'); ?></th>
	</tr>
<?php

$sql = "SELECT *, quiz_question.question, firstname, lastname FROM $TBL_TRACK_ATTEMPT_RECORDING t, $TBL_USER,$TBL_EXERCICES_QUESTION quiz_question
		WHERE quiz_question.id = question_id AND user_id = author AND exe_id = '".(int)$_GET['exe_id']."' ORDER BY position";
$query = Database::query($sql);
while($row = Database::fetch_array($query)){
	echo '<tr';
	if($i%2==0) echo 'class="row_odd"'; else echo 'class="row_even"';
	echo '>';
	echo '<td>'.$row['question'].'</td>';
	echo '<td>'.$row['marks'].'</td>';
	if(!empty($row['teacher_comment'])){
		echo '<td>'.$row['teacher_comment'].'</td>';
	} else {
		echo '<td>'.get_lang('WithoutComment').'</td>';
	}
    echo '<td>'.(empty($row['firstname']) && empty($row['lastname']) ? '<i>'.get_lang('OriginalValue').'</i>' : api_get_person_name($row['firstname'], $row['lastname'])).'</td>';
	echo '<td>'.api_convert_and_format_date($row['insert_date'], DATE_TIME_FORMAT_LONG).'</td>';
    echo '</tr>';
}
echo '</table>';
Display::display_footer();
