<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
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
*	@package dokeos.user
==============================================================================
*/
/*
==============================================================================
	   INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included
$language_file = array('registration','admin','userInfo');
require_once ("../inc/global.inc.php");
$this_section = SECTION_COURSES;
/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/
require_once (api_get_path(LIBRARY_PATH)."debug.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."events.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."export.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."course.lib.php");
require_once (api_get_path(LIBRARY_PATH).'sortabletable.class.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');

//CHECK KEYS
 if( !isset ($_cid))
{
	header("location: ".$_configuration['root_web']);
}

/*
-----------------------------------------------------------
	Constants and variables
-----------------------------------------------------------
*/
$currentCourseID = $_course['sysCode'];


/*--------------------------------------
	Unregistering a user section
--------------------------------------
*/
if(api_is_allowed_to_edit())
{
	if(isset($_POST['action']))
	{
		switch($_POST['action'])
		{
			case 'unsubscribe' :
				// Make sure we don't unsubscribe current user from the course
				$user_ids = array_diff($_POST['user'],array($_user['user_id']));
				if(count($user_ids) > 0)
				{
					CourseManager::unsubscribe_user($user_ids, $_SESSION['_course']['sysCode']);
					$message = get_lang('UsersUnsubscribed');
				}
				break;
		}
	}
}

if(api_is_allowed_to_edit())
{

	if( isset ($_GET['action']))
	{
		switch ($_GET['action'])
		{
			case 'export' :
				if(api_get_setting('use_session_mode')!="true"){
					$table_user = Database::get_main_table(TABLE_MAIN_USER);
					$table_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
					$course = api_get_course_info();
					$sql = "SELECT official_code,firstname,lastname,email FROM $table_user u, $table_course_user cu WHERE cu.user_id = u.user_id AND cu.course_code = '".$course['sysCode']."' ORDER BY lastname ASC";
				}
				else
				{
					$sql = "SELECT `user`.`user_id`, `user`.`lastname`, `user`.`firstname`,
				                      `user`.`email`, `user`.`official_code`, session.name
				               FROM ".Database::get_main_table(TABLE_MAIN_USER)." `user`, ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)." cu, ".Database::get_main_table(TABLE_MAIN_SESSION)." session
				               WHERE `user`.`user_id`= cu.`id_user`
				               AND cu.`course_code`='".$currentCourseID."'
								AND session.id=cu.id_session
								AND session.id='".$_SESSION['id_session']."'";
				}
				$users = api_sql_query($sql, __FILE__, __LINE__);
				while ($user = mysql_fetch_array($users, MYSQL_ASSOC))
				{
					$data[] = $user;
				}
				switch ($_GET['type'])
				{
					case 'csv' :
						Export::export_table_csv($data);
					case 'xls' :
						Export::export_table_xls($data);
				}

		}
	}
} // end if allowed to edit

if(api_is_allowed_to_edit())
{
	// Unregister user from course
	if($_GET['unregister'])
	{
		if(isset($_GET['user_id']) && is_numeric($_GET['user_id']) && $_GET['user_id'] != $_user['user_id'])
		{
			CourseManager::unsubscribe_user($_GET['user_id'],$_SESSION['_course']['sysCode']);
			$message = get_lang('UserUnsubscribed');
		}
	}
} // end if allowed to edit


/*
==============================================================================
		FUNCTIONS
==============================================================================
*/

