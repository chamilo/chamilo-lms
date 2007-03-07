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
* 	@author unknown, the initial survey that did not make it in 1.8 because of bad code
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of the code
* 	@version $Id: survey_invite.php 10680 2007-01-11 21:26:23Z pcool $
*
* 	@todo checking if the additional emails are valid (or add a rule for this)
* 	@todo check if the mailtext contains the **link** part, if not, add the link to the end
* 	@todo add rules: title and text cannot be empty
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
	Display :: display_error_message(get_lang('NotAllowedHere'), false);
	Display :: display_footer();
	exit;
}

// Database table definitions
$table_survey 					= Database :: get_course_table(TABLE_SURVEY);
$table_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
$table_course 					= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_user 					= Database :: get_main_table(TABLE_MAIN_USER);
$user_info 						= Database :: get_main_table(TABLE_MAIN_SURVEY_REMINDER);

// getting the survey information
$survey_data = survey_manager::get_survey($_GET['survey_id']);
$urlname = substr(strip_tags($survey_data['title']), 0, 40);
if (strlen(strip_tags($survey_data['title'])) > 40)
{
	$urlname .= '...';
}

// breadcrumbs
$interbreadcrumb[] = array ('url' => 'survey_list.php', 'name' => get_lang('SurveyList'));
$interbreadcrumb[] = array ('url' => 'survey.php?survey_id='.$_GET['survey_id'], 'name' => $urlname);
$tool_name = get_lang('SurveyPublication');

// Displaying the header
Display::display_header($tool_name);

// checking if there is another survey with this code.
// If this is the case there will be a language choice
$sql = "SELECT * FROM $table_survey WHERE code='".mysql_real_escape_string($survey_data['code'])."'";
$result = api_sql_query($sql, __FILE__, __LINE__);
if (mysql_num_rows($result) > 1)
{
	Display::display_warning_message(get_lang('IdenticalSurveycodeWarning'));
}

