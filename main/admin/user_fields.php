<?php // $Id: $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos S.A.

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, rue du Corbeau, 108, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	@package dokeos.admin
==============================================================================
*/
// name of the language file that needs to be included
$language_file = array('admin','registration');
$cidReset = true;

// including necessary libraries
require ('../inc/global.inc.php');
$libpath = api_get_path(LIBRARY_PATH);
include_once ($libpath.'usermanager.lib.php');
require_once ($libpath.'formvalidator/FormValidator.class.php');

// section for the tabs
$this_section=SECTION_PLATFORM_ADMIN;

// user permissions
api_protect_admin_script();

// Database table definitions
$table_admin	= Database :: get_main_table(TABLE_MAIN_ADMIN);
$table_user 	= Database :: get_main_table(TABLE_MAIN_USER);
$table_uf	 	= Database :: get_main_table(TABLE_MAIN_USER_FIELD);
$table_uf_opt 	= Database :: get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
$table_uf_val 	= Database :: get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));

// Display form
if(1)
{
	$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
	$tool_name = get_lang('UserFields');
	Display :: display_header($tool_name, "");
	//api_display_tool_title($tool_name);
	if (isset ($_GET['action']))
	{
		$check = Security::check_token('get');
		if($check)
		{
			switch ($_GET['action'])
			{
				case 'show_message' :
					Display :: display_normal_message($_GET['message']);
					break;
				case 'delete_user' :
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
					$message=lock_unlock_user('lock',$_GET['user_id']);
					Display :: display_normal_message($message);
					break;
				case 'unlock';
					$message=lock_unlock_user('unlock',$_GET['user_id']);
					Display :: display_normal_message($message);
					break;
	
			}
			Security::clear_token();
		}
	}
	if (isset ($_POST['action']))
	{
		$check = Security::check_token('get');
		if($check)
		{
			switch ($_POST['action'])
			{
				default:
					break;
			}
			Security::clear_token();
		}
	}
	// Create an add-field box
	$form = new FormValidator('add_field','post','','',null,false);
	$renderer =& $form->defaultRenderer();
	$renderer->setElementTemplate('<span>{element}</span> ');
	//$form->addElement('text','label',get_lang('FieldLabel'));
	//$form->addElement('text','type',get_lang('FieldType'));
	//$form->addElement('text','title',get_lang('FieldTitle'));
	//$form->addElement('text','default',get_lang('FieldDefaultValue'));
	//$form->addElement('submit','submit',get_lang('Search'));
	$form->addElement('static','search_advanced_link',null,'<a href="user_fields_add.php?action=fill">'.get_lang('AddUserField').'</a>');
	$form->display();

	// Create a sortable table with user-data
	$parameters['sec_token'] = Security::get_token();
	$table = new SortableTable('extra_fields', 'get_number_of_extra_fields', 'get_extra_fields',5);
	$table->set_additional_parameters($parameters);
	$table->set_header(0, '', false);
	$table->set_header(1, get_lang('FieldLabel'));
	$table->set_header(2, get_lang('FieldType'));
	$table->set_header(3, get_lang('FieldTitle'));
	$table->set_header(4, get_lang('FieldDefaultValue'));
	$table->set_header(5, '', false);
	$table->set_header(6, get_lang('FieldVisibility'));
	$table->set_header(7, get_lang('FieldChangeability'));
	$table->display();
}



/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
//gateway functions to the UserManager methods (provided for SorteableTable callback mechanism)
function get_number_of_extra_fields()
{
	return UserManager::get_number_of_extra_fields();
}
function get_extra_fields($f,$n,$o,$d)
{
	return UserManager::get_extra_fields($f,$n,$o,$d);
}
?>