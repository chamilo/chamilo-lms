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
						Display :: display_confirmation_message(get_lang('FieldShown'));
					}
					else
					{
						Display :: display_error_message(get_lang('CannotShowField'));
					}
					break;
				case 'hide_field' :
					if (api_is_platform_admin() && !empty($_GET['field_id']) && UserManager :: update_extra_field($_GET['field_id'],array('field_visible'=>'0')))
					{
						Display :: display_confirmation_message(get_lang('FieldHidden'));
					}
					else
					{
						Display :: display_error_message(get_lang('CannotHideField'));
					}
					break;	
				case 'thaw_field' :
					if (api_is_platform_admin() && !empty($_GET['field_id']) && UserManager :: update_extra_field($_GET['field_id'],array('field_changeable'=>'1')))
					{
						Display :: display_confirmation_message(get_lang('FieldMadeChangeable'));
					}
					else
					{
						Display :: display_error_message(get_lang('CannotMakeFieldChangeable'));
					}
					break;	
				case 'freeze_field' :
					if (api_is_platform_admin() && !empty($_GET['field_id']) && UserManager :: update_extra_field($_GET['field_id'],array('field_changeable'=>'0')))
					{
						Display :: display_confirmation_message(get_lang('FieldMadeUnchangeable'));
					}
					else
					{
						Display :: display_error_message(get_lang('CannotMakeFieldUnchangeable'));
					}
					break;
				case 'moveup' :
					if (api_is_platform_admin() && !empty($_GET['field_id']))
					{
						if (move_user_field('moveup', $_GET['field_id']))
						{
							Display :: display_confirmation_message(get_lang('FieldMovedUp'));
						}
						else 
						{
							Display :: display_error_message(get_lang('CannotMoveField'));
						}
					}
					break;
				case 'movedown' :
						if (move_user_field('movedown', $_GET['field_id']))
						{
							Display :: display_confirmation_message(get_lang('FieldMovedDown'));
						}
						else 
						{
							Display :: display_error_message(get_lang('CannotMoveField'));
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
	$column_order = array(1,2,3,4,5,6,7,8,9);
	$extra_fields = UserManager::get_extra_fields(0,50,5,'ASC');
	$number_of_extra_fields = count($extra_fields);

 
	$table = new SortableTableFromArrayConfig($extra_fields, 5, 50, '', $column_show, $column_order, 'ASC');
	$table->set_additional_parameters($parameters);
	$table->set_header(0, '', false);
	$table->set_header(1, get_lang('FieldLabel'));
	$table->set_header(2, get_lang('FieldType'));
	$table->set_header(3, get_lang('FieldTitle'),false);
	$table->set_header(4, get_lang('FieldDefaultValue'),false);
	$table->set_header(5, get_lang('FieldOrder'));
	$table->set_header(6, get_lang('FieldVisibility'));
	$table->set_header(7, get_lang('FieldChangeability'));
	$table->set_header(8, get_lang('Modify'));
	$table->set_column_filter(5, 'order_filter');
	$table->set_column_filter(6, 'modify_visibility');
	$table->set_column_filter(7, 'modify_changeability');
	$table->set_column_filter(8, 'edit_filter');
	$table->set_column_filter(2, 'type_filter');
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
 * This functions translates the id of the form type into a human readable description
 *
 * @param integer $type the id of the form type
 * @return string the huma readable description of the field type (text, date, select drop-down, ...)
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version July 2008
 * @since Dokeos 1.8.6
 */
function type_filter($type)
{
	$types[USER_FIELD_TYPE_TEXT]  				= get_lang('FieldTypeText');
	$types[USER_FIELD_TYPE_TEXTAREA] 			= get_lang('FieldTypeTextarea');
	$types[USER_FIELD_TYPE_RADIO] 				= get_lang('FieldTypeRadio');
	$types[USER_FIELD_TYPE_SELECT] 				= get_lang('FieldTypeSelect');
	$types[USER_FIELD_TYPE_SELECT_MULTIPLE] 	= get_lang('FieldTypeSelectMultiple');
	$types[USER_FIELD_TYPE_DATE] 				= get_lang('FieldTypeDate');
	$types[USER_FIELD_TYPE_DATETIME] 			= get_lang('FieldTypeDatetime');
	$types[USER_FIELD_TYPE_DOUBLE_SELECT] 		= get_lang('FieldTypeDoubleSelect');
	$types[USER_FIELD_TYPE_DIVIDER] 			= get_lang('FieldTypeDivider');	
	
	return $types[$type];
}

/**
 * Modify the display order field into up and down arrows
 *
 * @param unknown_type $field_order
 * @param	array	Url parameters
 * @param	array	The results row
 * @return	string	The link
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version July 2008
 * @since Dokeos 1.8.6
 */
function order_filter($field_order,$url_params,$row)
{
	global $number_of_extra_fields;
	
	// the up icon only has to appear when the row can be moved up (all but the first row)
	if ($row[5]<>1)
	{
		$return .= '<a href="'.api_get_self().'?action=moveup&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('up.gif', get_lang('Up')).'</a>';
	}
	else 
	{
		$return .= Display::return_icon('blank.gif','',array('width'=>'21px'));
	}
	
	// the down icon only has to appear when the row can be moved down (all but the last row)
	if ($row[5]<>$number_of_extra_fields)
	{
		$return .= '<a href="'.api_get_self().'?action=movedown&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('down.gif', get_lang('Down')).'</a>';
	}
	
	return $return; 
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
	return ($visibility?'<a href="'.api_get_self().'?action=hide_field&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'"><img src="'.api_get_path(WEB_IMG_PATH).'visible.gif" alt="'.get_lang('Hide').'" /></a>':'<a href="'.api_get_self().'?action=show_field&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'"><img src="'.api_get_path(WEB_IMG_PATH).'invisible.gif" alt="'.get_lang('Show').'" /></a>');
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
	return ($visibility?'<a href="'.api_get_self().'?action=freeze_field&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'"><img src="'.api_get_path(WEB_IMG_PATH).'right.gif" alt="'.get_lang('MakeUnchangeable').'" /></a>':'<a href="'.api_get_self().'?action=thaw_field&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'"><img src="'.api_get_path(WEB_IMG_PATH).'wrong.gif" alt="'.get_lang('MakeChangeable').'" /></a>');
}

function edit_filter()
{
	
}
/**
 * Move a user defined field up or down
 *
 * @param string $direction the direction we have to move the field to (up or down)
 * @param unknown_type $field_id
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version July 2008
 * @since Dokeos 1.8.6
 */
function move_user_field($direction,$field_id)
{
	// Databse table definitions
	$table_user_field = Database::get_main_table(TABLE_MAIN_USER_FIELD);
	
	// check the parameters
	if (!in_array($direction,array('moveup','movedown')) OR !is_numeric($field_id))
	{
		return false; 
	}
	
	// determine the SQL sort direction
	if ($direction == 'moveup')
	{
		$sortdirection = 'DESC';
	}
	else 
	{
		$sortdirection = 'ASC';
	}
	
	$found = false; 
	
	$sql = "SELECT id, field_order FROM $table_user_field ORDER BY field_order $sortdirection";
	$result = api_sql_query($sql,__FILE__,__LINE__);
	while($row = Database::fetch_array($result))
	{
		if ($found)
		{
			$next_id = $row['id'];
			$next_order = $row['field_order'];	
			break;
		}
		
		if ($field_id == $row['id'])
		{
			$this_id = $row['id'];
			$this_order = $row['field_order'];
			$found = true;
		}
	}
	
	$sql1 = "UPDATE ".$table_user_field." SET field_order = '".Database::escape_string($next_order)."' WHERE id =  '".Database::escape_string($this_id)."'";
	$sql2 = "UPDATE ".$table_user_field." SET field_order = '".Database::escape_string($this_order)."' WHERE id =  '".Database::escape_string($next_id)."'";
	api_sql_query($sql1,__FILE__,__LINE__);
	api_sql_query($sql2,__FILE__,__LINE__);
	
	return true;
}
?>