// building the form for publishing the survey
$form = new FormValidator('publish_form','post', $_SERVER['PHP_SELF'].'?survey_id='.$_GET['survey_id']);
// Course users
$complete_user_list = CourseManager :: get_user_list_from_course_code($_course['id']);
$possible_users = array ();
foreach ($complete_user_list as $index => $user)
{
	$possible_users[$user['user_id']] = $user['lastname'].' '.$user['firstname'];
}
$users = $form->addElement('advmultiselect', 'course_users', get_lang('CourseUsers'), $possible_users);
$users->setElementTemplate('
{javascript}
<table{class}>
<!-- BEGIN label_2 --><tr><th>{label_2}</th><!-- END label_2 -->
<!-- BEGIN label_3 --><th>&nbsp;</th><th>{label_3}</th></tr><!-- END label_3 -->
<tr>
  <td valign="top">{unselected}</td>
  <td align="center">{add}<br /><br />{remove}</td>
  <td valign="top">{selected}</td>
</tr>
</table>
');
// additional users
$form->addElement('textarea', 'additional_users', get_lang('AdditonalUsers'), array ('cols' => 50, 'rows' => 2));
// additional users comment
$form->addElement('static', null, null, get_lang('AdditonalUsersComment'));
// the title of the mail
$form->addElement('text', 'mail_title', get_lang('MailTitle'),array('size' => '80'));
// the text of the mail
$fck_attribute['Width'] = '100%';
$fck_attribute['Height'] = '150';
$fck_attribute['ToolbarSet'] = 'Survey';
$form->addElement('html_editor', 'mail_text', get_lang('MailText'));
// some explanation of the mail
$form->addElement('static', null, null, get_lang('UseLinkSyntax'));
// allow resending to all selected users
$form->addElement('checkbox', 'resend_to_all', '', get_lang('ReminderResendToAllUsers'));
// submit button
$form->addElement('submit', 'submit', get_lang('Ok'));
// The rules (required fields)
$form->addRule('mail_title', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('mail_text', get_lang('ThisFieldIsRequired'), 'required');
if ($form->validate())
{
	$values = $form->exportValues();
	// save the invitation mail
	save_invite_mail($values['mail_text'], $values['resend_to_all']);
	// saving the invitations for the course users
	$count_course_users = save_invitations($values['course_users'], $values['mail_title'], $values['mail_text'], $values['resend_to_all']);
	// saving the invitations for the additional users
	$values['additional_users'] = $values['additional_users'].';'; 	// this is for the case when you enter only one email
	$temp = str_replace(',',';',$values['additional_users']);		// this is to allow , and ; as email separators
	$additional_users = explode(';',$temp);
	$counter_additional_users = save_invitations($additional_users, $values['mail_title'], $values['mail_text'], $values['resend_to_all']);
	// updating the invited field in the survey table
	update_count_invited($survey_data['code']);
	$total_count = $count_course_users + $counter_additional_users;
	Display :: display_confirmation_message($total_count.' '.get_lang('InvitationsSend'), false);
}
else
{
	$defaults = get_invitations($survey_data['code']);
	if (!empty($survey_data['reminder_mail']))
	{
		$defaults['mail_text'] = $survey_data['reminder_mail'];
	}
	else
	{
		$defaults['mail_text'] = $survey_data['invite_mail'];
	}
	$form->setDefaults($defaults);

	$form->display();
}

// Footer
Display :: display_footer();



/**
 * Save the invitation mail
 *
 * @param unknown_type $mailtext
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version January 2007
 */
function save_invite_mail($mailtext, $reminder=0)
{
	// Database table definition
	$table_survey 					= Database :: get_course_table(TABLE_SURVEY);

	// reminder or not
	if ($reminder == 0)
	{
		$mail_field = 'invite_mail';
	}
	else
	{
		$mail_field = 'reminder_mail';
	}

	$sql = "UPDATE $table_survey SET $mail_field = '".mysql_real_escape_string($mailtext)."' WHERE survey_id = '".$_GET['survey_id']."'";
	$result = api_sql_query($sql, __FILE__, __LINE__);
}

/**
 * This function saves all the invitations of course users and additional users in the
 *
 * @param unknown_type $users_array
 * @param string $invitation_title the title of the invitation is used as the title of the mail
 * @param string $invitation_text the text of the invitation is used as the text of the mail.
 * 				 The $invitation_text has to contain a **link** string or this will automatically be added to the end
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version January 2007
 *
 * @todo create the survey link
 */
function save_invitations($users_array, $invitation_title, $invitation_text, $reminder=0)
{
	global $_user;
	global $_course;
	global $_configuration;

	// getting the survey information
	$survey_data = survey_manager::get_survey($_GET['survey_id']);

	// Database table definition
	$table_survey_invitation 	= Database :: get_course_table(TABLE_SURVEY_INVITATION);
	$table_user 				= Database :: get_main_table(TABLE_MAIN_USER);

	// get the people who are already invited
	if ($reminder == 1)
	{
		$already_invited = array();
	}
	else
	{
		$already_invited = get_invitations($survey_data['code']);
	}

	$counter = 0;
	if (is_array($users_array))
	{
		foreach ($users_array as $key=>$value)
		{
			// generating the unique code
			$invitation_code = md5($value.microtime());

			// storing the invitation (only if the user_id is not in $already_invited['course_users'] OR email is not in $already_invited['additional_users']
			if ((is_numeric($value) AND !in_array($value,$already_invited['course_users'])) OR (!is_numeric($value) AND !strstr($already_invited['additional_users'], $value)) AND !empty($value))
			{
				$sql = "INSERT INTO $table_survey_invitation (user, survey_code, invitation_code, invitation_date) VALUES
							('".mysql_real_escape_string($value)."','".mysql_real_escape_string($survey_data['code'])."','".mysql_real_escape_string($invitation_code)."','".mysql_real_escape_string(date('Y-m-d H:i:s'))."')";
				$result = api_sql_query($sql, __FILE__, __LINE__);

				// replacing the **link** part with a valid link for the user
				$survey_link = $_configuration['root_web'].$_configuration['code_append'].'survey/'.'fillsurvey.php?course='.$_course['sysCode'].'&invitationcode='.$invitation_code;
				$invitation_text = str_ireplace('**link**', $survey_link ,$invitation_text, $replace_count);
				if ($replace_count < 1)
				{
					$invitation_text = $invitation_text . $survey_link;
				}

				// optionally: finding the e-mail of the course user
				if (is_numeric($value))
				{
					$sql = "SELECT firstname, lastname, email FROM $table_user WHERE user_id='".mysql_real_escape_string($value)."'";
					$result = api_sql_query($sql, __FILE__, __LINE__);
					$row = mysql_fetch_assoc($result);
					$recipient_email = $row['email'];
					$recipient_name = $row['firstname'].' '.$row['lastname'];
				}
				else
				{
					/** @todo check if the $value is a valid email	 */
					$recipient_email = $value;
				}

				// sending the mail
				$sender_name  = $_user['firstname'].' '.$_user['lastname'];
				$sender_email = $_user['email'];
				//echo $recipient_name.'-'.$recipient_email.'-'.$invitation_title.'-'.$invitation_text.'-'.$sender_name.'-'.$sender_email.'-';
				//api_mail($recipient_name, $recipient_email, $invitation_title, $invitation_text, $sender_name, $sender_email, '');
				mail($recipient_email, strip_tags($invitation_titleà, strip_tags($invitation_text)));
				$counter++;
			}
		}
	}

	return $counter;
}

/**
 * This function recalculates the number of users who have been invited and updates the survey table with this value.
 *
 * @param unknown_type $survey_id
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version January 2007
 */
function update_count_invited($survey_code)
{
	// Database table definition
	$table_survey_invitation 	= Database :: get_course_table(TABLE_SURVEY_INVITATION);
	$table_survey 				= Database :: get_course_table(TABLE_SURVEY);

	// counting the number of people that are invited
	$sql = "SELECT count(user) as total FROM $table_survey_invitation WHERE survey_code = '".mysql_real_escape_string($survey_code)."'";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	$row = mysql_fetch_assoc($result);
	$total_invited = $row['total'];

	// updating the field in the survey table
	$sql = "UPDATE $table_survey SET invited = '".mysql_real_escape_string($total_invited)."' WHERE code = '".mysql_real_escape_string($survey_code)."'";
	$result = api_sql_query($sql, __FILE__, __LINE__);
}

/**
 * This function gets all the invitations for a given survey code.
 *
 * @param unknown_type $survey_id
 * @return array containing the course users and additional users (non course users)
 *
 * @todo consider making $defaults['additional_users'] also an array
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version January 2007
 */
function get_invitations($survey_code)
{
	// Database table definition
	$table_survey_invitation 	= Database :: get_course_table(TABLE_SURVEY_INVITATION);

	// Selecting all the invitations of this survey
	$sql = "SELECT user FROM $table_survey_invitation WHERE survey_code='".mysql_real_escape_string($survey_code)."'";

	$result = api_sql_query($sql, __FILE__, __LINE__);
	while ($row = mysql_fetch_assoc($result))
	{
		if (is_numeric($row['user']))
		{
			$defaults['course_users'][] = $row['user'];
		}
		else
		{
			if (empty($defaults['additional_users']))
			{
				$defaults['additional_users'] = $row['user'];
			}
			else
			{
				$defaults['additional_users'] .= ';'.$row['user'];
			}
		}
	}
	return $defaults;
}
?>