<?php //$id: $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos SPRL
	Copyright (c) 2007 Mustapha Alouani (supervised by Michel Moreau-Belliard)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
	@author Mustapha Alouani
*	@package dokeos.admin
==============================================================================
*/

// name of the language file that needs to be included
$language_file[] = 'registration';
$language_file[] = 'admin';
$cidReset = true;
require('../inc/global.inc.php');
require_once(api_get_path(LIBRARY_PATH).'sortabletable.class.php');
require_once(api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once(api_get_path(LIBRARY_PATH).'security.lib.php');
require('../auth/ldap/authldap.php');
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

/**
==============================================================================
		INIT SECTION
==============================================================================
*/
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
$action = $_GET["action"];
$login_as_user_id = $_GET["user_id"];

// Login as ...
if ($_GET['action'] == "login_as" && isset ($login_as_user_id))
{
	login_user($login_as_user_id);
}

//if we already have a session id and a user...
/*
if (($_GET['action']=="add_user") && ($_GET['id_session'] == strval(intval($_GET['id_session']))) && $_GET['id_session']>0 ){
	header('Location: ldap_import_students_to_session.php?id_session='.$_GET['id_session'].'&ldap_user='.$_GET['id']);
}
*/

$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
$tool_name = get_lang('SearchLDAPUsers');
//Display :: display_header($tool_name); //cannot display now as we need to redirect
//api_display_tool_title($tool_name);

if (isset ($_GET['action']))
{
	$check = Security::check_token('get');
	if($check)
	{
		switch ($_GET['action'])
		{
			case 'show_message' :
				Display :: display_header($tool_name);
				Display :: display_normal_message($_GET['message']);
				break;
			case 'delete_user' :
				Display :: display_header($tool_name);
				if ($user_id != $_user['user_id'] && UserManager :: delete_user($_GET['user_id']))
				{
					Display :: display_normal_message(get_lang('UserDeleted'));
				}
				else
				{
					Display :: display_error_message(get_lang('CannotDeleteUser'));
				}
				break;
			case 'lock' :
				Display :: display_header($tool_name);
				$message=lock_unlock_user('lock',$_GET['user_id']);
				Display :: display_normal_message($message);
				break;
			case 'unlock';
				Display :: display_header($tool_name);
				$message=lock_unlock_user('unlock',$_GET['user_id']);
				Display :: display_normal_message($message);
				break;
			case 'add_user';
				$id=$_GET['id'];
				$UserList=array();
				$userid_match_login = array();
				foreach ($id as $user_id) {
					$tmp = ldap_add_user($user_id);
					$UserList[]= $tmp;
					$userid_match_login[$tmp] = $user_id;
				}
				if (isset($_GET['id_session']) && ($_GET['id_session'] == strval(intval($_GET['id_session']))) && ($_GET['id_session']>0)) {
					ldap_add_user_to_session($UserList, $_GET['id_session']);
					header('Location: resume_session.php?id_session='.$_GET['id_session']);
				} else {
					Display :: display_header($tool_name);
					if(count($userid_match_login)>0)
					{
						$message=get_lang('LDAPUsersAddedOrUpdated').':<br />';
						foreach($userid_match_login as $user_id => $login)
						{
							$message .= '- '.$login.'<br />';
						}
					}
					else
					{
						$message=get_lang('NoUserAdded');
					}
					Display :: display_normal_message($message,false);
				}
				break;
			default :
				Display :: display_header($tool_name);
		}
		Security::clear_token();
	}
	else
	{
		Display::display_header($tool_name);
	}
}
else
{
	Display::display_header($tool_name);
}
if (isset ($_POST['action']))
{
	$check = Security::check_token('get');
	if($check)
	{
		switch ($_POST['action'])
		{
			case 'delete' :
				$number_of_selected_users = count($_POST['id']);
				$number_of_deleted_users = 0;
				foreach ($_POST['id'] as $index => $user_id)
				{
					if($user_id != $_user['user_id'])
					{
						if(UserManager :: delete_user($user_id))
						{
							$number_of_deleted_users++;
						}
					}
				}
				if($number_of_selected_users == $number_of_deleted_users)
				{
					Display :: display_normal_message(get_lang('SelectedUsersDeleted'));
				}
				else
				{
					Display :: display_error_message(get_lang('SomeUsersNotDeleted'));
				}
				break;
			case 'add_user' :
				$number_of_selected_users = count($_POST['id']);
				$number_of_added_users = 0;
				$UserList=array();
				foreach ($_POST['id'] as $index => $user_id)
				{
					if($user_id != $_user['user_id'])
					{
						$UserList[] = ldap_add_user($user_id);
					}
				}
				if (isset($_GET['id_session']) && (trim($_GET['id_session'])!=""))
					addUserToSession($UserList, $_GET['id_session']);
				if(count($UserList)>0)
				{
					Display :: display_normal_message(count($UserList)." ".get_lang('LDAPUsersAdded'));
				}
				else
				{
					Display :: display_normal_message(get_lang('NoUserAdded'));
				}
				break;

		}
		Security::clear_token();
	}
}

$form = new FormValidator('advanced_search','get');
$form->add_textfield('keyword_username',get_lang('LoginName'),false);
if (api_is_western_name_order())
{
	$form->add_textfield('keyword_firstname', get_lang('FirstName'), false);
	$form->add_textfield('keyword_lastname', get_lang('LastName'), false);
}
else
{
	$form->add_textfield('keyword_lastname',get_lang('LastName'),false);
	$form->add_textfield('keyword_firstname',get_lang('FirstName'),false);
}
if (isset($_GET['id_session']))
	$form->addElement('hidden','id_session',$_GET['id_session']);

$type = array();
$type["all"] = get_lang('All');
$type["employee"]  = get_lang('Teacher');
$type["student"] = get_lang('Student');

$form->addElement('select','keyword_type',get_lang('Status'),$type);
// Structure a rajouer ??
$form->addElement('submit','submit',get_lang('Ok'));
//$defaults['keyword_active'] = 1;
//$defaults['keyword_inactive'] = 1;
//$form->setDefaults($defaults);
$form->display();



$parameters['keyword_username'] = $_GET['keyword_username'];
$parameters['keyword_firstname'] = $_GET['keyword_firstname'];
$parameters['keyword_lastname'] = $_GET['keyword_lastname'];
$parameters['keyword_email'] = $_GET['keyword_email'];
if (isset($_GET['id_session']))
	$parameters['id_session'] = $_GET['id_session'];
// Create a sortable table with user-data

$parameters['sec_token'] = Security::get_token();
$table = new SortableTable('users', 'ldap_get_number_of_users', 'ldap_get_user_data', (api_is_western_name_order() xor api_sort_by_first_name()) ? 3 : 2);
$table->set_additional_parameters($parameters);
$table->set_header(0, '', false);
$table->set_header(1, get_lang('LoginName'));
if (api_is_western_name_order())
{
	$table->set_header(2, get_lang('FirstName'));
	$table->set_header(3, get_lang('LastName'));
}
else
{
	$table->set_header(2, get_lang('LastName'));
	$table->set_header(3, get_lang('FirstName'));
}
$table->set_header(4, get_lang('Email'));
$table->set_header(5, get_lang('Actions'));
//$table->set_column_filter(5, 'email_filter');
//$table->set_column_filter(5, 'active_filter');
$table->set_column_filter(5, 'modify_filter');
$table->set_form_actions(array ('add_user' => get_lang('AddLDAPUsers')));
$table->display();

/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();
?>