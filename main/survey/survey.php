<?php
/* For licensing terms, see /license.txt */

/**
 *	@package chamilo.survey
 * 	@author unknown
 * 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of the code
 * 	@version $Id: survey.php 22573 2009-08-03 03:38:13Z yannoo $
 *
 * 	@todo use quickforms for the forms
 */

// Language file that needs to be included
$language_file = 'survey';

// Including the global initialization file
require_once '../inc/global.inc.php';

$this_section = SECTION_COURSES;

// Including additional libraries
require_once 'survey.lib.php';

/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
// Coach can't view this page
$extend_rights_for_coachs = api_get_setting('extend_rights_for_coach_on_survey');
if (!api_is_allowed_to_edit(false, true) || (api_is_course_coach() && $extend_rights_for_coachs == 'false')) {
	Display :: display_header(get_lang('ToolSurvey'));
	Display :: display_error_message(get_lang('NotAllowed'), false);
	Display :: display_footer();
	exit;
}

// Database table definitions
$table_survey 					= Database :: get_course_table(TABLE_SURVEY);
$table_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
$table_survey_question_group    = Database :: get_course_table(TABLE_SURVEY_QUESTION_GROUP);
$table_course 					= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_user 					= Database :: get_main_table(TABLE_MAIN_USER);
$user_info 						= Database :: get_main_table(TABLE_MAIN_SURVEY_REMINDER); // TODO: To be checked. TABLE_MAIN_SURVEY_REMINDER has not been defined.

$survey_id = intval($_GET['survey_id']);

$course_id = api_get_course_int_id();

// Breadcrumbs
$interbreadcrumb[] = array ('url' => 'survey_list.php', 'name' => get_lang('SurveyList'));

// Getting the survey information
if (isset($_GET['survey_id'])) {
	$course_code = api_get_course_id();
	if ($course_code!=-1) {
		$survey_data = survey_manager::get_survey($survey_id);		
	} else {
		Display :: display_header(get_lang('ToolSurvey'));
		Display :: display_error_message(get_lang('NotAllowed'), false);
		Display :: display_footer();
		exit;
	}
}

if (api_substr($survey_data['title'], 0, 3) != '<p>') {
	$tool_name = strip_tags(api_substr(api_html_entity_decode($survey_data['title'], ENT_QUOTES), 0, 40));
} else {
	$tool_name = strip_tags(api_substr(api_html_entity_decode(api_substr($survey_data['title'], 3, -4), ENT_QUOTES), 0, 40));
}

$is_survey_type_1 = $survey_data['survey_type'] == 1;

if (api_strlen(strip_tags($survey_data['title'])) > 40) {
	$tool_name .= '...';
}
$course_id = api_get_course_int_id();
if ($is_survey_type_1 && $_GET['action'] == 'addgroup' || $_GET['action'] == 'deletegroup') {
	$_POST['name'] = trim($_POST['name']);

	if (($_GET['action'] == 'addgroup')) {
		if (!empty($_POST['group_id'])) {
			Database::query('UPDATE '.$table_survey_question_group.' SET description = \''.Database::escape_string($_POST['description']).'\' 
			                 WHERE c_id = '.$course_id.' AND id = \''.Database::escape_string($_POST['group_id']).'\'');
			$sendmsg = 'GroupUpdatedSuccessfully';
		} elseif(!empty($_POST['name'])) {
			Database::query('INSERT INTO '.$table_survey_question_group.' (c_id, name,description,survey_id) values ('.$course_id.', \''.Database::escape_string($_POST['name']).'\',\''.Database::escape_string($_POST['description']).'\',\''.Database::escape_string($survey_id).'\') ');
			$sendmsg = 'GroupCreatedSuccessfully';
		} else {
			$sendmsg = 'GroupNeedName';
		}
	}

	if ($_GET['action'] == 'deletegroup'){
		Database::query('DELETE FROM '.$table_survey_question_group.' WHERE c_id = '.$course_id.' AND id = '.Database::escape_string($_GET['gid']).' and survey_id = '.Database::escape_string($survey_id));
		$sendmsg = 'GroupDeletedSuccessfully';
	}
	header('Location:survey.php?survey_id='.$survey_id.'&sendmsg='.$sendmsg);
	exit;
}

// Displaying the header
Display::display_header($tool_name,'Survey');

// Action handling
$my_action_survey		= Security::remove_XSS($_GET['action']);
$my_question_id_survey  = Security::remove_XSS($_GET['question_id']);
$my_survey_id_survey    = Security::remove_XSS($_GET['survey_id']);
$message_information    = Security::remove_XSS($_GET['message']);