function display_user_search_form()
{
	echo '<form method="get" action="user.php">';
	echo get_lang("SearchForUser") . "&nbsp;&nbsp;";
	echo '<input type="text" name="keyword" value="'.$_GET['keyword'].'"/>';
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
function show_users_in_virtual_courses()
{
	global $_course, $_user;
	$real_course_code = $_course['sysCode'];
	$real_course_info = Database::get_course_info($real_course_code);
	$user_subscribed_virtual_course_list = CourseManager::get_list_of_virtual_courses_for_specific_user_and_real_course($_user['user_id'], $real_course_code);
	$number_of_virtual_courses = count($user_subscribed_virtual_course_list);
	$row = 0;
	$column_header[$row ++] = "ID";
	$column_header[$row ++] = get_lang("FullUserName");
	$column_header[$row ++] = get_lang("Role");
	$column_header[$row ++] = get_lang("Group");
	 if( api_is_allowed_to_edit())
	{
		$column_header[$row ++] = get_lang("Tutor");
	}
	 if( api_is_allowed_to_edit())
	{
		$column_header[$row ++] = get_lang("CourseManager");
	}
	//$column_header[$row++] = get_lang("Edit");
	//$column_header[$row++] = get_lang("Unreg");
	//$column_header[$row++] = get_lang("Tracking");
	 if( !is_array($user_subscribed_virtual_course_list))
		return;
	foreach ($user_subscribed_virtual_course_list as $virtual_course)
	{
		$virtual_course_code = $virtual_course["code"];
		$virtual_course_user_list = CourseManager::get_user_list_from_course_code($virtual_course_code);
		$message = get_lang("RegisteredInVirtualCourse")." ".$virtual_course["title"]."&nbsp;&nbsp;(".$virtual_course["code"].")";
		echo "<br/>";
		echo "<h4>".$message."</h4>";
		$properties["width"] = "100%";
		$properties["cellspacing"] = "1";
		Display::display_complex_table_header($properties, $column_header);
		foreach ($virtual_course_user_list as $this_user)
		{
			$user_id = $this_user["user_id"];
			$loginname = $this_user["username"];
			$lastname = $this_user["lastname"];
			$firstname = $this_user["firstname"];
			$status = $this_user["status"];
			$role = $this_user["role"];
			 if( $status == "1")
				$status = get_lang("CourseManager");
			else
				$status = " - ";
			//if(xxx['tutor'] == '0') $tutor = " - ";
			//else  $tutor = get_lang("Tutor");
			$full_name = $lastname.", ".$firstname;
			 if( $lastname == "" || $firstname == '')
				$full_name = $loginname;
			$user_info_hyperlink = "<a href=\"userInfo.php?".api_get_cidreq()."&origin=".$origin."&uInfo=".$user_id."&virtual_course=".$virtual_course["code"]."\">".$full_name."</a>";
			$row = 0;
			$table_row[$row ++] = $user_id;
			$table_row[$row ++] = $user_info_hyperlink; //Full name
			$table_row[$row ++] = $role; //Description
			$table_row[$row ++] = " - "; //Group, for the moment groups don't work for students in virtual courses
			 if( api_is_allowed_to_edit())
				$table_row[$row ++] = " - "; //Tutor column
			 if( api_is_allowed_to_edit())
				$table_row[$row ++] = $status; //Course Manager column
			Display::display_table_row($bgcolor, $table_row, true);
		}
		Display::display_table_footer();
	}
}

if(!$is_allowed_in_course){
	api_not_allowed(true);
}

/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/
 if( $origin != 'learnpath')
{
	if (isset($_GET['keyword']))
	{
		$interbreadcrumb[] = array ("url" => "user.php", "name" => get_lang("Users"));
		$tool_name = get_lang('SearchResults');
	}
	else
	{
		$tool_name = get_lang('Users');
	}


	Display::display_header($tool_name, "User");
}
else
{
?> <link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CODE_PATH); ?>css/default.css" /> <?php


}

if( isset($message))
{
	Display::display_normal_message($message);
}

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

//statistics
event_access_tool(TOOL_USER);
/*
--------------------------------------
	Setting the permissions for this page
--------------------------------------
*/
$is_allowed_to_track = ($is_courseAdmin || $is_courseTutor) && $_configuration['tracking_enabled'];
/*


/*
-----------------------------------------------------------
	Introduction section
	(editable by course admins)
-----------------------------------------------------------
*/
Display::display_introduction_section(TOOL_USER, $is_allowed);

 if( api_is_allowed_to_edit())
{
	echo "<div align=\"right\">";
	echo '<a href="user.php?'.api_get_cidreq().'&action=export&amp;type=csv">'.Display::return_icon('excel.gif', get_lang('ExportAsCSV')).'&nbsp;'.get_lang('ExportAsCSV').'</a> | ';
	echo '<a href="subscribe_user.php?'.api_get_cidreq().'">'.Display::return_icon('add_user_big.gif',get_lang("SubscribeUserToCourse")).'&nbsp;'.get_lang("SubscribeUserToCourse").'</a> | ';
	echo "<a href=\"subscribe_user.php?".api_get_cidreq()."&type=teacher\">".Display::return_icon('add_user_big.gif', get_lang("SubscribeUserToCourseAsTeacher"))."&nbsp;".get_lang("SubscribeUserToCourseAsTeacher")."</a> | ";
	echo "<a href=\"../group/group.php?".api_get_cidreq()."\">".Display::return_icon('edit_group.gif', get_lang("GroupUserManagement"))."&nbsp;".get_lang("GroupUserManagement")."</a>";
	if(api_get_setting('use_session_mode')=='false')
	{
		echo ' | <a href="class.php?'.api_get_cidreq().'">'.get_lang('Classes').'</a>';
	}
	echo "</div>";
}
/*
--------------------------------------
	DISPLAY USERS LIST
--------------------------------------
	Also shows a "next page" button if there are
	more than 50 users.

	There's a bug in here somewhere - some users count as more than one if they are in more than one group
	--> code for > 50 users should take this into account
	(Roan, Feb 2004)
*/
 if( CourseManager::has_virtual_courses_from_code($course_id, $user_id))
{
	$real_course_code = $_course['sysCode'];
	$real_course_info = Database::get_course_info($real_course_code);
	$message = get_lang("RegisteredInRealCourse")." ".$real_course_info["title"]."&nbsp;&nbsp;(".$real_course_info["official_code"].")";
	echo "<h4>".$message."</h4>";
}

