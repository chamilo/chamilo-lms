<?php
/* For licensing terms, see /license.txt */
/**
*	This script displays a list of the users of the current course.
*	Course admins can change user perimssions, subscribe and unsubscribe users...
*
*	EXPERIMENTAL: support for virtual courses
*	- show users registered in virtual and real courses;
*	- only show the users of a virtual course if the current user;
*	is registered in that virtual course.
*
*	Exceptions: platform admin and the course admin will see all virtual courses.
*	This is a new feature, there may be bugs.
*
*	@todo possibility to edit user-course rights and view statistics for users in virtual courses
*	@todo convert normal table display to display function (refactor virtual course display function)
*	@todo display table functions need support for align and valign (e.g. to center text in cells) (this is now possible)
*	@author Roan Embrechts, refactoring + virtual courses support
*	@author Julio Montoya Armas, Several fixes
*	@package chamilo.user
*/
/**
 * Code
 */
// name of the language file that needs to be included
$language_file = array('registration', 'admin', 'userInfo');
$use_anonymous = true;
require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;

// notice for unauthorized people.
api_protect_course_script(true);


/*		Libraries	*/
require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

global $_configuration;

//CHECK KEYS
if (!isset ($_cid)) {
	header('location: '.api_get_path(WEB_PATH));
	exit;
}

if (!api_is_platform_admin(true)) {
	if (!api_is_course_admin() && !api_is_coach()) {
		if (api_get_course_setting('allow_user_view_user_list') == 0) {
			api_not_allowed(true);
		}
	}	
}

/*
	Constants and variables
*/
$currentCourseID 		= Database::escape_string($_course['sysCode']);
$is_western_name_order 	= api_is_western_name_order();
$sort_by_first_name 	= api_sort_by_first_name();

/* Unregistering a user section	*/
if (api_is_allowed_to_edit(null, true)) {
	if (isset($_POST['action'])) {
		switch ($_POST['action']) {
			case 'unsubscribe' :
				// Make sure we don't unsubscribe current user from the course

				if (is_array($_POST['user'])) {
					$user_ids = array_diff($_POST['user'], array($_user['user_id']));
					if (count($user_ids) > 0) {
						CourseManager::unsubscribe_user($user_ids, $_SESSION['_course']['sysCode']);
						$message = get_lang('UsersUnsubscribed');
					}
				}
		}
	}
}