if (isset($_GET['action'])) {
	if (($_GET['action'] == 'moveup' || $_GET['action'] == 'movedown') && isset($_GET['question_id'])) {
		survey_manager::move_survey_question($my_action_survey,$my_question_id_survey,$my_survey_id_survey);
		Display::display_confirmation_message(get_lang('SurveyQuestionMoved'));
	}
	if ($_GET['action'] == 'delete' AND is_numeric($_GET['question_id'])) {
		survey_manager::delete_survey_question($my_survey_id_survey, $my_question_id_survey, $survey_data['is_shared']);
	}
}
if (isset($_GET['message'])) {
	// We have created the survey or updated the survey
	if (in_array($_GET['message'], array('SurveyUpdatedSuccesfully','SurveyCreatedSuccesfully'))) {
		Display::display_confirmation_message(get_lang($message_information).', '.PHP_EOL.api_strtolower(get_lang('YouCanNowAddQuestionToYourSurvey')));
	}
	// We have added a question
	if (in_array($_GET['message'], array('QuestionAdded', 'QuestionUpdated'))) {
		Display::display_confirmation_message(get_lang($message_information));
	}

	if (in_array($_GET['message'], array('YouNeedToCreateGroups'))) {
		Display::display_warning_message(get_lang($message_information), false);
	}
}

if (!empty($survey_data['survey_version'])) echo '<b>'.get_lang('Version').': '.$survey_data['survey_version'].'</b>';

// We exit here is the first or last question is a pagebreak (which causes errors)
SurveyUtil::check_first_last_question($_GET['survey_id']);

// Action links
$survey_actions = '<a href="create_new_survey.php?'.api_get_cidreq().'&amp;action=edit&amp;survey_id='.$survey_id.'">'.Display::return_icon('edit.png', get_lang('EditSurvey'),'',ICON_SIZE_MEDIUM).'</a>';
$survey_actions .= '<a href="survey_list.php?'.api_get_cidreq().'&amp;action=delete&amp;survey_id='.$survey_id.'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('DeleteSurvey').'?', ENT_QUOTES)).'\')) return false;">'.Display::return_icon('delete.png', get_lang('DeleteSurvey'),'',ICON_SIZE_MEDIUM).'</a>';
//$survey_actions .= '<a href="create_survey_in_another_language.php?id_survey='.$survey_id.'">'.Display::return_icon('copy.gif', get_lang('Copy')).'</a>';
$survey_actions .= '<a href="preview.php?'.api_get_cidreq().'&amp;survey_id='.$survey_id.'">'.Display::return_icon('preview_view.png', get_lang('Preview'),'',ICON_SIZE_MEDIUM).'</a>';
$survey_actions .= '<a href="survey_invite.php?'.api_get_cidreq().'&amp;survey_id='.$survey_id.'">'.Display::return_icon('mail_send.png', get_lang('Publish'),'',ICON_SIZE_MEDIUM).'</a>';
$survey_actions .= '<a href="reporting.php?'.api_get_cidreq().'&amp;survey_id='.$survey_id.'">'.Display::return_icon('stats.png', get_lang('Reporting'),'',ICON_SIZE_MEDIUM).'</a>';
echo '<div class="actions">'.$survey_actions.'</div>';

if ($survey_data['survey_type'] == 0) {
	echo '<div class="actionsbig">';
	echo '<a style="padding-left:0px;" href="question.php?'.api_get_cidreq().'&amp;action=add&type=yesno&amp;survey_id='.$survey_id.'">'.Display::return_icon('yesno.gif', get_lang('YesNo')).'</a>';
	echo '<a href="question.php?'.api_get_cidreq().'&amp;action=add&type=multiplechoice&amp;survey_id='.$survey_id.'">'.Display::return_icon('mcua.gif', get_lang('UniqueSelect')).'<br /></a>';
	echo '<a href="question.php?'.api_get_cidreq().'&amp;action=add&type=multipleresponse&amp;survey_id='.$survey_id.'">'.Display::return_icon('mcma.gif', get_lang('MultipleResponse')).'</a>';
	echo '<a href="question.php?'.api_get_cidreq().'&amp;action=add&type=open&amp;survey_id='.$survey_id.'">'.Display::return_icon('open_answer.gif', get_lang('Open')).'<br /></a>';
	echo '<a href="question.php?'.api_get_cidreq().'&amp;action=add&type=dropdown&amp;survey_id='.$survey_id.'">'.Display::return_icon('dropdown.gif', get_lang('Dropdown')).'<br /></a>';
	echo '<a href="question.php?'.api_get_cidreq().'&amp;action=add&type=percentage&amp;survey_id='.$survey_id.'">'.Display::return_icon('percentagequestion.gif', get_lang('Percentage')).'<br /></a>';
	echo '<a href="question.php?'.api_get_cidreq().'&amp;action=add&type=score&amp;survey_id='.$survey_id.'">'.Display::return_icon('scorequestion.gif', get_lang('Score')).'</a>';
	echo '<a href="question.php?'.api_get_cidreq().'&amp;action=add&type=comment&amp;survey_id='.$survey_id.'">'.Display::return_icon('commentquestion.gif', get_lang('Comment')).'</a>';
	echo '<a href="question.php?'.api_get_cidreq().'&amp;action=add&type=pagebreak&amp;survey_id='.$survey_id.'">'.Display::return_icon('page_end.gif', get_lang('Pagebreak')).'</a>';
	echo '</div>';
} else {
	echo '<div class="actionsbig">';
	//echo '<a href="group.php?'.api_get_cidreq().'&amp;action=add&amp;survey_id='.$survey_id.'"><img src="../img/yesno.gif" /><br />'.get_lang('Add groups').'</a></div>';
	echo '<a style="padding-left:0px;" href="question.php?'.api_get_cidreq().'&amp;action=add&type=personality&amp;survey_id='.$survey_id.'"><img src="../img/yesno.gif" /></a></div>';
	echo '</div>';
}

