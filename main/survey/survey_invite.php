<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2009 Dokeos SPRL

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/

/**
*	@package dokeos.survey
* 	@author unknown, the initial survey that did not make it in 1.8 because of bad code
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of the code
*   @author Julio Montoya Dokeos: cleanup, refactoring, security improvements
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
$user_info 			= Database::get_main_table(TABLE_MAIN_SURVEY_REMINDER); // TODO: To be checked. TABLE_MAIN_SURVEY_REMINDER has not been defined.

// getting the survey information
$survey_id = Security::remove_XSS($_GET['survey_id']);
$survey_data = survey_manager::get_survey($survey_id);
if (empty($survey_data)) {
	Display :: display_header(get_lang('Survey'));
	Display :: display_error_message(get_lang('InvallidSurvey'), false);
	Display :: display_footer();
	exit;
}


$urlname = strip_tags(api_substr(api_html_entity_decode($survey_data['title'],ENT_QUOTES,$charset), 0, 40));
if (api_strlen(strip_tags($survey_data['title'])) > 40)
{
	$urlname .= '...';
}

// breadcrumbs
$interbreadcrumb[] = array ('url' => 'survey_list.php', 'name' => get_lang('SurveyList'));
if (api_is_course_admin()) {
    $interbreadcrumb[] = array ('url' => 'survey.php?survey_id='.$survey_id, 'name' => $urlname);
} else {
    $interbreadcrumb[] = array ('url' => 'survey_invite.php?survey_id='.$survey_id, 'name' => $urlname);    
}
$tool_name = get_lang('SurveyPublication');

// Displaying the header
Display::display_header($tool_name,'Survey');

// checking if there is another survey with this code.
// If this is the case there will be a language choice
$sql = "SELECT * FROM $table_survey WHERE code='".Database::escape_string($survey_data['code'])."'";
$result = api_sql_query($sql, __FILE__, __LINE__);
if (Database::num_rows($result) > 1)
{
	Display::display_warning_message(get_lang('IdenticalSurveycodeWarning'));
}

// invited / answered message
if ($survey_data['invited'] > 0)
{
	$message  = '<a href="survey_invitation.php?view=answered&amp;survey_id='.$survey_data['survey_id'].'">'.$survey_data['answered'].'</a> ';
	$message .= get_lang('HaveAnswered').' ';
	$message .= '<a href="survey_invitation.php?view=invited&amp;survey_id='.$survey_data['survey_id'].'">'.$survey_data['invited'].'</a> ';
	$message .= get_lang('WereInvited');
	Display::display_normal_message($message, false);
}

// building the form for publishing the survey
$form = new FormValidator('publish_form','post', api_get_self().'?survey_id='.$survey_id);

$form->addElement('header', '', $tool_name);

// Course users
$complete_user_list = CourseManager :: get_user_list_from_course_code($_course['id'], true, $_SESSION['id_session'], '', api_sort_by_first_name() ? 'ORDER BY firstname' : 'ORDER BY lastname');
$possible_users = array ();
foreach ($complete_user_list as $index => $user)
{
	$possible_users[$user['user_id']] = api_get_person_name($user['firstname'], $user['lastname']);
}
$users = $form->addElement('advmultiselect', 'course_users', get_lang('CourseUsers'), $possible_users, 'style="width: 250px; height: 200px;"');
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
$form->addElement('html_editor', 'mail_text', get_lang('MailText'), null, array('ToolbarSet' => 'Survey', 'Width' => '100%', 'Height' => '150'));
// some explanation of the mail
$form->addElement('static', null, null, get_lang('UseLinkSyntax'));
$form->addElement('checkbox', 'send_mail', '', get_lang('SendMail'));
// you cab send a reminder to unanswered people if the survey is not anonymous
if ($survey_data['anonymous'] != 1) {
    $form->addElement('checkbox', 'remindUnAnswered', '', get_lang('RemindUnanswered'));
}
// allow resending to all selected users
$form->addElement('checkbox', 'resend_to_all', '', get_lang('ReminderResendToAllUsers'));
// submit button
$form->addElement('style_submit_button', 'submit', get_lang('PublishSurvey'), 'class="save"');
// The rules (required fields)
$form->addRule('mail_title', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('mail_text', get_lang('ThisFieldIsRequired'), 'required');

$portal_url = $_configuration['root_web'];
if ($_configuration['multiple_access_urls']==true) {
	$access_url_id = api_get_current_access_url_id();				
	if ($access_url_id != -1 ){
		$url = api_get_access_url($access_url_id);
		$portal_url = $url['url'];
	}
}	
		  
// show the URL that can be used by users to fill a survey without invitation
$auto_survey_link = $portal_url.$_configuration['code_append'].
            'survey/'.'fillsurvey.php?course='.$_course['sysCode'].
            '&invitationcode=auto&scode='.$survey_data['survey_code'];
$form->addElement('static',null, null, '<br \><br \>' . get_lang('AutoInviteLink'));
$form->addElement('static',null, null, $auto_survey_link);
if ($form->validate())
{
	$values = $form->exportValues();
	// save the invitation mail
	SurveyUtil::save_invite_mail($values['mail_text'], $values['mail_title'], !empty($survey_data['invite_mail']));
	// saving the invitations for the course users
	$count_course_users = SurveyUtil::save_invitations($values['course_users'], $values['mail_title'], 
                $values['mail_text'], $values['resend_to_all'], $values['send_mail'], $values['remindUnAnswered']);
	// saving the invitations for the additional users
	$values['additional_users'] = $values['additional_users'].';'; 	// this is for the case when you enter only one email
	$temp = str_replace(',',';',$values['additional_users']);		// this is to allow , and ; as email separators
	$additional_users = explode(';',$temp);
	for($i=0; $i<count($additional_users); $i++)
	{
		$additional_users[$i] = trim($additional_users[$i]);
	}
	$counter_additional_users = SurveyUtil::save_invitations($additional_users, $values['mail_title'], 
            $values['mail_text'], $values['resend_to_all'], $values['send_mail'], $values['remindUnAnswered']);
	// updating the invited field in the survey table
	SurveyUtil::update_count_invited($survey_data['code']);
	$total_count = $count_course_users + $counter_additional_users;
	Display :: display_confirmation_message($total_count.' '.get_lang('InvitationsSend'));
}
else
{
	// getting the invited users
	$defaults = SurveyUtil::get_invited_users($survey_data['code']);
	// getting the survey mail text
	if (!empty($survey_data['reminder_mail']))
	{
		$defaults['mail_text'] = $survey_data['reminder_mail'];
	}
	else
	{
		$defaults['mail_text'] = $survey_data['invite_mail'];
	}
	$defaults['mail_title'] = $survey_data['mail_subject'];
	$defaults['send_mail'] = 1;
	$form->setDefaults($defaults);
	$form->display();
}

// Footer
Display :: display_footer();
?>
