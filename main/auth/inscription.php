<?php
// $Id: inscription.php 20488 2009-05-11 17:14:41Z cvargas1 $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2009 Dokeos SPRL
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

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	This script displays a form for registering new users.
*	@package	 dokeos.auth
==============================================================================
*/
// name of the language file that needs to be included
$language_file = array("registration");

include ("../inc/global.inc.php");

require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once (api_get_path(CONFIGURATION_PATH).'profile.conf.php');
require_once(api_get_path(INCLUDE_PATH).'lib/mail.lib.inc.php');
//require_once(api_get_path(LIBRARY_PATH).'fileManage.lib.php');
//require_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
//require_once (api_get_path(LIBRARY_PATH).'image.lib.php');

$tool_name = get_lang('Registration');
Display :: display_header($tool_name);
echo '<div class="actions-title">';
echo $tool_name;
echo '</div>';
// Forbidden to self-register
if (get_setting('allow_registration') == 'false') {
	api_not_allowed();
}
//api_display_tool_title($tool_name);
if (get_setting('allow_registration')=='approval') {
	Display::display_normal_message(get_lang('YourAccountHasToBeApproved'));
}
//if openid was not found
if (!empty($_GET['openid_msg']) && $_GET['openid_msg'] == 'idnotfound') {
	Display::display_warning_message(get_lang('OpenIDCouldNotBeFoundPleaseRegister'));	
}

$fck_attribute['Height'] = "150";
$fck_attribute['Width'] = "450";
$fck_attribute['ToolbarSet'] = "Profil";