/*
==============================================================================
		DISPLAY LIST OF USERS
==============================================================================
*/
/**
 *  * Get the users to display on the current page.
 */
function get_number_of_users()
{
	/*$user_table = Database::get_main_table(TABLE_MAIN_USER);
	$course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
	if(api_get_setting('use_session_mode')!="true"){
		$sql = "SELECT COUNT(u.user_id) AS number_of_users FROM $user_table u,$course_user_table cu WHERE u.user_id = cu.user_id and course_code='".$_SESSION['_course']['id']."'";
	}
	else{

		if(!empty($_SESSION['id_session'])){
			$session_course_user_table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
			$sql = "SELECT COUNT(DISTINCT id_user) AS number_of_users
					FROM $user_table u, $session_course_user_table
					WHERE course_code= '".$_SESSION['_course']['id']."'
					AND id_session='".$_SESSION['id_session']."'";
		}
		else{
			$sql = "SELECT COUNT(u.user_id) AS number_of_users FROM $user_table u,$course_user_table cu WHERE u.user_id = cu.user_id and course_code='".$_SESSION['_course']['id']."'";
		}
	}
	 if( isset ($_GET['keyword']))
	{
		$keyword = mysql_real_escape_string($_GET['keyword']);
		$sql .= " AND (firstname LIKE '%".$keyword."%' OR lastname LIKE '%".$keyword."%'  OR username LIKE '%".$keyword."%'  OR official_code LIKE '%".$keyword."%')";
	}
	$res = api_sql_query($sql, __FILE__, __LINE__);
	$result = mysql_fetch_object($res);
	return $result->number_of_users;*/
	$counter=0;

	if(!empty($_SESSION["id_session"])){
		$a_course_users = CourseManager :: get_user_list_from_course_code($_SESSION['_course']['id'], true, $_SESSION['id_session']);
	}
	else{
		$a_course_users = CourseManager :: get_user_list_from_course_code($_SESSION['_course']['id'], true);
	}

	foreach($a_course_users as $user_id=>$o_course_user){

		if( (isset ($_GET['keyword']) && search_keyword($o_course_user['firstname'],$o_course_user['lastname'],$o_course_user['username'],$o_course_user['official_code'],$_GET['keyword'])) || !isset($_GET['keyword']) || empty($_GET['keyword'])){
			$counter++;			
		}
	}

	return $counter;
	
}

function search_keyword($firstname,$lastname,$username,$official_code,$keyword){

	if(strripos($firstname,$keyword)!==false || strripos($lastname,$keyword)!==false || strripos($username,$keyword)!==false || strripos($official_code,$keyword)!==false){
		return true;
	}
	else{
		return false;
	}

}

function sort_users($a,$b){
	$a = trim(strtolower($a[$_GET['users_column']]));
	$b = trim(strtolower($b[$_GET['users_column']]));
	if($_GET['users_direction'] == 'DESC')
		return strcmp($b, $a);
	else
		return strcmp($a, $b);
}

/**
 * Get the users to display on the current page.
 */
function get_user_data($from, $number_of_items, $column, $direction)
{
	$a_users=array();

	if(!empty($_SESSION["id_session"])){
		$a_course_users = CourseManager :: get_user_list_from_course_code($_SESSION['_course']['id'], true, $_SESSION['id_session']);
	}
	else{
		$a_course_users = CourseManager :: get_user_list_from_course_code($_SESSION['_course']['id'], true);
	}

	foreach($a_course_users as $user_id=>$o_course_user){

		if( (isset ($_GET['keyword']) && search_keyword($o_course_user['firstname'],$o_course_user['lastname'],$o_course_user['username'],$o_course_user['official_code'],$_GET['keyword'])) || !isset($_GET['keyword']) || empty($_GET['keyword'])){

			$groups_name=GroupManager :: get_user_group_name($user_id);

			if(api_is_allowed_to_edit()){
				$temp=array();
				$temp[] = $user_id;
				$temp[] = $o_course_user['official_code'];
				$temp[] = $o_course_user['firstname'];
				$temp[] = $o_course_user['lastname'];
				$temp[] = $o_course_user['role'];
				$temp[] = implode(', ',$groups_name); //Group

				if(isset($o_course_user['tutor_id']) && $o_course_user['tutor_id']==1)
					$temp[] = get_lang('Tutor');
				else
					$temp[] = '-';
				if(isset($o_course_user['status']) && $o_course_user['status']==1)
					$temp[] = get_lang('CourseManager');
				else
					$temp[] = '-';

				$temp[] = $user_id;
			}
			else{
				$temp=array();
				$temp[] = $o_course_user['official_code'];
				$temp[] = $o_course_user['firstname'];
				$temp[] = $o_course_user['lastname'];
				$temp[] = $o_course_user['role'];
				$temp[] = implode(', ',$groups_name);//Group
				$temp[] = $user_id;
			}
			$a_users[$user_id] = $temp;
		}
	}
	usort($a_users, 'sort_users');
	return $a_users;
}


