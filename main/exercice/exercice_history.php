<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.

    Contact:
		Dokeos
		Rue du Corbeau, 108
		B-1030 Brussels - Belgium
		info@dokeos.com
*/


/**
*	Exercise list: This script shows the list of exercises for administrators and students.
*	@package dokeos.exercise
*	@author Olivier Brouckaert, original author
*	@author Denes Nagy, HotPotatoes integration
*	@author Wolfgang Schneider, code/html cleanup
* 	@version $Id:exercice.php 12269 2007-05-03 14:17:37Z elixir_julian $
*/


// name of the language file that needs to be included
$language_file='exercice';

require_once('../inc/global.inc.php');
$this_section=SECTION_COURSES;
api_protect_course_script(true);

$show=(isset($_GET['show']) && $_GET['show'] == 'result')?'result':'test'; // moved down to fix bug: http://www.dokeos.com/forum/viewtopic.php?p=18609#18609

/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/

require_once(api_get_path(LIBRARY_PATH).'document.lib.php');
include(api_get_path(LIBRARY_PATH).'mail.lib.inc.php');
include(api_get_path(LIBRARY_PATH).'usermanager.lib.php');

/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
$is_allowedToEdit = api_is_allowed_to_edit(null,true);
$is_tutor = api_is_allowed_to_edit(true);

if(!$is_allowedToEdit){
	header('Location: /main/exercice/exercice.php?cidReq='.Security::Remove_XSS($_GET['cidReq']));
	exit;
}

$interbreadcrumb[]= array (
	'url' => 'exercice.php'.'?show=result',
	'name' => get_lang('Exercices')
);
$interbreadcrumb[]= array (
	'url' => 'exercice.php'.'?show=result&amp;filter=2',
	'name' => get_lang('StudentScore')
);
$interbreadcrumb[]= array (
	'url' => 'exercice_history.php'.'?exe_id='.Security::remove_XSS($_GET['exe_id']),
	'name' => get_lang('Details')
);

$TBL_USER          	    = Database::get_main_table(TABLE_MAIN_USER);
$TBL_EXERCICES			= Database::get_course_table(TABLE_QUIZ_TEST);
$TBL_EXERCICES_QUESTION	= Database::get_course_table(TABLE_QUIZ_QUESTION);
$TBL_TRACK_EXERCICES	= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$TBL_TRACK_ATTEMPT_RECORDING= Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
//$nameTools=get_lang('Exercices');
Display::display_header($nameTools,"Exercise");

	if(isset($_GET['message'])) {
		if (in_array($_GET['message'], array('ExerciseEdited'))) {
			$my_message_history=Security::remove_XSS($_GET['message']);
			Display::display_confirmation_message(get_lang($my_message_history));
		}
	}

//event_access_tool(TOOL_QUIZ);

?>
<div class="actions">
<a href="exercice.php?cidReq=<?php echo $_GET['cidReq'] ?>&show=result"><img src="../img/back.png"/><?php echo get_lang('BackToResultList') ?></a>
</div>
<table class="data_table">
		 <tr class="row_odd">
		  <th><?php echo get_lang('Question'); ?></th>
		  <th width="50px"><?php echo get_lang('Value'); ?></th>
		  <th><?php echo get_lang('Feedback'); ?></th>
		  <th width="150px"><?php echo get_lang('Date'); ?></th>
		  <th ><?php echo get_lang('Author'); ?></th>
		 </tr>
<?php

//Display::display_introduction_section(TOOL_QUIZ);
/*
$sql = 'SELECT * FROM '.$TBL_EXERCICES;
$query = Database::query($sql,__FILE__,__LINE__);
*/
$sql = "SELECT *, quiz_question.question, firstname, lastname FROM $TBL_TRACK_ATTEMPT_RECORDING t,$TBL_USER,$TBL_EXERCICES_QUESTION quiz_question WHERE quiz_question.id = question_id AND user_id = author AND exe_id = '".(int)$_GET['exe_id']."' ORDER BY t.insert_date desc,question ASC";
$query = Database::query($sql,__FILE__,__LINE__);
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

	echo '<td>'.$row['insert_date'].'</td>';
	echo '<td>'.(empty($row['firstname']) && empty($row['lastname']) ? '<i>'.get_lang('OriginalValue').'</i>' : api_get_person_name($row['firstname'], $row['lastname'])).'</td>';

	echo '</tr>';
}
echo '</table>';

Display::display_footer();

?>