// Displaying the table header with all the questions
echo '<table class="data_table">';
echo '	<tr class="row_odd">';
echo '		<th width="15">'.get_lang('QuestionNumber').'</th>';
echo '		<th>'.get_lang('Title').'</th>';
echo '		<th>'.get_lang('Type').'</th>';
echo '		<th width="50" >'.get_lang('NumberOfOptions').'</th>';
echo '		<th width="100">'.get_lang('Modify').'</th>';
if ($is_survey_type_1) {
	echo '<th width="100">'.get_lang('Condition').'</th>';
    echo '<th width="40">'.get_lang('Group').'</th>';
}
echo '	</tr>';

// Displaying the table contents with all the questions
$question_counter = 1;
$sql = "SELECT * FROM $table_survey_question_group WHERE c_id = '.$course_id.' AND survey_id = '".Database::escape_string($survey_id)."' ORDER BY id";
$result = Database::query($sql);
$groups = array();
while ($row = Database::fetch_array($result)) {
    $groups[$row['id']] = $row['name'];
}
$sql = "SELECT survey_question.*, count(survey_question_option.question_option_id) as number_of_options
			FROM $table_survey_question survey_question
			LEFT JOIN $table_survey_question_option survey_question_option
			ON survey_question.question_id = survey_question_option.question_id AND survey_question_option.c_id = $course_id
			WHERE    survey_question.survey_id 	= '".Database::escape_string($survey_id)."' AND 
			         survey_question.c_id 		= $course_id
			GROUP BY survey_question.question_id
			ORDER BY survey_question.sort ASC";
			