if (api_is_allowed_to_edit(null, true)) {
	if ( isset ($_GET['action'])) {
		switch ($_GET['action']) {
			case 'export' :
				$table_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
				$table_users = Database::get_main_table(TABLE_MAIN_USER);
				$session_id = 0;
				$is_western_name_order = api_is_western_name_order(PERSON_NAME_DATA_EXPORT);

				$data = array();
				$a_users = array();
				
				if ($_configuration['multiple_access_urls']) {				
					$current_access_url_id = api_get_current_access_url_id();
				}
					
				$session_id = api_get_session_id();	
				
				$extra_fields = UserManager::get_extra_user_data(api_get_user_id(), false, false, false, true);
				$extra_fields = array_keys($extra_fields);
				if ($sort_by_first_name) {
					$a_users[0] = array(get_lang('FirstName'), get_lang('LastName'), get_lang('Email'), get_lang('Phone'), get_lang('OfficialCode'), get_lang('Active'));
				} else {
					$a_users[0] = array(get_lang('LastName'), get_lang('FirstName'), get_lang('Email'), get_lang('Phone'), get_lang('OfficialCode'), get_lang('Active'));
				}
				$a_users[0] = array_merge($a_users[0],$extra_fields);
								
				// users subscribed to the course through a session
				if (api_get_setting('use_session_mode') == 'true') {
					if (api_get_session_id()) {						
						$table_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
						$sql_query = "SELECT DISTINCT user.user_id, ".($is_western_name_order ? "user.firstname, user.lastname" : "user.lastname, user.firstname").", user.email, phone, user.official_code, active
									  FROM $table_session_course_user as session_course_user, $table_users as user ";
						if ($_configuration['multiple_access_urls']) {
							$sql_query .= ' , '.Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER).' au ';
						}
						$sql_query .=" WHERE course_code = '$currentCourseID' AND session_course_user.id_user = user.user_id ";
						$sql_query .= ' AND id_session = '.$session_id;							
						
						if ($_configuration['multiple_access_urls']) {				
							$sql_query .= " AND user.user_id = au.user_id AND access_url_id =  $current_access_url_id  ";
						}					
						
						$sql_query .= $sort_by_first_name ? ' ORDER BY user.firstname, user.lastname' : ' ORDER BY user.lastname, user.firstname';
						
						$rs = Database::query($sql_query);
						
						while ($user = Database:: fetch_array($rs, 'ASSOC')) {
							$extra_fields = UserManager::get_extra_user_data($user['user_id'], false, false, false, true);
							if (!empty($extra_fields)) {
								foreach($extra_fields as $key => $extra_value) {
									$user[$key] = $extra_value;
								}
							}
							unset($user['user_id']);
							$data[] = $user;							
							$a_users[] = $user;
						}
					}
				}

				if ($session_id == 0) {
					// users directly subscribed to the course
					$table_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
					$sql_query = "SELECT DISTINCT user.user_id, ".($is_western_name_order ? "user.firstname, user.lastname" : "user.lastname, user.firstname").", user.email, phone, user.official_code, active
								  FROM $table_course_user as course_user, $table_users as user ";
					if ($_configuration['multiple_access_urls']) {
						$sql_query .= ' , '.Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER).' au ';
					}
					$sql_query .= "WHERE course_code = '$currentCourseID' AND course_user.relation_type<>".COURSE_RELATION_TYPE_RRHH." AND course_user.user_id = user.user_id ";
					
					if ($_configuration['multiple_access_urls']) {							
						$sql_query .= " AND user.user_id = au.user_id  AND access_url_id =  $current_access_url_id  ";
					}
					$sql_query .= ($sort_by_first_name ? "ORDER BY user.firstname, user.lastname" : "ORDER BY user.lastname, user.firstname");
					
					$rs = Database::query($sql_query);
					while ($user = Database::fetch_array($rs, 'ASSOC')) {
						$extra_fields = UserManager::get_extra_user_data($user['user_id'], false, false, false, true);
						if (!empty($extra_fields)) {						
							foreach($extra_fields as $key => $extra_value) {
								$user[$key] = $extra_value;
							}
						}
						unset($user['user_id']);						
						$data[] = $user;						
						$a_users[] = $user;
					}										
				}		
				
				switch ($_GET['type']) {
					case 'csv' :						
						Export::export_table_csv($a_users);
						exit;
					case 'xls' :
						Export::export_table_xls($a_users);
						exit;
				}
		}
	}
} // end if allowed to edit

if (api_is_allowed_to_edit(null, true)) {
	// Unregister user from course
	if ($_REQUEST['unregister']) {
		if (isset($_GET['user_id']) && is_numeric($_GET['user_id']) && $_GET['user_id'] != $_user['user_id']) {
			$user_id					= Database::escape_string($_GET['user_id']);
			$tbl_user					= Database::get_main_table(TABLE_MAIN_USER);
			$tbl_session_rel_course		= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
			$tbl_session_rel_user		= Database::get_main_table(TABLE_MAIN_SESSION_USER);

			$sql = 'SELECT '.$tbl_user.'.user_id
					FROM '.$tbl_user.' user
					INNER JOIN '.$tbl_session_rel_user.' reluser
					ON user.user_id = reluser.id_user AND reluser.relation_type<>'.SESSION_RELATION_TYPE_RRHH.'
					INNER JOIN '.$tbl_session_rel_course.' rel_course
					ON rel_course.id_session = reluser.id_session
					WHERE user.user_id = "'.$user_id.'"
					AND rel_course.course_code = "'.$currentCourseID.'"';

			$result = Database::query($sql);
			$row = Database::fetch_array($result, 'ASSOC');
			if ($row['user_id'] == $user_id || $row['user_id'] == "") {
				CourseManager::unsubscribe_user($_GET['user_id'], $_SESSION['_course']['sysCode']);
				$message = get_lang('UserUnsubscribed');
			} else {
				$message = get_lang('ThisStudentIsSubscribeThroughASession');
			}
		}
	}
} // end if allowed to edit


