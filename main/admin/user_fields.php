<?php // $Id: $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos SPRL

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
				case 'show_field' :
					if (api_is_platform_admin() && !empty($_GET['field_id']) && UserManager :: update_extra_field($_GET['field_id'],array('field_visible'=>'1')))
					{
						Display :: display_normal_message(get_lang('FieldShown'));
					}
					else
					{
						Display :: display_error_message(get_lang('CannotShowField'));
					}
					break;
				case 'hide_field' :
					if (api_is_platform_admin() && !empty($_GET['field_id']) && UserManager :: update_extra_field($_GET['field_id'],array('field_visible'=>'0')))
					{
						Display :: display_normal_message(get_lang('FieldHidden'));
					}
					else
					{
						Display :: display_error_message(get_lang('CannotHideField'));
					}
					break;	
				case 'thaw_field' :
					if (api_is_platform_admin() && !empty($_GET['field_id']) && UserManager :: update_extra_field($_GET['field_id'],array('field_changeable'=>'1')))
					{
						Display :: display_normal_message(get_lang('FieldMadeChangeable'));
					}
					else
					{
						Display :: display_error_message(get_lang('CannotMakeFieldChangeable'));
					}
					break;	
				case 'freeze_field' :
					if (api_is_platform_admin() && !empty($_GET['field_id']) && UserManager :: update_extra_field($_GET['field_id'],array('field_changeable'=>'0')))
					{
						Display :: display_normal_message(get_lang('FieldMadeUnchangeable'));
					}
					else
					{
						Display :: display_error_message(get_lang('CannotMakeFieldUnchangeable'));
					}
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
	$column_show = array(1,1,1,1,1,1,1,1,0);
	$column_order = array(0,1,2,3,4,5,6,7,8);
	$extra_fields = get_extra_fields(0,50,5,'ASC');
 
	$table = new SortableTableFromArrayConfig($extra_fields, 5, 50, '', $column_show, $column_order, 'ASC');
	$table->set_additional_parameters($parameters);
	$table->set_header(0, '', false);
	$table->set_header(1, get_lang('FieldLabel'));
	$table->set_header(2, get_lang('FieldType'));
	$table->set_header(3, get_lang('FieldTitle'));
	$table->set_header(4, get_lang('FieldDefaultValue'));
	$table->set_header(5, get_lang('FieldOrder'), false);
	$table->set_header(6, get_lang('FieldVisibility'));
	$table->set_header(7, get_lang('FieldChangeability'));
	$table->set_column_filter(6, 'modify_visibility');
	$table->set_column_filter(7, 'modify_changeability');
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
/**
 * Modify the visible field to show links and icons
 * @param	int 	The current visibility
 * @param	array	Url parameters
 * @param	array	The results row
 * @return	string	The link
 */
function modify_visibility($visibility,$url_params,$row)
{
	return ($visibility?'<a href="'.api_get_self().'?action=hide_field&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'"><img src="'.api_get_path(WEB_IMG_PATH).'right.gif" alt="'.get_lang('Hide').'" /></a>':'<a href="'.api_get_self().'?action=show_field&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'"><img src="'.api_get_path(WEB_IMG_PATH).'wrong.gif" alt="'.get_lang('Show').'" /></a>');
}
/**
 * Modify the changeability field to show links and icons
 * @param	int 	The current changeability
 * @param	array	Url parameters
 * @param	array	The results row
 * @return	string	The link
 */
function modify_changeability($visibility,$url_params,$row)
{
	return ($visibility?'<a href="'.api_get_self().'?action=freeze_field&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'"><img src="'.api_get_path(WEB_IMG_PATH).'right.gif" alt="'.get_lang('MakeUnchangeable').'" /></a>':'<a href="'.api_get_self().'?action=thaw_field&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'"><img src="'.api_get_path(WEB_IMG_PATH).'wrong.gif" alt="'.get_lang('MakeFieldChangeable').'" /></a>');
}
?>