$result = Database::query($sql);
$question_counter_max = Database::num_rows($result);
while ($row = Database::fetch_array($result, 'ASSOC')) {
	echo '<tr>';
	echo '	<td>'.$question_counter.'</td>';
	echo '	<td>';
	if (api_strlen($row['survey_question']) > 100) {
		echo api_substr(strip_tags($row['survey_question']), 0, 100).' ... ';
	} else {
		echo $row['survey_question'];
	}

	if ($row['type'] == 'yesno') {
		$tool_name = get_lang('YesNo');
	} else if ($row['type'] == 'multiplechoice') {
		$tool_name = get_lang('UniqueSelect');
	} else {
		$tool_name = get_lang(api_ucfirst(Security::remove_XSS($row['type'])));
	}

	echo '</td>';
	echo '	<td>'.$tool_name.'</td>';
	echo '	<td>'.$row['number_of_options'].'</td>';
	echo '	<td>';
	echo '		<a href="question.php?'.api_get_cidreq().'&amp;action=edit&amp;type='.$row['type'].'&amp;survey_id='.$survey_id.'&amp;question_id='.$row['question_id'].'">'.Display::return_icon('edit.png', get_lang('Edit'),'',ICON_SIZE_SMALL).'</a>';
	echo '		<a href="survey.php?'.api_get_cidreq().'&amp;action=delete&amp;survey_id='.$survey_id.'&amp;question_id='.$row['question_id'].'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang("DeleteSurveyQuestion").'?',ENT_QUOTES,$charset)).'\')) return false;">'.Display::return_icon('delete.png', get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>';
	if ($question_counter > 1) {
		echo '		<a href="survey.php?'.api_get_cidreq().'&amp;action=moveup&amp;survey_id='.$survey_id.'&amp;question_id='.$row['question_id'].'">'.Display::return_icon('up.png', get_lang('MoveUp'),'',ICON_SIZE_SMALL).'</a>';
	} else {
		Display::display_icon('up_na.png','&nbsp;','',ICON_SIZE_SMALL);
	}
	if ($question_counter < $question_counter_max) {
		echo '		<a href="survey.php?'.api_get_cidreq().'&amp;action=movedown&amp;survey_id='.$survey_id.'&amp;question_id='.$row['question_id'].'">'.Display::return_icon('down.png', get_lang('MoveDown'),'',ICON_SIZE_SMALL).'</a>';
	} else {
		Display::display_icon('down_na.png','&nbsp;','',ICON_SIZE_SMALL);
	}
	echo '	</td>';
	$question_counter++;

	if ($is_survey_type_1) {
    	echo '<td>'.(($row['survey_group_pri']==0)?get_lang('Secondary'):get_lang('Primary')).'</td>';
        echo '<td>'.(($row['survey_group_pri']==0)?$groups[$row['survey_group_sec1']].'-'.$groups[$row['survey_group_sec2']]:$groups[$row['survey_group_pri']]).'</td>';
    }
	echo '</tr>';
}
echo '</table>';

if ($is_survey_type_1) {
	echo '<br /><br /><b>'.get_lang('ManageGroups').'</b><br /><br />';

	if (in_array($_GET['sendmsg'], array('GroupUpdatedSuccessfully', 'GroupDeletedSuccessfully', 'GroupCreatedSuccessfully'))) {
		echo Display::display_confirmation_message(get_lang($_GET['sendmsg']), false);
	}

	if (in_array($_GET['sendmsg'], array('GroupNeedName'))){
		echo Display::display_warning_message(get_lang($_GET['sendmsg']), false);
	}

	echo '<table border="0"><tr><td width="100">'.get_lang('Name').'</td><td>'.get_lang('Description').'</td></tr></table>';

	echo '<form action="survey.php?action=addgroup&survey_id='.$survey_id.'" method="post">';
	if ($_GET['action'] == 'editgroup') {
		$sql = 'SELECT name,description FROM '.$table_survey_question_group.' WHERE id = '.Database::escape_string($_GET['gid']).' AND survey_id = '.Database::escape_string($survey_id).' limit 1';
		$rs = Database::query($sql);
		$editedrow = Database::fetch_array($rs,'ASSOC');
		echo	'<input type="text" maxlength="20" name="name" value="'.$editedrow['name'].'" size="10" disabled>';
		echo	'<input type="text" maxlength="150" name="description" value="'.$editedrow['description'].'" size="40">';
		echo	'<input type="hidden" name="group_id" value="'.Security::remove_XSS($_GET['gid']).'">';
		echo	'<input type="submit" value="'.get_lang('Save').'"'.'<input type="button" value="'.get_lang('Cancel').'" onclick="window.location.href = \'survey.php?survey_id='.Security::remove_XSS($survey_id).'\';" />';
	} else {
		echo	'<input type="text" maxlength="20" name="name" value="" size="10">';
		echo	'<input type="text" maxlength="250" name="description" value="" size="80">';
		echo	'<input type="submit" value="'.get_lang('Create').'"';
	}
	echo	'</form><br />';

	echo '<table class="data_table">';
	echo '	<tr class="row_odd">';
	echo '		<th width="200">'.get_lang('Name').'</th>';
	echo '		<th>'.get_lang('Description').'</th>';
	echo '		<th width="100">'.get_lang('Modify').'</th>';
	echo '	</tr>';

	$sql = 'SELECT id,name,description FROM '.$table_survey_question_group.' WHERE c_id = '.$course_id.' AND survey_id = '.Database::escape_string($survey_id).' ORDER BY name';

	$rs = Database::query($sql);
	while($row = Database::fetch_array($rs,ASSOC)){
		$grouplist .= '<tr><td>'.$row['name'].'</td><td>'.$row['description'].'</td><td>'.
		'<a href="survey.php?survey_id='.$survey_id.'&gid='.$row['id'].'&action=editgroup">'.
		Display::return_icon('edit.png', get_lang('Edit'),'',ICON_SIZE_SMALL).'</a> '.
		'<a href="survey.php?survey_id='.$survey_id.'&gid='.$row['id'].'&action=deletegroup" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(sprintf(get_lang('DeleteSurveyGroup'),$row['name']).'?',ENT_QUOTES)).'\')) return false;">'.
		Display::return_icon('delete.png', get_lang('Delete'),'',ICON_SIZE_SMALL).'</a>'.
		'</td></tr>';
	}
	echo $grouplist.'</table>';
}

// Footer
Display :: display_footer();