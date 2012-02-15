<?php
/* For licensing terms, see /dokeos_license.txt */
/**
	@author Bart Mollet
*	@package chamilo.admin

*/

// name of the language file that needs to be included
$language_file = array ('registration','admin','userInfo');
$cidReset = true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'xajax/xajax.inc.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true);


/**
 * Get the total number of users on the platform
 * @see SortableTable#get_total_number_of_items()
 */
function get_number_of_groups()
{
	$group_table = Database :: get_main_table(TABLE_MAIN_GROUP);
	$sql = "SELECT COUNT(g.id) AS total_number_of_items FROM $group_table g";

	// adding the filter to see the user's only of the current access_url
	/*
    global $_configuration;
    if ((api_is_platform_admin() || api_is_session_admin()) && $_configuration['multiple_access_urls'] && api_get_current_access_url_id()!=-1) {
    	$access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    	$sql.= " INNER JOIN $access_url_rel_user_table url_rel_user ON (u.user_id=url_rel_user.user_id)";
    }
*/
	if ( isset ($_GET['keyword'])) {
		$keyword = Database::escape_string(trim($_GET['keyword']));
		$sql .= " WHERE (g.name LIKE '%".$keyword."%' OR g.description LIKE '%".$keyword."%'  OR  g.url LIKE '%".$keyword."%' )";
	}

    // adding the filter to see the user's only of the current access_url
    /*
	if ((api_is_platform_admin() || api_is_session_admin()) && $_configuration['multiple_access_urls'] && api_get_current_access_url_id()!=-1) {
    		$sql.= " AND url_rel_user.access_url_id=".api_get_current_access_url_id();
    }*/

	$res = Database::query($sql);
	$obj = Database::fetch_object($res);
	return $obj->total_number_of_items;
}
/**
 * Get the users to display on the current page (fill the sortable-table)
 * @param   int     offset of first user to recover
 * @param   int     Number of users to get
 * @param   int     Column to sort on
 * @param   string  Order (ASC,DESC)
 * @see SortableTable#get_table_data($from)
 */
function get_group_data($from, $number_of_items, $column, $direction)
{
	$group_table = Database :: get_main_table(TABLE_MAIN_GROUP);

	$sql = "SELECT
                 g.id			AS col0,
                 g.name			AS col1,
                 g.description 	AS col2,
                 g.visibility 	AS col3,
                 g.id			AS col4
             FROM $group_table g ";

    // adding the filter to see the user's only of the current access_url
    /*global $_configuration;
    if ((api_is_platform_admin() || api_is_session_admin()) && $_configuration['multiple_access_urls'] && api_get_current_access_url_id()!=-1) {
    	$access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    	$sql.= " INNER JOIN $access_url_rel_user_table url_rel_user ON (u.user_id=url_rel_user.user_id)";
    }*/

	if (isset ($_GET['keyword'])) {
		$keyword = Database::escape_string(trim($_GET['keyword']));
		$sql .= " WHERE (g.name LIKE '%".$keyword."%' OR g.description LIKE '%".$keyword."%'  OR  g.url LIKE '%".$keyword."%' )";
	}
	/*
    // adding the filter to see the user's only of the current access_url
	if ((api_is_platform_admin() || api_is_session_admin()) && $_configuration['multiple_access_urls'] && api_get_current_access_url_id()!=-1) {
    		$sql.= " AND url_rel_user.access_url_id=".api_get_current_access_url_id();
    }*/

    if (!in_array($direction, array('ASC','DESC'))) {
    	$direction = 'ASC';
    }
    $column = intval($column);
    $from = intval($from);
    $number_of_items = intval($number_of_items);

	$sql .= " ORDER BY col$column $direction ";
	$sql .= " LIMIT $from,$number_of_items";

	$res = Database::query($sql);

	$users = array ();
    $t = time();

    // Status
	$status = array();
	$status[GROUP_PERMISSION_OPEN] 		= get_lang('Open');
	$status[GROUP_PERMISSION_CLOSED]	= get_lang('Closed');

	while ($group = Database::fetch_row($res)) {
		$group[3] = $status[$group[3]];
		$group['1'] = '<a href="'.api_get_path(WEB_CODE_PATH).'social/groups.php?id='.$group['0'].'">'.$group['1'].'</a>';
        $groups[] = $group;
	}
	return $groups;
}


