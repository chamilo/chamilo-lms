<?php
// $Id: user_portal.php,v 1.10 2006/08/19 09:33:14 yannoo Exp $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
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

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	This is the index file displayed when a user is logged in on Dokeos.
*
*	It displays:
*	- personal course list
*	- menu bar
*
*	Part of the what's new ideas were based on a rene haentjens hack
*
*	Search for
*	CONFIGURATION parameters
*	to modify settings
*
*	@todo rewrite code to separate display, logic, database code
*	@package dokeos.main
==============================================================================
*/

/**
 * @todo shouldn't the SCRIPTVAL_ and CONFVAL_ constant be moved to the config page? Has anybody any idea what the are used for? 
 * 		 if these are really configuration settings then we can add those to the dokeos config settings
 * @todo move get_personal_course_list and some other functions to a more appripriate place course.lib.php or user.lib.php
 * @todo use api_get_path instead of $rootAdminWeb
 * @todo check for duplication of functions with index.php (user_portal.php is orginally a copy of index.php)
 * @todo display_digest, shouldn't this be removed and be made into an extension? 
 */

/*
==============================================================================
	   INIT SECTION
==============================================================================
*/
// Don't change these settings
define('SCRIPTVAL_No', 0);
define('SCRIPTVAL_InCourseList', 1);
define('SCRIPTVAL_UnderCourseList', 2);
define('SCRIPTVAL_Both', 3);
define('SCRIPTVAL_NewEntriesOfTheDay', 4);
define('SCRIPTVAL_NewEntriesOfTheDayOfLastLogin', 5);
define('SCRIPTVAL_NoTimeLimit', 6);
// End 'don't change' section

$langFile = array ('courses', 'index');

$cidReset = true; /* Flag forcing the 'current course' reset,
                   as we're not inside a course anymore  */
/*
-----------------------------------------------------------
	Included libraries
-----------------------------------------------------------
*/
include_once ('./main/inc/global.inc.php');
include_once (api_get_path(LIBRARY_PATH).'course.lib.php');
include_once (api_get_path(LIBRARY_PATH).'debug.lib.inc.php');
include_once (api_get_path(LIBRARY_PATH).'system_announcements.lib.php');
include_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');

api_block_anonymous_users(); // only users who are logged in can proceed

/*
-----------------------------------------------------------
	Table definitions
-----------------------------------------------------------
*/
//Database table definitions
$main_user_table 		= Database :: get_main_table(TABLE_MAIN_USER);
$main_admin_table 		= Database :: get_main_table(TABLE_MAIN_ADMIN);
$main_course_table 		= Database :: get_main_table(TABLE_MAIN_COURSE);
$main_course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$main_category_table 	= Database :: get_main_table(TABLE_MAIN_CATEGORY);

/*
-----------------------------------------------------------
	Constants and CONFIGURATION parameters
-----------------------------------------------------------
*/
// ---- Course list options ----
define('CONFVAL_showCourseLangIfNotSameThatPlatform', TRUE);
// Preview of course content
// to disable all: set CONFVAL_maxTotalByCourse = 0
// to enable all: set e.g. CONFVAL_maxTotalByCourse = 5
// by default disabled since what's new icons are better (see function display_digest() )
define('CONFVAL_maxValvasByCourse', 2); // Maximum number of entries
define('CONFVAL_maxAgendaByCourse', 2); //  collected from each course
define('CONFVAL_maxTotalByCourse', 0); //  and displayed in summary.
define('CONFVAL_NB_CHAR_FROM_CONTENT', 80);
// Order to sort data
$orderKey = array('keyTools', 'keyTime', 'keyCourse'); // default "best" Choice
//$orderKey = array('keyTools', 'keyCourse', 'keyTime');
//$orderKey = array('keyCourse', 'keyTime', 'keyTools');
//$orderKey = array('keyCourse', 'keyTools', 'keyTime');
define('CONFVAL_showExtractInfo', SCRIPTVAL_UnderCourseList);
// SCRIPTVAL_InCourseList    // best choice if $orderKey[0] == 'keyCourse'
// SCRIPTVAL_UnderCourseList // best choice
// SCRIPTVAL_Both // probably only for debug
//define('CONFVAL_dateFormatForInfosFromCourses', get_lang('dateFormatShort'));
define('CONFVAL_dateFormatForInfosFromCourses', get_lang('dateFormatLong'));
//define("CONFVAL_limitPreviewTo",SCRIPTVAL_NewEntriesOfTheDay);
//define("CONFVAL_limitPreviewTo",SCRIPTVAL_NoTimeLimit);
define("CONFVAL_limitPreviewTo", SCRIPTVAL_NewEntriesOfTheDayOfLastLogin);


if(api_is_allowed_to_create_course() && !isset($_GET['sessionview'])){
	$nosession = true;
}
else {
	$nosession = false;
}



if(api_get_setting('use_session_mode')=='true' && !$nosession)
{
	if (isset($_GET['inactives'])){
		$display_actives = false;
	}
	else {
		$display_actives = true;
	}
}
$nameTools = get_lang('MyCourses');
$this_section = SECTION_COURSES;


