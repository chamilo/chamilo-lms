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
		Rue des Palais 44 Paleizenstraat
		B-1030 Brussels - Belgium
		Tel. +32 (2) 211 34 56
*/

/**
*	@package dokeos.survey
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of the code
* 	@version $Id: survey_invite.php 10680 2007-01-11 21:26:23Z pcool $
*
* 	@todo the answered column
*/

// name of the language file that needs to be included
$language_file = 'survey';

// including the global dokeos file
require ('../inc/global.inc.php');

// including additional libraries
//require_once (api_get_path(LIBRARY_PATH)."/survey.lib.php");
require_once('survey.lib.php');
require_once (api_get_path(LIBRARY_PATH)."course.lib.php");
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once (api_get_path(LIBRARY_PATH)."mail.lib.inc.php");

/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
if (!api_is_allowed_to_edit(false,true))
{
	Display :: display_header(get_lang('Survey'));
	Display :: display_error_message(get_lang('NotAllowed'), false);
	Display :: display_footer();
	exit;
}

// Database table definitions
$table_survey 					= Database :: get_course_table(TABLE_SURVEY);
$table_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
$table_course 					= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_user 					= Database :: get_main_table(TABLE_MAIN_USER);
$table_survey_invitation 		= Database :: get_course_table(TABLE_SURVEY_INVITATION);

$tool_name = get_lang('SurveyInvitations');


// getting the survey information
// We exit here if ther is no valid $_GET parameter
if (!isset($_GET['survey_id']) OR !is_numeric($_GET['survey_id']))
{
	Display :: display_header($tool_name);
	Display :: display_error_message(get_lang('InvallidSurvey'), false);
	Display :: display_footer();
	exit;
}
$survey_id = Security::remove_XSS($_GET['survey_id']);
$survey_data = survey_manager::get_survey($survey_id);
if (empty($survey_data)) {
	Display :: display_header($tool_name);
	Display :: display_error_message(get_lang('InvallidSurvey'), false);
	Display :: display_footer();
	exit;
}
$urlname =strip_tags(api_substr(api_html_entity_decode($survey_data['title'],ENT_QUOTES,$charset), 0, 40));
if (api_strlen(strip_tags($survey_data['title'])) > 40) {
	$urlname .= '...';
}

//breadcrumbs
$interbreadcrumb[] = array ('url' => 'survey_list.php', 'name' => get_lang('SurveyList'));
$interbreadcrumb[] = array ('url' => 'survey.php?survey_id='.$survey_id, 'name' => $urlname);


// Displaying the header
Display::display_header($tool_name);

// Checking the parameters
if (!is_numeric($survey_id))
{
	Display::display_error_message(get_lang('Error'), false);
	Display::display_footer();
	exit;
}

// Getting all the people who have filled this survey
$answered_data = survey_manager::get_people_who_filled_survey($survey_id);
if ($survey_data['anonymous'] == 1)
{
	Display::display_normal_message(get_lang('AnonymousSurveyCannotKnowWhoAnswered').' '.count($answered_data).' '.get_lang('PeopleAnswered'));
	$answered_data = array();
}

//
if (!isset($_GET['view']) OR $_GET['view'] == 'invited')
{
	echo get_lang('ViewInvited'). ' | ';
}
else
{
	echo '	<a href="'.api_get_self().'?survey_id='.$survey_id.'&amp;view=invited">'.get_lang('ViewInvited').'</a> |';
}
if ($_GET['view'] == 'answered')
{
	echo get_lang('ViewAnswered').' | ';
}
else
{
	echo '	<a href="'.api_get_self().'?survey_id='.$survey_id.'&amp;view=answered">'.get_lang('ViewAnswered').'</a> |';
}
if ($_GET['view'] == 'unanswered')
{
	echo get_lang('ViewUnanswered');
}
else
{
	echo '	<a href="'.api_get_self().'?survey_id='.$survey_id.'&amp;view=unanswered">'.get_lang('ViewUnanswered').'</a>';
}

// table header
echo '<table class="data_table">';
echo '	<tr>';
echo '		<th>'.get_lang('User').'</th>';
echo '		<th>'.get_lang('InvitationDate').'</th>';
echo '		<th>'.get_lang('Answered').'</th>';
echo '	</tr>';

$sql = "SELECT survey_invitation.*, user.firstname, user.lastname, user.email FROM $table_survey_invitation survey_invitation
			LEFT JOIN $table_user user ON  survey_invitation.user = user.user_id
			WHERE survey_invitation.survey_code = '".Database::escape_string($survey_data['code'])."'";
$res = Database::query($sql, __FILE__, __LINE__);
while ($row = mysql_fetch_assoc($res))
{
	if (!$_GET['view'] OR $_GET['view'] == 'invited' OR ($_GET['view'] == 'answered' AND in_array($row['user'], $answered_data)) OR ($_GET['view'] == 'unanswered' AND !in_array($row['user'], $answered_data)))
	{
		echo '<tr>';
		if (is_numeric($row['user']))
		{
			echo '			<td><a href="../user/userInfo.php?editMainUserInfo='.$row['user'].'">'.api_get_person_name($row['firstname'], $row['lastname']).'</a></td>';
		}
		else
		{
				echo '	<td>'.$row['user'].'</td>';
		}
		echo '	<td>'.$row['invitation_date'].'</td>';
		echo '	<td>';
		if (in_array($row['user'], $answered_data))
		{
			echo '<a href="reporting.php?action=userreport&amp;survey_id='.$survey_id.'&amp;user='.$row['user'].'">'.get_lang('ViewAnswers').'</a>';
		}
		else
		{
			echo '-';
		}
		echo '	</td>';
		echo '</tr>';
	}
}
// closing the table
echo '</table>';

// Footer
Display :: display_footer();
/**
 * @todo add the additional parameters
 */
/*
$table = new SortableTable('survey_invitations', 'get_number_of_survey_invitations', 'get_survey_invitations_data',2);
$table->set_additional_parameters($parameters);
$table->set_header(0, get_lang('User'));
$table->set_header(1, get_lang('InvitationCode'));
$table->set_header(2, get_lang('InvitationDate'));
$table->set_header(3, get_lang('Answered'));
$table->set_column_filter(3, 'SurveyUtil::modify_filter');
$table->display();
*/
