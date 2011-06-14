<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
*/
// name of the language file that needs to be included
$language_file = array('admin', 'registration');
$cidReset = true;

// including necessary libraries
require '../inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'sortabletable.class.php';
require_once $libpath.'usermanager.lib.php';
require_once $libpath.'formvalidator/FormValidator.class.php';

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
		if($check) {
			switch ($_GET['action']) {
				case 'show_message' :
					Display :: display_normal_message($_GET['message']);
					break;
				case 'show_field' :
					if (api_is_platform_admin() && !empty($_GET['field_id']) && UserManager :: update_extra_field($_GET['field_id'],array('field_visible'=>'1'))) {
						Display :: display_confirmation_message(get_lang('FieldShown'));
					} else {
						Display :: display_error_message(get_lang('CannotShowField'));
					}
					break;
				case 'hide_field' :
					if (api_is_platform_admin() && !empty($_GET['field_id']) && UserManager :: update_extra_field($_GET['field_id'],array('field_visible'=>'0'))) {
						Display :: display_confirmation_message(get_lang('FieldHidden'));
					} else {
						Display :: display_error_message(get_lang('CannotHideField'));
					}
					break;
				case 'thaw_field' :
					if (api_is_platform_admin() && !empty($_GET['field_id']) && UserManager :: update_extra_field($_GET['field_id'],array('field_changeable'=>'1'))) {
						Display :: display_confirmation_message(get_lang('FieldMadeChangeable'));
					} else {
						Display :: display_error_message(get_lang('CannotMakeFieldChangeable'));
					}
					break;
				case 'freeze_field' :
					if (api_is_platform_admin() && !empty($_GET['field_id']) && UserManager :: update_extra_field($_GET['field_id'],array('field_changeable'=>'0'))) {
						Display :: display_confirmation_message(get_lang('FieldMadeUnchangeable'));
					} else {
						Display :: display_error_message(get_lang('CannotMakeFieldUnchangeable'));
					}
					break;
				case 'moveup' :
					if (api_is_platform_admin() && !empty($_GET['field_id'])) {
						if (move_user_field('moveup', $_GET['field_id'])) {
							Display :: display_confirmation_message(get_lang('FieldMovedUp'));
						} else {
							Display :: display_error_message(get_lang('CannotMoveField'));
						}
					}
					break;
				case 'movedown' :
					if (api_is_platform_admin() && !empty($_GET['field_id'])) {
						if (move_user_field('movedown', $_GET['field_id'])) {
							Display :: display_confirmation_message(get_lang('FieldMovedDown'));
						} else {
							Display :: display_error_message(get_lang('CannotMoveField'));
						}
					}
					break;
				case 'filter_on' :
					if (api_is_platform_admin() && !empty($_GET['field_id']) && UserManager :: update_extra_field($_GET['field_id'],array('field_filter'=>'1'))) {
						Display :: display_confirmation_message(get_lang('FieldFilterSetOn'));
					} else {
						Display :: display_error_message(get_lang('CannotShowField'));
					}
					break;
				case 'filter_off' :
					if (api_is_platform_admin() && !empty($_GET['field_id']) && UserManager :: update_extra_field($_GET['field_id'],array('field_filter'=>'0'))) {
						Display :: display_confirmation_message(get_lang('FieldFilterSetOff'));
					} else {
						Display :: display_error_message(get_lang('CannotShowField'));
					}
					break;

				case 'delete':
					if (api_is_platform_admin() && !empty($_GET['field_id'])) {
						if (delete_user_fields($_GET['field_id'])) {
							Display :: display_confirmation_message(get_lang('FieldDeleted'));
						} else {
							Display :: display_error_message(get_lang('CannotDeleteField'));
						}
					}
					break;
			}
			Security::clear_token();
		}
	}
	if (isset ($_POST['action'])) {
		$check = Security::check_token('get');
		if($check) {
			switch ($_POST['action']) {
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
	$form->addElement('static','search_advanced_link',null,'<a href="user_fields_add.php?action=fill">'.Display::return_icon('add_user_fields.png', get_lang('AddUserField'),'','32').'</a>');
	echo '<div class="actions">';
	$form->display();
	echo '</div>';

	// Create a sortable table with user-data
	$parameters['sec_token'] = Security::get_token();
	//$column_show  = array(1,1,1,1,1,1,1,1,1,0,0);
	//$column_order = array(1,2,3,4,5,6,7,8,9,10,11);
	$extra_fields = UserManager::get_extra_fields();
	$number_of_extra_fields = count($extra_fields);

    $table = new SortableTable('user_field', array('UserManager','get_number_of_extra_fields'), array('UserManager','get_extra_fields'),5);
	$table->set_additional_parameters($parameters);
	$table->set_header(0, '', false);
	$table->set_header(1, get_lang('FieldLabel'), false);
	$table->set_header(2, get_lang('FieldType'), false);
	$table->set_header(3, get_lang('FieldTitle'),false);
	$table->set_header(4, get_lang('FieldDefaultValue'),false);
	$table->set_header(5, get_lang('FieldOrder'), false);
	$table->set_header(6, get_lang('FieldVisibility'), false);
	$table->set_header(7, get_lang('FieldChangeability'), false);
	$table->set_header(8, get_lang('FieldFilter'), false);
	$table->set_header(9, get_lang('Modify'), false);
	$table->set_column_filter(5, 'order_filter');
	$table->set_column_filter(6, 'modify_visibility');
	$table->set_column_filter(7, 'modify_changeability');
	$table->set_column_filter(8, 'modify_field_filter');
	$table->set_column_filter(9, 'edit_filter');
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
	$types[USER_FIELD_TYPE_TAG] 				= get_lang('FieldTypeTag');
	$types[USER_FIELD_TYPE_TIMEZONE]			= get_lang('FieldTypeTimezone');
	$types[USER_FIELD_TYPE_SOCIAL_PROFILE]		= get_lang('FieldTypeSocialProfile');
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
	return ($visibility?'<a href="'.api_get_self().'?action=hide_field&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('visible.gif', get_lang('Hide')).'</a>':'<a href="'.api_get_self().'?action=show_field&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('invisible.gif', get_lang('Show')).'</a>');
}
/**
 * Modify the changeability field to show links and icons
 * @param	int 	The current changeability
 * @param	array	Url parameters
 * @param	array	The results row
 * @return	string	The link
 */
function modify_changeability($changeability,$url_params,$row)
{
	return ($changeability?'<a href="'.api_get_self().'?action=freeze_field&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('right.gif', get_lang('MakeUnchangeable')).'</a>':'<a href="'.api_get_self().'?action=thaw_field&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('wrong.gif', get_lang('MakeChangeable')).'</a>');
}

function modify_field_filter ($changeability,$url_params,$row)
{
	return ($changeability?'<a href="'.api_get_self().'?action=filter_off&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('right.gif', get_lang('FilterOff')).'</a>':'' .
						   '<a href="'.api_get_self().'?action=filter_on&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('wrong.gif', get_lang('FilterOn')).'</a>');
}

function edit_filter($id,$url_params,$row)
{
	global $charset;
	$return = '<a href="user_fields_add.php?action=edit&field_id='.$row[0].'&field_type='.$row[2].'&sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon('edit.gif',get_lang('Edit')).'</a>';
	$return .= ' <a href="'.api_get_self().'?action=delete&field_id='.$row[0].'&sec_token='.$_SESSION['sec_token'].'" onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset))."'".')) return false;">'.Display::return_icon('delete.gif',get_lang('Delete')).'</a>';
	return $return;
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

    // first reorder user_fields
    reorder_user_fields();

	$found = false;
	$sql = "SELECT id, field_order FROM $table_user_field ORDER BY field_order $sortdirection";
	$result = Database::query($sql);
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

	$sql1 = "UPDATE ".$table_user_field." SET field_order = '".intval($next_order)."' WHERE id =  '".intval($this_id)."'";
	$sql2 = "UPDATE ".$table_user_field." SET field_order = '".intval($this_order)."' WHERE id =  '".intval($next_id)."'";
	Database::query($sql1);
	Database::query($sql2);

	return true;
}

/**
* Re-order user fields
*/
function reorder_user_fields() {
      // Database table definition
      $t_user_field = Database::get_main_table(TABLE_MAIN_USER_FIELD);
      $sql = "SELECT * FROM $t_user_field ORDER by field_order ASC";
      $res = Database::query($sql);
      $i = 1;
      while ($row = Database::fetch_array($res)) {
              $sql_reorder = "UPDATE $t_user_field SET field_order = $i WHERE id = '".$row['id']."'";
              Database::query($sql_reorder);
              $i++;
      }
}

/**
 * Delete a user field (and also the options and values entered by the users)
 *
 * @param integer $field_id the id of the field that has to be deleted
 * @return boolean true if the field has been deleted, false if the field could not be deleted (for whatever reason)
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version July 2008
 * @since Dokeos 1.8.6
 */
function delete_user_fields($field_id)
{
	// Database table definitions
	$table_user_field 			= Database::get_main_table(TABLE_MAIN_USER_FIELD);
	$table_user_field_options	= Database::get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
	$table_user_field_values 	= Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

	// delete the fields
	$sql = "DELETE FROM $table_user_field WHERE id = '".Database::escape_string($field_id)."'";
	$result = Database::query($sql);
	if (Database::affected_rows() == 1)
	{
		// delete the field options
		$sql = "DELETE FROM $table_user_field_options WHERE field_id = '".Database::escape_string($field_id)."'";
		$result = Database::query($sql);

		// delete the field values
		$sql = "DELETE FROM $table_user_field_values WHERE field_id = '".Database::escape_string($field_id)."'";
		$result = Database::query($sql);

		// recalculate the field_order because the value is used to show/hide the up/down icon
		// and the field_order value cannot be bigger than the number of fields
		$sql = "SELECT * FROM $table_user_field ORDER BY field_order ASC";
		$result = Database::query($sql);
		$i = 1;
		while($row = Database::fetch_array($result))
		{
			$sql_reorder = "UPDATE $table_user_field SET field_order = '".Database::escape_string($i)."' WHERE id = '".Database::escape_string($row['id'])."'";
			$result_reorder = Database::query($sql_reorder);
			$i++;
		}

		// field was deleted so we return true
		return true;
	}
	else
	{
		// the field was not deleted so we return false
		return false;
	}
}
?>