/*
-----------------------------------------------------------
	Check configuration parameters integrity
-----------------------------------------------------------
*/
if (CONFVAL_showExtractInfo != SCRIPTVAL_UnderCourseList and $orderKey[0] != "keyCourse")
{
	// CONFVAL_showExtractInfo must be SCRIPTVAL_UnderCourseList to accept $orderKey[0] !="keyCourse"
	if (DEBUG || api_is_platform_admin()) // Show bug if admin. Else force a new order
		die('
					<strong>config error:'.__FILE__.'</strong><br />
					set
					<ul>
						<li>
							CONFVAL_showExtractInfo = SCRIPTVAL_UnderCourseList
							(actually : '.CONFVAL_showExtractInfo.')
						</li>
					</ul>
					or
					<ul>
						<li>
							$orderKey[0] != "keyCourse"
							(actually : '.$orderKey[0].')
						</li>
					</ul>');
	else
	{
		$orderKey = array ('keyCourse', 'keyTools', 'keyTime');
	}
}

/*
-----------------------------------------------------------
	Header
	include the HTTP, HTML headers plus the top banner
-----------------------------------------------------------
*/
Display :: display_header($nameTools);


/*
==============================================================================
		FUNCTIONS

		display_admin_links()
		display_create_course_link()
		display_edit_course_list_links()
		display_digest($toolsList, $digest, $orderKey, $courses)
		show_notification($my_course)

		get_personal_course_list($user_id)
		get_logged_user_course_html($my_course)
		get_user_course_categories()
==============================================================================
*/
/*
-----------------------------------------------------------
	Database functions
	some of these can go to database layer.
-----------------------------------------------------------
*/

/**
* Database function that gets the list of courses for a particular user.
* @param $user_id, the id of the user
* @return an array with courses
*/
function get_personal_course_list($user_id)
{
	// initialisation
	$personal_course_list = array();
	
	// table definitions
	$main_user_table 		= Database :: get_main_table(TABLE_MAIN_USER);
	$main_course_table 		= Database :: get_main_table(TABLE_MAIN_COURSE);
	$main_course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
	$tbl_session_course 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
	$tbl_session_course_user= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
	$tbl_session 			= Database :: get_main_table(TABLE_MAIN_SESSION);
	
	$personal_course_list = array ();
	
	$personal_course_list_sql = "SELECT course.code k, course.directory d, course.visual_code c, course.db_name db, course.title i,
										course.tutor_name t, course.course_language l, course_rel_user.status s, course_rel_user.sort sort,
										course_rel_user.user_course_cat user_course_cat
										FROM    ".$main_course_table."       course,".$main_course_user_table."   course_rel_user
										WHERE course.code = course_rel_user.course_code"."
										AND   course_rel_user.user_id = '".$user_id."'
										ORDER BY course_rel_user.user_course_cat, course_rel_user.sort ASC,course.title,course.code";
	$course_list_sql_result = api_sql_query($personal_course_list_sql, __FILE__, __LINE__);
	
	while ($result_row = mysql_fetch_array($course_list_sql_result))
	{
		$personal_course_list[] = $result_row;
	}
	
	//$personal_course_list = array_merge($personal_course_list, $course_list_sql_result);
	
	$personal_course_list_sql = "SELECT DISTINCT course.code k, course.directory d, course.visual_code c, course.db_name db, course.title i, course.tutor_name t, course.course_language l, 5 as s 
								FROM $main_course_table as course, $tbl_session_course_user as srcru 
							  	WHERE srcru.course_code=course.code AND srcru.id_user='$user_id'";
	
	$course_list_sql_result = api_sql_query($personal_course_list_sql, __FILE__, __LINE__);
	
	while ($result_row = mysql_fetch_array($course_list_sql_result))
	{
		$personal_course_list[] = $result_row;
	}
	
	//$personal_course_list = array_merge($personal_course_list, $course_list_sql_result);
	
	$personal_course_list_sql = "SELECT DISTINCT course.code k, course.directory d, course.visual_code c, course.db_name db, course.title i, course.tutor_name t, course.course_language l, 2 as s 
								FROM $main_course_table as course, $tbl_session_course as src, $tbl_session as session 
							  	WHERE session.id_coach='$user_id' AND session.id=src.id_session AND src.course_code=course.code";
	
	$course_list_sql_result = api_sql_query($personal_course_list_sql, __FILE__, __LINE__);
	
	//$personal_course_list = array_merge($personal_course_list, $course_list_sql_result);
	
	while ($result_row = mysql_fetch_array($course_list_sql_result))
	{
		$personal_course_list[] = $result_row;
	}
	return $personal_course_list;
}

/**
 * Enter description here...
 *
 * @param unknown_type $user_id
 * @param unknown_type $list_sessions
 * @return unknown
 * 
 */
function get_personal_session_course_list($user_id, $list_sessions)
{
	// Database Table Definitions
	$tbl_course 				= Database :: get_main_table(TABLE_MAIN_COURSE);
	$tbl_user 					= Database :: get_main_table(TABLE_MAIN_USER);
	$tbl_session 				= Database :: get_main_table(TABLE_MAIN_SESSION);
	$tbl_course_user 			= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
	$tbl_session_course 		= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
	$tbl_session_course_user 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
	$tbl_session_rel_user 		= Database :: get_main_table(TABLE_MAIN_SESSION_USER);

	// variable initialisation
	$personal_course_list_sql = '';
	$personal_course_list = array();

	// get the list of sessions where the user is subscribed as student
	$result=api_sql_query("SELECT DISTINCT id, name, date_start, date_end, 5 as s
							FROM session_rel_user, session
							WHERE id_session=id AND id_user=$user_id ORDER BY date_start, date_end, name",__FILE__,__LINE__);
	
	$Sessions=api_store_result($result);

	$Sessions = array_merge($Sessions , api_store_result($result));
	
	// get the list of sessions where the user is subscribed as coach in a course
	$result=api_sql_query("SELECT DISTINCT id, name, date_start, date_end, 2 as s
							FROM $tbl_session as session
							INNER JOIN $tbl_session_course as session_rel_course
								ON session_rel_course.id_coach = $user_id
							ORDER BY date_start, date_end, name",__FILE__,__LINE__);

	//global $sessionIsCoach;
	$sessionIsCoach = api_store_result($result);

	$Sessions = array_merge($Sessions , $sessionIsCoach);
	
	// get the list of sessions where the user is subscribed as coach
	$result=api_sql_query("SELECT DISTINCT id, name, date_start, date_end, 2 as s
							FROM $tbl_session as session
							WHERE session.id_coach = $user_id
							ORDER BY date_start, date_end, name",__FILE__,__LINE__);

	$Sessions = array_merge($Sessions , api_store_result($result));


	if(api_is_allowed_to_create_course())
	{
		foreach($Sessions as $enreg)
		{
			$id_session = $enreg['id'];
			$personal_course_list_sql = "SELECT DISTINCT course.code k, course.directory d, course.visual_code c, course.db_name db, course.title i, CONCAT(user.lastname,' ',user.firstname) t, email, course.course_language l, 1 sort, category_code user_course_cat, date_start, date_end, session.id as id_session, session.name as session_name
										 FROM $tbl_session_course as session_course
										 INNER JOIN $tbl_course AS course
										 	ON course.code = session_course.course_code
										 INNER JOIN $tbl_session as session
											ON session.id = session_course.id_session
										 LEFT JOIN $tbl_user as user
											ON user.user_id = session_course.id_coach
										 WHERE session_course.id_session = $id_session
										 AND (session_course.id_coach=$user_id OR session.id_coach=$user_id)
										ORDER BY i";
			
			$course_list_sql_result = api_sql_query($personal_course_list_sql, __FILE__, __LINE__);

			while ($result_row = mysql_fetch_array($course_list_sql_result))
			{
				$result_row['s'] = 2;
				$key = $result_row['id_session'].' - '.$result_row['k'];
				$personal_course_list[$key] = $result_row;
			}
		}

	}

	foreach($Sessions as $enreg)
	{
		$id_session = $enreg['id'];
		$personal_course_list_sql = "SELECT DISTINCT course.code k, course.directory d, course.visual_code c, course.db_name db, course.title i, CONCAT(user.lastname,' ',user.firstname) t, email, course.course_language l, 1 sort, category_code user_course_cat, date_start, date_end, session.id as id_session, session.name as session_name
									 FROM $tbl_session_course as session_course
									 INNER JOIN $tbl_course AS course
									 	ON course.code = session_course.course_code
									 LEFT JOIN $tbl_user as user
										ON user.user_id = session_course.id_coach
									 INNER JOIN $tbl_session_course_user
										ON $tbl_session_course_user.id_session = $id_session
										AND $tbl_session_course_user.id_user = $user_id
									INNER JOIN $tbl_session  as session
										ON session_course.id_session = session.id
									 WHERE session_course.id_session = $id_session
									 ORDER BY i";

		$course_list_sql_result = api_sql_query($personal_course_list_sql, __FILE__, __LINE__);

		while ($result_row = mysql_fetch_array($course_list_sql_result))
		{
			$key = $result_row['id_session'].' - '.$result_row['k'];
			$result_row['s'] = $enreg['s'];
			if(!isset($personal_course_list[$key]))	
			{
				$personal_course_list[$key] = $result_row;
			}
		}
	}

	return $personal_course_list;
}

/*
-----------------------------------------------------------
	Display functions
-----------------------------------------------------------
*/
/**
 * Warning: this function defines a global.
 * @todo use the correct get_path function
 */
function display_admin_links()
{
	global $rootAdminWeb;
	echo "<li><a href=\"".$rootAdminWeb."\">".get_lang("PlatformAdmin")."</a></li>";
}
/**
 * Enter description here...
 *
 */
function display_create_course_link()
{
	echo "<li><a href=\"main/create_course/add_course.php\">".get_lang("CourseCreate")."</a></li>";
}
/**
 * Enter description here...
 *
 */
function display_edit_course_list_links()
{
	echo "<li><a href=\"main/auth/courses.php\">".get_lang("CourseManagement")."</a></li>";
}

/**
*	Displays a digest e.g. short summary of new agenda and announcements items.
*	This used to be displayed in the right hand menu, but is now
*	disabled by default (see config settings in this file) because most people like
*	the what's new icons better.
*
*	@version 1.0
*/
function display_digest($toolsList, $digest, $orderKey, $courses)
{
	if (is_array($digest) && (CONFVAL_showExtractInfo == SCRIPTVAL_UnderCourseList || CONFVAL_showExtractInfo == SCRIPTVAL_Both))
	{
		// // // LEVEL 1 // // //
		reset($digest);
		echo "<br/><br/>\n";
		while (list ($key1) = each($digest))
		{
			if (is_array($digest[$key1]))
			{
				// // // Title of LEVEL 1 // // //
				echo "<b>\n";
				if ($orderKey[0] == 'keyTools')
				{
					$tools = $key1;
					echo $toolsList[$key1][name];
				}
				elseif ($orderKey[0] == 'keyCourse')
				{
					$courseSysCode = $key1;
					echo "<a href=\"", api_get_path(WEB_COURSE_PATH), $courses[$key1][coursePath], "\">", $courses[$key1][courseCode], "</a>\n";
				}
				elseif ($orderKey[0] == 'keyTime')
				{
					echo format_locale_date(CONFVAL_dateFormatForInfosFromCourses, strtotime($digest[$key1]));
				}
				echo "</b>\n";
				// // // End Of Title of LEVEL 1 // // //
				// // // LEVEL 2 // // //
				reset($digest[$key1]);
				while (list ($key2) = each($digest[$key1]))
				{
					// // // Title of LEVEL 2 // // //
					echo "<p>\n", "\n";
					if ($orderKey[1] == 'keyTools')
					{
						$tools = $key2;
						echo $toolsList[$key2][name];
					}
					elseif ($orderKey[1] == 'keyCourse')
					{
						$courseSysCode = $key2;
						echo "<a href=\"", api_get_path(WEB_COURSE_PATH), $courses[$key2]['coursePath'], "\">", $courses[$key2]['courseCode'], "</a>\n";
					}
					elseif ($orderKey[1] == 'keyTime')
					{
						echo format_locale_date(CONFVAL_dateFormatForInfosFromCourses, strtotime($key2));
					}
					echo "\n";
					echo "</p>";
					// // // End Of Title of LEVEL 2 // // //
					// // // LEVEL 3 // // //
					reset($digest[$key1][$key2]);
					while (list ($key3, $dataFromCourse) = each($digest[$key1][$key2]))
					{
						// // // Title of LEVEL 3 // // //
						if ($orderKey[2] == 'keyTools')
						{
							$level3title = "<a href=\"".$toolsList[$key3]["path"].$courseSysCode."\">".$toolsList[$key3]["name"]."</a>";
						}
						elseif ($orderKey[2] == 'keyCourse')
						{
							$level3title = "&#8226; <a href=\"".$toolsList[$tools]["path"].$key3."\">".$courses[$key3]['courseCode']."</a>\n";
						}
						elseif ($orderKey[2] == 'keyTime')
						{
							$level3title = "&#8226; <a href=\"".$toolsList[$tools]["path"].$courseSysCode."\">".format_locale_date(CONFVAL_dateFormatForInfosFromCourses, strtotime($key3))."</a>";
						}
						// // // End Of Title of LEVEL 3 // // //
						// // // LEVEL 4 (data) // // //
						reset($digest[$key1][$key2][$key3]);
						while (list ($key4, $dataFromCourse) = each($digest[$key1][$key2][$key3]))
						{
							echo $level3title, ' &ndash; ', substr(strip_tags($dataFromCourse), 0, CONFVAL_NB_CHAR_FROM_CONTENT);
							//adding ... (three dots) if the texts are too large and they are shortened
							if (strlen($dataFromCourse) >= CONFVAL_NB_CHAR_FROM_CONTENT)
							{
								echo '...';
							}
						}
						echo "<br/>\n";
					}
				}
			}
		}
	}
} // end function display_digest

/**
 * Display code for one specific course a logged in user is subscribed to.
 * Shows a link to the course, what's new icons...
 *
 * $my_course['d'] - course directory
 * $my_course['i'] - course title
 * $my_course['c'] - visual course code
 * $my_course['k']   - system course code
 * $my_course['db']  - course database
 *
 * @version 1.0.3
 * @todo refactor into different functions for database calls | logic | display
 * @todo replace single-character $my_course['d'] indices
 * @todo move code for what's new icons to a separate function to clear things up
 * @todo add a parameter user_id so that it is possible to show the courselist of other users (=generalisation). This will prevent having to write a new function for this. 
 */
function get_logged_user_course_html($my_course)
{
	global $nosession;
	
	if(api_get_setting('use_session_mode')=='true' && !$nosession)
	{
		global $now, $date_start, $date_end;
	}
	
	//initialise
	$result = '';
	
	// Table definitions
	//$statistic_database = Database::get_statistic_database();
	$course_tool_table 			= Database :: get_course_table(TOOL_LIST_TABLE, $course_database);
	$tool_edit_table 			= Database :: get_course_table(LAST_TOOL_EDIT_TABLE, $course_database);
	$course_group_user_table 	= Database :: get_course_table(TOOL_USER, $course_database);	
	
	$user_id = api_get_user_id();
	$course_database = $my_course['db'];
	$course_system_code = $my_course['k'];
	$course_visual_code = $my_course['c'];
	$course_title = $my_course['i'];
	$course_directory = $my_course['d'];
	$course_teacher = $my_course['t'];
	$course_info = Database :: get_course_info($course_system_code);
	$course_access_settings = CourseManager :: get_access_settings($course_system_code);
	$course_id = $course_info['course_id'];
	$course_visibility = $course_access_settings['visibility'];
	$user_in_course_status = CourseManager :: get_user_in_course_status(api_get_user_id(), $course_system_code);
	//function logic - act on the data
	$is_virtual_course = CourseManager :: is_virtual_course_from_system_code($my_course['c']);
	if ($is_virtual_course)
	{
		// If the current user is also subscribed in the real course to which this
		// virtual course is linked, we don't need to display the virtual course entry in
		// the course list - it is combined with the real course entry.
		$target_course_code = CourseManager :: get_target_of_linked_course($course_system_code);
		$is_subscribed_in_target_course = CourseManager :: is_user_subscribed_in_course(api_get_user_id(), $target_course_code);
		if ($is_subscribed_in_target_course)
		{
			return; //do not display this course entry
		}
	}
	$has_virtual_courses = CourseManager :: has_virtual_courses_from_code($course_system_code, api_get_user_id());
	if ($has_virtual_courses)
	{
		$return_result = CourseManager :: determine_course_title_from_course_info(api_get_user_id(), $course_info);
		$course_display_title = $return_result['title'];
		$course_display_code = $return_result['code'];
	}
	else
	{
		$course_display_title = $course_title;
		$course_display_code = $course_visual_code;
	}

	$s_course_status=$my_course["s"];

	$s_htlm_status_icon="";

	if($s_course_status==1){
		$s_htlm_status_icon="<img src='main/img/teachers.gif'>";
	}
	if($s_course_status==2){
		$s_htlm_status_icon="<img src='main/img/coachs.gif'>";
	}
	if($s_course_status==5){
		$s_htlm_status_icon="<img src='main/img/students.gif'>";
	}

	//display course entry
	$result.="\n\t";
	$result .= '<li style="list-style-type: none; margin-bottom: 5px;"><div style="border:0px solid #000; width: auto; float:left;padding-right: 5px;">'.$s_htlm_status_icon.'</div>';
	//show a hyperlink to the course, unless the course is closed and user is not course admin
	if ($course_visibility != COURSE_VISIBILITY_CLOSED || $user_in_course_status == COURSEMANAGER)
	{
		if(api_get_setting('use_session_mode')=='true' && !$nosession)
		{
			if(empty($my_course['id_session']))
			{
				$my_course['id_session'] = 0;
			}
			if($user_in_course_status == COURSEMANAGER || ($date_start <= $now && $date_end >= $now) || $date_start=='0000-00-00')
			{
				$result .= '<a href="'.api_get_path(WEB_COURSE_PATH).$course_directory.'/?id_session='.$my_course['id_session'].'">'.$course_display_title.'</a>';
			}
		}
		else 
		{
			$result .= '<a href="'.api_get_path(WEB_COURSE_PATH).$course_directory.'/">'.$course_display_title.'</a>';
		}
	}
	else
	{
		$result .= $course_display_title." "." ".get_lang("CourseClosed")."";
	}
	// show the course_code and teacher if chosen to display this
	if (get_setting("display_coursecode_in_courselist") == "true" OR get_setting("display_teacher_in_courselist") == "true")
	{
		$result .= "<br/>";
	}
	if (get_setting("display_coursecode_in_courselist") == "true")
	{
		$result .= $course_display_code;
	}
	if (get_setting("display_coursecode_in_courselist") == "true" AND get_setting("display_teacher_in_courselist") == "true")
	{
		$result .= ' &ndash; ';
	}
	if (get_setting("display_teacher_in_courselist") == "true")
	{
		$result .= $course_teacher;
	}
	// display the what's new icons
	$result .= show_notification($my_course);
	/*
	// get the user's last access dates to all tools of this course
	$sqlLastTrackInCourse = "SELECT * FROM $statistic_database.track_e_lastaccess"
							." WHERE access_user_id = ".$user_id
							." AND access_cours_code = '".$my_course['k']."'";
	$resLastTrackInCourse = api_sql_query($sqlLastTrackInCourse,__FILE__,__LINE__);
	while($lastTrackInCourse = mysql_fetch_array($resLastTrackInCourse))
	{
		$lastTrackInCourseDate[$lastTrackInCourse["access_tool"]] = $lastTrackInCourse["access_date"];
	}

	// get the last edits of all tools of this course
	$sql = "SELECT tooledit.lastedit_date last_date, tooledit.tool tool, tooledit.ref ref,
				tooledit.lastedit_type type, tooledit.to_group_id group_id,
				accueil.image image, accueil.link link
			FROM $tool_edit_table tooledit, $course_tool_table accueil
			WHERE accueil.name = tooledit.tool
			AND accueil.visibility = '1'
			AND tooledit.insert_user_id != $user_id
			AND (tooledit.to_user_id = '$user_id' OR tooledit.to_user_id = 0)";
	$res = api_sql_query($sql,__FILE__,__LINE__);

	$sql = "SELECT group_id FROM $course_group_user_table WHERE user_id = '$user_id'";
	$groupres = api_sql_query($sql,__FILE__,__LINE__);
	$groups = mysql_fetch_array($groupres);
	$groups[] = 0;

	//show icons of tools where there is something new

	while($lastToolEdit = mysql_fetch_array($res))
	{
		if ($lastTrackInCourseDate[$lastToolEdit["tool"]]<$lastToolEdit["last_date"] && in_array($lastToolEdit["group_id"], $groups))
		{
			$lastDate = date("d/m/Y H:i", convert_mysql_date($lastToolEdit["last_date"]));
			$type = ($lastToolEdit["type"]=="" || $lastToolEdit["type"]==NULL) ? get_lang('_new_item') : $lastToolEdit["type"];

			$result.= '<a href="'.api_get_path(WEB_CODE_PATH).$lastToolEdit['link'].'?cidReq='.$my_course['k'].'">'.
				'<img title="&mdash; '.$lastToolEdit['tool'].' &mdash; '.get_lang('_title_notification').": $type ($lastDate).\""
						.' src="'.api_get_path(WEB_IMG_PATH).$lastToolEdit['image'].'" border="0" align="middle" alt="'.$lastToolEdit['image'].'" /></a>';

		}
	}
	unset($lastTrackInCourseDate);
	unset($groups);*/
	if ((CONFVAL_showExtractInfo == SCRIPTVAL_InCourseList || CONFVAL_showExtractInfo == SCRIPTVAL_Both) && $nbDigestEntries > 0)
	{
		reset($digest);
		$result .= "<ul>";
		while (list ($key2) = each($digest[$thisCourseSysCode]))
		{
			$result .= "<li>";
			if ($orderKey[1] == 'keyTools')
			{
				$result .= "<a href=\"$toolsList[$key2] [\"path\"] $thisCourseSysCode \">";
				$result .= "$toolsList[$key2][\"name\"]</a>";
			}
			else
			{
				$result .= format_locale_date(CONFVAL_dateFormatForInfosFromCourses, strtotime($key2));
			}
			$result .= "</li>";
			$result .= "<ul>";
			reset($digest[$thisCourseSysCode][$key2]);
			while (list ($key3, $dataFromCourse) = each($digest[$thisCourseSysCode][$key2]))
			{
				$result .= "<li>";
				if ($orderKey[2] == 'keyTools')
				{
					$result .= "<a href=\"$toolsList[$key3] [\"path\"] $thisCourseSysCode \">";
					$result .= "$toolsList[$key3][\"name\"]</a>";
				}
				else
				{
					$result .= format_locale_date(CONFVAL_dateFormatForInfosFromCourses, strtotime($key3));
				}
				$result .= "<ul compact=\"compact\">";
				reset($digest[$thisCourseSysCode][$key2][$key3]);
				while (list ($key4, $dataFromCourse) = each($digest[$thisCourseSysCode][$key2][$key3]))
				{
					$result .= "<li>";
					$result .= htmlspecialchars(substr(strip_tags($dataFromCourse), 0, CONFVAL_NB_CHAR_FROM_CONTENT));
					$result .= "</li>";
				}
				$result .= "</ul>";
				$result .= "</li>";
			}
			$result .= "</ul>";
			$result .= "</li>";
		}
		$result .= "</ul>";
	}
	$result .= "</li>";


	if(api_get_setting('use_session_mode')=='true' && !$nosession)
	{
		if(!empty($my_course['session_name']))
		{
			$session = $my_course['session_name'];
			if($date_start=='0000-00-00')
			{
				$session .= ' - '.get_lang('Without time limits');
				$active = true;
			}
			else 
			{
				$session .= ' - '.get_lang('From').' '.$my_course['date_start'].' '.get_lang('To').' '.$my_course['date_end'];
				$active = ($date_start <= $now && $date_end >= $now)?true:false;
			}
		}
		$output = array ($my_course['user_course_cat'], $result, $my_course['id_session'], $session, 'active'=>$active);
	}
	else 
	{
		$output = array ($my_course['user_course_cat'], $result);
	}
	return $output;
}

/**
 * Returns the "what's new" icon notifications
 * @version
 */
function show_notification($my_course)
{
	$statistic_database = Database :: get_statistic_database();
	$user_id = api_get_user_id();
	$course_database = $my_course['db'];
	$course_tool_table = Database::get_course_table(TOOL_LIST_TABLE, $course_database);
	$tool_edit_table = Database::get_course_table(LAST_TOOL_EDIT_TABLE, $course_database);
	$course_group_user_table = Database :: get_course_table(GROUP_USER_TABLE, $course_database);
	// get the user's last access dates to all tools of this course
	$sqlLastTrackInCourse = "SELECT * FROM $statistic_database.track_e_lastaccess
									 USE INDEX (access_cours_code, access_user_id)
									 WHERE access_cours_code = '".$my_course['k']."'
									 AND access_user_id = '$user_id'";
	$resLastTrackInCourse = api_sql_query($sqlLastTrackInCourse, __FILE__, __LINE__);
	$oldestTrackDate = "3000-01-01 00:00:00";
	while ($lastTrackInCourse = mysql_fetch_array($resLastTrackInCourse))
	{
		$lastTrackInCourseDate[$lastTrackInCourse["access_tool"]] = $lastTrackInCourse["access_date"];
		if ($oldestTrackDate > $lastTrackInCourse["access_date"])
			$oldestTrackDate = $lastTrackInCourse["access_date"];
	}
	// get the last edits of all tools of this course
	$sql = "SELECT tet.*, tet.lastedit_date last_date, tet.tool tool, tet.ref ref,
						tet.lastedit_type type, tet.to_group_id group_id,
						ctt.image image, ctt.link link
					FROM $tool_edit_table tet, $course_tool_table ctt
					WHERE tet.lastedit_date > '$oldestTrackDate'
					AND ctt.name = tet.tool
					AND ctt.visibility = '1'
					AND tet.lastedit_user_id != $user_id
					ORDER BY tet.lastedit_date";
	$res = api_sql_query($sql);
	//get the group_id's with user membership
	$group_ids = GroupManager :: get_group_ids($course_database, $user_id);
	$groups_ids[] = 0; //add group 'everyone'
	//filter all selected items
	while ($res && ($item_property = mysql_fetch_array($res)))
	{
		if ((!isset ($lastTrackInCourseDate[$item_property['tool']]) || $lastTrackInCourseDate[$item_property['tool']] < $item_property['lastedit_date']) && (in_array($item_property['to_group_id'], $groups_ids) || $item_property['to_user_id'] == $user_id) && ($item_property['visibility'] == '1' || ($my_course['s'] == '1' && $item_property['visibility'] == '0') || !isset ($item_property['visibility'])))
		{
			$notifications[$item_property['tool']] = $item_property;
		}
	}
	//show all tool icons where there is something new
	$retvalue = '&nbsp;';
	if (isset ($notifications))
	{
		while (list ($key, $notification) = each($notifications))
		{
			$lastDate = date("d/m/Y H:i", convert_mysql_date($notification['lastedit_date']));
			$type = $notification['lastedit_type'];
			//$notification[image]=str_replace(".png","gif",$notification[image]);
			//$notification[image]=str_replace(".gif","_s.gif",$notification[image]);
			$retvalue .= '<a href="'.api_get_path(WEB_CODE_PATH).$notification['link'].'?cidReq='.$my_course['k'].'&amp;ref='.$notification['ref'].'">'.'<img title="-- '.get_lang($notification['tool']).' -- '.get_lang('_title_notification').": $type ($lastDate).\"".' src="'.api_get_path(WEB_CODE_PATH).'img/'.$notification['image'].'" border="0" align="middle" /></a>&nbsp;';
		}
	}
	return $retvalue;
}

/**
 * retrieves the user defined course categories
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @return array containing all the titles of the user defined courses with the id as key of the array
*/
function get_user_course_categories()
{
	global $_user;
	
	$table_category = Database::get_user_personal_table(USER_COURSE_CATEGORY_TABLE);
	$sql = "SELECT * FROM ".$table_category." WHERE user_id='".$_user['user_id']."'";
	$result = api_sql_query($sql,__FILE__,__LINE__);
	while ($row = mysql_fetch_array($result))
	{
		$output[$row['id']] = $row['title'];
	}
	return $output;
}

/*
==============================================================================
		MAIN CODE
==============================================================================
*/
/*
==============================================================================
		PERSONAL COURSE LIST
==============================================================================
*/
if (!isset ($maxValvas))
{
	$maxValvas = CONFVAL_maxValvasByCourse; // Maximum number of entries
}
if (!isset ($maxAgenda))
{
	$maxAgenda = CONFVAL_maxAgendaByCourse; //  collected from each course
}
if (!isset ($maxCourse))
{
	$maxCourse = CONFVAL_maxTotalByCourse; //  and displayed in summary.
}
$maxValvas = (int) $maxValvas;
$maxAgenda = (int) $maxAgenda;
$maxCourse = (int) $maxCourse; // 0 if invalid
if ($maxCourse > 0)
{
	unset ($allentries); // we shall collect all summary$key1 entries in here:
	$toolsList['agenda']['name'] = get_lang('Agenda');
	$toolsList['agenda']['path'] = api_get_path(WEB_CODE_PATH)."calendar/agenda.php?cidReq=";
	$toolsList['valvas']['name'] = get_lang('Valvas');
	$toolsList['valvas']['path'] = api_get_path(WEB_CODE_PATH)."announcements/announcements.php?cidReq=";
}

/*
-----------------------------------------------------------------------------
	Plugins for banner section
-----------------------------------------------------------------------------
*/


echo "<div class=\"maincontent\">"; // start of content for logged in users

// link to see the session view or course view
if(api_get_setting('use_session_mode')=='true' && api_is_allowed_to_create_course()) {
	if(isset($_GET['sessionview'])){
		echo '<a href="'.$_SERVER['PHP_SELF'].'">'.get_lang('CourseView').'</a>';
	}
	else {
		echo '<a href="'.$_SERVER['PHP_SELF'].'?sessionview=true">'.get_lang('SessionView').'</a>';
	}
}

/*
-----------------------------------------------------------------------------
	System Announcements
-----------------------------------------------------------------------------
*/
$announcement = $_GET['announcement'] ? $_GET['announcement'] : -1;
$visibility = api_is_allowed_to_create_course() ? VISIBLE_TEACHER : VISIBLE_STUDENT;
SystemAnnouncementManager :: display_announcements($visibility, $announcement);

if (!empty ($_GET['include']) && !strstr($_GET['include'], '/') && strstr($_GET['include'], '.html'))
{
	include ('./home/'.$_GET['include']);
	$pageIncluded = true;
}
else
{
	/*--------------------------------------
	              DISPLAY COURSES
	   --------------------------------------*/

	$list = '';

	if(api_get_setting('use_session_mode')=='true' && !$nosession)
	{
		$personal_course_list = get_personal_session_course_list($_user['user_id']);
	}
	else
	{
		$personal_course_list = get_personal_course_list($_user['user_id']);
	}
	foreach ($personal_course_list as $my_course)
	{
		$thisCourseDbName = $my_course['db'];
		$thisCourseSysCode = $my_course['k'];
		$thisCoursePublicCode = $my_course['c'];
		$thisCoursePath = $my_course['d'];
		$sys_course_path = api_get_path(SYS_COURSE_PATH);
		/*
		currently disabled functionality, should return
		$thisCoursePath = $sys_course_path . $thisCoursePath;
		if(! file_exists($thisCoursePath))
		{
		echo	"<li>".$my_course['i']."<br/>";
		echo "".get_lang("CourseDoesntExist")." (<a href=\"main/install/update_courses.php\">";
		echo	"".get_lang("GetCourseFromOldPortal")."</a>)</li>";

			continue;
		}*/
		$dbname = $my_course['k'];
		$status[$dbname] = $my_course['s'];

		$nbDigestEntries = 0; // number of entries already collected
		if ($maxCourse < $maxValvas)
			$maxValvas = $maxCourse;
		if ($maxCourse > 0)
		{
			$courses[$thisCourseSysCode]['coursePath'] = $thisCoursePath;
			$courses[$thisCourseSysCode]['courseCode'] = $thisCoursePublicCode;
		}
		/*
		-----------------------------------------------------------
			Announcements
		-----------------------------------------------------------
		*/
		$course_database = $my_course['db'];
		$course_tool_table = Database::get_course_table(TOOL_LIST_TABLE, $course_database);
		$query = "SELECT visibility FROM $course_tool_table WHERE link = 'announcements/announcements.php' AND visibility = 1";
		$result = api_sql_query($query);
		// collect from announcements, but only if tool is visible for the course
		if ($result && $maxValvas > 0 && mysql_num_rows($result) > 0)
		{
			//Search announcements table
			//Take the entries listed at the top of advalvas/announcements tool
			$course_announcement_table = Database :: get_course_announcement_table($thisCourseDbName);
			$sqlGetLastAnnouncements = "SELECT end_date publicationDate, content
									                            FROM ".$course_announcement_table;
			switch (CONFVAL_limitPreviewTo)
			{
				case SCRIPTVAL_NewEntriesOfTheDay :
					$sqlGetLastAnnouncements .= "WHERE DATE_FORMAT(end_date,'%Y %m %d') >= '".date("Y m d")."'";
					break;
				case SCRIPTVAL_NoTimeLimit :
					break;
				case SCRIPTVAL_NewEntriesOfTheDayOfLastLogin :
					// take care mysql -> DATE_FORMAT(time,format) php -> date(format,date)
					$sqlGetLastAnnouncements .= "WHERE DATE_FORMAT(end_date,'%Y %m %d') >= '".date("Y m d", $_user["lastLogin"])."'";
			}
			$sqlGetLastAnnouncements .= "ORDER BY end_date DESC
									                             LIMIT ".$maxValvas;
			$resGetLastAnnouncements = api_sql_query($sqlGetLastAnnouncements, __FILE__, __LINE__);
			if ($resGetLastAnnouncements)
			{
				while ($annoncement = mysql_fetch_array($resGetLastAnnouncements))
				{
					$keyTools = "valvas";
					$keyTime = $annoncement['publicationDate'];
					$keyCourse = $thisCourseSysCode;
					$digest[$$orderKey[0]][$$orderKey[1]][$$orderKey[2]][] = htmlspecialchars(substr(strip_tags($annoncement["content"]), 0, CONFVAL_NB_CHAR_FROM_CONTENT));
					$nbDigestEntries ++; // summary has same order as advalvas
				}
			}
		}
		/*
		-----------------------------------------------------------
			Agenda
		-----------------------------------------------------------
		*/
		$course_database = $my_course['db'];
		$course_tool_table = Database :: get_course_table(TOOL_LIST_TABLE,$course_database);
		$query = "SELECT visibility FROM $course_tool_table WHERE link = 'calendar/agenda.php' AND visibility = 1";
		$result = api_sql_query($query);
		$thisAgenda = $maxCourse - $nbDigestEntries; // new max entries for agenda
		if ($maxAgenda < $thisAgenda)
			$thisAgenda = $maxAgenda;
		// collect from agenda, but only if tool is visible for the course
		if ($result && $thisAgenda > 0 && mysql_num_rows($result) > 0)
		{
			$tableCal = $courseTablePrefix.$thisCourseDbName.$_configuration['db_glue']."calendar_event";
			$sqlGetNextAgendaEvent = "SELECT  start_date , title content, start_time
									                          FROM $tableCal
									                          WHERE start_date >= CURDATE()
									                          ORDER BY start_date, start_time
									                          LIMIT $maxAgenda";
			$resGetNextAgendaEvent = api_sql_query($sqlGetNextAgendaEvent, __FILE__, __LINE__);
			if ($resGetNextAgendaEvent)
			{
				while ($agendaEvent = mysql_fetch_array($resGetNextAgendaEvent))
				{
					$keyTools = 'agenda';
					$keyTime = $agendaEvent['start_date'];
					$keyCourse = $thisCourseSysCode;
					$digest[$$orderKey[0]][$$orderKey[1]][$$orderKey[2]][] = htmlspecialchars(substr(strip_tags($agendaEvent["content"]), 0, CONFVAL_NB_CHAR_FROM_CONTENT));
					$nbDigestEntries ++; // summary has same order as advalvas
				}
			}
		}
		/*
		-----------------------------------------------------------
			Digest Display
			take collected data and display it
		-----------------------------------------------------------
		*/
		$list[] = get_logged_user_course_html($my_course);
	} //end while mycourse...
}

if (is_array($list))
{
	if(api_get_setting('use_session_mode')=='true' && !$nosession)
	{
		$listActives = $listInactives = $listCourses = array();
		foreach($list as $key=>$value){
			if($value['active'])
				$listActives[] = $value;
			else if(!empty($value[2]))
				$listInactives[] = $value;
		}
		$old_user_category = 0;
		$userdefined_categories = get_user_course_categories();
		
		//Courses which belong to no sessions
		//echo "<ul style=\"line-height: 20px;\">\n\n\n\t<ul class=\"user_course_category\"><li>".get_lang("Courses_no_sessions")."</li></ul>\n</ul>";
		
		
		if(count($listActives)>0 && $display_actives){
			echo "<ul style=\"line-height: 20px;\">\n";

			foreach ($listActives as $key => $value)
			{
				if(!empty($value[2])){
					if($old_session != $value[2]){
						$old_session = $value[2];
						if($key != 0){
							echo "\n</ul>";
						}
						echo "\n\n\t<ul class=\"user_course_category\"><li>".$value[3]."</li></ul>\n";

						echo "<ul>";
					}
				}
				echo $value[1];

			}

			echo "\n</ul></ul><br /><br />\n";

		}

		if(count($listInactives)>0 && !$display_actives){
			echo "<ul style=\"line-height: 20px;\">";

			foreach ($listInactives as $key => $value)
			{
				if(!empty($value[2])){
					if($old_session != $value[2]){
						$old_session = $value[2];
						if($key != 0){
							echo "\n</ul>";
						}
					echo "\n\n\t<ul class=\"user_course_category\"><li>".$value[3]."</li></ul>\n";

					echo "<ul>";

					}
				}
				echo $value[1];

			}

			echo "\n</ul><br /><br />\n";
		}
	}
	else
	{
		$old_user_category = 0;
		$userdefined_categories = get_user_course_categories();
		echo "<ul>\n";
		foreach ($list as $key => $value)
		{
			if ($old_user_category<>$value[0])
			{
				if ($key<>0 OR $value[0]<>0) // there are courses in the previous category
				{
					echo "\n</ul>";
				}
				echo "\n\n\t<ul class=\"user_course_category\"><li>".$userdefined_categories[$value[0]]."</li></ul>\n";
				if ($key<>0 OR $value[0]<>0) // there are courses in the previous category
				{
					echo "<ul>";
				}
				$old_user_category=$value[0];

			}
			echo $value[1];

		}
		echo "\n</ul>\n";
	}
}
echo "</div>"; // end of content section
// Register whether full admin or null admin course
// by course through an array dbname x user status
api_session_register('status');

/*
==============================================================================
		RIGHT MENU
==============================================================================
*/
echo "<div class=\"menu\">";

// api_display_language_form(); // moved to the profile page.
echo "<div class=\"menusection\">";
echo "<span class=\"menusectioncaption\">".get_lang("MenuUser")."</span>";
echo "<ul class=\"menulist\">";

$display_add_course_link = api_is_allowed_to_create_course() && ($_SESSION["studentview"] != "studentenview");
if ($display_add_course_link)
	display_create_course_link();
display_edit_course_list_links();
display_digest($toolsList, $digest, $orderKey, $courses);

$navigation=array();
// Link to my profile
$navigation['myprofile']['url'] = api_get_path(WEB_CODE_PATH).'auth/profile.php'.(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '' );
$navigation['myprofile']['title'] = get_lang('ModifyProfile');
// Link to my agenda
$navigation['myagenda']['url'] = api_get_path(WEB_CODE_PATH).'calendar/myagenda.php'.(!empty($_course['path']) ? '?coursePath='.$_course['path'].'&amp;courseCode='.$_course['official_code'] : '' );
$navigation['myagenda']['title'] = get_lang('MyAgenda');

foreach($navigation as $section => $navigation_info)
{
	$current = ($section == $GLOBALS['this_section'] ? ' id="current"' : '');
	echo '<li'.$current.'>';
	echo '<a href="'.$navigation_info['url'].'" target="_top">'.$navigation_info['title'].'</a>';
	echo '</li>';
	echo "\n";
}

echo "</ul>";
echo "</div>";


/*
-----------------------------------------------------------------------------
	Plugins for banner section
-----------------------------------------------------------------------------
*/

if (is_array($_plugins['mycourses_menu'])){

	echo '<div class="note" style="background: none">';
	api_plugin('mycourses_menu');
	echo "</div>";
	
}
	
echo "</div>"; // end of menu

/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();
?>