function get_recent_group_data($from =0 , $number_of_items = 5, $column, $direction)
{
	$group_table = Database :: get_main_table(TABLE_MAIN_GROUP);

	$sql = "SELECT
                 g.id			AS col0,
                 g.name			AS col1,
                 g.description 	AS col2,
                 g.visibility 	AS col3,
                 g.id			AS col4
             FROM $group_table g ";

    // adding the filter to see the user's only of the current access_url
    /*global $_configuration;
    if ((api_is_platform_admin() || api_is_session_admin()) && $_configuration['multiple_access_urls'] && api_get_current_access_url_id()!=-1) {
    	$access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    	$sql.= " INNER JOIN $access_url_rel_user_table url_rel_user ON (u.user_id=url_rel_user.user_id)";
    }*/

	if (isset ($_GET['keyword'])) {
		$keyword = Database::escape_string(trim($_GET['keyword']));
		$sql .= " WHERE (g.name LIKE '%".$keyword."%' OR g.description LIKE '%".$keyword."%'  OR  g.url LIKE '%".$keyword."%' )";
	}
	/*
    // adding the filter to see the user's only of the current access_url
	if ((api_is_platform_admin() || api_is_session_admin()) && $_configuration['multiple_access_urls'] && api_get_current_access_url_id()!=-1) {
    		$sql.= " AND url_rel_user.access_url_id=".api_get_current_access_url_id();
    }*/

    if (!in_array($direction, array('ASC','DESC'))) {
    	$direction = 'ASC';
    }
    $column = intval($column);
    $from = intval($from);
    $number_of_items = intval($number_of_items);

	$sql .= " ORDER BY col$column $direction ";
	$sql .= " LIMIT $from,$number_of_items";

	$res = Database::query($sql);

	$users = array ();
    $t = time();
	while ($group = Database::fetch_row($res)) {
        // forget about the expiration date field
        $groups[] = $group;
	}
	return $groups;
}


/**
 * Build the modify-column of the table
 * @param   int     The user id
 * @param   string  URL params to add to table links
 * @param   array   Row of elements to alter
 * @return string Some HTML-code with modify-buttons
 */
function modify_filter($group_id,$url_params,$row)
{
	global $charset;
	global $_user;
	global $_admins_list;
	if (api_is_platform_admin()) {
		$result .= '<a href="'.api_get_path(WEB_CODE_PATH).'admin/add_users_to_group.php?id='.$group_id.'">'.Display::return_icon('subscribe_users_social_network.png',get_lang('AddUsersToGroup'),'',ICON_SIZE_SMALL).'</a>';
		$result .= '<a href="group_edit.php?id='.$group_id.'">'.Display::return_icon('edit.png', get_lang('Edit'), array(), 22).'</a>&nbsp;&nbsp;';
		$result .= '<a href="group_list.php?action=delete_group&amp;group_id='.$group_id.'&amp;'.$url_params.'&amp;sec_token='.$_SESSION['sec_token'].'"  onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset))."'".')) return false;">'.Display::return_icon('delete.png', get_lang('Delete'), array(), 22).'</a>';
	}
	return $result;
}


/**
 * Build the active-column of the table to lock or unlock a certain user
 * lock = the user can no longer use this account
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param int $active the current state of the account
 * @param int $user_id The user id
 * @param string $url_params
 * @return string Some HTML-code with the lock/unlock button
 */
