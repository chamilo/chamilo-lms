<?php
// $Id: inscription.php 13551 2007-10-24 11:53:14Z pcool $
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
require_once (api_get_path(CONFIGURATION_PATH).'profile.conf.php');
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

$fck_attribute['Height'] = "150";
$fck_attribute['Width'] = "450";
$fck_attribute['ToolbarSet'] = "Profil";

$form = new FormValidator('registration');
//	LAST NAME and FIRST NAME
$form->addElement('text', 'lastname',  get_lang('LastName'),  array('size' => 40));
$form->addElement('text', 'firstname', get_lang('FirstName'), array('size' => 40));
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
$form->addElement('text', 'username', get_lang('UserName'), array('size' => 20));
$form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('username', get_lang('UsernameWrong'), 'username');
$form->addRule('username', get_lang('UserTaken'), 'username_available');
$form->addRule('username', '', 'maxlength',20);
//	PASSWORD
$form->addElement('password', 'pass1', get_lang('Pass'),         array('size' => 40));
$form->addElement('password', 'pass2', get_lang('Confirmation'), array('size' => 40));
$form->addRule('pass1', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('pass2', get_lang('ThisFieldIsRequired'), 'required');
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
//	EXTENDED FIELDS
if (api_get_setting('extended_profile') == 'true' AND api_get_setting('extendedprofile_registration','mycomptetences') == 'true')
{
	$form->add_html_editor('competences', get_lang('MyCompetences'), false);
}
if (api_get_setting('extended_profile') == 'true' AND api_get_setting('extendedprofile_registration','mydiplomas') == 'true')
{
	$form->add_html_editor('diplomas', get_lang('MyDiplomas'), false);
}
if (api_get_setting('extended_profile') == 'true' AND api_get_setting('extendedprofile_registration','myteach') == 'true')
{
	$form->add_html_editor('teach', get_lang('MyTeach'), false);
}
if (api_get_setting('extended_profile') == 'true' AND api_get_setting('extendedprofile_registration','mypersonalopenarea') == 'true')
{
	$form->add_html_editor('openarea', get_lang('MyPersonalOpenArea'), false);
}
if (api_get_setting('extended_profile') == 'true')
{
	if (api_get_setting('extendedprofile_registrationrequired','mycomptetences') == 'true')
	{
		$form->addRule('competences', get_lang('ThisFieldIsRequired'), 'required');
	}
	if (api_get_setting('extendedprofile_registrationrequired','mydiplomas') == 'true')
	{
		$form->addRule('diplomas', get_lang('ThisFieldIsRequired'), 'required');
	}
	if (api_get_setting('extendedprofile_registrationrequired','myteach') == 'true')
	{
		$form->addRule('teach', get_lang('ThisFieldIsRequired'), 'required');
	}
	if (api_get_setting('extendedprofile_registrationrequired','mypersonalopenarea') == 'true')
	{
		$form->addRule('openarea', get_lang('ThisFieldIsRequired'), 'required');
	}
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
	$values['username'] = substr($values['username'],0,20); //make *sure* the login isn't too long

	if (get_setting('allow_registration_as_teacher') == 'false')
	{
		$values['status'] = STUDENT;
	}

	// creating a new user
	$user_id = UserManager::create_user($values['firstname'],$values['lastname'],$values['status'],$values['email'],$values['username'],$values['pass1'],$values['official_code'], $values['language']);



	if ($user_id)
	{
		// storing the extended profile
		$store_extended = false;
		$sql = "UPDATE ".Database::get_main_table(TABLE_MAIN_USER)." SET ";
		if (api_get_setting('extended_profile') == 'true' AND api_get_setting('extendedprofile_registration','mycomptetences') == 'true')
		{
			$sql_set[] = "competences = '".Database::escape_string($values['competences'])."'";
			$store_extended = true;
		}
		if (api_get_setting('extended_profile') == 'true' AND api_get_setting('extendedprofile_registration','mydiplomas') == 'true')
		{
			$sql_set[] = "diplomas = '".Database::escape_string($values['diplomas'])."'";
			$store_extended = true;
		}
		if (api_get_setting('extended_profile') == 'true' AND api_get_setting('extendedprofile_registration','myteach') == 'true')
		{
			$sql_set[] = "teach = '".Database::escape_string($values['teach'])."'";
			$store_extended = true;
		}
		if (api_get_setting('extended_profile') == 'true' AND api_get_setting('extendedprofile_registration','mypersonalopenarea') == 'true')
		{
			$sql_set[] = "openarea = '".Database::escape_string($values['openarea'])."'";
			$store_extended = true;
		}
		if ($store_extended)
		{
			$sql .= implode(',',$sql_set);
			$sql .= " WHERE user_id = '".Database::escape_string($user_id)."'";
			api_sql_query($sql,__FILE__,__LINE__);
		}

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
			$emailbody		.=get_lang('UserName').': '.$values['username']."\n";
			$emailbody		.=get_lang('LastName').': '.$values['lastname']."\n";
			$emailbody		.=get_lang('FirstName').': '.$values['firstname']."\n";
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