$form = new FormValidator('registration');
//	LAST NAME and FIRST NAME
$form->addElement('text', 'lastname',  get_lang('LastName'),  array('size' => 40));
$form->applyFilter('lastname','trim');
$form->addElement('text', 'firstname', get_lang('FirstName'), array('size' => 40));
$form->applyFilter('firstname','trim');
$form->addRule('lastname',  get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');
//	EMAIL
$form->addElement('text', 'email', get_lang('Email'), array('size' => 40));
if (api_get_setting('registration', 'email') == 'true')
	$form->addRule('email', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('email', get_lang('EmailWrong'), 'email');
if (api_get_setting('openid_authentication')=='true') {
	$form->addElement('text', 'openid', get_lang('OpenIDURL'), array('size' => 40));	
}
/*
//	OFFICIAL CODE
if (CONFVAL_ASK_FOR_OFFICIAL_CODE) {
	$form->addElement('text', 'official_code', get_lang('OfficialCode'), array('size' => 40));
	if (api_get_setting('registration', 'officialcode') == 'true')
		$form->addRule('official_code', get_lang('ThisFieldIsRequired'), 'required');
}
*/
//	USERNAME
$form->addElement('text', 'username', get_lang('UserName'), array('size' => 20));
$form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('username', get_lang('UsernameWrong'), 'username');
$form->addRule('username', get_lang('UserTaken'), 'username_available');
$form->addRule('username', sprintf(get_lang('UsernameMaxXCharacters'),'20'), 'maxlength',20);
//	PASSWORD
$form->addElement('password', 'pass1', get_lang('Pass'),         array('size' => 40));
$form->addElement('password', 'pass2', get_lang('Confirmation'), array('size' => 40));
$form->addRule('pass1', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('pass2', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule(array('pass1', 'pass2'), get_lang('PassTwo'), 'compare');
if (CHECK_PASS_EASY_TO_FIND)
	$form->addRule('password1', get_lang('PassTooEasy').': '.api_generate_password(), 'callback', 'api_check_password');

//	PHONE
$form->addElement('text', 'phone', get_lang('Phone'), array('size' => 40));
if (api_get_setting('registration', 'phone') == 'true')
	$form->addRule('phone', get_lang('ThisFieldIsRequired'), 'required');
	
// PICTURE
/*if (api_get_setting('profile', 'picture') == 'true') {
	$form->addElement('file', 'picture', get_lang('AddPicture'));
	$allowed_picture_types = array ('jpg', 'jpeg', 'png', 'gif');
	$form->addRule('picture', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);
}*/

//	LANGUAGE
if (get_setting('registration', 'language') == 'true') {
	$form->addElement('select_language', 'language', get_lang('Language'));
}
//	STUDENT/TEACHER
if (get_setting('allow_registration_as_teacher') <> 'false') {
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
// EXTRA FIELDS
$extra = UserManager::get_extra_fields(0,50,5,'ASC');
$extra_data = UserManager::get_extra_user_data(api_get_user_id(),true);
foreach ($extra as $id => $field_details) {
	if ($field_details[6] == 0) {
		continue;
	}
	switch($field_details[2]) {
		case USER_FIELD_TYPE_TEXT:
			$form->addElement('text', 'extra_'.$field_details[1], $field_details[3], array('size' => 40));
			$form->applyFilter('extra_'.$field_details[1], 'stripslashes');
			$form->applyFilter('extra_'.$field_details[1], 'trim');
			if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);
			break;
		case USER_FIELD_TYPE_TEXTAREA:
			$form->add_html_editor('extra_'.$field_details[1], $field_details[3], false);
			//$form->addElement('textarea', 'extra_'.$field_details[1], $field_details[3], array('size' => 80));
			$form->applyFilter('extra_'.$field_details[1], 'stripslashes');
			$form->applyFilter('extra_'.$field_details[1], 'trim');
			if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);
			break;
		case USER_FIELD_TYPE_RADIO:
			$group = array();
			foreach ($field_details[9] as $option_id => $option_details) {
				$options[$option_details[1]] = $option_details[2];
				$group[] =& HTML_QuickForm::createElement('radio', 'extra_'.$field_details[1], $option_details[1],$option_details[2].'<br />',$option_details[1]);
			}
			$form->addGroup($group, 'extra_'.$field_details[1], $field_details[3], '');
			if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);	
			break;
		case USER_FIELD_TYPE_SELECT:
			$options = array();
			foreach($field_details[9] as $option_id => $option_details) {
				$options[$option_details[1]] = $option_details[2];
			}
			$form->addElement('select','extra_'.$field_details[1],$field_details[3],$options,'');	
			if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);			
			break;
		case USER_FIELD_TYPE_SELECT_MULTIPLE:
			$options = array();
			foreach ($field_details[9] as $option_id => $option_details) {
				$options[$option_details[1]] = $option_details[2];
			}
			$form->addElement('select','extra_'.$field_details[1],$field_details[3],$options,array('multiple' => 'multiple'));
			if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);	
			break;
		case USER_FIELD_TYPE_DATE:
			$form->addElement('datepickerdate', 'extra_'.$field_details[1], $field_details[3],array('form_name'=>'registration'));
			$form->_elements[$form->_elementIndex['extra_'.$field_details[1]]]->setLocalOption('minYear',1900);
			$defaults['extra_'.$field_details[1]] = date('Y-m-d 12:00:00');
			$form -> setDefaults($defaults);
			if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);
			$form->applyFilter('theme', 'trim');
			break;
		case USER_FIELD_TYPE_DATETIME:
			$form->addElement('datepicker', 'extra_'.$field_details[1], $field_details[3],array('form_name'=>'registration'));
			$form->_elements[$form->_elementIndex['extra_'.$field_details[1]]]->setLocalOption('minYear',1900);
			$defaults['extra_'.$field_details[1]] = date('Y-m-d 12:00:00');
			$form -> setDefaults($defaults);
			if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);
			$form->applyFilter('theme', 'trim');
			break;
		case USER_FIELD_TYPE_DOUBLE_SELECT:
			foreach ($field_details[9] as $key=>$element) {
				if ($element[2][0] == '*') {
					$values['*'][$element[0]] = str_replace('*','',$element[2]);
				} else {
					$values[0][$element[0]] = $element[2];
				}
			}
			
			$group='';
			$group[] =& HTML_QuickForm::createElement('select', 'extra_'.$field_details[1],'',$values[0],'');
			$group[] =& HTML_QuickForm::createElement('select', 'extra_'.$field_details[1].'*','',$values['*'],'');
			$form->addGroup($group, 'extra_'.$field_details[1], $field_details[3], '&nbsp;');
			if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);

			// recoding the selected values for double : if the user has selected certain values, we have to assign them to the correct select form
			if (key_exists('extra_'.$field_details[1], $extra_data)) {
				// exploding all the selected values (of both select forms)
				$selected_values = explode(';',$extra_data['extra_'.$field_details[1]]);
				$extra_data['extra_'.$field_details[1]]  =array();
				
				// looping through the selected values and assigning the selected values to either the first or second select form
				foreach ($selected_values as $key=>$selected_value) {
					if (key_exists($selected_value,$values[0])) {
						$extra_data['extra_'.$field_details[1]]['extra_'.$field_details[1]] = $selected_value;
					} else {
						$extra_data['extra_'.$field_details[1]]['extra_'.$field_details[1].'*'] = $selected_value;
					}
				}
			}
			break;
		case USER_FIELD_TYPE_DIVIDER:
			$form->addElement('static',$field_details[1], '<br /><strong>'.$field_details[3].'</strong>');
			break;
	}
}
$form->addElement('style_submit_button', 'submit', get_lang('RegisterUser'),'class="save"');
if(isset($_SESSION["user_language_choice"]) && $_SESSION["user_language_choice"]!=""){
	$defaults['language'] = $_SESSION["user_language_choice"];
}
else{
	$defaults['language'] = api_get_setting('platformLanguage');
}
if(!empty($_GET['username']))
{
	$defaults['username'] = Security::remove_XSS($_GET['username']);
}
if(!empty($_GET['email']))
{
	$defaults['email'] = Security::remove_XSS($_GET['email']);
}

