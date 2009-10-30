<?php // $Id: subscribe_user.php 20412 2009-05-08 16:09:34Z herodoto $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2009 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, rue Notre Dame, 152, B-1140 Evere, Belgium, info@dokeos.com
==============================================================================
*/

/**
==============================================================================
*	This script allows teachers to subscribe existing users
*	to their course.
*
*	@package dokeos.user
==============================================================================
*/
/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included
$language_file = array('registration','admin');

// including the global Dokeos file
include ('../inc/global.inc.php');

// the section (for the tabs)
$this_section = SECTION_COURSES;
// access restriction
if (!api_is_allowed_to_edit()) {
	 api_not_allowed(true);
}
// including additional libraries
require_once (api_get_path(LIBRARY_PATH).'course.lib.php');
require_once (api_get_path(LIBRARY_PATH).'sortabletable.class.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/

$tool_name = get_lang("SubscribeUserToCourse");
if ($_REQUEST['type']=='teacher') {
	$tool_name = get_lang("SubscribeUserToCourseAsTeacher");
}


//extra entries in breadcrumb
$interbreadcrumb[] = array ("url" => "user.php", "name" => get_lang("Users"));
if ($_POST['keyword'])
{
	$interbreadcrumb[] = array ("url" => "subscribe_user.php?type=".Security::remove_XSS($_GET['type']), "name" => $tool_name);
	$tool_name = get_lang('SearchResults');
}
Display :: display_header($tool_name, "User");

// api_display_tool_title($tool_name);

/*
==============================================================================
		MAIN SECTION
==============================================================================
*/

$list_register_user='';
$list_not_register_user='';

if (isset ($_REQUEST['register'])) {
	if (isset($_REQUEST['type']) && $_REQUEST['type']=='teacher') {
		$result_simple_sub=CourseManager :: subscribe_user(Database::escape_string($_REQUEST['user_id']), $_course['sysCode'],COURSEMANAGER);
	} else {
		$result_simple_sub=CourseManager :: subscribe_user(Database::escape_string($_REQUEST['user_id']), $_course['sysCode']);
	}

	$user_id_temp=$_SESSION['session_user_id'];

	if (is_array($user_id_temp)) {
		$counter = count($user_id_temp);
		for ($j=0; $j<$counter;$j++) {
			if 	($user_id_temp[$j]==$_GET['user_id']) {
				if ($result_simple_sub)	{
					Display::display_confirmation_message($_SESSION['session_user_name'][$j].' '.get_lang('langAddedToCourse'));
				} else {
					Display::display_error_message($_SESSION['session_user_name'][$j].' '.get_lang('langNotAddedToCourse'));

				}
			}
		}
		unset($_SESSION['session_user_id']);
		unset($_SESSION['session_user_name']);
	}
}

if (isset ($_POST['action'])) {
	switch ($_POST['action']) {
		case 'subscribe' :

			if (is_array($_POST['user'])) {
				foreach ($_POST['user'] as $index => $user_id) {
					$user_id=Database::escape_string($user_id);
					if(isset($_REQUEST['type']) && $_REQUEST['type']=='teacher') {
						$is_suscribe[]=CourseManager :: subscribe_user($user_id, $_course['sysCode'],COURSEMANAGER);
					} else {
						$is_suscribe[]=CourseManager :: subscribe_user($user_id, $_course['sysCode']);
					}
						$is_suscribe_user_id[]=$user_id;
				}
			}

			$user_id_temp=$_SESSION['session_user_id'];
			$user_name_temp=$_SESSION['session_user_name'];

			unset($_SESSION['session_user_id']);
 			unset($_SESSION['session_user_name']);
			$counter=0;
			$$is_suscribe_counter=count($is_suscribe_user_id);

			$list_register_user='';

			//if ($$is_suscribe_counter!=1) {
				for ($i=0; $i<$$is_suscribe_counter;$i++) {
					for ($j=0; $j<count($user_id_temp);$j++) {
						if ($is_suscribe_user_id[$i]==$user_id_temp[$j]) {
								if ($is_suscribe[$i]) {
									$list_register_user.=" - ".$user_name_temp[$j].'<br/>';
									$temp_unique_user=$user_name_temp[$j];
									$counter++;
								} else {
									$list_not_register_user.=" - ".$user_name_temp[$j].'<br/>';
								}
						}
					}
				}
			//} else {
				//$list_register_user=$temp_unique_user; // only 1 user register
			//}

			if (!empty($list_register_user)) {
				if ($$is_suscribe_counter==1) {
					$register_user_message=$temp_unique_user.' '.get_lang('langAddedToCourse');
					Display::display_confirmation_message($register_user_message,false);
				} else {
					$register_user_message='<br />'.get_lang('UsersRegistered').'<br/><br />'.$list_register_user;
					Display::display_confirmation_message($register_user_message,false);
				}
			}

			if (!empty($list_not_register_user)) {
				$not_register_user_message='<br />'.get_lang('UsersNotRegistered').'<br/><br /><br />'.$list_not_register_user;
				Display::display_error_message($not_register_user_message,false);
			}
			break;
	}
}

if (!empty($_SESSION['session_user_id'])) {
	unset($_SESSION['session_user_id']);
}

if (!empty($_SESSION['session_user_name'])) {
	unset($_SESSION['session_user_name']);
}

/*
-----------------------------------------------------------
		SHOW LIST OF USERS
-----------------------------------------------------------
*/

/**
 *  * Get the users to display on the current page.
 */
function get_number_of_users() {
	// Database table definition
	$user_table = Database :: get_main_table(TABLE_MAIN_USER);
	$course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
	$table_user_field_values 	= Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
	
	if (isset($_REQUEST['type']) && $_REQUEST['type']=='teacher') {
		$sql = "SELECT 	u.user_id
						FROM $user_table u
						LEFT JOIN $course_user_table cu on u.user_id = cu.user_id and course_code='".$_SESSION['_course']['id']."'
						WHERE cu.user_id IS NULL";

		global $_configuration;
		if ($_configuration['multiple_access_urls']==true) {
			$url_access_id = api_get_current_access_url_id();
			if ($url_access_id !=-1) {
				$tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

				$sql = "SELECT
					u.user_id
					FROM $user_table u
					LEFT JOIN $course_user_table cu on u.user_id = cu.user_id and course_code='".$_SESSION['_course']['id']."'
					INNER JOIN  $tbl_url_rel_user as url_rel_user
					ON (url_rel_user.user_id = u.user_id)
					WHERE cu.user_id IS NULL AND access_url_id= $url_access_id ";
			}
		}

	} else {
		$sql = "SELECT 	u.user_id
						FROM $user_table u
						LEFT JOIN $course_user_table cu on u.user_id = cu.user_id and course_code='".$_SESSION['_course']['id']."'";
						
		// we change the SQL when we have a filter
		if (isset($_GET['subscribe_user_filter_value']) AND api_get_setting('ProfilingFilterAddingUsers') == 'true'){
			$field_identification = explode('*',$_GET['subscribe_user_filter_value']);
			$sql .=	"
				LEFT JOIN $table_user_field_values field_values 
					ON field_values.user_id = u.user_id 
				WHERE cu.user_id IS NULL
					AND field_values.field_id = '".Database::escape_string($field_identification[0])."' 
					AND field_values.field_value = '".Database::escape_string($field_identification[1])."'";
		} else	{
			$sql .=	"WHERE cu.user_id IS NULL";
		}		

		global $_configuration;
		if ($_configuration['multiple_access_urls']==true) {
			$url_access_id = api_get_current_access_url_id();
			if ($url_access_id !=-1) {
				$tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

				$sql = "SELECT
					u.user_id
					FROM $user_table u
					LEFT JOIN $course_user_table cu on u.user_id = cu.user_id and course_code='".$_SESSION['_course']['id']."'
					INNER JOIN  $tbl_url_rel_user as url_rel_user
					ON (url_rel_user.user_id = u.user_id)
					WHERE cu.user_id IS NULL AND access_url_id= $url_access_id ";
			}
		}




	}

	// when there is a keyword then we are searching and we have to change the SQL statement
	if (isset ($_REQUEST['keyword'])) {
		$keyword = Database::escape_string($_REQUEST['keyword']);
		$sql .= " AND (firstname LIKE '%".$keyword."%' OR lastname LIKE '%".$keyword."%'   OR email LIKE '%".$keyword."%'  OR username LIKE '%".$keyword."%'  OR official_code LIKE '%".$keyword."%')";
		
		// we also want to search for users who have something in their profile fields that matches the keyword
		if (api_get_setting('ProfilingFilterAddingUsers') == 'true') {
			$additional_users = search_additional_profile_fields($keyword);
		}
					
		// getting all the users of the course (to make sure that we do not display users that are already in the course)
		if (!empty($_SESSION["id_session"])) {
			$a_course_users = CourseManager :: get_user_list_from_course_code($_SESSION['_course']['id'], true, $_SESSION['id_session']);
		} else {
			$a_course_users = CourseManager :: get_user_list_from_course_code($_SESSION['_course']['id'], true);
	}
		foreach ($a_course_users as $user_id=>$course_user) {
			$users_of_course[] = $course_user['user_id'];
	    }
		
	}
	
	
	//executing the SQL statement
	$res = api_sql_query($sql, __FILE__, __LINE__);
	while ($user = Database::fetch_row($res)) {
		$users[] = $user[0];
	}	
	$result = Database::num_rows($res);
	// we add 1 for every additional user (a user where the keyword matches one of the additional profile fields)
	// that is not yet in the course and not yet in the search result
	if (isset ($_REQUEST['keyword']) AND api_get_setting('ProfilingFilterAddingUsers') == 'true') {	
		foreach($additional_users as $additional_user_key=>$additional_user_value){
			if (!in_array($additional_user_key,$users) AND !in_array($additional_user_key,$users_of_course)){
				$result++;
			}
		}	
	}
	return $result;
}
/**
 * Get the users to display on the current page.
 */
function get_user_data($from, $number_of_items, $column, $direction) {
	global $_course;

	// Database table definitions
	$user_table = Database :: get_main_table(TABLE_MAIN_USER);
	$course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
	$tbl_session_rel_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
	$table_user_field_values 	= Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
	
	// adding teachers
	$is_western_name_order = api_is_western_name_order();
	if (isset($_REQUEST['type']) && $_REQUEST['type']=='teacher') {
		// adding a teacher through a session
		if (!empty($_SESSION["id_session"])) {
			$sql = "SELECT
					u.user_id AS col0,
					u.official_code AS col1,
					".($is_western_name_order
					? "u.firstname AS col2,
					u.lastname AS col3,"
					: "u.lastname AS col2,
					u.firstname AS col3,")."
					u.email 	AS col4,
					u.active 	AS col5,
					u.user_id   AS col6
					FROM $user_table u
					LEFT JOIN $tbl_session_rel_course_user cu on u.user_id = cu.id_user and course_code='".$_SESSION['_course']['id']."'";

			// applying the filter of the additional user profile fields 	
			if (isset($_GET['subscribe_user_filter_value']) AND api_get_setting('ProfilingFilterAddingUsers') == 'true'){
				$field_identification = explode('*',$_GET['subscribe_user_filter_value']);
				$sql .=	"
					LEFT JOIN $table_user_field_values field_values 
						ON field_values.user_id = u.user_id 
					WHERE cu.user_id IS NULL
						AND field_values.field_id = '".Database::escape_string($field_identification[0])."' 
						AND field_values.field_value = '".Database::escape_string($field_identification[1])."'";
			} else {	
				$sql .=	"WHERE cu.user_id IS NULL";
			}
		} else {
		// adding a teacher NOT through a session
			$sql = "SELECT
					u.user_id AS col0,
					u.official_code AS col1,
					".($is_western_name_order
					? "u.firstname AS col2,
					u.lastname AS col3,"
					: "u.lastname AS col2,
					u.firstname AS col3,")."
					u.email 	AS col4,
					u.active 	AS col5,
					u.user_id   AS col6
				FROM $user_table u
				LEFT JOIN $course_user_table cu on u.user_id = cu.user_id and course_code='".$_SESSION['_course']['id']."'";
				
				// applying the filter of the additional user profile fields 	
				if (isset($_GET['subscribe_user_filter_value']) AND api_get_setting('ProfilingFilterAddingUsers') == 'true'){
					$field_identification = explode('*',$_GET['subscribe_user_filter_value']);
					$sql .=	"
						LEFT JOIN $table_user_field_values field_values 
							ON field_values.user_id = u.user_id 
						WHERE cu.user_id IS NULL
							AND field_values.field_id = '".Database::escape_string($field_identification[0])."' 
							AND field_values.field_value = '".Database::escape_string($field_identification[1])."'";
				} else	{
					$sql .=	"WHERE cu.user_id IS NULL";
				}
				//showing only the courses of the current Dokeos access_url_id
				global $_configuration;
				
				// adding a teacher NOT trough a session on a portal with multiple URLs
				if ($_configuration['multiple_access_urls']==true) {
					$url_access_id = api_get_current_access_url_id();
					if ($url_access_id !=-1) {
						$tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
						$sql = "SELECT
						u.user_id AS col0,
						u.official_code AS col1,
						".($is_western_name_order
						? "u.firstname AS col2,
						u.lastname AS col3,"
						: "u.lastname AS col2,
						u.firstname AS col3,")."
						u.email 	AS col4,
						u.active 	AS col5,
						u.user_id   AS col6
						FROM $user_table u
						LEFT JOIN $course_user_table cu on u.user_id = cu.user_id and course_code='".$_SESSION['_course']['id']."'
						INNER JOIN  $tbl_url_rel_user as url_rel_user
					ON (url_rel_user.user_id = u.user_id) 
					WHERE cu.user_id IS NULL AND access_url_id= $url_access_id ";				


					// applying the filter of the additional user profile fields 	
					if (isset($_GET['subscribe_user_filter_value']) AND api_get_setting('ProfilingFilterAddingUsers') == 'true'){
						$field_identification = explode('*',$_GET['subscribe_user_filter_value']);
						$sql .=	"
							LEFT JOIN $table_user_field_values field_values 
								ON field_values.user_id = u.user_id 
							WHERE cu.user_id IS NULL
								AND field_values.field_id = '".Database::escape_string($field_identification[0])."' 
								AND field_values.field_value = '".Database::escape_string($field_identification[1])."'";
					} else	{
						$sql .=	"WHERE cu.user_id IS NULL AND access_url_id= $url_access_id ";
					}		
				}		
			}
		}
	} else {
		// adding a student
		if (!empty($_SESSION["id_session"])) {
			$sql = "SELECT
					u.user_id AS col0,
					u.official_code AS col1,
					".($is_western_name_order
					? "u.firstname AS col2,
					u.lastname AS col3,"
					: "u.lastname AS col2,
					u.firstname AS col3,")."
					u.email 	AS col4,
					u.active 	AS col5,
					u.user_id   AS col6
				FROM $user_table u
				LEFT JOIN $tbl_session_rel_course_user cu on u.user_id = cu.id_user and course_code='".$_SESSION['_course']['id']."'";
				
			// applying the filter of the additional user profile fields 	
			if (isset($_GET['subscribe_user_filter_value'])){
				$field_identification = explode('*',$_GET['subscribe_user_filter_value']);
				$sql .=	"
					LEFT JOIN $table_user_field_values field_values 
						ON field_values.user_id = u.user_id 
					WHERE cu.user_id IS NULL
						AND field_values.field_id = '".Database::escape_string($field_identification[0])."' 
						AND field_values.field_value = '".Database::escape_string($field_identification[1])."'";
			} else	{
				$sql .=	"WHERE cu.user_id IS NULL";
			}
		} else {
		$sql = "SELECT
					u.user_id AS col0,
					u.official_code   AS col1,
					".($is_western_name_order
					? "u.firstname  AS col2,
					u.lastname AS col3,"
					: "u.lastname  AS col2,
					u.firstname AS col3,")."
					u.email 	AS col4,
					u.active 	AS col5,
					u.user_id   AS col6
				FROM $user_table u
				LEFT JOIN $course_user_table cu on u.user_id = cu.user_id and course_code='".$_SESSION['_course']['id']."'";
				
			// applying the filter of the additional user profile fields 	
			if (isset($_GET['subscribe_user_filter_value'])){
				$field_identification = explode('*',$_GET['subscribe_user_filter_value']);
				$sql .=	"
					LEFT JOIN $table_user_field_values field_values 
						ON field_values.user_id = u.user_id 
					WHERE cu.user_id IS NULL
						AND field_values.field_id = '".Database::escape_string($field_identification[0])."' 
						AND field_values.field_value = '".Database::escape_string($field_identification[1])."'";
			} else	{
				$sql .=	"WHERE cu.user_id IS NULL";
			}

			//showing only the courses of the current Dokeos access_url_id
			global $_configuration;
			if ($_configuration['multiple_access_urls']==true) {
				$url_access_id = api_get_current_access_url_id();
				if ($url_access_id !=-1) {
					$tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
					$sql = "SELECT
					u.user_id AS col0,
					u.official_code AS col1,
					".($is_western_name_order
					? "u.firstname  AS col2,
					u.lastname AS col3,"
					: "u.lastname  AS col2,
					u.firstname AS col3,")."
					u.email 	AS col4,
					u.active 	AS col5,
					u.user_id   AS col6
					FROM $user_table u
					LEFT JOIN $course_user_table cu on u.user_id = cu.user_id and course_code='".$_SESSION['_course']['id']."'
					INNER JOIN  $tbl_url_rel_user as url_rel_user
					ON (url_rel_user.user_id = u.user_id)
					WHERE cu.user_id IS NULL AND access_url_id= $url_access_id ";


					// applying the filter of the additional user profile fields 	
					if (isset($_GET['subscribe_user_filter_value']) AND api_get_setting('ProfilingFilterAddingUsers') == 'true'){
						$field_identification = explode('*',$_GET['subscribe_user_filter_value']);
						$sql .=	"
							LEFT JOIN $table_user_field_values field_values 
								ON field_values.user_id = u.user_id 
							WHERE cu.user_id IS NULL
								AND field_values.field_id = '".Database::escape_string($field_identification[0])."' 
								AND field_values.field_value = '".Database::escape_string($field_identification[1])."'";
					} else	{
						$sql .=	"WHERE cu.user_id IS NULL AND access_url_id= $url_access_id ";
					}		

				}
			}
		}
	}

	// adding additional WHERE statements to the SQL for the search functionality
	if (isset ($_REQUEST['keyword'])) {
		$keyword = Database::escape_string($_REQUEST['keyword']);
		$sql .= " AND (firstname LIKE '%".$keyword."%' OR lastname LIKE '%".$keyword."%'   OR email LIKE '%".$keyword."%'  OR username LIKE '%".$keyword."%'  OR official_code LIKE '%".$keyword."%')";
		
		if (api_get_setting('ProfilingFilterAddingUsers') == 'true') {
			// we also want to search for users who have something in their profile fields that matches the keyword
			$additional_users = search_additional_profile_fields($keyword);
		}
				
		// getting all the users of the course (to make sure that we do not display users that are already in the course)
		if (!empty($_SESSION["id_session"])) {
			$a_course_users = CourseManager :: get_user_list_from_course_code($_SESSION['_course']['id'], true, $_SESSION['id_session']);
		} else {
			$a_course_users = CourseManager :: get_user_list_from_course_code($_SESSION['_course']['id'], true);
	}
		foreach ($a_course_users as $user_id=>$course_user) {
			$users_of_course[] = $course_user['user_id'];
		}
	}
	
	// Sorting and pagination (used by the sortable table)
	$sql .= " ORDER BY col$column $direction ";
	$sql .= " LIMIT $from,$number_of_items";
	$res = Database::query($sql, __FILE__, __LINE__);
	$users = array ();
	while ($user = Database::fetch_row($res)) {
		$users[] = $user;
		$_SESSION['session_user_id'][] = $user[0];
		if ($is_western_name_order) {
			$_SESSION['session_user_name'][] = api_get_person_name($user[2], $user[3]);
		} else {
			$_SESSION['session_user_name'][] = api_get_person_name($user[3], $user[2]);
		}
	}
	// adding additional users based on the search on the additional profile fields
	if (isset ($_REQUEST['keyword'])){
		foreach($additional_users as $additional_user_key=>$additional_user_value){
			if (!in_array($additional_user_key,$_SESSION['session_user_id']) AND !in_array($additional_user_key,$users_of_course)){
				$users[]= array($additional_user_value['col0'],$additional_user_value['col1'],$additional_user_value['col2'].'*',$additional_user_value['col3'].'*',$additional_user_value['col4'],$additional_user_value['col5'], $additional_user_value['col6']);
			}
		}
	}
	return $users;
}
/**
* Returns a mailto-link
* @param string $email An email-address
* @return string HTML-code with a mailto-link
*/
function email_filter($email) {
	return Display :: encrypted_mailto_link($email, $email);
}
/**
 * Build the reg-column of the table
 * @param int $user_id The user id
 * @return string Some HTML-code
 */
function reg_filter($user_id) {
	if(isset($_REQUEST['type']) && $_REQUEST['type']=='teacher') $type='teacher'; else $type='student';
	$result = "<a href=\"".api_get_self()."?register=yes&amp;type=".$type."&amp;user_id=".$user_id."\">".get_lang("reg")."</a>";
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
		$action='AccountActive';
		$image='right';
	}

	if ($active=='0') {
		$action='AccountInactive';
		$image='wrong';
	}
	if ($row['0']<>$_user['user_id']) { // you cannot lock yourself out otherwise you could disable all the accounts including your own => everybody is locked out and nobody can change it anymore.
		$result = '<center><img src="../img/'.$image.'.gif" border="0" style="vertical-align: middle;" alt="'.get_lang(ucfirst($action)).'" title="'.get_lang(ucfirst($action)).'"/></center>';
	}
	return $result;
}


