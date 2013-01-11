<?php

/* For licensing terms, see /license.txt */
/**
 * 	@package chamilo.admin
 */
// name of the language file that needs to be included
$language_file = array('admin', 'registration');

// resetting the course information
$cidReset = true;

// including the global library
require '../inc/global.inc.php';

// section for the tabs
$this_section = SECTION_PLATFORM_ADMIN;

// user permissions
api_protect_admin_script();

// breadcrumbs
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'user_fields.php', 'name' => get_lang('UserFields'));
$interbreadcrumb[] = array('url' => 'user_fields_add.php?action=edit&field_id=' . Security::remove_XSS($_GET['field_id']) . '&amp;sec_token=' . $_SESSION['sec_token'], 'name' => get_lang('EditUserFields'));

// name of the tools
$tool_name = get_lang('UserFieldsSortOptions');

// display header
Display::display_header($tool_name);

if (isset($_GET['action'])) {
    $check = Security::check_token('get');
    if ($check) {
        switch ($_GET['action']) {
            case 'moveup' :
                if (api_is_platform_admin() && !empty($_GET['option_id'])) {
                    if (move_user_field_option('moveup', $_GET['option_id'])) {
                        Display :: display_confirmation_message(get_lang('FieldOptionMovedUp'));
                    } else {
                        Display :: display_error_message(get_lang('CannotMoveFieldOption'));
                    }
                }
                break;
            case 'movedown' :
                if (api_is_platform_admin() && !empty($_GET['option_id'])) {
                    if (move_user_field_option('movedown', $_GET['option_id'])) {
                        Display :: display_confirmation_message(get_lang('FieldOptionMovedDown'));
                    } else {
                        Display :: display_error_message(get_lang('CannotMoveFieldOption'));
                    }
                }
                break;
        }
    }
}

// getting all the information of the field
$field_info = UserManager::get_extra_field_information($_GET['field_id']);
echo Display::page_header($field_info['3']);

// the total number of options (used in the actions_filter function but declared here for performance reasons)
$number_of_options = get_number_of_options();

// displaying the sortable table
$parameters['sec_token'] = Security::get_token();
$parameters['field_id'] = Security::remove_XSS($_GET['field_id']);
$table = new SortableTable('options', 'get_number_of_options', 'get_options_data', 2);
$table->set_additional_parameters($parameters);
$table->set_header(0, get_lang('DisplayOrder'), false);
$table->set_header(1, get_lang('OptionText'), false);
$table->set_header(2, get_lang('Actions'), false);
$table->set_column_filter(2, 'actions_filter');
$table->display();

// display footer
Display::display_footer();

function get_options_data($from, $number_of_items, $column, $direction) {
    // Database table definition
    $table_userfields_options = Database :: get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);

    // The sql statement
    $sql = "SELECT
				option_order 		AS col0,
				option_display_text	AS col1,
				id 					AS col2
			FROM $table_userfields_options WHERE field_id='" . Database::escape_string($_GET['field_id']) . "' ORDER BY option_order ASC";
    $sql .= " LIMIT $from,$number_of_items";
    $res = Database::query($sql);
    $return = array();
    while ($option = Database::fetch_row($res)) {
        $return[] = $option;
    }
    return $return;
}

function get_number_of_options($from = null, $number_of_items = null, $column = null, $direction = null) {
    // Database table definition
    $table_userfields_options = Database :: get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);

    // The sql statement
    $sql = "SELECT count(id) as total FROM $table_userfields_options WHERE field_id='" . Database::escape_string($_GET['field_id']) . "' ";
    $res = Database::query($sql);
    $row = Database::fetch_row($res);
    return $row[0];
}

function actions_filter($option_id, $url_params, $row) {
    global $number_of_options;

    if ($row[0] <> 1) {
        $return .= '<a href="' . api_get_self() . '?action=moveup&amp;option_id=' . $option_id . '&amp;field_id=' . Security::remove_XSS($_GET['field_id']) . '&amp;sec_token=' . $_SESSION['sec_token'] . '">' . Display::return_icon('up.gif', get_lang('Up')) . '</a>';
    } else {
        $return .= Display::return_icon('blank.gif', '', array('width' => '21px'));
    }

    // the down icon only has to appear when the row can be moved down (all but the last row)
    if ($row[0] <> $number_of_options) {
        $return .= '<a href="' . api_get_self() . '?action=movedown&amp;option_id=' . $option_id . '&amp;field_id=' . Security::remove_XSS($_GET['field_id']) . '&amp;sec_token=' . $_SESSION['sec_token'] . '">' . Display::return_icon('down.gif', get_lang('Down')) . '</a>';
    }
    return $return;
}

/**
 * Move a user defined field option up or down
 *
 * @param string $direction the direction we have to move the field to (up or down)
 * @param unknown_type $field_id
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 * @version July 2008
 * @since Dokeos 1.8.6
 */
function move_user_field_option($direction, $option_id) {
    // Database table definition
    $table_userfields_options = Database :: get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);

    // check the parameters
    if (!in_array($direction, array('moveup', 'movedown')) OR !is_numeric($option_id)) {
        return false;
    }

    // determine the SQL sort direction
    if ($direction == 'moveup') {
        $sortdirection = 'DESC';
    } else {
        $sortdirection = 'ASC';
    }

    $found = false;

    $sql = "SELECT id, option_order FROM $table_userfields_options  WHERE field_id='" . Database::escape_string($_GET['field_id']) . "' ORDER BY option_order $sortdirection";
    $result = Database::query($sql);
    while ($row = Database::fetch_array($result)) {
        if ($found) {
            $next_id = $row['id'];
            $next_order = $row['option_order'];
            break;
        }

        if ($option_id == $row['id']) {
            $this_id = $row['id'];
            $this_order = $row['option_order'];
            $found = true;
        }
    }

    $sql1 = "UPDATE " . $table_userfields_options . " SET option_order = '" . Database::escape_string($next_order) . "' WHERE id =  '" . Database::escape_string($this_id) . "'";
    $sql2 = "UPDATE " . $table_userfields_options . " SET option_order = '" . Database::escape_string($this_order) . "' WHERE id =  '" . Database::escape_string($next_id) . "'";
    Database::query($sql1);
    Database::query($sql2);
    return true;
}