/*		FUNCTIONS	*/

function display_user_search_form() {
	echo '<form method="get" action="user.php">';
	echo get_lang("SearchForUser") . "&nbsp;&nbsp;";
	echo '<input type="text" name="keyword" value="'.Security::remove_XSS($_GET['keyword']).'"/>';
	echo '<input type="submit" value="'.get_lang('SearchButton').'"/>';
	echo '</form>';	
}
/**
*	This function displays a list if users for each virtual course linked to the current
*	real course.
*
*	defines globals
*
*	@version 1.0
*	@author Roan Embrechts
*	@todo users from virtual courses always show "-" for the group related output. Edit and statistics columns are disabled *	for these users, for now.
*/
/*
function show_users_in_virtual_courses() {
	global $_course, $_user, $origin;
	$real_course_code = $_course['sysCode'];
	$real_course_info = Database::get_course_info($real_course_code);
	$user_subscribed_virtual_course_list = CourseManager::get_list_of_virtual_courses_for_specific_user_and_real_course($_user['user_id'], $real_course_code);
	
	$number_of_virtual_courses = count($user_subscribed_virtual_course_list);
	$row = 0;
	$column_header[$row++] = "ID";
	$column_header[$row++] = get_lang("FullUserName");
	$column_header[$row++] = get_lang("Role");
	$column_header[$row++] = get_lang("Group");
	if (api_is_allowed_to_edit()) {
		$column_header[$row++] = get_lang("Tutor");
	}
	if (api_is_allowed_to_edit()) {
		$column_header[$row++] = get_lang("CourseManager");
	}

	if (!is_array($user_subscribed_virtual_course_list)) {
		return;
	}

	foreach ($user_subscribed_virtual_course_list as $virtual_course) {
		$virtual_course_code = $virtual_course["code"];
		$virtual_course_user_list = CourseManager::get_user_list_from_course_code($virtual_course_code);
		$message = get_lang("RegisteredInVirtualCourse")." ".$virtual_course["title"]."&nbsp;&nbsp;(".$virtual_course["code"].")";
		echo "<br/>";
		echo "<h4>".$message."</h4>";
		$properties["width"] = "100%";
		$properties["cellspacing"] = "1";
		Display::display_complex_table_header($properties, $column_header);
		foreach ($virtual_course_user_list as $this_user) {
			$user_id = $this_user["user_id"];
			$loginname = $this_user["username"];
			$lastname = $this_user["lastname"];
			$firstname = $this_user["firstname"];
			$status = $this_user["status"];
			$role = $this_user["role"];
			if ($status == "1") {
				$status = get_lang("CourseManager");
			} else {
				$status = " - ";
			}

			$full_name = api_get_person_name($firstname, $lastname);
			if ($lastname == '' || $firstname == '') {
				$full_name = $loginname;
			}

			$user_info_hyperlink = "<a href=\"userInfo.php?".api_get_cidreq()."&origin=".$origin."&uInfo=".$user_id."&virtual_course=".$virtual_course["code"]."\">".$full_name."</a>";
			$row = 0;
			$table_row[$row++] = $user_id;
			$table_row[$row++] = $user_info_hyperlink; //Full name
			$table_row[$row++] = $role; //Description
			$table_row[$row++] = " - "; //Group, for the moment groups don't work for students in virtual courses
			if (api_is_allowed_to_edit()) {
				$table_row[$row ++] = " - "; //Tutor column
				$table_row[$row ++] = $status; //Course Manager column
			}
			Display::display_table_row(null, $table_row, true);
		}
		echo '</tbody></table>';
	}
}*/

if (!$is_allowed_in_course) {
	api_not_allowed(true);
}

/*	Header */
if ($origin != 'learnpath') {
	if (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
		$interbreadcrumb[] = array ("url" => "user.php", "name" => get_lang("Users"));
		$tool_name = get_lang('SearchResults');
	} else {
		$tool_name = get_lang('Users');
		$origin = 'users';
	}
	Display::display_header($tool_name, "User");
} else {
?> <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH); ?>css/default.css" /> <?php
}