$is_western_name_order = api_is_western_name_order();
$sort_by_first_name = api_sort_by_first_name();

// Build search-form
echo '<div class="actions">';

$actions .= '<a href="user.php">'.Display::return_icon('members.gif',get_lang('BackToUserList')).' '.get_lang('BackToUserList').'</a>';
if ($_POST['keyword'])
{
	$actions .= '<a href="subscribe_user.php?type='.Security::remove_XSS($_GET['type']).'">'.Display::return_icon('clean_group.gif').' '.get_lang('ClearSearchResults').'</a>';
}
if ($_GET['subscribe_user_filter_value'] AND !empty($_GET['subscribe_user_filter_value']))
{
	$actions .= '<a href="subscribe_user.php?type='.Security::remove_XSS($_GET['type']).'">'.Display::return_icon('clean_group.gif').' '.get_lang('ClearFilterResults').'</a>';
}
if (api_get_setting('ProfilingFilterAddingUsers') == 'true') {
	display_extra_profile_fields_filter();
}

$form = new FormValidator('search_user', 'POST',api_get_self().'?type='.$_REQUEST['type'],'',null,false);
$renderer = & $form->defaultRenderer();
$renderer->setElementTemplate('<span>{element}</span> ');
$form->add_textfield('keyword', '', false);
$form->addElement('style_submit_button', 'submit', get_lang('SearchButton'), 'class="search"');
$form->addElement('static','additionalactions',null,$actions);
$form->display();
echo '</div>';

