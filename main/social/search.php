<?php
/* For licensing terms, see /dokeos_license.txt */

// name of the language file that needs to be included
$language_file = array ('registration','admin');

require_once '../inc/global.inc.php';

require_once (api_get_path(LIBRARY_PATH).'sortabletable.class.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once (api_get_path(LIBRARY_PATH).'security.lib.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');

$tool_name = get_lang('SearchAUser');
Display :: display_header($tool_name);


// Build search-form
$form = new FormValidator('search_user', 'get', '', '', null, false);
$renderer = & $form->defaultRenderer();
$renderer->setElementTemplate('<span>{element}</span> ');
$form->add_textfield('keyword', '', false);
$form->addElement('style_submit_button', 'submit', get_lang('SearchButton'), 'class="search"');
$form->addElement('static', 'additionalactions', null, $actions);
$form->display();


if (isset ($_GET['keyword'])) {	
	if (isset ($_GET['keyword'])) {
		$parameters = array ('keyword' => Security::remove_XSS($_GET['keyword']));
	}
	// Create a sortable table with user-data
	$parameters['sec_token'] = Security::get_token();	
	
	$table = new SortableTable('users', 'get_number_of_users', 'get_user_data', (api_is_western_name_order() xor api_sort_by_first_name()) ? 3 : 2);
	$table->set_additional_parameters($parameters);
	$table->set_header(0, '', false);
	$table->set_header(1, get_lang('OfficialCode'));
	if (api_is_western_name_order()) {
		$table->set_header(2, get_lang('FirstName'));
		$table->set_header(3, get_lang('LastName'));
	} else {
		$table->set_header(2, get_lang('LastName'));
		$table->set_header(3, get_lang('FirstName'));
	}
	$table->set_header(4, get_lang('LoginName'));
	$table->set_header(5, get_lang('Email'));
	
	//tag	
	$table_tag = new SortableTable('tags', 'get_number_of_user_tags', 'get_user_tag_data');
	$table_tag->set_additional_parameters($parameters);
	$table_tag->set_header(0, '', false);
	$table->set_header(1, get_lang('OfficialCode'));
	if (api_is_western_name_order()) {
		$table_tag->set_header(2, get_lang('FirstName'));
		$table_tag->set_header(3, get_lang('LastName'));
	} else {
		$table_tag->set_header(2, get_lang('LastName'));
		$table_tag->set_header(3, get_lang('FirstName'));
	}	
	/*
	//groups	
	$table_tag = new SortableTable('groups', 'get_number_of_user_tags', 'get_user_tag_data');
	$table_tag->set_additional_parameters($parameters);
	$table_tag->set_header(0, '', false);
	$table->set_header(1, get_lang('OfficialCode'));
	if (api_is_western_name_order()) {
		$table_tag->set_header(2, get_lang('FirstName'));
		$table_tag->set_header(3, get_lang('LastName'));
	} else {
		$table_tag->set_header(2, get_lang('LastName'));
		$table_tag->set_header(3, get_lang('FirstName'));
	}
*/
	echo get_lang('Users');
	$table->display_grid();
	
	echo get_lang('Tags');
	$table_tag->display_grid();
	/*
	echo get_lang('Groups');
	$table_group->display_grid();
	*/
}

/**
 * Get the users to display on the current page (fill the sortable-table)
 * @param   int     offset of first user to recover
 * @param   int     Number of users to get
 * @param   int     Column to sort on
 * @param   string  Order (ASC,DESC)
 * @see SortableTable#get_table_data($from)
 */
function get_user_tag_data($from, $number_of_items, $column, $direction)
{
	if (isset ($_GET['keyword'])) {
		$keyword = Database::escape_string($_GET['keyword']);
    }    
	$user_tags = UserManager::get_all_user_tags($keyword,'5',$from, $number_of_items);
    return $user_tags;
}



/**
 * Get the total number of users on the platform
 * @see SortableTable#get_total_number_of_items()
 */
function get_number_of_user_tags()
{
	$tag_table = Database :: get_main_table(TABLE_MAIN_USER_TAG);
	$sql = "SELECT COUNT(tag) AS total_number_of_items FROM $tag_table u";
  	if (isset ($_GET['keyword'])) {
		$keyword = Database::escape_string($_GET['keyword']);
		$sql .= " WHERE (tag LIKE '%".$keyword."%' )";
    }    
	$res = Database::query($sql, __FILE__, __LINE__);
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
function get_user_data($from, $number_of_items, $column, $direction)
{
	$user_table = Database :: get_main_table(TABLE_MAIN_USER);
	$sql = "SELECT
                 u.user_id				AS col0,
                 u.official_code		AS col1,
				 ".(api_is_western_name_order()
                 ? "u.firstname 			AS col2,
                 u.lastname 			AS col3,"
                 : "u.lastname 			AS col2,
                 u.firstname 			AS col3,")."
                 u.username				AS col4,
                 u.email				AS col5
            FROM $user_table u ";

    // adding the filter to see the user's only of the current access_url
    global $_configuration;
    if ((api_is_platform_admin() || api_is_session_admin()) && $_configuration['multiple_access_urls']==true && api_get_current_access_url_id()!=-1) {
    	$access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    	$sql.= " INNER JOIN $access_url_rel_user_table url_rel_user ON (u.user_id=url_rel_user.user_id)";
    }

	if (isset ($_GET['keyword'])) {
		$keyword = Database::escape_string($_GET['keyword']);
		$sql .= " WHERE (u.firstname LIKE '%".$keyword."%' OR u.lastname LIKE '%".$keyword."%'  OR u.username LIKE '%".$keyword."%'  OR u.official_code LIKE '%".$keyword."%' OR u.email LIKE '%".$keyword."%' )";
	} elseif (isset ($_GET['keyword_firstname'])) {
		$admin_table = Database :: get_main_table(TABLE_MAIN_ADMIN);
		$keyword_firstname = Database::escape_string($_GET['keyword_firstname']);
		$keyword_lastname = Database::escape_string($_GET['keyword_lastname']);
		$keyword_email = Database::escape_string($_GET['keyword_email']);
		$keyword_officialcode = Database::escape_string($_GET['keyword_officialcode']);
		$keyword_username = Database::escape_string($_GET['keyword_username']);
		$keyword_status = Database::escape_string($_GET['keyword_status']);
		$query_admin_table = '';
		$keyword_admin = '';

		if ($keyword_status == SESSIONADMIN) {
			$keyword_status = '%';
			$query_admin_table = " , $admin_table a ";
			$keyword_admin = ' AND a.user_id = u.user_id ';
		}
		$keyword_active = isset($_GET['keyword_active']);
		$keyword_inactive = isset($_GET['keyword_inactive']);
		$sql .= $query_admin_table." WHERE (u.firstname LIKE '%".$keyword_firstname."%' " .
				"AND u.lastname LIKE '%".$keyword_lastname."%' " .
				"AND u.username LIKE '%".$keyword_username."%'  " .
				"AND u.email LIKE '%".$keyword_email."%'   " .
				"AND u.official_code LIKE '%".$keyword_officialcode."%'    " .
				"AND u.status LIKE '".$keyword_status."'" .
				$keyword_admin;

		if ($keyword_active && !$keyword_inactive) {
			$sql .= " AND u.active='1'";
		} elseif($keyword_inactive && !$keyword_active) {
			$sql .= " AND u.active='0'";
		}
		$sql .= " ) ";
	}

    // adding the filter to see the user's only of the current access_url
	if ((api_is_platform_admin() || api_is_session_admin()) && $_configuration['multiple_access_urls']==true && api_get_current_access_url_id()!=-1) {
    		$sql.= " AND url_rel_user.access_url_id=".api_get_current_access_url_id();
    }

    if (!in_array($direction, array('ASC','DESC'))) {
    	$direction = 'ASC';
    }
    $column = intval($column);
    $from = intval($from);
    $number_of_items = intval($number_of_items);

	$sql .= " ORDER BY col$column $direction ";
	$sql .= " LIMIT $from,$number_of_items";
	$res = Database::query($sql, __FILE__, __LINE__);

	$users = array ();
    $t = time();
	while ($user = Database::fetch_row($res)) {
        if ($user[7] == 1 && $user[9] != '0000-00-00 00:00:00') {
            // check expiration date
            $expiration_time = convert_mysql_date($user[9]);
            // if expiration date is passed, store a special value for active field
            if ($expiration_time < $t) {
        	   $user[7] = '-1';
            }
        }
        // forget about the expiration date field
        $users[] = array($user[0],$user[1],$user[2],$user[3],$user[4],$user[5]);
	}
	return $users;
}



/**
 * Get the total number of users on the platform
 * @see SortableTable#get_total_number_of_items()
 */
function get_number_of_users()
{
	$user_table = Database :: get_main_table(TABLE_MAIN_USER);
	$sql = "SELECT COUNT(u.user_id) AS total_number_of_items FROM $user_table u";

	// adding the filter to see the user's only of the current access_url
    global $_configuration;
    if ((api_is_platform_admin() || api_is_session_admin()) && $_configuration['multiple_access_urls']==true && api_get_current_access_url_id()!=-1) {
    	$access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    	$sql.= " INNER JOIN $access_url_rel_user_table url_rel_user ON (u.user_id=url_rel_user.user_id)";
    }

	if ( isset ($_GET['keyword'])) {
		$keyword = Database::escape_string($_GET['keyword']);
		$sql .= " WHERE (u.firstname LIKE '%".$keyword."%' OR u.lastname LIKE '%".$keyword."%'  OR u.username LIKE '%".$keyword."%' OR u.email LIKE '%".$keyword."%'  OR u.official_code LIKE '%".$keyword."%') ";
	} elseif (isset ($_GET['keyword_firstname'])) {
		$admin_table = Database :: get_main_table(TABLE_MAIN_ADMIN);
		$keyword_firstname = Database::escape_string($_GET['keyword_firstname']);
		$keyword_lastname = Database::escape_string($_GET['keyword_lastname']);
		$keyword_email = Database::escape_string($_GET['keyword_email']);
		$keyword_officialcode = Database::escape_string($_GET['keyword_officialcode']);
		$keyword_username = Database::escape_string($_GET['keyword_username']);
		$keyword_status = Database::escape_string($_GET['keyword_status']);
		$query_admin_table = '';
		$keyword_admin = '';
		if ($keyword_status == SESSIONADMIN) {
			$keyword_status = '%';
			$query_admin_table = " , $admin_table a ";
			$keyword_admin = ' AND a.user_id = u.user_id ';
		}
		$keyword_active = isset($_GET['keyword_active']);
		$keyword_inactive = isset($_GET['keyword_inactive']);
		$sql .= $query_admin_table .
				" WHERE (u.firstname LIKE '%".$keyword_firstname."%' " .
				"AND u.lastname LIKE '%".$keyword_lastname."%' " .
				"AND u.username LIKE '%".$keyword_username."%'  " .
				"AND u.email LIKE '%".$keyword_email."%'   " .
				"AND u.official_code LIKE '%".$keyword_officialcode."%'    " .
				"AND u.status LIKE '".$keyword_status."'" .
				$keyword_admin;
		if($keyword_active && !$keyword_inactive) {
			$sql .= " AND u.active='1'";
		} elseif($keyword_inactive && !$keyword_active) {
			$sql .= " AND u.active='0'";
		}
		$sql .= " ) ";
	}

    // adding the filter to see the user's only of the current access_url
	if ((api_is_platform_admin() || api_is_session_admin()) && $_configuration['multiple_access_urls']==true && api_get_current_access_url_id()!=-1) {
    		$sql.= " AND url_rel_user.access_url_id=".api_get_current_access_url_id();
    }

	$res = Database::query($sql, __FILE__, __LINE__);
	$obj = Database::fetch_object($res);
	return $obj->total_number_of_items;
}