/**
 * Build the modify-column of the table
 * @param int $user_id The user id
 * @return string Some HTML-code
 */
function modify_filter($user_id)
{
	global $origin,$_user, $_course, $is_allowed_to_track;

	$result="<div style='text-align: center'>";

	// info
	$result .= '<a href="userInfo.php?'.api_get_cidreq().'&origin='.$origin.'&amp;uInfo='.$user_id.'"><img border="0" alt="'.get_lang('Info').'" src="../img/user_info.gif" /></a>&nbsp;';

	if($is_allowed_to_track)
	{
		$result .= '<a href="../mySpace/myStudents.php?'.api_get_cidreq().'&student='.$user_id.'&amp;details=true&amp;course='.$_course['id'].'&amp;origin=user_course"><img border="0" alt="'.get_lang('Tracking').'" src="../img/statistics.gif" /></a>&nbsp;';
	}

	if(api_is_allowed_to_edit())
	{

		// edit
		$result .= '<a href="userInfo.php?'.api_get_cidreq().'&origin='.$origin.'&amp;editMainUserInfo='.$user_id.'"><img border="0" alt="'.get_lang('Edit').'" src="../img/edit.gif" /></a>&nbsp;';
		// unregister
		 if( $user_id != $_user['user_id'])
		{
			$result .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&unregister=yes&amp;user_id='.$user_id.'&amp;'.$sort_params.'" onclick="javascript:if(!confirm(\''.addslashes(htmlentities(get_lang('ConfirmYourChoice'))).'\')) return false;"><img border="0" alt="'.get_lang("Unreg").'" src="../img/delete.gif"/></a>';
		}

	}
	$result.="</div>";
	return $result;
}
$default_column = api_is_allowed_to_edit() ? 1 : 0;
$table = new SortableTable('users', 'get_number_of_users', 'get_user_data',$default_column);
$parameters['keyword'] = $_GET['keyword'];
$table->set_additional_parameters($parameters);
$header_nr = 0;
 if( api_is_allowed_to_edit())
{
	$table->set_header($header_nr++, '', false);
}
$table->set_header($header_nr++, get_lang('OfficialCode'));
$table->set_header($header_nr++, get_lang('FirstName'));
$table->set_header($header_nr++, get_lang('LastName'));
$table->set_header($header_nr++, get_lang('Description'));
$table->set_header($header_nr++, get_lang('Group'),false);
 if( api_is_allowed_to_edit())
{
	$table->set_header($header_nr++, get_lang('Tutor'));
	$table->set_header($header_nr++, get_lang('CourseManager'));
}

//actions column
$table->set_header($header_nr++, '', false);
$table->set_column_filter($header_nr-1,'modify_filter');
 if( api_is_allowed_to_edit())
{
	$table->set_form_actions(array ('unsubscribe' => get_lang('Unreg')), 'user');
}

// Build search-form
$form = new FormValidator('search_user', 'get','','',null,false);
$renderer = & $form->defaultRenderer();
$renderer->setElementTemplate('<span>{element}</span> ');
$form->add_textfield('keyword', '', false);
$form->addElement('submit', 'submit', get_lang('SearchButton'));

$form->display();
echo '<br />';
$table->display();
 if( get_setting('allow_user_headings') == 'true' && $is_courseAdmin && api_is_allowed_to_edit() && $origin != 'learnpath') // only course administrators see this line
{
	echo "<div align=\"right\">", "<form method=\"post\" action=\"userInfo.php\">", get_lang("CourseAdministratorOnly"), " : ", "<input type=\"submit\" name=\"viewDefList\" value=\"".get_lang("DefineHeadings")."\" />", "</form>", "</div>\n";
}

//User list of the virtual courses linked to this course.
//show_users_in_virtual_courses($is_allowed_to_track);

/*
==============================================================================
		FOOTER
==============================================================================
*/
 if( $origin != 'learnpath')
{
	Display::display_footer();
}
?>