if (isset($message)) {
	Display::display_confirmation_message($message);
}

/*		MAIN CODE*/

//statistics
event_access_tool(TOOL_USER);
/*	Setting the permissions for this page */
$is_allowed_to_track = ($is_courseAdmin || $is_courseTutor);


// Tool introduction
Display::display_introduction_section(TOOL_USER, 'left');
$actions = '';
if ( api_is_allowed_to_edit(null, true)) {
	echo "<div class=\"actions\">";

	// the action links
        if (api_get_setting('allow_user_course_subscription_by_course_admin') == 'true') {
            $actions .= '<a href="subscribe_user.php?'.api_get_cidreq().'">'.Display::return_icon('user_subscribe_course.png',get_lang("SubscribeUserToCourse"),'','32').'</a> ';
            $actions .= "<a href=\"subscribe_user.php?".api_get_cidreq()."&type=teacher\">".Display::return_icon('teacher_subscribe_course.png', get_lang("SubscribeUserToCourseAsTeacher"),'','32')."</a> ";
        }
		$actions .= '<a href="user.php?'.api_get_cidreq().'&action=export&amp;type=csv">'.Display::return_icon('export_csv.png', get_lang('ExportAsCSV'),'','32').'</a> ';
	$actions .= '<a href="user.php?'.api_get_cidreq().'&action=export&amp;type=xls">'.Display::return_icon('export_excel.png', get_lang('ExportAsXLS'),'','32').'</a> ';
		$actions .= "<a href=\"../group/group.php?".api_get_cidreq()."\">".Display::return_icon('group.png', get_lang("GroupUserManagement"),'','32')."</a>";
		
	if (api_get_setting('use_session_mode') == 'false') {
		$actions .= ' <a href="class.php?'.api_get_cidreq().'">'.get_lang('Classes').'</a>';
	}

	// Build search-form
	$form = new FormValidator('search_user', 'get', '', '', null, false);
	$renderer = & $form->defaultRenderer();
	$renderer->setElementTemplate('<span>{element}</span> ');
	$form->add_textfield('keyword', '', false);
	$form->addElement('style_submit_button', 'submit', get_lang('SearchButton'), 'class="search"');
	$form->addElement('static', 'additionalactions', null, $actions);
	$form->display();
	echo '</div>';
}
/*
if (1) // platform setting api_get_setting('subscribe_user_by_coach') {
	if (!api_is_allowed_to_edit() && $is_courseTutor) {
		echo "<div align=\"right\">";
		echo '<a href="subscribe_user.php?'.api_get_cidreq().'">'.Display::return_icon('add_user_big.gif',get_lang("SubscribeUserToCourse")).'&nbsp;'.get_lang("SubscribeUserToCourse").'</a>';
		echo "</div>";
	}
}*/


/*
	DISPLAY USERS LIST
	Also shows a "next page" button if there are
	more than 50 users.

	There's a bug in here somewhere - some users count as more than one if they are in more than one group
	--> code for > 50 users should take this into account
	(Roan, Feb 2004)
*/
/*
 * @todo seems not to affect anything in the code
if (CourseManager::has_virtual_courses_from_code($course_id, $user_id)) {
	$real_course_code = $_course['sysCode'];
	$real_course_info = Database::get_course_info($real_course_code);
	$message = get_lang("RegisteredInRealCourse")." ".$real_course_info["title"]."&nbsp;&nbsp;(".$real_course_info["official_code"].")";
	echo "<h4>".$message."</h4>";
}*/

/*
		DISPLAY LIST OF USERS
*/
/**
 *  * Get the users to display on the current page.
 */
