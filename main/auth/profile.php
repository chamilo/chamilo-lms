<?php
// $Id: profile.php 9983 2006-11-15 00:21:16Z pcool $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Roan Embrechts (Vrije Universiteit Brussel)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
==============================================================================
* This file displays the user's profile,
* optionally it allows users to modify their profile as well.
*
* See inc/conf/profile.conf.inc.php to modify settings
*
* @package dokeos.auth
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
$langFile = 'registration';
$cidReset = true;

require ('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
$this_section = SECTION_MYPROFILE;

api_block_anonymous_users();

$htmlHeadXtra[] = '<script type="text/javascript">
function confirmation(name)
{
	if (confirm("'.get_lang('AreYouSureToDelete').' " + name + " ?"))
		{return true;}
	else
		{return false;}
}
</script>';

if (!empty ($_GET['coursePath']))
{
	$course_url = api_get_path(WEB_COURSE_PATH).$_GET['coursePath'].'/index.php';
	$interbreadcrumb[] = array ('url' => $course_url, 'name' => $_GET['courseCode']);
}

/*
-----------------------------------------------------------
	Configuration file
-----------------------------------------------------------
*/
require_once (api_get_path(CONFIGURATION_PATH).'profile.conf.inc.php');

/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/
include_once (api_get_path(LIBRARY_PATH).'fileManage.lib.php');
include_once (api_get_path(LIBRARY_PATH).'fileUpload.lib.php');

if (is_profile_editable())
	$tool_name = get_lang('ModifProfile');
else
	$tool_name = get_lang('ViewProfile');

$table_user = Database :: get_main_table(MAIN_USER_TABLE);

/*
-----------------------------------------------------------
	Form
-----------------------------------------------------------
*/
/*
 * Get initial values for all fields.
 */
$sql = "SELECT * FROM $table_user WHERE user_id = '".$_user['user_id']."'";
$result = api_sql_query($sql, __FILE__, __LINE__);
if ($result)
{
	$user_data = mysql_fetch_array($result, MYSQL_ASSOC);

	if (is_null($user_data['language']))
		$user_data['language'] = api_get_setting('platformLanguage');
}

/*
 * Initialize the form.
 */
$form = new FormValidator('profile', 'post', "{$_SERVER['PHP_SELF']}?{$_SERVER['QUERY_STRING']}", null, array('style' => 'width: 60%; float: '.($text_dir=='rtl'?'right;':'left;')));

/* Make sure this is the first submit on the form, even though it is hidden!
 * Otherwise, if a user has productions and presses ENTER to submit, he will
 * attempt to delete the first production in the list. */
if (is_profile_editable())
	$form->addElement('submit', null, get_lang('Ok'), array('style' => 'visibility:hidden;'));

//	LAST NAME and FIRST NAME
$form->addElement('text', 'lastname',  get_lang('Lastname'),  array('size' => 40));
$form->addElement('text', 'firstname', get_lang('Firstname'), array('size' => 40));
if (api_get_setting('profile', 'name') !== 'true')
	$form->freeze(array('lastname', 'firstname'));
$form->applyFilter(array('lastname', 'firstname'), 'stripslashes');
$form->applyFilter(array('lastname', 'firstname'), 'trim');
$form->addRule('lastname' , get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');

//	OFFICIAL CODE
if (CONFVAL_ASK_FOR_OFFICIAL_CODE)
{
	$form->addElement('text', 'official_code', get_lang('OfficialCode'), array('size' => 40));
	if (api_get_setting('profile', 'officialcode') !== 'true')
		$form->freeze('official_code');
	$form->applyFilter('official_code', 'stripslashes');
	$form->applyFilter('official_code', 'trim');
	if (api_get_setting('registration', 'officialcode') == 'true')
		$form->addRule('official_code', get_lang('ThisFieldIsRequired'), 'required');
}

//	EMAIL
$form->addElement('text', 'email', get_lang('Email'), array('size' => 40));
if (api_get_setting('profile', 'email') !== 'true')
	$form->freeze('email');
$form->applyFilter('email', 'stripslashes');
$form->applyFilter('email', 'trim');
if (api_get_setting('registration', 'email') == 'true')
	$form->addRule('email', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('email', get_lang('EmailWrong'), 'email');


//	PHONE
$form->addElement('text', 'phone', get_lang('phone'), array('size' => 20));
if (api_get_setting('profile', 'phone') !== 'true')
	$form->freeze('phone');
$form->applyFilter('phone', 'stripslashes');
$form->applyFilter('phone', 'trim');
/*if (api_get_setting('registration', 'phone') == 'true')
	$form->addRule('phone', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('phone', get_lang('EmailWrong'), 'email');*/


//	PICTURE
if (is_profile_editable() && api_get_setting('profile', 'picture') == 'true')
{
	$form->addElement('file', 'picture', (get_user_image($_user['user_id']) != '' ? get_lang('UpdateImage') : get_lang('AddImage')));
	$form->add_progress_bar();
	if( strlen($user_data['picture_uri']) > 0)
	{
		$form->addElement('checkbox', 'remove_picture', null, get_lang('DelImage'));
	}
	$form->addRule('picture', get_lang('OnlyImagesAllowed'), 'mimetype', array('image/gif', 'image/jpeg', 'image/png'));
}

//	USERNAME
$form->addElement('text', 'username', get_lang('Username'), array('size' => 40));
if (api_get_setting('profile', 'login') !== 'true')
	$form->freeze('username');
$form->applyFilter('username', 'stripslashes');
$form->applyFilter('username', 'trim');
$form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('username', get_lang('UsernameWrong'), 'username');
$form->addRule('username', get_lang('UserTaken'), 'username_available', $user_data['username']);

//	LANGUAGE
$form->addElement('select_language', 'language', get_lang('Language'));
if (api_get_setting('profile', 'language') !== 'true')
	$form->freeze('language');

//	EXTENDED PROFILE
if (api_get_setting('extended_profile') == 'true')
{
	$form->addElement('static', null, '<em>'.get_lang('OptionalTextFields').'</em>');

	//	MY COMPETENCES
	$form->addElement('textarea', 'competences', get_lang('MyCompetences'), array('cols' => 30, 'rows' => 2));

	//	MY DIPLOMAS
	$form->addElement('textarea', 'diplomas', get_lang('MyDiplomas'), array('cols' => 30, 'rows' => 2));

	//	WHAT I AM ABLE TO TEACH
	$form->addElement('textarea', 'teach', get_lang('MyTeach'), array('cols' => 30, 'rows' => 2));

	//	MY PRODUCTIONS
	$form->addElement('file', 'production', get_lang('MyProductions'));
	if ($production_list = build_production_list($_user['user_id']))
		$form->addElement('static', 'productions', null, $production_list);

	//	MY PERSONAL OPEN AREA
	$form->add_html_editor('openarea', get_lang('MyPersonalOpenArea'), false);

	$form->applyFilter(array('competences', 'diplomas', 'teach', 'openarea'), 'stripslashes');
	$form->applyFilter(array('competences', 'diplomas', 'teach'), 'trim'); // openarea is untrimmed for maximum openness
}

//	PASSWORD
if (is_profile_editable() && api_get_setting('profile', 'password') == 'true')
{
	$form->addElement('static', null, null, '<em>'.get_lang('Enter2passToChange').'</em>');

	$form->addElement('password', 'password1', get_lang('Pass'),         array('size' => 40));
	$form->addElement('password', 'password2', get_lang('Confirmation'), array('size' => 40));

	//	user must enter identical password twice so we can prevent some user errors
	$form->addRule(array('password1', 'password2'), get_lang('PassTwo'), 'compare');
	if (CHECK_PASS_EASY_TO_FIND)
		$form->addRule('password1', get_lang('PassTooEasy').': '.api_generate_password(), 'callback', 'api_check_password');
}

//	SUBMIT
if (is_profile_editable())
{
	$form->addElement('submit', 'apply_change', get_lang('Ok'));
}
else
{
	$form->freeze();
}

/*
 * Set initial values for all fields.
 */
$form->setDefaults($user_data);

/*
==============================================================================
		FUNCTIONS
==============================================================================
*/

/*
-----------------------------------------------------------
	LOGIC FUNCTIONS
-----------------------------------------------------------
*/

/**
 * Can a user edit his/her profile?
 *
 * @return	boolean	Editability of the profile
 */
function is_profile_editable()
{
	return $GLOBALS['profileIsEditable'];
}

/*
-----------------------------------------------------------
	USER IMAGE FUNCTIONS
-----------------------------------------------------------
*/

/**
 * Get a user's display picture. If the user doesn't have a picture, this
 * function will return an empty string.
 *
 * @param	$user_id	User id
 * @return	The uri to the picture
 */
function get_user_image($user_id)
{
	$table_user = Database :: get_main_table(MAIN_USER_TABLE);
	$sql = "SELECT picture_uri FROM $table_user WHERE user_id = '$user_id'";
	$result = api_sql_query($sql, __FILE__, __LINE__);

	if ($result && $row = mysql_fetch_array($result, MYSQL_ASSOC))
		$image = trim($row['picture_uri']);
	else
		$image = '';

	return $image;
}

/**
 * Upload a submitted user image.
 *
 * @param	$user_id User id
 * @return	The filename of the new picture or FALSE if the upload has failed
 */
function upload_user_image($user_id)
{
	/* Originally added by Miguel (miguel@cesga.es) - 2003-11-04
	 * Code Refactoring by Hugues Peeters (hugues.peeters@claroline.net) - 2003-11-24
	 * Moved inside a function and refactored by Thomas Corthals - 2005-11-04
	 */

	$image_repository = api_get_path(SYS_CODE_PATH).'upload/users/';
	$existing_image = get_user_image($user_id);

	$file_extension = explode('.', $_FILES['picture']['name']);
	$file_extension = strtolower($file_extension[sizeof($file_extension) - 1]);

	if (!file_exists($image_repository))
		mkpath($image_repository);

	if ($existing_image != '')
	{
		if (KEEP_THE_NAME_WHEN_CHANGE_IMAGE)
		{
			$picture_filename = $existing_image;
			$old_picture_filename = 'saved_'.date('Y_m_d_H_i_s').'_'.uniqid('').'_'.$existing_image;
		}
		else
		{
			$old_picture_filename = $existing_image;
			$picture_filename = (PREFIX_IMAGE_FILENAME_WITH_UID ? 'u'.$user_id.'_' : '').uniqid('').'.'.$file_extension;
		}

		if (KEEP_THE_OLD_IMAGE_AFTER_CHANGE)
			rename($image_repository.$existing_image, $image_repository.$old_picture_filename);
		else
			unlink($image_repository.$existing_image);
	}
	else
	{
		$picture_filename = (PREFIX_IMAGE_FILENAME_WITH_UID ? $user_id.'_' : '').uniqid('').'.'.$file_extension;
	}

	if (move_uploaded_file($_FILES['picture']['tmp_name'], $image_repository.$picture_filename))
		return $picture_filename;

	return false; // this should be returned if anything went wrong with the upload
}

/**
 * Remove an existing user image.
 *
 * @param	$user_id	User id
 */
function remove_user_image($user_id)
{
	$image_repository = api_get_path(SYS_CODE_PATH).'upload/users/';
	$image = get_user_image($user_id);

	if ($image != '')
	{
		if (KEEP_THE_OLD_IMAGE_AFTER_CHANGE)
			rename($image_repository.$image, $image_repository.'deleted_'.date('Y_m_d_H_i_s').'_'.$image);
		else
			unlink($image_repository.$image);
	}
}

/*
-----------------------------------------------------------
	PRODUCTIONS FUNCTIONS
-----------------------------------------------------------
*/

/**
 * Returns an XHTML formatted list of productions for a user, or FALSE if he
 * doesn't have any.
 *
 * If there has been a request to remove a production, the function will return
 * without building the list unless forced to do so by the optional second
 * parameter. This increases performance by avoiding to read through the
 * productions on the filesystem before the removal request has been carried
 * out because they'll have to be re-read afterwards anyway.
 *
 * @param	$user_id	User id
 * @param	$force	Optional parameter to force building after a removal request
 * @return	A string containing the XHTML code to dipslay the production list, or FALSE
 */
function build_production_list($user_id, $force = false)
{
	if (!$force && $_POST['remove_production'])
		return true; // postpone reading from the filesystem

	$productions = get_user_productions($user_id);

	if (empty($productions))
		return false;

	$production_dir = api_get_path(WEB_CODE_PATH)."upload/users/$user_id/";
	$del_image = api_get_path(WEB_CODE_PATH).'img/delete.gif';
	$del_text = get_lang('Delete');

	$production_list = '<ul id="productions">';

	foreach ($productions as $file)
	{
		$production_list .= '<li><a href="'.$production_dir.urlencode($file).'" target="_blank">'.htmlentities($file).'</a>';
		$production_list .= '<input type="image" name="remove_production['.urlencode($file).']" src="'.$del_image.'" alt="'.$del_text.'" title="'.$del_text.' '.htmlentities($file).'" onclick="return confirmation(\''.htmlentities($file).'\');" /></li>';
	}

	$production_list .= '</ul>';

	return $production_list;
}

/**
 * Returns an array with the user's productions.
 *
 * @param	$user_id	User id
 * @return	An array containing the user's productions
 */
function get_user_productions($user_id)
{
	$production_repository = api_get_path(SYS_CODE_PATH)."upload/users/$user_id/";
	$productions = array();

	if (is_dir($production_repository))
	{
		$handle = opendir($production_repository);

		while ($file = readdir($handle))
		{
			if ($file == '.' || $file == '..' || $file == '.htaccess')
				continue; // skip current/parent directory and .htaccess

			$productions[] = $file;
		}
	}

	return $productions; // can be an empty array
}

/**
 * Upload a submitted user production.
 *
 * @param	$user_id	User id
 * @return	The filename of the new production or FALSE if the upload has failed
 */
function upload_user_production($user_id)
{
	$production_repository = api_get_path(SYS_CODE_PATH)."upload/users/$user_id/";

	if (!file_exists($production_repository))
		mkpath($production_repository);

	$filename = replace_dangerous_char($_FILES['production']['name']);
	$filename = php2phps($filename);

	if (move_uploaded_file($_FILES['production']['tmp_name'], $production_repository.$filename))
		return $filename;

	return false; // this should be returned if anything went wrong with the upload
}

/**
 * Remove a user production.
 *
 * @param	$user_id		User id
 * @param	$production	The production to remove
 */
function remove_user_production($user_id, $production)
{
	unlink(api_get_path(SYS_CODE_PATH)."upload/users/$user_id/$production");
}

/*
==============================================================================
		MAIN CODE
==============================================================================
*/
if ($_SESSION['profile_update'])
{
	$update_success = ($_SESSION['profile_update'] == 'success');

	unset($_SESSION['profile_update']);
}
elseif ($_POST['remove_production'])
{
	foreach (array_keys($_POST['remove_production']) as $production)
		remove_user_production($_user['user_id'], urldecode($production));

	if ($production_list = build_production_list($_user['user_id'], true))
		$form->insertElementBefore($form->createElement('static', null, null, $production_list), 'productions');

	$form->removeElement('productions');

	$file_deleted = true;
}
elseif ($form->validate())
{
	$user_data = $form->exportValues();

	// set password if a new one was provided
	if ($user_data['password1'])
		$password = $user_data['password1'];

	// upload picture if a new one is provided
	if ($_FILES['picture']['size'])
	{
		if ($new_picture = upload_user_image($_user['user_id']))
			$user_data['picture_uri'] = $new_picture;
	}
	// remove existing picture if asked
	elseif ($user_data['remove_picture'])
	{
		remove_user_image($_user['user_id']);

		$user_data['picture_uri'] = '';
	}

	// upload production if a new one is provided
	if ($_FILES['production']['size'])
		upload_user_production($_user['user_id']);

	// remove values that shouldn't go in the database
	unset($user_data['password1'], $user_data['password2'], $user_data['MAX_FILE_SIZE'],
		$user_data['remove_picture'], $user_data['apply_change']);

	// build SQL query
	$sql = "UPDATE $table_user SET";

	foreach($user_data as $key => $value)
	{
		$sql .= " $key = '".addslashes($value)."',";
	}

	if (isset($password))
	{
		if ($userPasswordCrypted)
		{
			$sql .= " password = MD5('".addslashes($password)."')";
		}
		else
		{
			$sql .= " password = '".addslashes($password)."'";
		}
	}
	else // remove trailing , from the query we have so far
	{
		$sql = rtrim($sql, ',');
	}

	$sql .= " WHERE user_id  = '$_user['user_id']'";

	api_sql_query($sql, __FILE__, __LINE__);

	// re-init the system to take new settings into account
	$uidReset = true;
	include (api_get_path(INCLUDE_PATH).'local.inc.php');
	$_SESSION['profile_update'] = 'success';
	header("Location: http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}?{$_SERVER['QUERY_STRING']}");
	exit;
}

/*
==============================================================================
		MAIN DISPLAY SECTION
==============================================================================
*/
Display :: display_header(get_lang('ModifyProfile'));

if ($file_deleted)
{
	Display :: display_normal_message(get_lang('FileDeleted'));
}
elseif ($update_success)
{
	Display :: display_normal_message(get_lang('ProfileReg'));
}
//	USER PICTURE
$image = get_user_image($_user['user_id']);
$image_file = ($image != '' ? api_get_path(WEB_CODE_PATH)."upload/users/$image" : api_get_path(WEB_CODE_PATH).'img/unknown.jpg');
$image_size = @getimagesize($image_file);

$img_attributes = 'src="'.$image_file.'?rand='.time().'" '
	.'alt="'.$user_data['lastname'].' '.$user_data['firstname'].'" '
	.'style="float:'.($text_dir == 'rtl' ? 'left' : 'right').'; padding:5px;" ';

if ($image_size[0] > 300) //limit display width to 300px
	$img_attributes .= 'width="300" ';

echo '<img '.$img_attributes.'/>';

$form->display();

echo '<div style="clear:both; border-top:thin solid; padding-top:2px;">
	<a href="'.api_get_path(WEB_CODE_PATH).'tracking/personnalLog.php">'.get_lang('MyStats').'</a>
	| <a href="'.api_get_path(WEB_CODE_PATH).'auth/courses.php">'.get_lang('MyCourses').'</a></div>';

Display :: display_footer();
?>