function active_filter($active, $url_params, $row) {
	global $_user;

	if ($active=='1') {
		$action='lock';
		$image='right';
	} elseif ($active=='-1') {
    	$action='edit';
        $image='expired';
    } elseif ($active=='0') {
		$action='unlock';
		$image='wrong';
	}

    if ($action=='edit') {
        $result = Display::return_icon($image.'.gif', get_lang('AccountExpired'));
    }elseif ($row['0']<>$_user['user_id']) { // you cannot lock yourself out otherwise you could disable all the accounts including your own => everybody is locked out and nobody can change it anymore.
		$result = '<a href="user_list.php?action='.$action.'&amp;user_id='.$row['0'].'&amp;'.$url_params.'&amp;sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon($image.'.gif', get_lang(ucfirst($action))).'</a>';
	}
	return $result;
}

/**
 * Lock or unlock a user
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @param int $status, do we want to lock the user ($status=lock) or unlock it ($status=unlock)
 * @param int $user_id The user id
 * @return language variable
 */
function lock_unlock_user($status,$user_id)
{
	$user_table = Database :: get_main_table(TABLE_MAIN_USER);
	if ($status=='lock')
	{
		$status_db='0';
		$return_message=get_lang('UserLocked');
	}
	if ($status=='unlock')
	{
		$status_db='1';
		$return_message=get_lang('UserUnlocked');
	}

	if(($status_db=='1' OR $status_db=='0') AND is_numeric($user_id))
	{
		$sql="UPDATE $user_table SET active='".Database::escape_string($status_db)."' WHERE user_id='".Database::escape_string($user_id)."'";
		$result = Database::query($sql);
	}

	if ($result)
	{
		return $return_message;
	}
}

/**
 * Instead of displaying the integer of the status, we give a translation for the status
 *
 * @param integer $status
 * @return string translation
 *
 * @version march 2008
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 */
function status_filter($status) {
	$statusname = api_get_status_langvars();
	return $statusname[$status];
}

// INIT SECTION
$action = $_GET["action"];