function get_number_of_users() {
	$counter = 0;
	if (!empty($_SESSION["id_session"])){
		$a_course_users = CourseManager :: get_user_list_from_course_code($_SESSION['_course']['id'], true, $_SESSION['id_session']);

	} else {
		$a_course_users = CourseManager :: get_user_list_from_course_code($_SESSION['_course']['id'], false);
	}
	foreach ($a_course_users as $user_id => $o_course_user) {
		if ((isset($_GET['keyword']) && search_keyword($o_course_user['firstname'], $o_course_user['lastname'], $o_course_user['username'], $o_course_user['official_code'], $_GET['keyword'])) || !isset($_GET['keyword']) || empty($_GET['keyword'])) {
			$counter++;
		}
	}
	return $counter;
}

function search_keyword($firstname, $lastname, $username, $official_code, $keyword) {
	if (api_strripos($firstname, $keyword) !== false || api_strripos($lastname, $keyword) !== false || api_strripos($username, $keyword) !== false || api_strripos($official_code, $keyword) !== false) {
		return true;
	} else {
		return false;
	}
	
}


/**
 * Get the users to display on the current page.
 */
function get_user_data($from, $number_of_items, $column, $direction) {
	global $origin;
	global $is_western_name_order;
	global $sort_by_first_name;
	$a_users = array();

	// limit
	if (!isset($_GET['keyword']) || empty($_GET['keyword'])) {
		$limit = 'LIMIT '.intval($from).','.intval($number_of_items);
	}

	if (!in_array($direction, array('ASC', 'DESC'))) {
		$direction = 'ASC';
	}

	// order by
	if (api_is_allowed_to_edit()) {
		$column--;
	}
	switch ($column) {
		case 1:
			if ($is_western_name_order) {
				$order_by = 'ORDER BY user.firstname '.$direction.', user.lastname '.$direction;
			} else {
				$order_by = 'ORDER BY user.lastname '.$direction.', user.firstname '.$direction;
			}
			break;
		case 2:
			if ($is_western_name_order) {
				$order_by = 'ORDER BY user.lastname '.$direction.', user.firstname '.$direction;
			} else {
				$order_by = 'ORDER BY user.firstname '.$direction.', user.lastname '.$direction;
			}
			break;
		case 5:
			$order_by = 'ORDER BY user.official_code '.$direction;
			break;
		default:
			if ($sort_by_first_name) {
				$order_by = 'ORDER BY user.firstname '.$direction.', user.lastname '.$direction;
			} else {
				$order_by = 'ORDER BY user.lastname '.$direction.', user.firstname '.$direction;
			}
			break;
	}

	if (!empty($_SESSION["id_session"])) {
		$a_course_users = CourseManager :: get_user_list_from_course_code($_SESSION['_course']['id'], true, $_SESSION['id_session'], $limit, $order_by);
	} else {
		$a_course_users = CourseManager :: get_user_list_from_course_code($_SESSION['_course']['id'], false, 0, $limit, $order_by);
	}

	foreach ($a_course_users as $user_id => $o_course_user) {
		if ((isset ($_GET['keyword']) && search_keyword($o_course_user['firstname'], $o_course_user['lastname'], $o_course_user['username'], $o_course_user['official_code'], $_GET['keyword'])) || !isset($_GET['keyword']) || empty($_GET['keyword'])) {

			$groups_name = GroupManager :: get_user_group_name($user_id);
			$temp = array();
			if (api_is_allowed_to_edit(null, true)) {				
				if (api_get_setting('allow_user_course_subscription_by_course_admin') == 'true') {
                	$temp[] = $user_id;
                }
				$image_path = UserManager::get_user_picture_path_by_id($user_id, 'web', false, true);
				$user_profile = UserManager::get_picture_user($user_id, $image_path['file'], 22, USER_IMAGE_SIZE_SMALL, ' width="22" height="22" ');
				if (!api_is_anonymous()) {
					$photo = '<center><a href="userInfo.php?'.api_get_cidreq().'&origin='.$origin.'&amp;uInfo='.$user_id.'" title="'.get_lang('Info').'"  ><img src="'.$user_profile['file'].'" '.$user_profile['style'].' alt="'.api_get_person_name($o_course_user['firstname'], $o_course_user['lastname']).'"  title="'.api_get_person_name($o_course_user['firstname'], $o_course_user['lastname']).'" /></a></center>';
				} else {
					$photo = '<center><img src="'.$user_profile['file'].'" '.$user_profile['style'].' alt="'.api_get_person_name($o_course_user['firstname'], $o_course_user['lastname']).'" title="'.api_get_person_name($o_course_user['firstname'], $o_course_user['lastname']).'" /></center>';
				}
				$temp[] = $photo;
				$temp[] = $o_course_user['official_code'];
				
				if ($is_western_name_order) {
					$temp[] = $o_course_user['firstname'];
					$temp[] = $o_course_user['lastname'];
				} else {
					$temp[] = $o_course_user['lastname'];
					$temp[] = $o_course_user['firstname'];
				}

				$temp[] = isset($o_course_user['role']) ? $o_course_user['role'] : null;
				$temp[] = implode(', ', $groups_name); //Group
				

				// deprecated feature
				if ((isset($o_course_user['status_rel']) && $o_course_user['status_rel'] == 1) || (isset($o_course_user['status_session']) && $o_course_user['status_session'] == 2)) {
					$temp[] = get_lang('CourseManager');
				} elseif (isset($o_course_user['tutor_id']) && $o_course_user['tutor_id'] == 1) {
					$temp[] = get_lang('Tutor');
				} else {
					$temp[] = '-';
				}
				$temp[] = $o_course_user['active'];
				$temp[] = $user_id;
			} else {
				$image_path = UserManager::get_user_picture_path_by_id($user_id, 'web', false, true);
				$image_repository = $image_path['dir'];
				$existing_image = $image_path['file'];
				if (!api_is_anonymous()) {
					$photo= '<center><a href="userInfo.php?'.api_get_cidreq().'&origin='.$origin.'&amp;uInfo='.$user_id.'" title="'.get_lang('Info').'"  ><img src="'.$image_repository.$existing_image.'" alt="'.api_get_person_name($o_course_user['firstname'], $o_course_user['lastname']).'"  width="22" height="22" title="'.api_get_person_name($o_course_user['firstname'], $o_course_user['lastname']).'" /></a></center>';
				} else {
					$photo= '<center><img src="'.$image_repository.$existing_image.'" alt="'.api_get_person_name($o_course_user['firstname'], $o_course_user['lastname']).'"  width="22" height="22" title="'.api_get_person_name($o_course_user['firstname'], $o_course_user['lastname']).'" /></center>';
				}
				$temp[] = $photo;

				if ($is_western_name_order) {
					$temp[] = $o_course_user['firstname'];
					$temp[] = $o_course_user['lastname'];
				} else {
					$temp[] = $o_course_user['lastname'];
					$temp[] = $o_course_user['firstname'];
				}
				$temp[] = $o_course_user['role'];
				$temp[] = implode(', ', $groups_name);//Group
				$temp[] = $o_course_user['official_code'];				
			}
			$a_users[$user_id] = $temp;
		}
	}
	return $a_users;
	
	
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
		$image='accept';
	}
	if ($active=='0') {
		$action='AccountInactive';
		$image='error';
	}
	$result = '';
	if ($row[count($row)-1]<>$_user['user_id']) {  // you cannot lock yourself out otherwise you could disable all the accounts including your own => everybody is locked out and nobody can change it anymore.
		$result = '<center><img src="../img/icons/16/'.$image.'.png" border="0" style="vertical-align: middle;" alt="'.get_lang(ucfirst($action)).'" title="'.get_lang(ucfirst($action)).'"/></center>';
	}
	return $result;
}


