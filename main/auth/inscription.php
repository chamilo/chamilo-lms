<?php
// $Id: inscription.php 10916 2007-01-26 09:30:12Z bmol $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors
	Copyright (c) Bart Mollet, Hogeschool Gent

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	This script displays a form for registering new users.
*	@package	 dokeos.auth
==============================================================================
*/
// name of the language file that needs to be included
$language_file = "registration";

include ("../inc/global.inc.php");

require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once (api_get_path(CONFIGURATION_PATH).'profile.conf.inc.php');
$tool_name = get_lang('Registration');

Display :: display_header($tool_name);

// Forbidden to self-register
if (get_setting('allow_registration') == 'false')
{
	api_not_allowed();
}
//api_display_tool_title($tool_name);
if (get_setting('allow_registration')=='approval')
{
	Display::display_normal_message(get_lang('YourAccountHasToBeApproved'));
}


$form = new FormValidator('registration');
//	LAST NAME and FIRST NAME
$form->addElement('text', 'lastname',  get_lang('Lastname'),  array('size' => 40));
$form->addElement('text', 'firstname', get_lang('Firstname'), array('size' => 40));
$form->addRule('lastname',  get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');
//	EMAIL
$form->addElement('text', 'email', get_lang('Email'), array('size' => 40));
if (api_get_setting('registration', 'email') == 'true')
	$form->addRule('email', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('email', get_lang('EmailWrong'), 'email');
//	OFFICIAL CODE
if (CONFVAL_ASK_FOR_OFFICIAL_CODE)
{
	$form->addElement('text', 'official_code', get_lang('OfficialCode'), array('size' => 40));
	if (api_get_setting('registration', 'officialcode') == 'true')
		$form->addRule('official_code', get_lang('ThisFieldIsRequired'), 'required');
}
//	USERNAME
$form->addElement('text', 'username', get_lang('Username'), array('size' => 40));
$form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('username', get_lang('UsernameWrong'), 'username');
$form->addRule('username', get_lang('UserTaken'), 'username_available');
//	PASSWORD
$form->addElement('password', 'pass1', get_lang('Pass'),         array('size' => 40));
$form->addElement('password', 'pass2', get_lang('Confirmation'), array('size' => 40));
$form->addRule('pass1', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule(array('pass1', 'pass2'), get_lang('PassTwo'), 'compare');
if (CHECK_PASS_EASY_TO_FIND)
	$form->addRule('password1', get_lang('PassTooEasy').': '.api_generate_password(), 'callback', 'api_check_password');
//	LANGUAGE
if (get_setting('registration', 'language') == 'true')
{
	$form->addElement('select_language', 'language', get_lang('Language'));
}
//	STUDENT/TEACHER
if (get_setting('allow_registration_as_teacher') <> 'false')
{
	$form->addElement('radio', 'status', get_lang('Status'), get_lang('RegStudent'), STUDENT);
	$form->addElement('radio', 'status', null, get_lang('RegAdmin'), COURSEMANAGER);
}
$form->addElement('submit', 'submit', get_lang('Ok'));
if(isset($_SESSION["user_language_choice"]) && $_SESSION["user_language_choice"]!=""){
	$defaults['language'] = $_SESSION["user_language_choice"];
}
else{
	$defaults['language'] = api_get_setting('platformLanguage');
}
$defaults['status'] = STUDENT;
$form->setDefaults($defaults);

if ($form->validate())
{
	/*-----------------------------------------------------
	  STORE THE NEW USER DATA INSIDE THE MAIN DOKEOS DATABASE
	  -----------------------------------------------------*/
	$values = $form->exportValues();

	if (get_setting('allow_registration_as_teacher') == 'false')
	{
		$values['status'] = STUDENT;
	}

	// creating a new user
	$user_id = UserManager::create_user($values['firstname'],$values['lastname'],$values['status'],$values['email'],$values['username'],$values['pass1'],$values['official_code'], $values['language']);



	if ($user_id)
	{
		// TODO: add language to parameter list of UserManager::create_user(...)
		$sql = "UPDATE ".Database::get_main_table(TABLE_MAIN_USER)."
		             SET language	= '".mysql_real_escape_string($values['language'])."'
					WHERE user_id = '".$user_id."'	 ";
		//api_sql_query($sql,__FILE__,__LINE__);

		// if there is a default duration of a valid account then we have to change the expiration_date accordingly
		if (get_setting('account_valid_duration')<>'')
		{
			$sql = "UPDATE ".Database::get_main_table(TABLE_MAIN_USER)."
						SET expiration_date='registration_date+1' WHERE user_id='".$user_id."'";
			api_sql_query($sql,__FILE__,__LINE__);
		}

		// if the account has to be approved then we set the account to inactive, sent a mail to the platform admin and exit the page.
		if (get_setting('allow_registration')=='approval')
		{
			// 1. set account inactive
			$sql = "UPDATE ".Database::get_main_table(TABLE_MAIN_USER)."
						SET active='0' WHERE user_id='".$user_id."'";
			api_sql_query($sql,__FILE__,__LINE__);

			// 2. send mail to the platform admin
			$emailfromaddr 	= api_get_setting('emailAdministrator');
			$emailfromname 	= api_get_setting('siteName');
			$emailto		= api_get_setting('emailAdministrator');
			$emailsubject	= get_lang('ApprovalForNewAccount').': '.$values['username'];
			$emailbody		= get_lang('ApprovalForNewAccount')."\n";
			$emailbody		.=get_lang('Username').': '.$values['username']."\n";
			$emailbody		.=get_lang('Lastname').': '.$values['lastname']."\n";
			$emailbody		.=get_lang('Firstname').': '.$values['firstname']."\n";
			$emailbody		.=get_lang('Email').': '.$values['email']."\n";
			$emailbody		.=get_lang('Status').': '.$values['status']."\n\n";
			$emailbody		.=get_lang('ManageUser').': '.api_get_path(WEB_CODE_PATH).'admin/user_edit.php?user_id='.$user_id;
			$emailheaders = "From: ".get_setting('administratorSurname')." ".get_setting('administratorName')." <".get_setting('emailAdministrator').">\n";
			$emailheaders .= "Reply-To: ".get_setting('emailAdministrator');
			@ api_send_mail($emailto, $emailsubject, $emailbody, $emailheaders);

			// 3. exit the page
			unset($user_id);
			Display :: display_footer();
			exit;
		}


		/*--------------------------------------
		          SESSION REGISTERING
		  --------------------------------------*/
		$_user['firstName'] = stripslashes($values['firstname']);
		$_user['lastName'] 	= stripslashes($values['lastname']);
		$_user['mail'] 		= $values['email'];
		$_user['language'] 	= $values['language'];
		$_user['user_id']	= $user_id;
		$is_allowedCreateCourse = ($values['status'] == 1) ? true : false;
		api_session_register('_user');
		api_session_register('is_allowedCreateCourse');

		//stats
		include (api_get_path(LIBRARY_PATH)."events.lib.inc.php");
		event_login();
		// last user login date is now
		$user_last_login_datetime = 0; // used as a unix timestamp it will correspond to : 1 1 1970

		api_session_register('user_last_login_datetime');

		/*--------------------------------------
		             EMAIL NOTIFICATION
		  --------------------------------------*/

		if (strstr($values['email'], '@'))
		{
			// Lets predefine some variables. Be sure to change the from address!
			$firstname = $values['firstname'];
			$lastname = $values['lastname'];
			$emailto = "\"$firstname $lastname\" <".$values['email'].">";
			$emailfromaddr = api_get_setting('emailAdministrator');
			$emailfromname = api_get_setting('siteName');
			$emailsubject = "[".get_setting('siteName')."] ".get_lang('YourReg')." ".get_setting('siteName');

			// The body can be as long as you wish, and any combination of text and variables

			$emailbody = get_lang('Dear')." ".stripslashes("$firstname $lastname").",\n\n".get_lang('YouAreReg')." ".get_setting('siteName')." ".get_lang('Settings')." ".$values['username']."\n".get_lang('Pass')." : ".stripslashes($values['pass1'])."\n\n".get_lang('Address')." ".get_setting('siteName')." ".get_lang('Is')." : ".$_configuration['root_web']."\n\n".get_lang('Problem')."\n\n".get_lang('Formula').",\n\n".get_setting('administratorName')." ".get_setting('administratorSurname')."\n".get_lang('Manager')." ".get_setting('siteName')."\nT. ".get_setting('administratorTelephone')."\n".get_lang('Email')." : ".get_setting('emailAdministrator');

			// Here we are forming one large header line
			// Every header must be followed by a \n except the last
			$emailheaders = "From: ".get_setting('administratorSurname')." ".get_setting('administratorName')." <".get_setting('emailAdministrator').">\n";
			$emailheaders .= "Reply-To: ".get_setting('emailAdministrator');

			// Because I predefined all of my variables, this api_send_mail() function looks nice and clean hmm?
			@ api_send_mail($emailto, $emailsubject, $emailbody, $emailheaders);
		}
	}

	echo "<p>".get_lang('Dear')." ".stripslashes("$firstname $lastname").",<br><br>".get_lang('PersonalSettings').".</p>\n";

	if (!empty ($values['email']))
	{
		echo "<p>".get_lang('MailHasBeenSent').".</p>";
	}

	if ($is_allowedCreateCourse)
	{
		echo "<p>", get_lang('NowGoCreateYourCourse'), ".</p>\n";
		$actionUrl = "../create_course/add_course.php";
	}
	else
	{
		echo "<p>", get_lang('NowGoChooseYourCourses'), ".</p>\n";
		$actionUrl = "courses.php?action=subscribe";
	}
	// ?uidReset=true&uidReq=$_user['user_id']

	echo "<form action=\"", $actionUrl, "\"  method=\"post\">\n", "<input type=\"submit\" name=\"next\" value=\"", get_lang('Next'), "\" validationmsg=\" ", get_lang('Next'), " \">\n", "</form><br>\n";

}
else
{
	$form->display();
}
?>
<a href="<?php echo api_get_path(WEB_PATH); ?>">&lt;&lt; <?php echo get_lang('Back'); ?></a>
<?php
/*
==============================================================================
		FOOTER
==============================================================================
*/

Display :: display_footer();
?>