if (isset ($_GET['search']) && $_GET['search'] == 'advanced') {
	$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
	$interbreadcrumb[] = array ("url" => 'group_list.php', "name" => get_lang('GroupList'));
	$tool_name = get_lang('SearchAUser');
	Display :: display_header($tool_name);
	//api_display_tool_title($tool_name);
	$form = new FormValidator('advanced_search','get');
	$form->addElement('header', '', $tool_name);
	$form->add_textfield('keyword_firstname',get_lang('FirstName'),false);
	$form->add_textfield('keyword_lastname',get_lang('LastName'),false);
	$form->add_textfield('keyword_username',get_lang('LoginName'),false);
	$form->add_textfield('keyword_email',get_lang('Email'),false);
	$form->add_textfield('keyword_officialcode',get_lang('OfficialCode'),false);
	$status_options = array();
	$status_options['%'] = get_lang('All');
	$status_options[STUDENT] = get_lang('Student');
	$status_options[COURSEMANAGER] = get_lang('Teacher');
	$status_options[SESSIONADMIN] = get_lang('Administrator');//
	$form->addElement('select','keyword_status',get_lang('Status'),$status_options);
	$active_group = array();
	$active_group[] = $form->createElement('checkbox','keyword_active','',get_lang('Active'));
	$active_group[] = $form->createElement('checkbox','keyword_inactive','',get_lang('Inactive'));
	$form->addGroup($active_group,'',get_lang('ActiveAccount'),'<br/>',false);
	$form->addElement('style_submit_button', 'submit',get_lang('SearchUsers'),'class="search"');
	$defaults['keyword_active'] = 1;
	$defaults['keyword_inactive'] = 1;
	$form->setDefaults($defaults);
	$form->display();
}
else
{
	$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
	$tool_name = get_lang('GroupList');
	Display :: display_header($tool_name, "");

	//api_display_tool_title($tool_name);
	if (isset ($_GET['action'])) {
		$check = Security::check_token('get');
		if($check) {
			switch ($_GET['action']) {
				case 'show_message' :
                    if (!empty($_GET['warn'])) {
                    	// to prevent too long messages
                    	if ($_GET['warn'] == 'session_message'){
                    		$_GET['warn'] = $_SESSION['session_message_import_users'];
                    	}
                    	Display::display_warning_message(urldecode($_GET['warn']),false);
                    }
                    if (!empty($_GET['message'])) {
                        Display :: display_confirmation_message(stripslashes($_GET['message']));
                    }
					break;
				case 'delete_group' :
					if (api_is_platform_admin()) {
						if (GroupPortalManager :: delete($_GET['group_id'])) {
							Display :: display_confirmation_message(get_lang('GroupDeleted'));
						} else {
							Display :: display_error_message(get_lang('CannotDeleteGroup'));
						}
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
	if (isset ($_POST['action'])) {
		$check = Security::check_token('get');
		if ($check) {
			switch ($_POST['action']) {
				case 'delete' :
					if (api_is_platform_admin()) {
						$number_of_selected_groups = count($_POST['id']);
						$number_of_deleted_groups = 0;
						foreach ($_POST['id'] as $index => $group_id) {
							if (GroupPortalManager :: delete($group_id)) {
								$number_of_deleted_groups++;
							}
						}
					}
					if ($number_of_selected_groups == $number_of_deleted_groups) {
						Display :: display_confirmation_message(get_lang('SelectedGroupsDeleted'));
					} else {
						Display :: display_error_message(get_lang('SomeGroupsNotDeleted'));
					}
					break;
			}
			Security::clear_token();
		}
	}
	// Create a search-box
	$form = new FormValidator('search_simple','get','','',null,false);
	$renderer =& $form->defaultRenderer();
	$renderer->setElementTemplate('<span>{element}</span> ');
	$form->addElement('text','keyword',get_lang('keyword'));
	$form->addElement('style_submit_button', 'submit',get_lang('Search'),'class="search"');
	//$form->addElement('static','search_advanced_link',null,'<a href="user_list.php?search=advanced">'.get_lang('AdvancedSearch').'</a>');
	echo '<div class="actions" style="width:100%;">';
	if (api_is_platform_admin()) {
		echo '<span style="float:right;">'.
			 '<a href="'.api_get_path(WEB_CODE_PATH).'admin/group_add.php">'.Display::return_icon('create_group_social_network.png',get_lang('AddGroups'),'',ICON_SIZE_MEDIUM).'</a>'.
			 '</span>';
	}
	$form->display();
	echo '</div>';
	if (isset ($_GET['keyword'])) {
		$parameters = array ('keyword' => Security::remove_XSS($_GET['keyword']));
	}
	// Create a sortable table with user-data
	$parameters['sec_token'] = Security::get_token();

	// get the list of all admins to mark them in the users list
	$admin_table = Database::get_main_table(TABLE_MAIN_ADMIN);
	$sql_admin = "SELECT user_id FROM $admin_table";
	$res_admin = Database::query($sql_admin);
	$_admins_list = array();
	while ($row_admin = Database::fetch_row($res_admin)) {
		$_admins_list[] = $row_admin[0];
	}

	$table = new SortableTable('group_list', 'get_number_of_groups', 'get_group_data', 2);
	$table->set_additional_parameters($parameters);
	$table->set_header(0, '', false);
	$table->set_header(1, get_lang('Name'));
	$table->set_header(2, get_lang('Description'));
	$table->set_header(3, get_lang('Visibility'));
	$table->set_header(4, '', false);
	$table->set_column_filter(4, 'modify_filter');
	//$table->set_column_filter(6, 'status_filter');
	//$table->set_column_filter(7, 'active_filter');
	//$table->set_column_filter(8, 'modify_filter');
	if (api_is_platform_admin())
		$table->set_form_actions(array ('delete' => get_lang('DeleteFromPlatform')));
	$table->display();
}
Display :: display_footer();