// Build table
$table = new SortableTable('users', 'get_number_of_users', 'get_user_data', ($is_western_name_order xor $sort_by_first_name) ? 3 : 2);
$parameters['keyword'] = $_REQUEST['keyword'];
$parameters ['type'] = $_REQUEST['type'];
$table->set_additional_parameters($parameters);
$col = 0;
$table->set_header($col ++, '', false);
$table->set_header($col ++, get_lang('OfficialCode'));
if (api_is_western_name_order()) {
	$table->set_header($col ++, get_lang('FirstName'));
	$table->set_header($col ++, get_lang('LastName'));
} else {
	$table->set_header($col ++, get_lang('LastName'));
	$table->set_header($col ++, get_lang('FirstName'));
}
$table->set_header($col ++, get_lang('Email'));
$table->set_column_filter($col -1, 'email_filter');
$table->set_header($col ++, get_lang('Active'),false);
$table->set_column_filter($col -1, 'active_filter');
$table->set_header($col ++, get_lang('reg'), false);
$table->set_column_filter($col -1, 'reg_filter');
$table->set_form_actions(array ('subscribe' => get_lang('reg')), 'user');

if (!empty($_POST['keyword'])) {
	$keyword_name=Security::remove_XSS($_POST['keyword']);
	echo '<br/>'.get_lang('SearchResultsFor').' <span style="font-style: italic ;"> '.$keyword_name.' </span><br>';
}

