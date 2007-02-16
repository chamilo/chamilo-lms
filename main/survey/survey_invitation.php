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
if (!api_is_allowed_to_edit())
{
	Display :: display_header();
	Display :: display_error_message(get_lang('NotAllowedHere'));
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

// breadcrumbs
$interbreadcrumb[] = array ('url' => 'survey_list.php', 'name' => get_lang('SurveyList'));
$interbreadcrumb[] = array ('url' => 'survey.php?survey_id='.$_GET['survey_id'], 'name' => get_lang('Survey'));
$tool_name = get_lang('SurveyInvitations');

// Displaying the header
Display::display_header($tool_name);

// Checking the parameters
if (!is_numeric($_GET['survey_id']))
{
	Display::display_error_message(get_lang('Error'));
	Display::display_footer();
	exit;
}

// Displaying the survey information
$survey_data = survey_manager::get_survey($_GET['survey_id']);
echo '<a href="survey.php?survey_id='.$survey_data['survey_id'].'">'.$survey_data['title'].'</a><br />';
echo $survey_data['subtitle'];

// Getting all the people who have filled this survey
$answered_data = survey_manager::get_people_who_filled_survey($_GET['survey_id']);


//
echo 'view invited | view answered | view unanswered';

// table header
echo '<table class="data_table">';
echo '	<tr>';
echo '		<th>'.get_lang('User').'</th>';
echo '		<th>'.get_lang('InvitationCode').'</th>';
echo '		<th>'.get_lang('InvitationDate').'</th>';
echo '		<th>'.get_lang('Answered').'</th>';
echo '	</tr>';

$sql = "SELECT survey_invitation.*, user.firstname, user.lastname, user.email FROM $table_survey_invitation survey_invitation
			LEFT JOIN $table_user user ON  survey_invitation.user = user.user_id
			WHERE survey_invitation.survey_id = '".mysql_real_escape_string($_GET['survey_id'])."'";
$res = api_sql_query($sql, __FILE__, __LINE__);
while ($row = mysql_fetch_assoc($res))
{
	echo '<tr>';
	if (is_numeric($row['user']))
	{
		echo '			<td><a href="../user/userInfo.php?editMainUserInfo='.$row['user'].'">'.$row['firstname'].' '.$row['lastname'].'</a></td>';
	}
	else
	{
			echo '	<td>'.$row['user'].'</td>';
	}
	/** @todo this is temporary to allow the developer to quickly fill a survey as a different user */
	// echo '	<td>'.$row['invitation_code'].'</td>';
	echo '	<td><a href="fillsurvey.php?course='.$_course['sysCode'].'&amp;invitationcode='.$row['invitation_code'].'">'.$row['invitation_code'].'</td>';
	echo '	<td>'.$row['invitation_date'].'</td>';
	echo '	<td>';
	if (in_array($row['user'], $answered_data))
	{
		echo '<a href="reporting.php?action=userreport&amp;survey_id='.$_GET['survey_id'].'&amp;user='.$row['user'].'">'.get_lang('ViewAnswers').'</a>';
	}
	else
	{
		echo '-';
	}
	echo '	</td>';
	echo '</tr>';

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
$table->set_column_filter(3, 'modify_filter');
$table->display();
*/

/**
 * Get all the information about the invitations of a certain survey
 *
 * @return unknown
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version January 2007
 *
 * @todo use survey_id parameter instead of $_GET
 */
function get_survey_invitations_data()
{
	// Database table definition
	$table_survey_invitation 		= Database :: get_course_table(TABLE_SURVEY_INVITATION);
	$table_user 					= Database :: get_main_table(TABLE_MAIN_USER);

	$sql = "SELECT
				survey_invitation.user as col1,
				survey_invitation.invitation_code as col2,
				survey_invitation.invitation_date as col3,
				'' as col4
				FROM $table_survey_invitation survey_invitation
		LEFT JOIN $table_user user ON  survey_invitation.user = user.user_id
		WHERE survey_invitation.survey_id = '".mysql_real_escape_string($_GET['survey_id'])."'";
	$res = api_sql_query($sql, __FILE__, __LINE__);
	while ($row = mysql_fetch_array($res))
	{
		$survey_invitation_data[] = $row;
	}
	return $survey_invitation_data;
}

/**
 * Get the total number of survey invitations for a given survey (through $_GET['survey_id'])
 *
 * @return unknown
 *
 * @todo use survey_id parameter instead of $_GET
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version January 2007
 */
function get_number_of_survey_invitations()
{
	// Database table definition
	$table_survey_invitation 		= Database :: get_course_table(TABLE_SURVEY_INVITATION);

	$sql = "SELECT count(user) AS total FROM $table_survey_invitation WHERE survey_id='".mysql_real_escape_string($_GET['survey_id'])."'";
	$res = api_sql_query($sql, __FILE__, __LINE__);
	$row = mysql_fetch_assoc($res);
	return $row['total'];
}
/**
 * @todo use global array for answered or not
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version January 2007
 */
function modify_filter()
{

}
?>