/**
 * Build the modify-column of the table
 * @param int $user_id The user id
 * @return string Some HTML-code
 */
function modify_filter($user_id) {
	global $origin, $_user, $_course, $is_allowed_to_track, $charset;

	$result="<div style='text-align: center'>";

	if ($is_allowed_to_track) {
		$result .= '<a href="../mySpace/myStudents.php?'.api_get_cidreq().'&student='.$user_id.'&amp;details=true&amp;course='.$_course['id'].'&amp;origin=user_course&amp;id_session='.api_get_session_id().'" title="'.get_lang('Tracking').'"  ><img border="0" alt="'.get_lang('Tracking').'" src="../img/icons/22/stats.png" /></a>';
	}

	if (api_is_allowed_to_edit(null, true)) {
                if (api_get_setting('allow_user_course_subscription_by_course_admin') == 'true') {
                    // edit
                    $result .= '<a href="userInfo.php?'.api_get_cidreq().'&origin='.$origin.'&amp;editMainUserInfo='.$user_id.'" title="'.get_lang('Edit').'" >'.Display::return_icon('edit.png', get_lang('Edit'),'','22').'</a>&nbsp;';
                    // unregister
                    if ($user_id != $_user['user_id']) {
                            $result .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&unregister=yes&amp;user_id='.$user_id.'" title="'.get_lang('Unreg').' " onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)).'\')) return false;">'.Display::return_icon('unsubscribe_course.png', get_lang('Unreg'),'','22').'</a>&nbsp;';
                    } else {
                            $result .= Display::return_icon('unsubscribe_course_na.png', get_lang('Unreg'),'','22').'</a>&nbsp;';
                    }
                }
	}
	$result .= "</div>";
	return $result;
}