// Display table
$table->display();

// footer
Display :: display_footer();

/**
 * Search the additional user profile fields defined by the platform administrator in 
 * platform administration > profiling for a given keyword. 
 * We not only search in the predefined options but also in the input fields wherer
 * the user can enter some text. 
 * 
 * For this we get the additional profile field options that match the (search) keyword, 
 * then we find all the users who have entered the (search)keyword in a input field of the 
 * additional profile fields or have chosen one of the matching predefined options
 *
 * @param string $keyword a keyword we are looking for in the additional profile fields
 * @return array $additional_users an array with the users who have an additional profile field that matches the keyword
 */
function search_additional_profile_fields($keyword)
{
	// database table definitions
	$table_user_field_options 	= Database :: get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
	$table_user_field_values 	= Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
	$table_user 				= Database::get_main_table(TABLE_MAIN_USER);
	$table_course_user		 	= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
	$table_session_course_user 	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);					

	// getting the field option text that match this keyword (for radio buttons and checkboxes)
	$sql_profiling = "SELECT * FROM $table_user_field_options WHERE option_display_text LIKE '%".$keyword."%'";
	$result_profiling = api_sql_query($sql_profiling, __FILE__, __LINE__);
	while ($profiling_field_options = Database::fetch_array($result_profiling)) {
		$profiling_field_options_exact_values[] = $profiling_field_options;
	}
	
	foreach ($profiling_field_options_exact_values as $profilingkey=>$profilingvalue){
		$profiling_field_options_exact_values_sql .= "OR (field_id = '".$profilingvalue['field_id']."' AND field_value='".$profilingvalue['option_value']."') ";
	}
	
	// getting all the user ids of the users who have chosen on of the predefined fields that contain the keyword
	// or all the users who have entered the keyword in a free-form field
	$sql_profiling_values = "SELECT user.user_id as col0, user.official_code as col1, user.lastname as col2, user.firstname as col3, user.email as col4, user.active as col5, user.user_id as col6
							FROM $table_user user, $table_user_field_values user_values 
							WHERE user.user_id = user_values.user_id 
							AND ( field_value LIKE '%".$keyword."%' 
							".$profiling_field_options_exact_values_sql.")";
	$result_profiling_values = api_sql_query($sql_profiling_values, __FILE__, __LINE__);
	while ($profiled_users = Database::fetch_array($result_profiling_values)) {
		$additional_users[$profiled_users['col0']] = $profiled_users;
	}
	
	return $additional_users; 
}
/**
 * This function displays a dropdown list with all the additional user profile fields defined by the platform administrator in 
 * platform administration > profiling. Only the fields that have predefined fields are usefull for such a filter. 
 *
 */