if(!empty($_GET['phone']))
{
	$defaults['phone'] = Security::remove_XSS($_GET['phone']);
}

if (api_get_setting('openid_authentication')=='true' && !empty($_GET['openid']))
{
	$defaults['openid'] = Security::remove_XSS($_GET['openid']);	
}
$defaults['status'] = STUDENT;
$form->setDefaults($defaults);

if ($form->validate()) {
	/*-----------------------------------------------------
	  STORE THE NEW USER DATA INSIDE THE MAIN DOKEOS DATABASE
	  -----------------------------------------------------*/
	$values = $form->exportValues();
	
	$values['username'] = api_substr($values['username'],0,20); //make *sure* the login isn't too long

	if (get_setting('allow_registration_as_teacher') == 'false') {
		$values['status'] = STUDENT;
	}
	
	// creating a new user
	$user_id = UserManager::create_user($values['firstname'],$values['lastname'],$values['status'],$values['email'],$values['username'],$values['pass1'],$values['official_code'], $values['language'],$values['phone'],$picture_uri);

	/****** register extra fields*************/
	$extras=array();
	foreach($values as $key => $value) {
		if (substr($key,0,6)=='extra_') {//an extra field
			$extras[substr($key,6)] = $value;
		} else {
			$sql .= " $key = '".Database::escape_string($value)."',";
		}
	}
	//update the extra fields
	$count_extra_field=count($extras);
	if ($count_extra_field>0) {
		foreach ($extras as $key=>$value) {
			$myres = UserManager::update_extra_field_value($user_id,$key,$value);
		}
	}

	/********************************************/
	if ($user_id) {
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
			$TABLE_USER= Database::get_main_table(TABLE_MAIN_USER);
			// 1. set account inactive
			$sql = "UPDATE ".$TABLE_USER."	SET active='0' WHERE user_id='".$user_id."'";
			api_sql_query($sql,__FILE__,__LINE__);

			
			$sql_get_id_admin="SELECT * FROM ".Database::get_main_table(TABLE_MAIN_ADMIN);
			$result=api_sql_query($sql_get_id_admin,__FILE__,__LINE__);
			while ($row = Database::fetch_array($result)) {
					
				$sql_admin_list="SELECT * FROM ".$TABLE_USER." WHERE user_id='".$row['user_id']."'";			
				$result_list=api_sql_query($sql_admin_list,__FILE__,__LINE__);
				$admin_list=Database::fetch_array($result_list);
				$emailto		= $admin_list['email'];


				// 2. send mail to the platform admin
				$emailfromaddr 	= api_get_setting('emailAdministrator');
				$emailfromname 	= api_get_setting('siteName');
				$emailsubject	= get_lang('ApprovalForNewAccount').': '.$values['username'];
				$emailbody		= get_lang('ApprovalForNewAccount')."\n";
				$emailbody		.=get_lang('UserName').': '.$values['username']."\n";
				$emailbody		.=get_lang('LastName').': '.$values['lastname']."\n";
				$emailbody		.=get_lang('FirstName').': '.$values['firstname']."\n";
				$emailbody		.=get_lang('Email').': '.$values['email']."\n";
				$emailbody		.=get_lang('Status').': '.$values['status']."\n\n";
				$emailbody		.=get_lang('ManageUser').': '.api_get_path(WEB_CODE_PATH).'admin/user_edit.php?user_id='.$user_id;	
				
				$sender_name = get_setting('administratorName').' '.get_setting('administratorSurname');
			    $email_admin = get_setting('emailAdministrator');				
				@api_mail('', $emailto, $emailsubject, $emailbody, $sender_name,$email_admin);
				
			}
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
			$recipient_name = $values['firstname'].' '.$values['lastname'];	
			$email = $values['email'];
			$emailfromaddr = api_get_setting('emailAdministrator');
			$emailfromname = api_get_setting('siteName');
			$emailsubject = "[".get_setting('siteName')."] ".get_lang('YourReg')." ".get_setting('siteName');

			// The body can be as long as you wish, and any combination of text and variables
			$portal_url = $_configuration['root_web'];
			if ($_configuration['multiple_access_urls']==true) {
				$access_url_id = api_get_current_access_url_id();				
				if ($access_url_id != -1 ){
					$url = api_get_access_url($access_url_id);
					$portal_url = $url['url'];
				}
			} 
	
			$emailbody = get_lang('Dear')." ".stripslashes(Security::remove_XSS($firstname)." ".Security::remove_XSS($lastname)).",\n\n".get_lang('YouAreReg')." ".get_setting('siteName')." ".get_lang('Settings')." ".$values['username']."\n".get_lang('Pass')." : ".stripslashes($values['pass1'])."\n\n".get_lang('Address')." ".get_setting('siteName')." ".get_lang('Is')." : ".$portal_url."\n\n".get_lang('Problem')."\n\n".get_lang('Formula').",\n\n".get_setting('administratorName')." ".get_setting('administratorSurname')."\n".get_lang('Manager')." ".get_setting('siteName')."\nT. ".get_setting('administratorTelephone')."\n".get_lang('Email')." : ".get_setting('emailAdministrator');
			
			// Here we are forming one large header line
			// Every header must be followed by a \n except the last			
			$sender_name = get_setting('administratorName').' '.get_setting('administratorSurname');
		    $email_admin = get_setting('emailAdministrator');						
			@api_mail($recipient_name, $email, $emailsubject, $emailbody, $sender_name,$email_admin);	
		}
	}

	echo "<p>".get_lang('Dear')." ".stripslashes(Security::remove_XSS($recipient_name)).",<br /><br />".get_lang('PersonalSettings').".</p>\n";

	if (!empty ($values['email']))
	{
		echo "<p>".get_lang('MailHasBeenSent').".</p>";
	}

	$button_text = "";
	if ($is_allowedCreateCourse) {
		echo "<p>", get_lang('NowGoCreateYourCourse'), ".</p>\n";
		$actionUrl = "../create_course/add_course.php";
		$button_text = get_lang('CourseCreate');
	} else {
		echo "<p>", get_lang('NowGoChooseYourCourses'), ".</p>\n";
		$actionUrl = "courses.php?action=subscribe";
		$button_text = get_lang('Next');
	}
	// ?uidReset=true&uidReq=$_user['user_id']

	echo "<form action=\"", $actionUrl, "\"  method=\"post\">\n", "<button type=\"submit\" class=\"next\" name=\"next\" value=\"", get_lang('Next'), "\" validationmsg=\" ", get_lang('Next'), " \">".$button_text."</button>\n", "</form><br />\n";

} else {
	$form->display();
}
?>
<br/>
<?php
if (!isset($_POST['username'])) {
?>
<div class="actions">
<a href="<?php echo api_get_path(WEB_PATH); ?>" class="fake_button_back" ><?php echo get_lang('Back'); ?></a>
</div>
<?php
}
?>
<?php
/*
==============================================================================
		FOOTER
==============================================================================
*/

Display :: display_footer();
?>
