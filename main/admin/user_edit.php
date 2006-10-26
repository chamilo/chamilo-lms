<?php // $Id: user_edit.php 9617 2006-10-20 12:39:54Z bmol $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert
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
*	@package dokeos.admin
==============================================================================
*/

$langFile=array('admin','registration');
$cidReset=true;
include('../inc/global.inc.php');
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();


$htmlHeadXtra[] = '
<script language="JavaScript" type="text/JavaScript">
<!--
function enable_expiration_date() { //v2.0
	document.user_add.radio_expiration_date[0].checked=false;
	document.user_add.radio_expiration_date[1].checked=true;
}
//-->
</script>';


include(api_get_path(LIBRARY_PATH).'fileManage.lib.php');
include(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
include(api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
$user_id=isset($_GET['user_id']) ? intval($_GET['user_id']) : intval($_POST['user_id']);
$noPHP_SELF=true;
$tool_name=get_lang('ModifyUserInfo');

//$interbreadcrumb[]=array("url" => "index.php","name" => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array("url" => "user_list.php","name" => get_lang('UserList'));

$table_user = Database::get_main_table(MAIN_USER_TABLE);
$table_admin = Database::get_main_table(MAIN_ADMIN_TABLE);
$sql = "SELECT u.*, a.user_id AS is_admin FROM $table_user u LEFT JOIN $table_admin a ON a.user_id = u.user_id WHERE u.user_id = '".$user_id."'";
$res = api_sql_query($sql,__FILE__,__LINE__);
if(mysql_num_rows($res) != 1)
{
	header('Location: user_list.php');
	exit;
}
$user_data = mysql_fetch_array($res,MYSQL_ASSOC);
$user_data['platform_admin'] = is_null($user_data['is_admin']) ? 0 : 1;
$user_data['send_mail'] = 0;
$user_data['old_password'] = $user_data['password'];
unset($user_data['password']);

// Create the form
$form = new FormValidator('user_add','post','','',array('style' => 'width: 60%; float: '.($text_dir=='rtl'?'right;':'left;')));
$form->addElement('hidden','user_id',$user_id);

// Lastname
$form->addElement('text','lastname',get_lang('LastName'));
$form->applyFilter('lastname','html_filter');
$form->applyFilter('lastname','trim');
$form->addRule('lastname', get_lang('ThisFieldIsRequired'), 'required');

// Firstname
$form->addElement('text','firstname',get_lang('FirstName'));
$form->applyFilter('firstname','html_filter');
$form->applyFilter('firstname','trim');
$form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');

// Official code
$form->addElement('text', 'official_code', get_lang('OfficialCode'),array('size' => '40'));
$form->applyFilter('official_code','html_filter');
$form->applyFilter('official_code','trim');

// Email
$form->addElement('text', 'email', get_lang('Email'),array('size' => '40'));
$form->addRule('email', get_lang('EmailWrong'), 'email');
$form->addRule('email', get_lang('EmailWrong'), 'required');

// Phone
$form->addElement('text','phone',get_lang('PhoneNumber'));

// Picture
$form->addElement('file', 'picture', get_lang('AddPicture'));
$allowed_picture_types = array ('jpg', 'jpeg', 'png', 'gif');
$form->addRule('picture', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);
if (strlen($user_data['picture_uri']) > 0 )
{
	$form->addElement('checkbox', 'delete_picture', '', get_lang('DelImage'));
}

// Username
$form->addElement('text', 'username', get_lang('LoginName'),array('maxlength'=>20));
$form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('username', get_lang('OnlyLettersAndNumbersAllowed'), 'username');
$form->addRule('username', '', 'maxlength',20);
$form->addRule('username', get_lang('UserTaken'), 'username_available', $user_data['username']);

// Password
$form->addElement('radio','reset_password',get_lang('Password'),get_lang('DontResetPassword'),0);
if(count($extAuthSource) > 0)
{
	$group[] =& HTML_QuickForm::createElement('radio','reset_password',null,get_lang('ExternalAuthentication').' ',3);
	$auth_sources = array();
	foreach($extAuthSource as $key => $info)
	{
		$auth_sources[$key] = $key;
	}
	$group[] =& HTML_QuickForm::createElement('select','auth_source',null,$auth_sources);
	$group[] =& HTML_QuickForm::createElement('static','','','<br />');
	$form->addGroup($group, 'password', null, '',false);
}
$form->addElement('radio','reset_password',null,get_lang('AutoGeneratePassword'),1);
$group = array();
$group[] =& HTML_QuickForm::createElement('radio', 'reset_password',null,null,2);
$group[] =& HTML_QuickForm::createElement('password', 'password',null,null);
$form->addGroup($group, 'password', null, '',false);

// Status
$status = array();
$status[COURSEMANAGER]  = get_lang('CourseAdmin');
$status[STUDENT] = get_lang('Student');
$form->addElement('select','status',get_lang('Status'),$status);

// Platform admin
// Only when changing another user!
if($user_id != $_SESSION['_uid'])
{
	$group = array();
	$group[] =& HTML_QuickForm::createElement('radio', 'platform_admin',null,get_lang('Yes'),1);
	$group[] =& HTML_QuickForm::createElement('radio', 'platform_admin',null,get_lang('No'),0);
	$form->addGroup($group, 'admin', get_lang('PlatformAdmin'), '&nbsp;',false);
}

// Send email
$group = array();
$group[] =& HTML_QuickForm::createElement('radio', 'send_mail',null,get_lang('Yes'),1);
$group[] =& HTML_QuickForm::createElement('radio', 'send_mail',null,get_lang('No'),0);
$form->addGroup($group, 'mail', get_lang('SendMailToNewUser'), '&nbsp;',false);

// Registration Date
$form->addElement('static','registration_date', get_lang('RegistrationDate'), $user_data['registration_date']);

if(! $user_data['platform_admin'] )
{
	// Expiration Date
	$form->addElement('radio', 'radio_expiration_date', get_lang('ExpirationDate'), get_lang('NeverExpires'), 0);
	$group = array ();
	$group[] = & $form->createElement('radio', 'radio_expiration_date', null, get_lang('On'), 1);
	$group[] = & $form->createElement('datepicker', 'expiration_date',null, array ('form_name' => $form->getAttribute('name'), 'onChange'=>'enable_expiration_date()'));
	$form->addGroup($group, 'max_member_group', null, '', false);

	// Active account or inactive account
	$form->addElement('radio','active',get_lang('ActiveAccount'),get_lang('Active'),1);
	$form->addElement('radio','active','',get_lang('Inactive'),0);
}
// Submit button
$form->addElement('submit', 'submit', 'OK');

// Set default values
$expiration_date=$user_data['expiration_date'];
if ($expiration_date=='0000-00-00 00:00:00')
{
	$user_data['radio_expiration_date']=0;
	$user_data['expiration_date']=array();
	$user_data['expiration_date']['d']=date('d');
	$user_data['expiration_date']['F']=date('m');
	$user_data['expiration_date']['Y']=date('Y');
}
else
{
	$user_data['radio_expiration_date']=1;
	$user_data['expiration_date']=array();
	$user_data['expiration_date']['d']=substr($expiration_date,8,2);
	$user_data['expiration_date']['F']=substr($expiration_date,5,2);
	$user_data['expiration_date']['Y']=substr($expiration_date,0,4);
}
$form->setDefaults($user_data);


// Validate form
if( $form->validate())
{
	$user = $form->exportValues();
	$picture_element = & $form->getElement('picture');
	$picture = $picture_element->getValue();
	$picture_uri = '';
	if (strlen($picture['name']) > 0)
	{
		$picture_uri = uniqid('').'_'.replace_dangerous_char($picture['name']);
		$picture_location = api_get_path(SYS_CODE_PATH).'upload/users/'.$picture_uri;
		move_uploaded_file($picture['tmp_name'], $picture_location);
	}
	elseif(isset($user['delete_picture']))
	{
		@unlink('../upload/users/'.$user_data['picture_uri']);
	}
	$lastname = $user['lastname'];
	$firstname = $user['firstname'];
	$official_code = $user['official_code'];
	$email = $user['email'];
	$phone = $user['phone'];
	$username = $user['username'];
	$status = intval($user['status']);
	$picture = $_FILES['picture'];
	$platform_admin = intval($user['platform_admin']);
	$send_mail = intval($user['send_mail']);
	$reset_password = intval($user['reset_password']);
	if ($user['radio_expiration_date']=='1' && ! $user_data['platform_admin'] )
	{
		$expiration_date=$user['expiration_date'];
	}
	else
	{
		$expiration_date='0000-00-00 00:00:00';
	}
	$active = $user_data['platform_admin'] ? 1 : intval($user['active']);

	if( $reset_password == 0)
	{
		$password = null;
		$auth_source = $user_data['auth_source'];
	}
	elseif($reset_password == 1)
	{
		$password = api_generate_password();
		$auth_source = PLATFORM_AUTH_SOURCE;
	}
	else
	{
		$password = $user['password'];
		$auth_source = PLATFORM_AUTH_SOURCE;
	}
	UserManager::update_user($user_id,$firstname,$lastname,$username,$password,$auth_source,$email,$status,$official_code,$phone,$picture_uri,$expiration_date, $active);
	if($user_id != $_SESSION['_uid'])
	{
		if($platform_admin == 1)
		{
			$sql = "INSERT IGNORE INTO $table_admin SET user_id = '".$user_id."'";
			api_sql_query($sql,__FILE__,__LINE__);
		}
		else
		{
			$sql = "DELETE FROM $table_admin WHERE user_id = '".$user_id."'";
			api_sql_query($sql,__FILE__,__LINE__);
		}
	}
	if (!empty ($email) && $send_mail)
	{
		$emailto = '"'.$firstname.' '.$lastname.'" <'.$email.'>';
		$emailsubject = '['.get_setting('siteName').'] '.get_lang('YourReg').' '.get_setting('siteName');
		$emailheaders = 'From: '.get_setting('administratorName').' '.get_setting('administratorSurname').' <'.get_setting('emailAdministrator').">\n";
		$emailheaders .= 'Reply-To: '.get_setting('emailAdministrator');
		$emailbody = get_lang('langDear')." ".stripslashes("$form_firstname $lastname").",\n\n".get_lang('langYouAreReg')." ". get_setting('siteName') ." ".get_lang('langSettings')." ". $username;
		if($reset_password != 0 || !$userPasswordCrypted )
		{
			$emaibody .= "\n".get_lang('langPass')." : ".stripslashes($password);
		}
		$emailbody .= "\n\n" .get_lang('langAddress') ." ". get_setting('siteName') ." ". get_lang('langIs') ." : ". $rootWeb ."\n\n". get_lang('langProblem'). "\n\n". get_lang('langFormula').",\n\n".get_setting('administratorName')." ".get_setting('administratorSurname')."\n". get_lang('langManager'). " ".get_setting('siteName')."\nT. ".get_setting('administratorTelephone')."\n" .get_lang('langEmail') ." : ".get_setting('emailAdministrator');
		@api_send_mail($emailto, $emailsubject, $emailbody, $emailheaders);
	}
	header('Location: user_list.php?action=show_message&message='.urlencode(get_lang('UserUpdated')));
	exit();
}

Display::display_header($tool_name);
//api_display_tool_title($tool_name);
// Show the users picture
if (strlen($user_data['picture_uri']) > 0)
{
	$picture_url = api_get_path(WEB_CODE_PATH).'upload/users/'.$user_data['picture_uri'];
}
else
{
	$picture_url = api_get_path(WEB_CODE_PATH)."img/unknown.jpg";
}
$image_size = @getimagesize($picture_url);
$img_attributes = 'src="'.$picture_url.'?rand='.time().'" '
	.'alt="'.$user_data['lastname'].' '.$user_data['firstname'].'" '
	.'style="float:'.($text_dir == 'rtl' ? 'left' : 'right').'; padding:5px;" ';
if ($image_size[0] > 200) //limit display width to 300px
	$img_attributes .= 'width="200" ';
echo '<img '.$img_attributes.'/>';
// Display form
$form->display();
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>