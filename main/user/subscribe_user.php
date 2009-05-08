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
include ("../inc/global.inc.php");
$this_section = SECTION_COURSES;
if (!api_is_allowed_to_edit()) {
	 api_not_allowed(true);
}
require_once (api_get_path(LIBRARY_PATH).'course.lib.php');
require_once (api_get_path(LIBRARY_PATH).'sortabletable.class.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

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
	$user_table = Database :: get_main_table(TABLE_MAIN_USER);
	$course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
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
		
		
		
		
	}
	
	if (isset ($_REQUEST['keyword'])) {
		$keyword = Database::escape_string($_REQUEST['keyword']);
		$sql .= " AND (firstname LIKE '%".$keyword."%' OR lastname LIKE '%".$keyword."%'   OR email LIKE '%".$keyword."%'  OR username LIKE '%".$keyword."%'  OR official_code LIKE '%".$keyword."%')";
	}
	$res = api_sql_query($sql, __FILE__, __LINE__);
	$result = Database::num_rows($res);
	return $result;
}
/**
 * Get the users to display on the current page.
 */
function get_user_data($from, $number_of_items, $column, $direction) {
	$user_table = Database :: get_main_table(TABLE_MAIN_USER);
	$course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
	$tbl_session_rel_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
	if(isset($_REQUEST['type']) && $_REQUEST['type']=='teacher') {
		if (!empty($_SESSION["id_session"])) {
			$sql = "SELECT 
						u.user_id AS col0,
						u.official_code   AS col1, 
						u.lastname  AS col2, 
						u.firstname AS col3, 
						u.email 	AS col4,
						u.active 	AS col5,
						u.user_id   AS col6
					FROM $user_table u
					LEFT JOIN $tbl_session_rel_course_user cu on u.user_id = cu.id_user and course_code='".$_SESSION['_course']['id']."'
					WHERE cu.id_user IS NULL";	
		} else {	
			$sql = "SELECT 
					u.user_id AS col0,
					u.official_code   AS col1, 
					u.lastname  AS col2, 
					u.firstname AS col3, 
					u.email 	AS col4,
					u.active 	AS col5,
					u.user_id   AS col6
				FROM $user_table u
				LEFT JOIN $course_user_table cu on u.user_id = cu.user_id and course_code='".$_SESSION['_course']['id']."'
				WHERE cu.user_id IS NULL";
				//showing only the courses of the current Dokeos access_url_id
				
				global $_configuration;
				if ($_configuration['multiple_access_urls']==true) {
					$url_access_id = api_get_current_access_url_id();
					if ($url_access_id !=-1) {
						$tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
								
						$sql = "SELECT 
						u.user_id AS col0,
						u.official_code   AS col1, 
						u.lastname  AS col2, 
						u.firstname AS col3, 
						u.email 	AS col4,
						u.active 	AS col5,
						u.user_id   AS col6
						FROM $user_table u
						LEFT JOIN $course_user_table cu on u.user_id = cu.user_id and course_code='".$_SESSION['_course']['id']."'
						INNER JOIN  $tbl_url_rel_user as url_rel_user 
						ON (url_rel_user.user_id = u.user_id) 
						WHERE cu.user_id IS NULL AND access_url_id= $url_access_id ";				
					}		
				}
			
				
		}
	} else {
		if (!empty($_SESSION["id_session"])) {
			$sql = "SELECT 
					u.user_id AS col0,
					u.official_code   AS col1, 
					u.lastname  AS col2, 
					u.firstname AS col3, 
					u.email 	AS col4,
					u.active 	AS col5,
					u.user_id   AS col6 
				FROM $user_table u
				LEFT JOIN $tbl_session_rel_course_user cu on u.user_id = cu.id_user and course_code='".$_SESSION['_course']['id']."'
				WHERE cu.id_user IS NULL
				";
		} else {
			$sql = "SELECT 
					u.user_id AS col0,
					u.official_code   AS col1, 
					u.lastname  AS col2, 
					u.firstname AS col3, 
					u.email 	AS col4,
					u.active 	AS col5,
					u.user_id   AS col6 
				FROM $user_table u
				LEFT JOIN $course_user_table cu on u.user_id = cu.user_id and course_code='".$_SESSION['_course']['id']."'
				WHERE cu.user_id IS NULL";
			//showing only the courses of the current Dokeos access_url_id
			global $_configuration;
			if ($_configuration['multiple_access_urls']==true) {
				$url_access_id = api_get_current_access_url_id();
				if ($url_access_id !=-1) {
					$tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
							
					$sql = "SELECT 
					u.user_id AS col0,
					u.official_code   AS col1, 
					u.lastname  AS col2, 
					u.firstname AS col3, 
					u.email 	AS col4,
					u.active 	AS col5,
					u.user_id   AS col6
					FROM $user_table u
					LEFT JOIN $course_user_table cu on u.user_id = cu.user_id and course_code='".$_SESSION['_course']['id']."'
					INNER JOIN  $tbl_url_rel_user as url_rel_user 
					ON (url_rel_user.user_id = u.user_id) 
					WHERE cu.user_id IS NULL AND access_url_id= $url_access_id ";				
				}		
			}
		}	
	}
	if (isset ($_REQUEST['keyword'])) {
		$keyword = Database::escape_string($_REQUEST['keyword']);
		$sql .= " AND (firstname LIKE '%".$keyword."%' OR lastname LIKE '%".$keyword."%'   OR email LIKE '%".$keyword."%'  OR username LIKE '%".$keyword."%'  OR official_code LIKE '%".$keyword."%')";
	}
	$sql .= " ORDER BY col$column $direction ";
	$sql .= " LIMIT $from,$number_of_items";
	$res = api_sql_query($sql, __FILE__, __LINE__);
	$users = array ();
	while ($user = Database::fetch_row($res)) {
		$users[] = $user;
		$_SESSION['session_user_id'][]=$user[0];
		$_SESSION['session_user_name'][]=$user[3].' '.$user[2];		
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



// Build search-form
echo '<div class="actions">';

$actions .= '<a href="user.php">'.Display::return_icon('members.gif',get_lang('BackToUserList')).' '.get_lang('BackToUserList').'</a>';
if ($_POST['keyword'])
{
	$actions .= '<a href="subscribe_user.php?type='.Security::remove_XSS($_GET['type']).'">'.Display::return_icon('clean_group.gif').' '.get_lang('ClearSearchResults').'</a>';
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
$table = new SortableTable('users', 'get_number_of_users', 'get_user_data', 2);
$parameters['keyword'] = $_REQUEST['keyword'];
$parameters ['type'] = $_REQUEST['type']; 
$table->set_additional_parameters($parameters);
$col = 0;
$table->set_header($col ++, '', false);
$table->set_header($col ++, get_lang('OfficialCode'));
$table->set_header($col ++, get_lang('LastName'));
$table->set_header($col ++, get_lang('FirstName'));
$table->set_header($col ++, get_lang('Email'));
$table->set_column_filter($col -1, 'email_filter');
$table->set_header($col ++, get_lang('Active'),false);
$table->set_column_filter($col -1, 'active_filter');
$table->set_header($col ++, get_lang('reg'), false);
$table->set_column_filter($col -1, 'reg_filter');
$table->set_form_actions(array ('subscribe' => get_lang('reg')), 'user');

if ( !empty($_POST['keyword'])) {
	$keyword_name=Security::remove_XSS($_POST['keyword']);
	echo '<br/>'.get_lang('SearchResultsFor').' <span style="font-style: italic ;"> '.$keyword_name.' </span><br>';	
}

// Display table
$table->display();


/*
============================================================================== 
		FOOTER 
============================================================================== 
*/
Display :: display_footer();