function display_extra_profile_fields_filter()
{
	// getting all the additional user profile fields
	$extra = UserManager::get_extra_fields(0,50,5,'ASC');
	
	$return='<option value="">'.get_lang('SelectFilter').'</option>';
	
	// looping through the additional user profile fields
	foreach($extra as $id => $field_details) {		
		// $field_details[2] contains the type of the additional user profile field
		switch($field_details[2]) {
			// text fields cannot be used as a filter
			case USER_FIELD_TYPE_TEXT:
				break;
			// text area fields cannot be used as a filter
			case USER_FIELD_TYPE_TEXTAREA:				
				break;
			case USER_FIELD_TYPE_RADIO:
			case USER_FIELD_TYPE_SELECT:
			case USER_FIELD_TYPE_SELECT_MULTIPLE:		
				$return .= '<optgroup label="'.$field_details[3].'">';
				foreach($field_details[9] as $option_id => $option_details) {
					if ($_GET['subscribe_user_filter_value'] == $field_details[0].'*'.$option_details[1]) {
						$selected = 'selected="selected"';
					} else {
						$selected = false;
					}
					$return .= '<option value="'.$field_details[0].'*'.$option_details[1].'" '.$selected.'>'.$option_details[2].'</option>';
				}				
				$return .= '</optgroup>';
				break;
		}			
	}
	
	echo '<form id="subscribe_user_filter" name="subscribe_user_filter" method="get" action="'.api_get_self().'?api_get_cidreq" style="float:left;">';
	echo '	<input type="hidden" name="type" id="type" value="'.Security::Remove_XSS($_GET['type']).'" />';
	echo   '<select name="subscribe_user_filter_value" id="subscribe_user_filter_value">'.$return.'</select>';
	echo   '<button type="submit" name="submit_filter" id="submit_filter" value="" class="search">'.get_lang('Filter').'</button>';
	echo '</form>';

}

function debug($var) {
	echo '<pre>';
	print_r($var);
	echo '</pre>';
}