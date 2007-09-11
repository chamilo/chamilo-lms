<?php
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Elie harfouche (elie at harfdesign dot com)
	Copyright (c) Roan Embrechts, Vrije Universiteit Brussel
	Copyright (c) Wolfgang Schneider
	Copyright (c) Bart Mollet, Hogeschool Gent
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
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
$language_file = 'registration';
include ("../inc/global.inc.php");
$this_section = SECTION_COURSES;
if (!api_is_allowed_to_edit()) api_not_allowed(true);
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
//extra entries in breadcrumb
$interbreadcrumb[] = array ("url" => "user.php", "name" => get_lang("Users"));
Display :: display_header($tool_name, "User");

api_display_tool_title($tool_name);

/*
============================================================================== 
		MAIN SECTION
============================================================================== 
*/

if (isset ($_REQUEST['register']))
{
	if(isset($_REQUEST['type']) && $_REQUEST['type']=='teacher'){
		CourseManager :: subscribe_user($_REQUEST['user_id'], $_course['sysCode'],COURSEMANAGER);
	}
	else{
		CourseManager :: subscribe_user($_REQUEST['user_id'], $_course['sysCode']);
	}
}
if (isset ($_POST['action']))
{
	switch ($_POST['action'])
	{
		case 'subscribe' :
			if (is_array($_POST['user']))
			{
				foreach ($_POST['user'] as $index => $user_id)
				{
					CourseManager :: subscribe_user($user_id, $_course['sysCode']);
				}
			}
			break;
	}
}

/*
-----------------------------------------------------------
		SHOW LIST OF USERS
-----------------------------------------------------------
*/

/**
 *  * Get the users to display on the current page.
 */
function get_number_of_users()
{
	$user_table = Database :: get_main_table(TABLE_MAIN_USER);
	$course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
	if(isset($_REQUEST['type']) && $_REQUEST['type']=='teacher'){
		$sql = "SELECT 	u.user_id  
							FROM $user_table u
							LEFT JOIN $course_user_table cu on u.user_id = cu.user_id and course_code='".$_SESSION['_course']['id']."'
							WHERE cu.user_id IS NULL AND u.status='1'
							";
	}
	else{
		$sql = "SELECT 	u.user_id  
							FROM $user_table u
							LEFT JOIN $course_user_table cu on u.user_id = cu.user_id and course_code='".$_SESSION['_course']['id']."'
							WHERE cu.user_id IS NULL AND u.status='5'
							";
	}
	if (isset ($_REQUEST['keyword']))
	{
		$keyword = mysql_real_escape_string($_REQUEST['keyword']);
		$sql .= " AND (firstname LIKE '%".$keyword."%' OR lastname LIKE '%".$keyword."%'  OR username LIKE '%".$keyword."%'  OR official_code LIKE '%".$keyword."%')";
	}
	$res = api_sql_query($sql, __FILE__, __LINE__);
	$result = mysql_num_rows($res);
	return $result;
}
/**
 * Get the users to display on the current page.
 */
function get_user_data($from, $number_of_items, $column, $direction)
{
	$user_table = Database :: get_main_table(TABLE_MAIN_USER);
	$course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
	
	if(isset($_REQUEST['type']) && $_REQUEST['type']=='teacher'){
		$sql = "SELECT 
					u.user_id AS col0,
					u.official_code   AS col1, 
					u.lastname  AS col2, 
					u.firstname AS col3, 
					u.email 	AS col4,
					u.user_id   AS col5 
				FROM $user_table u
				LEFT JOIN $course_user_table cu on u.user_id = cu.user_id and course_code='".$_SESSION['_course']['id']."'
				WHERE u.status='1' and cu.user_id IS NULL
				";
	}
	else{
		$sql = "SELECT 
					u.user_id AS col0,
					u.official_code   AS col1, 
					u.lastname  AS col2, 
					u.firstname AS col3, 
					u.email 	AS col4,
					u.user_id   AS col5 
				FROM $user_table u
				LEFT JOIN $course_user_table cu on u.user_id = cu.user_id and course_code='".$_SESSION['_course']['id']."'
				WHERE u.status='5' and cu.user_id IS NULL
				";
	}
	if (isset ($_REQUEST['keyword']))
	{
		$keyword = mysql_real_escape_string($_REQUEST['keyword']);
		$sql .= " AND (firstname LIKE '%".$keyword."%' OR lastname LIKE '%".$keyword."%'  OR username LIKE '%".$keyword."%'  OR official_code LIKE '%".$keyword."%')";
	}
	$sql .= " ORDER BY col$column $direction ";
	$sql .= " LIMIT $from,$number_of_items";
	$res = api_sql_query($sql, __FILE__, __LINE__);
	$users = array ();
	while ($user = mysql_fetch_row($res))
	{
		$users[] = $user;
	}
	return $users;
}
/**
* Returns a mailto-link
* @param string $email An email-address
* @return string HTML-code with a mailto-link
*/
function email_filter($email)
{
	return Display :: encrypted_mailto_link($email, $email);
}
/**
 * Build the reg-column of the table
 * @param int $user_id The user id 
 * @return string Some HTML-code
 */
function reg_filter($user_id)
{
	if(isset($_REQUEST['type']) && $_REQUEST['type']=='teacher') $type='teacher'; else $type='student';
	$result = "<a href=\"".api_get_self()."?register=yes&amp;type=".$type."&amp;user_id=".$user_id."\">".get_lang("reg")."</a>";
	return $result;
}
// Build search-form
$form = new FormValidator('search_user', 'POST',api_get_self().'?type='.$_REQUEST['type'],'',null,false);
$renderer = & $form->defaultRenderer();
$renderer->setElementTemplate('<span>{element}</span> ');
$form->add_textfield('keyword', '', false);
$form->addElement('submit', 'submit', get_lang('SearchButton'));

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
$table->set_header($col ++, get_lang('reg'), false);
$table->set_column_filter($col -1, 'reg_filter');
$table->set_form_actions(array ('subscribe' => get_lang('reg')), 'user');

// Display form & table
$form->display();
echo '<br />';
$table->display();
/*
============================================================================== 
		FOOTER 
============================================================================== 
*/
Display :: display_footer();
?>