$default_column = ($is_western_name_order xor $sort_by_first_name) ? 3 : 2;
$default_column = api_is_allowed_to_edit() ? 2 : 1;

$table = new SortableTable('user_list', 'get_number_of_users', 'get_user_data', $default_column);
$parameters['keyword'] = isset($_GET['keyword']) ? Security::remove_XSS($_GET['keyword']) : null;
$table->set_additional_parameters($parameters);
$header_nr = 0;

if (api_is_allowed_to_edit(null, true)) {
        if (api_get_setting('allow_user_course_subscription_by_course_admin') == 'true') {
            $table->set_header($header_nr++, '', false);
        }
}
$table->set_header($header_nr++, get_lang('Photo'), false);
$table->set_header($header_nr++, get_lang('OfficialCode'));

if ($is_western_name_order) {
	$table->set_header($header_nr++, get_lang('FirstName'));
	$table->set_header($header_nr++, get_lang('LastName'));
} else {
	$table->set_header($header_nr++, get_lang('LastName'));
	$table->set_header($header_nr++, get_lang('FirstName'));
}
$table->set_header($header_nr++, get_lang('Description'), false);
$table->set_header($header_nr++, get_lang('GroupSingle'), false);

if (api_is_allowed_to_edit(null, true)) {
	// deprecated feature
	//$table->set_header($header_nr++, get_lang('Tutor'), false);
	$table->set_header($header_nr++, get_lang('Status'), false);
	$table->set_header($header_nr++, get_lang('Active'), false);
        if (api_get_setting('allow_user_course_subscription_by_course_admin') == 'true') {
            $table->set_column_filter(8, 'active_filter');
        } else {
            $table->set_column_filter(7, 'active_filter');
        }
	//actions column
	$table->set_header($header_nr++, get_lang('Action'), false);
	$table->set_column_filter($header_nr-1, 'modify_filter');

        if (api_get_setting('allow_user_course_subscription_by_course_admin') == 'true') {
            $table->set_form_actions(array('unsubscribe' => get_lang('Unreg')), 'user');
        }
}

$table->display();

if (!empty($_GET['keyword']) && !empty($_GET['submit'])) {
	$keyword_name = Security::remove_XSS($_GET['keyword']);
	echo '<br/>'.get_lang('SearchResultsFor').' <span style="font-style: italic ;"> '.$keyword_name.' </span><br>';
}

if (api_get_setting('allow_user_headings') == 'true' && $is_courseAdmin && api_is_allowed_to_edit() && $origin != 'learnpath') { // only course administrators see this line
	echo "<div align=\"right\">", "<form method=\"post\" action=\"userInfo.php\">", get_lang("CourseAdministratorOnly"), " : ", "<input type=\"submit\" class=\"save\" name=\"viewDefList\" value=\"".get_lang("DefineHeadings")."\" />", "</form>", "</div>\n";
}

//User list of the virtual courses linked to this course.
//@todo 
//show_users_in_virtual_courses($is_allowed_to_track);

/*		FOOTER  */
if ($origin != 'learnpath') {
	Display::display_footer();
}