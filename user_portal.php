<?php // $Id: user_portal.php 22375 2009-07-26 18:54:59Z herodoto $

/* For licensing terms, see /dokeos_license.txt */
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

// Language files that should be included
$language_file = array ('courses', 'index');

$cidReset = true; /* Flag forcing the 'current course' reset,
					as we're not inside a course anymore  */
/*
-----------------------------------------------------------
	Included libraries
-----------------------------------------------------------
*/
require_once './main/inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'course.lib.php';
require_once $libpath.'debug.lib.inc.php';
require_once $libpath.'system_announcements.lib.php';
require_once $libpath.'groupmanager.lib.php';
require_once $libpath.'usermanager.lib.php';
require_once 'main/survey/survey.lib.php';
require_once $libpath.'sessionmanager.lib.php';
api_block_anonymous_users(); // only users who are logged in can proceed

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.toggle.js" type="text/javascript" language="javascript"></script>';

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
define('CONFVAL_showCourseLangIfNotSameThatPlatform', true);
// Preview of course content
// to disable all: set CONFVAL_maxTotalByCourse = 0
// to enable all: set e.g. CONFVAL_maxTotalByCourse = 5
// by default disabled since what's new icons are better (see function display_digest() )
define('CONFVAL_maxValvasByCourse', 2); // Maximum number of entries
define('CONFVAL_maxAgendaByCourse', 2); // collected from each course
define('CONFVAL_maxTotalByCourse', 0); //  and displayed in summary.
define('CONFVAL_NB_CHAR_FROM_CONTENT', 80);
// Order to sort data
$orderKey = array('keyTools', 'keyTime', 'keyCourse'); // default "best" Choice
//$orderKey = array('keyTools', 'keyCourse', 'keyTime');
//$orderKey = array('keyCourse', 'keyTime', 'keyTools');
//$orderKey = array('keyCourse', 'keyTools', 'keyTime');
define('CONFVAL_showExtractInfo', SCRIPTVAL_UnderCourseList);
// SCRIPTVAL_InCourseList		// best choice if $orderKey[0] == 'keyCourse'
// SCRIPTVAL_UnderCourseList	// best choice
// SCRIPTVAL_Both // probably only for debug
//define('CONFVAL_dateFormatForInfosFromCourses', get_lang('dateFormatShort'));
define('CONFVAL_dateFormatForInfosFromCourses', get_lang('dateFormatLong'));
//define("CONFVAL_limitPreviewTo",SCRIPTVAL_NewEntriesOfTheDay);
//define("CONFVAL_limitPreviewTo",SCRIPTVAL_NoTimeLimit);
define("CONFVAL_limitPreviewTo", SCRIPTVAL_NewEntriesOfTheDayOfLastLogin);

// this is the main function to get the course list
$personal_course_list = UserManager::get_personal_session_course_list($_user['user_id']);

// check if a user is enrolled only in one course for going directly to the course after the login
if (api_get_setting('go_to_course_after_login') == 'true') {
	if (!isset($_SESSION['coursesAlreadyVisited']) && is_array($personal_course_list) && count($personal_course_list) == 1) {					 
				
		$key = array_keys($personal_course_list);
		$course_info = $personal_course_list[$key[0]]; 		
		
		$course_directory = $course_info['d'];
		$id_session = isset($course_info['id_session'])?$course_info['id_session']:0;	
		header('location:'.api_get_path(WEB_COURSE_PATH).$course_directory.'/?id_session='.$id_session);
		exit;
	} 
}

$nosession = false;

if (api_get_setting('use_session_mode') == 'true' && !$nosession) {
	$display_actives = !isset($_GET['inactives']);
}

$nameTools = get_lang('MyCourses');
$this_section = SECTION_COURSES;

/*
-----------------------------------------------------------
	Check configuration parameters integrity
-----------------------------------------------------------
*/
if (CONFVAL_showExtractInfo != SCRIPTVAL_UnderCourseList and $orderKey[0] != "keyCourse") {
	// CONFVAL_showExtractInfo must be SCRIPTVAL_UnderCourseList to accept $orderKey[0] !="keyCourse"
	if (DEBUG || api_is_platform_admin()){ // Show bug if admin. Else force a new order
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
	}else {
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
function get_personal_course_list($user_id) {
	// initialisation
	$personal_course_list = array();

	// table definitions
	$main_user_table 		= Database :: get_main_table(TABLE_MAIN_USER);
	$main_course_table 		= Database :: get_main_table(TABLE_MAIN_COURSE);
	$main_course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
	$tbl_session_course 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
	$tbl_session_course_user= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
	$tbl_session 			= Database :: get_main_table(TABLE_MAIN_SESSION);

	$user_id = Database::escape_string($user_id);
	$personal_course_list = array ();

	//Courses in which we suscribed out of any session
	$personal_course_list_sql = "SELECT course.code k, course.directory d, course.visual_code c, course.db_name db, course.title i,
										course.tutor_name t, course.course_language l, course_rel_user.status s, course_rel_user.sort sort,
										course_rel_user.user_course_cat user_course_cat
										FROM    ".$main_course_table."       course,".$main_course_user_table."   course_rel_user
										WHERE course.code = course_rel_user.course_code"."
										AND   course_rel_user.user_id = '".$user_id."'
										ORDER BY course_rel_user.user_course_cat, course_rel_user.sort ASC,i";

	$course_list_sql_result = Database::query($personal_course_list_sql, __FILE__, __LINE__);

	while ($result_row = Database::fetch_array($course_list_sql_result)) {
		$personal_course_list[] = $result_row;
	}

	//$personal_course_list = array_merge($personal_course_list, $course_list_sql_result);

	$personal_course_list_sql = "SELECT DISTINCT course.code k, course.directory d, course.visual_code c, course.db_name db, course.title i, course.tutor_name t, course.course_language l, 5 as s
								FROM $main_course_table as course, $tbl_session_course_user as srcru
								WHERE srcru.course_code=course.code AND srcru.id_user='$user_id'";

	$course_list_sql_result = Database::query($personal_course_list_sql, __FILE__, __LINE__);

	while ($result_row = Database::fetch_array($course_list_sql_result)) {
		$personal_course_list[] = $result_row;
	}

	//$personal_course_list = array_merge($personal_course_list, $course_list_sql_result);

	$personal_course_list_sql = "SELECT DISTINCT course.code k, course.directory d, course.visual_code c, course.db_name db, course.title i, course.tutor_name t, course.course_language l, 2 as s
								FROM $main_course_table as course, $tbl_session_course as src, $tbl_session as session
								WHERE session.id_coach='$user_id' AND session.id=src.id_session AND src.course_code=course.code";

	$course_list_sql_result = Database::query($personal_course_list_sql, __FILE__, __LINE__);

	//$personal_course_list = array_merge($personal_course_list, $course_list_sql_result);

	while ($result_row = Database::fetch_array($course_list_sql_result)) {
		$personal_course_list[] = $result_row;
	}
	return $personal_course_list;
}

/**
 *  display special courses
 *  @param int User id
 *  @return void
 */
function display_special_courses ($user_id) {

		$user_id = intval($user_id);
		$special_course_list = array();

		$tbl_course 				= Database::get_main_table(TABLE_MAIN_COURSE);
		$tbl_course_user 			= Database::get_main_table(TABLE_MAIN_COURSE_USER);
		$tbl_course_field 			= Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
		$tbl_course_field_value		= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
		$tbl_user_course_category   = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
	
		// get course list auto-register
		$sql = "SELECT course_code FROM $tbl_course_field_value tcfv INNER JOIN $tbl_course_field tcf ON " .
				" tcfv.field_id =  tcf.id WHERE tcf.field_variable = 'special_course' AND tcfv.field_value = 1 ";	

		$special_course_result = Database::query($sql, __FILE__, __LINE__);					
		if(Database::num_rows($special_course_result)>0) {
			$special_course_list = array();
			while ($result_row = Database::fetch_array($special_course_result)) {
				$special_course_list[] = '"'.$result_row['course_code'].'"';
			}
		}
		$with_special_courses = $without_special_courses = '';
		if (!empty($special_course_list)) {
			$with_special_courses = ' course.code IN ('.implode(',',$special_course_list).')';			
		}

		if (!empty($with_special_courses)) {
			$sql = "SELECT course.code, course.visual_code, course.subscribe subscr, course.unsubscribe unsubscr,
								course.title title, course.tutor_name tutor, course.db_name, course.directory, course_rel_user.status status,
								course_rel_user.sort sort, course_rel_user.user_course_cat user_course_cat, course_rel_user.user_id, course.visibility
		                        FROM $TABLECOURS course
		                        LEFT JOIN $TABLECOURSUSER course_rel_user ON course.code = course_rel_user.course_code AND course_rel_user.user_id = '$user_id'
		                        WHERE $with_special_courses";
																																									
			$rs_special_course = api_sql_query($sql, __FILE__, __LINE__);				
			$number_of_courses = Database::num_rows($rs_special_course);
			$key = 0;
			$status_icon = '';
			if ($number_of_courses > 0) {
				echo "<table width=\"100%\">";
				while ($course = Database::fetch_array($rs_special_course)) {
					
					// get notifications		
					$my_course = array();
					$my_course['db']	= $course['db_name'];
					$my_course['k']     = $course['code'];
					$my_course['id_session'] =	null;
					$my_course['s']		= $course['status'];		
					$show_notification = show_notification($my_course);

					if (empty($course['user_id'])) {
						$course['status'] = $user_info['status'];
					}
										
					if ($course['status'] == 1) {
						$status_icon=Display::return_icon('course.gif', get_lang('Course')).' '.Display::return_icon('teachers.gif', get_lang('Status').': '.get_lang('Teacher'),array('style'=>'width:11px; height:11px;'));
					}
					if (($course['status'] == 5 && !api_is_coach()) || empty($course['status'])) {
						$status_icon=Display::return_icon('course.gif', get_lang('Course')).' '.Display::return_icon('students.gif', get_lang('Status').': '.get_lang('Student'),array('style'=>'width:11px; height:11px'));
					} 
					$progress_thematic_icon = get_thematic_progress_icon($course['db_name']);
					echo "\t<tr>\n";
					echo "\t\t<td>\n";
					
					//show a hyperlink to the course, unless the course is closed and user is not course admin
					//$course_access_settings = CourseManager :: get_access_settings($course['code']);

					$course_visibility = $course['visibility'];
					if ($course_visibility != COURSE_VISIBILITY_CLOSED || $course['status'] == COURSEMANAGER) {			
						$course_title = '<a href="'.api_get_path(WEB_COURSE_PATH).$course['directory'].'/?id_session=0&amp;autoreg=1">'.$course['title'].'</a>';			
					} else {
						$course_title = $course['title']." ".get_lang('CourseClosed');
					}
										
					echo "<div style=\"float:left;margin-right:10px;\">".$status_icon."</div><span style=\"font-size:135%;\">".$course_title."</span>&nbsp;&nbsp;<span>$progress_thematic_icon</span><br />";					
					if (api_get_setting('display_coursecode_in_courselist') == 'true') {
						echo $course['visual_code'];
					}
					if (api_get_setting('display_coursecode_in_courselist') == 'true' && api_get_setting('display_teacher_in_courselist') == 'true') {
						echo " - ";
					}
					if (api_get_setting('display_teacher_in_courselist') == 'true') {
						echo $course['tutor'];
					}	
					echo '&nbsp;';
					echo Display::return_icon('klipper.png', get_lang('CourseAutoRegister'));
					
					// show notifications
					echo $show_notification;
									
					echo "\t\t</td>\n";
					echo "\t</tr>\n";
					$key++;
				}
				echo "</table>";
			}			
		}
}

/**
 *  display courses without special courses
 *  @param int User id
 *  @return void
 */
function display_courses($user_id) {

	global $_user, $_configuration;

	echo "<table width=\"100%\">\n";

	// building an array that contains all the id's of the user defined course categories
	// initially this was inside the display_courses_in_category function but when we do it here we have fewer
	// sql executions = performance increase.
	$all_user_categories = get_user_course_categories();

	// step 0: we display the course without a user category
	display_courses_in_category(0, 'true');

	// Step 1: We get all the categories of the user.
	$tucc = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
	$sql = "SELECT * FROM $tucc WHERE user_id='".$_user['user_id']."' ORDER BY sort ASC";
	$result = Database::query($sql, __FILE__, __LINE__);
	while ($row = Database::fetch_array($result)) {
		// We simply display the title of the category.
		echo "<tr><td colspan=\"2\" class=\"user_course_category\">";
		echo '<a name="category'.$row['id'].'"></a>'; // display an internal anchor.
		echo $row['title'];		
		echo "</td>";
		echo "</tr>";		
		display_courses_in_category($row['id']);
	}
	echo "</table>\n";
}

/**
 *  display courses in category without special courses
 *  @param int User category id
 *  @return void
 */
function display_courses_in_category($user_category_id) {
	global $_user;
	// table definitions
	$TABLECOURS						= Database::get_main_table(TABLE_MAIN_COURSE);
	$TABLECOURSUSER					= Database::get_main_table(TABLE_MAIN_COURSE_USER);
	$TABLE_USER_COURSE_CATEGORY 	= Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);	
	$TABLE_COURSE_FIELD 			= Database :: get_main_table(TABLE_MAIN_COURSE_FIELD);
	$TABLE_COURSE_FIELD_VALUE		= Database :: get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);

	// get course list auto-register
	$sql = "SELECT course_code FROM $TABLE_COURSE_FIELD_VALUE tcfv INNER JOIN $TABLE_COURSE_FIELD tcf ON " .
			" tcfv.field_id =  tcf.id WHERE tcf.field_variable = 'special_course' AND tcfv.field_value = 1 ";	
							
	$special_course_result = Database::query($sql, __FILE__, __LINE__);					
	if(Database::num_rows($special_course_result)>0) {
		$special_course_list = array();
		while ($result_row = Database::fetch_array($special_course_result)) {
			$special_course_list[] = '"'.$result_row['course_code'].'"';
		}
	}
	$without_special_courses = '';
	if (!empty($special_course_list)) {
		$without_special_courses = ' AND course.code NOT IN ('.implode(',',$special_course_list).')';			
	}
	

	$sql_select_courses = "SELECT course.code, course.visual_code, course.subscribe subscr, course.unsubscribe unsubscr,
								course.title title, course.tutor_name tutor, course.db_name, course.directory, course_rel_user.status status,
								course_rel_user.sort sort, course_rel_user.user_course_cat user_course_cat, course.visibility
		                        FROM    $TABLECOURS       course,
										$TABLECOURSUSER  course_rel_user
		                        WHERE course.code = course_rel_user.course_code
		                        AND   course_rel_user.user_id = '".$_user['user_id']."'
		                        AND course_rel_user.user_course_cat='".$user_category_id."' $without_special_courses
		                        ORDER BY course_rel_user.user_course_cat, course_rel_user.sort ASC";
	$result = Database::query($sql_select_courses,__FILE__,__LINE__);
	$number_of_courses = Database::num_rows($result);
	$key = 0;
	$status_icon = '';
	while ($course = Database::fetch_array($result)) {
				
		// get notifications		
		$my_course = array();
		$my_course['db']	= $course['db_name'];
		$my_course['k']     = $course['code'];
		$my_course['id_session'] =	null;
		$my_course['s']		= $course['status'];		
		$show_notification = show_notification($my_course); 

		// course list		
		if ($course['status'] == COURSEMANAGER) {
			$status_icon=Display::return_icon('course.gif', get_lang('Course')).' '.Display::return_icon('teachers.gif', get_lang('Status').': '.get_lang('Teacher'),array('style'=>'width:11px; height:11px;'));
		}
		if (($course['status'] == STUDENT && !api_is_coach()) || empty($course['status'])) {
			$status_icon=Display::return_icon('course.gif', get_lang('Course')).' '.Display::return_icon('students.gif', get_lang('Status').': '.get_lang('Student'),array('style'=>'width:11px; height:11px'));
		}				
		$progress_thematic_icon = get_thematic_progress_icon($course['db_name']);			
		echo "\t<tr>\n";
		echo "\t\t<td>\n";
				
		//function logic - act on the data
		$is_virtual_course = CourseManager :: is_virtual_course_from_system_code($course['code']);
		if ($is_virtual_course) {
			// If the current user is also subscribed in the real course to which this
			// virtual course is linked, we don't need to display the virtual course entry in
			// the course list - it is combined with the real course entry.
			$target_course_code = CourseManager :: get_target_of_linked_course($course['code']);
			$is_subscribed_in_target_course = CourseManager :: is_user_subscribed_in_course(api_get_user_id(), $target_course_code);
			if ($is_subscribed_in_target_course) {
				return; //do not display this course entry
			}
		}
		
		$has_virtual_courses = CourseManager :: has_virtual_courses_from_code($course['code'], api_get_user_id());
		if ($has_virtual_courses) {
			$course_info = api_get_course_info($course['code']);
			$return_result = CourseManager :: determine_course_title_from_course_info(api_get_user_id(), $course_info);
			$course_title = $return_result['title'];
			$course_display_code = $return_result['code'];
		} else {
			$course_title = $course['title'];
			$course_display_code = $course['visual_code'];
		}
		
		//show a hyperlink to the course, unless the course is closed and user is not course admin		
		$course_visibility = $course['visibility'];
		if ($course_visibility != COURSE_VISIBILITY_CLOSED || $course['status'] == COURSEMANAGER) {			
			$course_title = '<a href="'.api_get_path(WEB_COURSE_PATH).$course['directory'].'/?id_session=0">'.$course['title'].'</a>';			
		} else {
			$course_title = $course['title']." ".get_lang('CourseClosed');
		}
		
		
		echo "<div style=\"float:left;margin-right:10px;\">".$status_icon."</div><span style=\"font-size:135%;\">".$course_title."</span>&nbsp;&nbsp;<span>$progress_thematic_icon </span><br />";
		if (api_get_setting('display_coursecode_in_courselist') == 'true') {
			echo $course_display_code;
		}
		if (api_get_setting('display_coursecode_in_courselist') == 'true' && api_get_setting('display_teacher_in_courselist') == 'true') {
			echo " - ";
		}
		if (api_get_setting('display_teacher_in_courselist') == 'true') {
			echo $course['tutor'];
		}
		// show notifications		
		echo $show_notification;
		
		echo "\t\t</td>\n";
		echo "\t</tr>\n";
		$key++;
	}
}

/*
-----------------------------------------------------------
	Display functions
-----------------------------------------------------------
*/

/**
 * get icon showing thematic progress for a course
 * @param string 	Course database name
 * @param int		Session id (optional)
 * @return string   img html
 */
 function get_thematic_progress_icon($course_dbname,$session_id = 0) {
 	$tbl_course_description = Database::get_course_table(TABLE_COURSE_DESCRIPTION,$course_dbname);
 	$session_id = intval($session_id);
	$sql = "SELECT progress FROM $tbl_course_description WHERE description_type = 8 AND session_id = '$session_id' ";
	$rs  = Database::query($sql, __FILE__, __LINE__);
	$img = '';
	$title = '0%';
	$image = 'level_0.png';
	if (Database::num_rows($rs) > 0) {
		$row = Database::fetch_array($rs);		
		$progress = $row['progress'];
		$image = 'level_'.$progress.'.png';
		$title = $row['progress'].'%';	
	} 
	$img = Display::return_icon($image,get_lang('ThematicAdvance'),array('style'=>'vertical-align:middle')).'&nbsp;'.$title;			
	return $img;	
 }
		

/**
 * Warning: this function defines a global.
 * @todo use the correct get_path function
 */
function display_admin_links() {
	global $rootAdminWeb;
	echo "<li><a href=\"".$rootAdminWeb."\">".get_lang('PlatformAdmin')."</a></li>";
}

/**
 * Enter description here...
 *
 */
function display_create_course_link() {
	echo "<li><a href=\"main/create_course/add_course.php\">".get_lang('CourseCreate')."</a></li>";
}

/**
 * Enter description here...
 *
 */
function display_edit_course_list_links() {
	echo "<li><a href=\"main/auth/courses.php\">".get_lang('CourseManagement')."</a></li>";
}

/**
 * Show history sessions
 *
 */
function display_history_course_session() {
	if (api_get_setting('use_session_mode')=='true') {
		if (isset($_GET['history']) && intval($_GET['history']) == 1) {
			echo "<li><a href=\"user_portal.php\">".get_lang('DisplayTrainingList')."</a></li>";
		} else {
				echo "<li><a href=\"user_portal.php?history=1\">".get_lang('HistoryTrainingSessions')."</a></li>";
		}
	}
}

/**
*	Displays a digest e.g. short summary of new agenda and announcements items.
*	This used to be displayed in the right hand menu, but is now
*	disabled by default (see config settings in this file) because most people like
*	the what's new icons better.
*
*	@version 1.0
*/
function display_digest($toolsList, $digest, $orderKey, $courses) {
	if (is_array($digest) && (CONFVAL_showExtractInfo == SCRIPTVAL_UnderCourseList || CONFVAL_showExtractInfo == SCRIPTVAL_Both)) {
		// // // LEVEL 1 // // //
		reset($digest);
		echo "<br /><br />\n";
		while (list($key1) = each($digest)) {
			if (is_array($digest[$key1])) {
				// // // Title of LEVEL 1 // // //
				echo "<strong>\n";
				if ($orderKey[0] == 'keyTools') {
					$tools = $key1;
					echo $toolsList[$key1]['name'];
				} elseif ($orderKey[0] == 'keyCourse') {
					$courseSysCode = $key1;
					echo "<a href=\"", api_get_path(WEB_COURSE_PATH), $courses[$key1]['coursePath'], "\">", $courses[$key1]['courseCode'], "</a>\n";
				} elseif ($orderKey[0] == 'keyTime') {
					echo format_locale_date(CONFVAL_dateFormatForInfosFromCourses, strtotime($digest[$key1]));
				}
				echo "</strong>\n";
				// // // End Of Title of LEVEL 1 // // //
				// // // LEVEL 2 // // //
				reset($digest[$key1]);
				while (list ($key2) = each($digest[$key1])) {
					// // // Title of LEVEL 2 // // //
					echo "<p>\n", "\n";
					if ($orderKey[1] == 'keyTools') {
						$tools = $key2;
						echo $toolsList[$key2][name];
					} elseif ($orderKey[1] == 'keyCourse') {
						$courseSysCode = $key2;
						echo "<a href=\"", api_get_path(WEB_COURSE_PATH), $courses[$key2]['coursePath'], "\">", $courses[$key2]['courseCode'], "</a>\n";
					} elseif ($orderKey[1] == 'keyTime') {
						echo format_locale_date(CONFVAL_dateFormatForInfosFromCourses, strtotime($key2));
					}
					echo "\n";
					echo "</p>";
					// // // End Of Title of LEVEL 2 // // //
					// // // LEVEL 3 // // //
					reset($digest[$key1][$key2]);
					while (list ($key3, $dataFromCourse) = each($digest[$key1][$key2])) {
						// // // Title of LEVEL 3 // // //
						if ($orderKey[2] == 'keyTools') {
							$level3title = "<a href=\"".$toolsList[$key3]["path"].$courseSysCode."\">".$toolsList[$key3]['name']."</a>";
						} elseif ($orderKey[2] == 'keyCourse') {
							$level3title = "&#8226; <a href=\"".$toolsList[$tools]["path"].$key3."\">".$courses[$key3]['courseCode']."</a>\n";
						} elseif ($orderKey[2] == 'keyTime') {
							$level3title = "&#8226; <a href=\"".$toolsList[$tools]["path"].$courseSysCode."\">".format_locale_date(CONFVAL_dateFormatForInfosFromCourses, strtotime($key3))."</a>";
						}
						// // // End Of Title of LEVEL 3 // // //
						// // // LEVEL 4 (data) // // //
						reset($digest[$key1][$key2][$key3]);
						while (list ($key4, $dataFromCourse) = each($digest[$key1][$key2][$key3])) {
							echo $level3title, ' &ndash; ', api_substr(strip_tags($dataFromCourse), 0, CONFVAL_NB_CHAR_FROM_CONTENT);
							//adding ... (three dots) if the texts are too large and they are shortened
							if (api_strlen($dataFromCourse) >= CONFVAL_NB_CHAR_FROM_CONTENT) {
								echo '...';
							}
						}
						echo "<br />\n";
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
 * $my_course['k']  - system course code
 * $my_course['db'] - course database
 *
 * @version 1.0.3
 * @todo refactor into different functions for database calls | logic | display
 * @todo replace single-character $my_course['d'] indices
 * @todo move code for what's new icons to a separate function to clear things up
 * @todo add a parameter user_id so that it is possible to show the courselist of other users (=generalisation). This will prevent having to write a new function for this.
 */
function get_logged_user_course_html($course, $session_id = 0, $class='courses') {
	global $charset;
	global $nosession;

	$current_uid = api_get_user_id();
	$info = api_get_course_info($course['code']);
	$status_course = CourseManager::get_user_in_course_status($current_uid, $course['code']);

	if (!is_array($course['code'])) {
		$my_course = api_get_course_info($course['code']);
		$my_course['k'] = $my_course['id'];
		$my_course['db'] = $my_course['dbName'];
		$my_course['c'] = $my_course['official_code'];
		$my_course['i'] = $my_course['name'];
		$my_course['d'] = $my_course['path'];
		$my_course['t'] = $my_course['titular'];
		$my_course['id_session'] = $session_id;
		$my_course['status'] = ((empty($session_id))?$status_course:5);
	}

	if (api_get_setting('use_session_mode')=='true' && !$nosession) {
		global $now, $date_start, $date_end;
	}

	//initialise
	$result = '';

	// Table definitions
	//$statistic_database = Database::get_statistic_database();
	$main_user_table 		= Database :: get_main_table(TABLE_MAIN_USER);
	$tbl_session 			= Database :: get_main_table(TABLE_MAIN_SESSION);
	$tbl_session_category 	= Database :: get_main_table(TABLE_MAIN_SESSION_CATEGORY);
	$course_database = $my_course['db'];
	$course_tool_table 			= Database :: get_course_table(TABLE_TOOL_LIST, $course_database);
	$tool_edit_table 			= Database :: get_course_table(TABLE_ITEM_PROPERTY, $course_database);
	$course_group_user_table 	= Database :: get_course_table(TOOL_USER, $course_database);

	$user_id = api_get_user_id();
	$course_system_code = $my_course['k'];
	$course_visual_code = $my_course['c'];
	$course_title = $my_course['i'];
	$course_directory = $my_course['d'];
	$course_teacher = $my_course['t'];
	$course_teacher_email = isset($my_course['email'])?$my_course['email']:'';
	$course_info = Database :: get_course_info($course_system_code);
	$course_access_settings = CourseManager :: get_access_settings($course_system_code);
	$course_id = isset($course_info['course_id'])?$course_info['course_id']:null;
	$course_visibility = $course_access_settings['visibility'];

	$user_in_course_status = CourseManager :: get_user_in_course_status(api_get_user_id(), $course_system_code);

	//function logic - act on the data
	$is_virtual_course = CourseManager :: is_virtual_course_from_system_code($my_course['k']);
	if ($is_virtual_course) {
		// If the current user is also subscribed in the real course to which this
		// virtual course is linked, we don't need to display the virtual course entry in
		// the course list - it is combined with the real course entry.
		$target_course_code = CourseManager :: get_target_of_linked_course($course_system_code);
		$is_subscribed_in_target_course = CourseManager :: is_user_subscribed_in_course(api_get_user_id(), $target_course_code);
		if ($is_subscribed_in_target_course) {
			return; //do not display this course entry
		}
	}
	$has_virtual_courses = CourseManager :: has_virtual_courses_from_code($course_system_code, api_get_user_id());
	if ($has_virtual_courses) {
		$return_result = CourseManager :: determine_course_title_from_course_info(api_get_user_id(), $course_info);
		$course_display_title = $return_result['title'];
		$course_display_code = $return_result['code'];
	} else {
		$course_display_title = $course_title;
		$course_display_code = $course_visual_code;
	}

	$s_course_status = $my_course['status'];
	$is_coach = api_is_coach($my_course['id_session'],$course['code']);

	$s_htlm_status_icon = "";

	if ($s_course_status == 1) {
		$s_htlm_status_icon=Display::return_icon('course.gif', get_lang('Course')).' '.Display::return_icon('teachers.gif', get_lang('Status').': '.get_lang('Teacher'),array('style'=>'width:11px; height:11px'));
	}
	if ($s_course_status == 2 || ($is_coach && $s_course_status != 1)) {
		$s_htlm_status_icon=Display::return_icon('course.gif', get_lang('Course')).' '.Display::return_icon('coachs.gif', get_lang('Status').': '.get_lang('GeneralCoach'),array('style'=>'width:11px; height:11px'));		
	}
	if (($s_course_status == 5 && !$is_coach) || empty($s_course_status)) {
		$s_htlm_status_icon=Display::return_icon('course.gif', get_lang('Course')).' '.Display::return_icon('students.gif', get_lang('Status').': '.get_lang('Student'),array('style'=>'width:11px; height:11px'));
	}

	//display course entry
	$result.="\n\t";
	$result .= '<li class="'.$class.'"><div class="coursestatusicons">'.$s_htlm_status_icon.'</div>';
	//show a hyperlink to the course, unless the course is closed and user is not course admin
	if ($course_visibility != COURSE_VISIBILITY_CLOSED || $user_in_course_status == COURSEMANAGER) {
		if(api_get_setting('use_session_mode')=='true' && !$nosession) {
			if(empty($my_course['id_session'])) {
				$my_course['id_session'] = 0;
			}
			if($user_in_course_status == COURSEMANAGER || ($date_start <= $now && $date_end >= $now) || $date_start=='0000-00-00') {
				$result .= '<a href="'.api_get_path(WEB_COURSE_PATH).$course_directory.'/?id_session='.$my_course['id_session'].'">'.$course_display_title.'</a>';
			}
		} else {
			$result .= '<a href="'.api_get_path(WEB_COURSE_PATH).$course_directory.'/">'.$course_display_title.'</a>';
		}
	} else {
		$result .= $course_display_title." "." ".get_lang('CourseClosed')."";
	}
	$progress_thematic_icon = get_thematic_progress_icon($course_database,$session_id);
	$result .= '&nbsp;&nbsp;<span>'.$progress_thematic_icon.'</span>';			
	// show the course_code and teacher if chosen to display this
	if (api_get_setting('display_coursecode_in_courselist') == 'true' || api_get_setting('display_teacher_in_courselist') == 'true') {
		$result .= '<br />';
	}
	if (api_get_setting('display_coursecode_in_courselist') == 'true') {
		$result .= $course_display_code;
	}
	if (api_get_setting('display_coursecode_in_courselist') == 'true' && api_get_setting('display_teacher_in_courselist') == 'true') {
		$result .= ' &ndash; ';
	}
	if (api_get_setting('display_teacher_in_courselist') == 'true') {
		if (api_get_setting('use_session_mode')=='true' && !$nosession) {
			$coachs_course = api_get_coachs_from_course($my_course['id_session'],$course['code']);
			$course_coachs = array();
			if (is_array($coachs_course)) {
				foreach ($coachs_course as $coach_course) {
					$course_coachs[] = api_get_person_name($coach_course['firstname'], $coach_course['lastname']);
				}
			}

			if ($s_course_status == 1 || ($s_course_status == 5 && empty($my_course['id_session'])) || empty($s_course_status)) {
				$result .= $course_teacher;
			}
			
			if (($s_course_status == 5 && !empty($my_course['id_session'])) || ($is_coach && $s_course_status != 1)) {
				$result .= get_lang('Coachs').': '.implode(', ',$course_coachs);
			}


		} else {
			$result .= $course_teacher;
		}

		if(!empty($course_teacher_email)) {
			$result .= ' ('.$course_teacher_email.')';
		}
	}

	$result .= (isset($course['special_course']))? ' '.Display::return_icon('klipper.png', get_lang('CourseAutoRegister')) : '';
	
	$current_course_settings = CourseManager :: get_access_settings($my_course['k']);

	// display the what's new icons
	$result .= show_notification($my_course);

	if ((CONFVAL_showExtractInfo == SCRIPTVAL_InCourseList || CONFVAL_showExtractInfo == SCRIPTVAL_Both) && $nbDigestEntries > 0) {

		reset($digest);
		$result .= '
					<ul>';
		while (list ($key2) = each($digest[$thisCourseSysCode])) {
			$result .= '<li>';
			if ($orderKey[1] == 'keyTools') {
				$result .= "<a href=\"$toolsList[$key2] [\"path\"] $thisCourseSysCode \">";
				$result .= "$toolsList[$key2][\"name\"]</a>";
			} else {
				$result .= format_locale_date(CONFVAL_dateFormatForInfosFromCourses, strtotime($key2));
			}
			$result .= '</li>';
			$result .= '<ul>';
			reset ($digest[$thisCourseSysCode][$key2]);
			while (list ($key3, $dataFromCourse) = each($digest[$thisCourseSysCode][$key2])) {
				$result .= '<li>';
				if ($orderKey[2] == 'keyTools') {
					$result .= "<a href=\"$toolsList[$key3] [\"path\"] $thisCourseSysCode \">";
					$result .= "$toolsList[$key3][\"name\"]</a>";
				} else {
					$result .= format_locale_date(CONFVAL_dateFormatForInfosFromCourses, strtotime($key3));
				}
				$result .= '<ul compact="compact">';
				reset($digest[$thisCourseSysCode][$key2][$key3]);
				while (list ($key4, $dataFromCourse) = each($digest[$thisCourseSysCode][$key2][$key3])) {
					$result .= '<li>';
					$result .= htmlspecialchars(api_substr(strip_tags($dataFromCourse), 0, CONFVAL_NB_CHAR_FROM_CONTENT), ENT_QUOTES, $charset);
					$result .= '</li>';
				}
				$result .= '</ul>';
				$result .= '</li>';
			}
			$result .= '</ul>';
			$result .= '</li>';
		}
		$result .= '</ul>';
	}
	$result .= '</li>';
	
	if (api_get_setting('use_session_mode') == 'true' && !$nosession) {
		$session = '';
		$active = false;
		if (!empty($my_course['session_name'])) {

			// Request for the name of the general coach
			$sql = 'SELECT lastname, firstname,sc.name
			FROM '.$tbl_session.' ts
			LEFT JOIN '.$main_user_table .' tu
			ON ts.id_coach = tu.user_id
			INNER JOIN '.$tbl_session_category.' sc ON ts.session_category_id = sc.id
			WHERE ts.id='.(int) $my_course['id_session']. ' LIMIT 1';

			$rs = Database::query($sql, __FILE__, __LINE__);
			$sessioncoach = Database::store_result($rs);
			$sessioncoach = $sessioncoach[0];

			$session = array();
			$session['title'] = $my_course['session_name'];
			$session_category_id = CourseManager::get_session_category_id_by_session_id($my_course['id_session']);
			$session['category'] = $sessioncoach['name'];
			if ( $my_course['date_start']=='0000-00-00' ) {
				$session['dates'] = get_lang('WithoutTimeLimits');
				if (api_get_setting('show_session_coach') === 'true') {
					$session['coach'] = get_lang('GeneralCoach').': '.api_get_person_name($sessioncoach['firstname'], $sessioncoach['lastname']);
				}
				$active = true;
			} else {
				$session ['dates'] = ' - '.get_lang('From').' '.$my_course['date_start'].' '.get_lang('To').' '.$my_course['date_end'];
				if (api_get_setting('show_session_coach') === 'true') {
					$session['coach'] = get_lang('GeneralCoach').': '.api_get_person_name($sessioncoach['firstname'], $sessioncoach['lastname']);
				}
				$active = ($date_start <= $now && $date_end >= $now)?true:false;
			}
		}
		$output = array ($my_course['user_course_cat'], $result, $my_course['id_session'], $session, 'active'=>$active,'session_category_id'=>$session_category_id);
	} else {
		$output = array ($my_course['user_course_cat'], $result);
	}

	return $output;
}

/**
 * Get the session box details as an array
 * @param	int	Session ID
 * @return	array	Empty array or session array ['title'=>'...','category'=>'','dates'=>'...','coach'=>'...','active'=>true/false,'session_category_id'=>int]
 */
function get_session_title_box($session_id) {
	global $nosession;
	$output = array();
	if (api_get_setting('use_session_mode')=='true' && !$nosession) {
		$main_user_table 		= Database :: get_main_table(TABLE_MAIN_USER);
		$tbl_session 			= Database :: get_main_table(TABLE_MAIN_SESSION);
		$tbl_session_category 		= Database :: get_main_table(TABLE_MAIN_SESSION_CATEGORY);
		$active = false;
		// Request for the name of the general coach
		$sql =
		'SELECT tu.lastname, tu.firstname, ts.name, ts.date_start, ts.date_end, ts.session_category_id
		FROM '.$tbl_session.' ts
		LEFT JOIN '.$main_user_table .' tu
		ON ts.id_coach = tu.user_id
		WHERE ts.id='.intval($session_id);
		$rs = Database::query($sql, __FILE__, __LINE__);
		$session_info = Database::store_result($rs);
		$session_info = $session_info[0];
		$session = array();
		$session['title'] = $session_info[2];
		$session['coach'] = '';
		if ( $session_info[3]=='0000-00-00' ) {
			$session['dates'] = get_lang('WithoutTimeLimits');
			if ( api_get_setting('show_session_coach') === 'true' ) {
				$session['coach'] = get_lang('GeneralCoach').': '.api_get_person_name($session_info[1], $session_info[0]);
			}
			$active = true;
		} else {
			$session ['dates'] = get_lang('From').' '.$session_info[3].' '.get_lang('Until').' '.$session_info[4];
			if ( api_get_setting('show_session_coach') === 'true' ) {
				$session['coach'] = get_lang('GeneralCoach').': '.api_get_person_name($session_info[1], $session_info[0]);
			}
			$active = ($date_start <= $now && $date_end >= $now)?true:false;
		}
		$session['active'] = $active;
		$session['session_category_id'] = $session_info[5];
		$output = $session;
	}
	return $output;
}

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
function get_global_courses_list($user_id) {
	//1. get list of sessions the user is subscribed to
	//2. build an ordered list of session categories and sessions
	//3. fill this ordered list with course details
	global $charset;
	global $nosession;

	if (api_get_setting('use_session_mode')=='true' && !$nosession) {
		global $now, $date_start, $date_end;
	}

	$output = array();
	return $output;
}

/**
 * Returns the "what's new" icon notifications
 * @param	array	Course information array, containing at least elements 'db' and 'k'
 * @return	string	The HTML link to be shown next to the course
 * @version
 */
function show_notification($my_course) {
	$statistic_database = Database :: get_statistic_database();
	$user_id = api_get_user_id();
	$course_database = $my_course['db'];
	$course_tool_table = Database::get_course_table(TABLE_TOOL_LIST, $course_database);
	$tool_edit_table = Database::get_course_table(TABLE_ITEM_PROPERTY, $course_database);
	$course_group_user_table = Database :: get_course_table(TABLE_GROUP_USER, $course_database);
	$t_track_e_access = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
	// get the user's last access dates to all tools of this course
	$sqlLastTrackInCourse = "SELECT * FROM $t_track_e_access
									 USE INDEX (access_cours_code, access_user_id)
									 WHERE access_cours_code = '".$my_course['k']."'
									 AND access_user_id = '$user_id' AND access_session_id ='".$my_course['id_session']."'";
	$resLastTrackInCourse = Database::query($sqlLastTrackInCourse, __FILE__, __LINE__);
	$oldestTrackDate = "3000-01-01 00:00:00";
	while ($lastTrackInCourse = Database::fetch_array($resLastTrackInCourse)) {
		$lastTrackInCourseDate[$lastTrackInCourse['access_tool']] = $lastTrackInCourse['access_date'];
		if ($oldestTrackDate > $lastTrackInCourse['access_date']) {
			$oldestTrackDate = $lastTrackInCourse['access_date'];
		}
	}
	// get the last edits of all tools of this course
	$sql = "SELECT tet.*, tet.lastedit_date last_date, tet.tool tool, tet.ref ref,
						tet.lastedit_type type, tet.to_group_id group_id,
						ctt.image image, ctt.link link
					FROM $tool_edit_table tet, $course_tool_table ctt
					WHERE tet.lastedit_date > '$oldestTrackDate'
					AND ctt.name = tet.tool
					AND ctt.visibility = '1'
					AND tet.lastedit_user_id != $user_id AND tet.id_session = '".$my_course['id_session']."'
					ORDER BY tet.lastedit_date";

	$res = Database::query($sql);
	//get the group_id's with user membership
	$group_ids = GroupManager :: get_group_ids($course_database, $user_id);
	$group_ids[] = 0; //add group 'everyone'
	//filter all selected items
	while ($res && ($item_property = Database::fetch_array($res))) {
					
		if ((!isset ($lastTrackInCourseDate[$item_property['tool']]) || $lastTrackInCourseDate[$item_property['tool']] < $item_property['lastedit_date'])
			&& ((in_array($item_property['to_group_id'], $group_ids) && ($item_property['tool'] != TOOL_DROPBOX && $item_property['tool'] != TOOL_NOTEBOOK && $item_property['tool'] != TOOL_CHAT)))
			&& ($item_property['visibility'] == '1' || ($my_course['s'] == '1' && $item_property['visibility'] == '0') || !isset ($item_property['visibility']))) {

			if (($item_property['tool'] == TOOL_ANNOUNCEMENT || $item_property['tool'] == TOOL_CALENDAR_EVENT) && (($item_property['to_user_id'] != $user_id ) && (!isset($item_property['to_group_id']) || !in_array($item_property['to_group_id'],$group_ids)) )) continue;
						
			if ($item_property['tool'] == TOOL_SURVEY) {				
				$survey_info = survey_manager::get_survey($item_property['ref'],0,$my_course['k']);				
				$invited_users = SurveyUtil::get_invited_users($survey_info['code'],$course_database);												
				if (!in_array($user_id,$invited_users['course_users'])) continue;				
			}					
			$notifications[$item_property['tool']] = $item_property;						
		}
	}
	//show all tool icons where there is something new
	$retvalue = '&nbsp;';
	if (isset ($notifications)) {
		while (list ($key, $notification) = each($notifications)) {
			$lastDate = date('d/m/Y H:i', convert_mysql_date($notification['lastedit_date']));
			$type = $notification['lastedit_type'];
			//$notification[image]=str_replace(".png","gif",$notification[image]);
			//$notification[image]=str_replace(".gif","_s.gif",$notification[image]);
			if(empty($my_course['id_session'])) {
				$my_course['id_session'] = 0;
			}
			$retvalue .= '<a href="'.api_get_path(WEB_CODE_PATH).$notification['link'].'?cidReq='.$my_course['k'].'&amp;ref='.$notification['ref'].'&amp;gidReq='.$notification['to_group_id'].'&amp;id_session='.$my_course['id_session'].'">'.'<img title="-- '.get_lang(ucfirst($notification['tool'])).' -- '.get_lang('_title_notification').": ".get_lang($type)." ($lastDate).\"".' src="'.api_get_path(WEB_CODE_PATH).'img/'.$notification['image'].'" border="0" align="absbottom" /></a>&nbsp;';
		}
	}
	return $retvalue;
}

/**
 * retrieves the user defined course categories
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @return array containing all the titles of the user defined courses with the id as key of the array
*/
function get_user_course_categories() {
	global $_user;
	$output = array();
	$table_category = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
	$sql = "SELECT * FROM ".$table_category." WHERE user_id='".Database::escape_string($_user['user_id'])."'";
	$result = Database::query($sql,__FILE__,__LINE__);
	while ($row = Database::fetch_array($result)) {
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
if (!isset ($maxValvas)) {
	$maxValvas = CONFVAL_maxValvasByCourse; // Maximum number of entries
}
if (!isset ($maxAgenda)) {
	$maxAgenda = CONFVAL_maxAgendaByCourse; // collected from each course
}
if (!isset ($maxCourse)) {
	$maxCourse = CONFVAL_maxTotalByCourse; // and displayed in summary.
}
$maxValvas = (int) $maxValvas;
$maxAgenda = (int) $maxAgenda;
$maxCourse = (int) $maxCourse; // 0 if invalid
if ($maxCourse > 0) {
	unset ($allentries); // we shall collect all summary$key1 entries in here:
	$toolsList['agenda']['name'] = get_lang('Agenda');
	$toolsList['agenda']['path'] = api_get_path(WEB_CODE_PATH)."calendar/agenda.php?cidReq=";
	$toolsList['valvas']['name'] = get_lang('Valvas');
	$toolsList['valvas']['path'] = api_get_path(WEB_CODE_PATH)."announcements/announcements.php?cidReq=";
}

echo '	<div class="maincontent" id="maincontent">'; // start of content for logged in users
// Plugins for the my courses main area
api_plugin('mycourses_main');

/*
-----------------------------------------------------------------------------
	System Announcements
-----------------------------------------------------------------------------
*/
$announcement = isset($_GET['announcement']) ? $_GET['announcement'] : -1;
$visibility = api_is_allowed_to_create_course() ? VISIBLE_TEACHER : VISIBLE_STUDENT;
SystemAnnouncementManager :: display_announcements($visibility, $announcement);

if (!empty ($_GET['include']) && preg_match('/^[a-zA-Z0-9_-]*\.html$/',$_GET['include'])) {
	include ('./home/'.$_GET['include']);
	$pageIncluded = true;
} else {
	/*--------------------------------------
	              DISPLAY COURSES
	   --------------------------------------*/
	// compose a structured array of session categories, sessions and courses
	// for the current user
		
	if (isset($_GET['history']) && intval($_GET['history']) == 1) {
		$courses_tree = UserManager::get_sessions_by_category($_user['user_id'],true,true);
	} else {
		$courses_tree = UserManager::get_sessions_by_category($_user['user_id'],true);
	}
	foreach ($courses_tree as $cat => $sessions) {
		$courses_tree[$cat]['details'] = SessionManager::get_session_category($cat);
		if ($cat == 0) {
			$courses_tree[$cat]['courses'] = CourseManager::get_courses_list_by_user_id($_user['user_id'],false);											
		}
		$courses_tree[$cat]['sessions'] = array_flip(array_flip($sessions));
		if (count($courses_tree[$cat]['sessions'])>0) {
			foreach ($courses_tree[$cat]['sessions'] as $k => $s_id) {
				$courses_tree[$cat]['sessions'][$k] = array('details' => SessionManager::fetch($s_id));
				$courses_tree[$cat]['sessions'][$k]['courses'] = UserManager::get_courses_list_by_session($_user['user_id'],$s_id);
			}
		}
	}

	$list = '';		
	foreach ($personal_course_list as $my_course) {
		$thisCourseDbName = $my_course['db'];
		$thisCourseSysCode = $my_course['k'];
		$thisCoursePublicCode = $my_course['c'];
		$thisCoursePath = $my_course['d'];
		$sys_course_path = api_get_path(SYS_COURSE_PATH);
		$dbname = $my_course['k'];
		$status[$dbname] = $my_course['s'];

		$nbDigestEntries = 0; // number of entries already collected
		if ($maxCourse < $maxValvas) {
			$maxValvas = $maxCourse;
		}
		if ($maxCourse > 0) {
			$courses[$thisCourseSysCode]['coursePath'] = $thisCoursePath;
			$courses[$thisCourseSysCode]['courseCode'] = $thisCoursePublicCode;
		}
		/*
		-----------------------------------------------------------
			Announcements
		-----------------------------------------------------------
		*/
		$course_database = $my_course['db'];
		$course_tool_table = Database::get_course_table(TABLE_TOOL_LIST, $course_database);
		$query = "SELECT visibility FROM $course_tool_table WHERE link = 'announcements/announcements.php' AND visibility = 1";
		$result = Database::query($query);
		// collect from announcements, but only if tool is visible for the course
		if ($result && $maxValvas > 0 && Database::num_rows($result) > 0) {
			//Search announcements table
			//Take the entries listed at the top of advalvas/announcements tool
			$course_announcement_table = Database::get_course_table(TABLE_ANNOUNCEMENT);
			$sqlGetLastAnnouncements = "SELECT end_date publicationDate, content
											FROM ".$course_announcement_table;
			switch (CONFVAL_limitPreviewTo) {
				case SCRIPTVAL_NewEntriesOfTheDay :
					$sqlGetLastAnnouncements .= "WHERE DATE_FORMAT(end_date,'%Y %m %d') >= '".date("Y m d")."'";
					break;
				case SCRIPTVAL_NoTimeLimit :
					break;
				case SCRIPTVAL_NewEntriesOfTheDayOfLastLogin :
					// take care mysql -> DATE_FORMAT(time,format) php -> date(format,date)
					$sqlGetLastAnnouncements .= "WHERE DATE_FORMAT(end_date,'%Y %m %d') >= '".date("Y m d", $_user["lastLogin"])."'";
			}
			$sqlGetLastAnnouncements .= "ORDER BY end_date DESC LIMIT ".$maxValvas;
			$resGetLastAnnouncements = Database::query($sqlGetLastAnnouncements, __FILE__, __LINE__);
			if ($resGetLastAnnouncements) {
				while ($annoncement = Database::fetch_array($resGetLastAnnouncements)) {
					$keyTools = 'valvas';
					$keyTime = $annoncement['publicationDate'];
					$keyCourse = $thisCourseSysCode;
					$digest[$$orderKey[0]][$$orderKey[1]][$$orderKey[2]][] = htmlspecialchars(api_substr(strip_tags($annoncement["content"]), 0, CONFVAL_NB_CHAR_FROM_CONTENT), ENT_QUOTES, $charset);
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
		$course_tool_table = Database :: get_course_table(TABLE_TOOL_LIST,$course_database);
		$query = "SELECT visibility FROM $course_tool_table WHERE link = 'calendar/agenda.php' AND visibility = 1";
		$result = Database::query($query);
		$thisAgenda = $maxCourse - $nbDigestEntries; // new max entries for agenda
		if ($maxAgenda < $thisAgenda) {
			$thisAgenda = $maxAgenda;
		}
		// collect from agenda, but only if tool is visible for the course
		if ($result && $thisAgenda > 0 && Database::num_rows($result) > 0) {
			$tableCal = $courseTablePrefix.$thisCourseDbName.$_configuration['db_glue']."calendar_event";
			$sqlGetNextAgendaEvent = "SELECT start_date, title content, start_time
											FROM $tableCal
											WHERE start_date >= CURDATE()
											ORDER BY start_date, start_time
											LIMIT $maxAgenda";
			$resGetNextAgendaEvent = Database::query($sqlGetNextAgendaEvent, __FILE__, __LINE__);
			if ($resGetNextAgendaEvent) {
				while ($agendaEvent = Database::fetch_array($resGetNextAgendaEvent)) {
					$keyTools = 'agenda';
					$keyTime = $agendaEvent['start_date'];
					$keyCourse = $thisCourseSysCode;
					$digest[$$orderKey[0]][$$orderKey[1]][$$orderKey[2]][] = htmlspecialchars(api_substr(strip_tags($agendaEvent["content"]), 0, CONFVAL_NB_CHAR_FROM_CONTENT), ENT_QUOTES, $charset);
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
		//$list[] = get_logged_user_course_html($my_course);
	} //end while mycourse...
}

if (isset($_GET['history']) && intval($_GET['history']) == 1) {
	echo '<h3>'.get_lang('HistoryTrainingSession').'</h3>';	
	if (empty($courses_tree[0]['sessions'])){
		echo get_lang('YouDoNotHaveAnySessionInItsHistory');
	}
}

if ( is_array($courses_tree) ) {

	foreach ($courses_tree as $key => $category) {


		if ($key == 0) {

		// sessions and courses that are not in a session category

			if (!isset($_GET['history'])) { // check if it's not history trainnign session list
				display_special_courses(api_get_user_id());
				display_courses(api_get_user_id());
			}
			//independent sessions
			foreach ($category['sessions'] as $session) {

				//don't show empty sessions
				if (count($session['courses'])<1) { continue; }	
										
				//courses inside the current session
				$date_session_start = $session['details']['date_start'];
				$days_access_before_beginning  = ($session['details']['nb_days_access_before_beginning'])*24*3600;
				$session_now = time();
				$html_courses_session = '';
				$count_courses_session = 0;
				foreach ($session['courses'] as $course) {
					$is_coach_course = api_is_coach($session['details']['id'],$course['code']); 					
					if ($is_coach_course) {
						$allowed_time = (strtotime($date_session_start)-$days_access_before_beginning);						 
					} else {
						$allowed_time = strtotime($date_session_start);
					}
					if ($session_now > $allowed_time) {
						$c = get_logged_user_course_html($course, $session['details']['id'], 'session_course_item');
						$html_courses_session .= $c[1];
						$count_courses_session++;		
					}
				}
							
				if ($count_courses_session > 0) {
					echo '<ul class="session_box">';
						echo '<li class="session_box_title" id="session_'.$session['details']['id'].'" >';
						echo Display::return_icon('div_hide.gif', get_lang('Expand').'/'.get_lang('Hide'), array('align' => 'absmiddle', 'id' => 'session_img_'.$session['details']['id'])) . ' ';
						$s = get_session_title_box($session['details']['id']);
						echo get_lang('SessionName') . ': ' . $s['title']. ' - '.(!empty($s['coach'])?$s['coach'].' - ':'').$s['dates'];
						echo '</li>';
					    echo $html_courses_session;
					echo '</ul>';
				}											
			}

		} else {
							
			// all sessions included in
			if (!empty($category['details'])) {
				$count_courses_session = 0;
				$html_sessions = '';
				foreach ($category['sessions'] as $session) {
					//don't show empty sessions
					if (count($session['courses'])<1) { continue; }					
					$date_session_start = $session['details']['date_start'];
					$days_access_before_beginning  = ($session['details']['nb_days_access_before_beginning'])*24*3600;
					$session_now = time();
					$html_courses_session = '';
					$count = 0;
					foreach ($session['courses'] as $course) {
						$is_coach_course = api_is_coach($session['details']['id'],$course['code']); 					
						if ($is_coach_course) {
							$allowed_time = (strtotime($date_session_start)-$days_access_before_beginning);						 
						} else {
							$allowed_time = strtotime($date_session_start);
						}
						if ($session_now > $allowed_time) {
							$c = get_logged_user_course_html($course, $session['details']['id'], 'session_course_item');
							$html_courses_session .= $c[1];
							$count_courses_session++;
							$count++;		
						}	
					}	

					if ($count > 0) {
						$s = get_session_title_box($session['details']['id']);
						$html_sessions .= '<ul class="session_box" id="session_'.$session['details']['id'].'">';
						$html_sessions .= '<li class="session_box_title" id="session_'.$session['details']['id'].'">';
						$html_sessions .= Display::return_icon('div_hide.gif', get_lang('Expand').'/'.get_lang('Hide'), array('align' => 'absmiddle', 'id' => 'session_img_'.$session['details']['id'])) . ' ';					
						$html_sessions .= get_lang('SessionName') . ': ' . $s['title']. ' - '.(!empty($s['coach'])?$s['coach'].' - ':'').$s['dates'];
						$html_sessions .= '</li>';
						$html_sessions .= $html_courses_session;
						$html_sessions .= '</ul>';
					}								
				}
					
				if ($count_courses_session > 0) {
					echo '<div class="session_category" id="session_category_'.$category['details']['id'].'" style="background-color:#fbfbfb; border:1px solid #dddddd; padding:5px; margin-top: 10px;">';
					echo '<div class="session_category_title_box" id="session_category_title_box_'.$category['details']['id'].'" style="font-size:larger; color: #555555;">'. Display::return_icon('div_hide.gif', get_lang('Expand').'/'.get_lang('Hide'), array('align' => 'absmiddle', 'id' => 'category_img_'.$category['details']['id'])) . ' ' . get_lang('SessionCategory') . ': ' . $category['details']['name'].'  -  '.get_lang('From').' '.$category['details']['date_start'].' '.get_lang('Until').' '.$category['details']['date_end'].'</div>';														
					echo $html_sessions;						
					echo '</div>';
				}				
			}
			
			
		}
	}
}

/*
$userdefined_categories = get_user_course_categories();
if ( is_array($list) ) {
	//Courses whithout sessions
	$old_user_category = 0;
	foreach ($list as $key => $value) {
		if (empty($value[2])) { //if out of any session

			$userdefined_categories = get_user_course_categories();
			echo '<ul class="courseslist">';

			if ($old_user_category<>$value[0]) {
				if ($key <> 0 || $value[0] <> 0) {// there are courses in the previous category
					echo "\n</ul>";
				}
				echo "\n\n\t<ul class=\"user_course_category\"><li>".$userdefined_categories[$value[0]]."</li></ul>\n";
				if ($key<>0 OR $value[0]<>0){ // there are courses in the previous category
					echo "<ul class=\"courseslist\">";
				}
				$old_user_category = $value[0];

			}
			echo $value[1];
			echo "</ul>\n";
		}
	}

	$listActives = $listInactives = $listCourses = array();
	foreach ($list as $key => $value) {
		if ($value['active']) { //if the session is still active (as told by get_logged_user_course_html())
			$listActives[] = $value;
		} else if (!empty($value[2])) { //if there is a session but it is not active
			$listInactives[] = $value;
		}
	}
	$old_user_category = 0;
	$userdefined_categories = get_user_course_categories();

	if (count($listActives) > 0 && $display_actives) {
		echo "<ul class=\"courseslist\">\n";

		$name_category = array();
		$i = 0;
		$j=0;
		foreach ($listActives as $key => $value) {
			$session_category_id=$value['session_category_id'];
			if (!empty($value[3]['category'])) {
				if (!in_array($value[3]['category'], $name_category)) {

					if ($key != 0) {
						echo '</ul>';
					}
					//Category
					$name_category['name'] = $value[3]['category'];
					echo '<ul class="category_box" id="category_box_'.$session_category_id.'">' .
							'<li class="category_box_title" id="category_box_title_'.$session_category_id.'">'.$name_category['name'].'</li>';
					echo "</ul>\n";
				}

			}

			if (!empty($value[2])) {
				if ((isset($old_session) && $old_session != $value[2]) or ((!isset($old_session)) && isset($value[2]))) {
					$old_session = $value[2];
					if ($key != 0) {
						echo '</ul>';
					}
					//Session

					echo '<ul style="display:none" class="session_box_'.$session_category_id.'"  >' .
							'<li class="session_box_title" >'.$value[3]['title'].' '.$value[3]['dates'].'</li>';
					if ( !empty($value[3]['coach']) ) {
						echo '<li class="session_box_coach">'.$value[3]['coach'].'</li>';
					}
					echo "</ul>\n";


					echo '<ul  class="session_course_item" id="session_course_item_'.$i.'">';

				}
			}
			//Courses
			echo $value[1];
			$i++;
		}

		echo "\n</ul><br /><br />\n";

	}

	if (count($listInactives) > 0 && !$display_actives) {
		echo '<ul class="sessions_list_inactive">';
		foreach ($listInactives as $key => $value) {
			if (!empty($value[2])) {
				if ($old_session != $value[2]) {
					$old_session = $value[2];
					if ($key != 0) {
						echo '</ul>';
					}
					echo '<ul class="session_box">' .
							'<li class="session_box_title">'.$value[3]['title'].' '.$value[3]['dates'].'</li>';
					if (!empty($value[3]['coach'])) {
						echo '<li class="session_box_coach">'.$value[3]['coach'].'</li>';
					}
					echo "</ul>\n";
					echo '<ul>';
				}
			}
			echo $value[1];
		}
		echo "\n</ul><br /><br />\n";
	}
} 
*/

echo '</div>'; // end of content section
// Register whether full admin or null admin course
// by course through an array dbname x user status
api_session_register('status');

/*
==============================================================================
		RIGHT MENU
==============================================================================
*/
echo '	<div id="menu" class="menu">';

// api_display_language_form(); // moved to the profile page.

$show_menu = false;
$show_create_link = false;
$show_course_link = false;
$show_digest_link = false;

$display_add_course_link = api_is_allowed_to_create_course() && ($_SESSION['studentview'] != 'studentenview');
if ($display_add_course_link) {
	$show_menu = true;
	$show_create_link = true;
}

if (api_is_platform_admin() || api_is_course_admin() || api_is_allowed_to_create_course()) {
	$show_menu = true;
	$show_course_link = true;
} else {
	if (api_get_setting('allow_students_to_browse_courses')=='true') {
		$show_menu = true;
		$show_course_link = true;
	}
}

if (isset($toolsList) and is_array($toolsList) and isset($digest)) {
	$show_digest_link = true;
	$show_menu = true;
}

echo '<div class="menusection">';
echo '<span class="menusectioncaption">'.get_lang('MenuUser').'</span>';
    
//user image
//  @todo add a platform setting to add the user image
if (api_get_setting('allow_social_tool')=='true' && api_get_setting('allow_message_tool') == 'true') {
    $img_array= UserManager::get_user_picture_path_by_id(api_get_user_id(),'web',true,true);        
    $no_image =false;
    if ($img_array['file'] == 'unknown.jpg') {
        $no_image =true;
    }       
    $img_array = UserManager::get_picture_user(api_get_user_id(), $img_array['file'], 50, USER_IMAGE_SIZE_MEDIUM, ' width="90" height="90" ');
    echo '<div class="clear"></div>';
    echo '<div id="social_widget" >';
      
    echo '  <div id="social_widget_image">';
    if ($no_image == false) {
        echo '<a href="'.api_get_path(WEB_PATH).'main/social/home.php"><img src="'.$img_array['file'].'"  '.$img_array['style'].' border="1"></a>';
    } else { 
        echo '<a href="'.api_get_path(WEB_PATH).'main/auth/profile.php"><img title="'.get_lang('EditProfile').'" src="'.$img_array['file'].'" '.$img_array['style'].' border="1"></a>';
    }
    echo '</div>';  
                    
    require_once api_get_path(LIBRARY_PATH).'message.lib.php';
    require_once api_get_path(LIBRARY_PATH).'social.lib.php';
    require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';
        
    // New messages
    $number_of_new_messages             = MessageManager::get_new_messages();
    // New contact invitations
    $number_of_new_messages_of_friend   = SocialManager::get_message_number_invitation_by_user_id(api_get_user_id());
       
    // New group invitations sent by a moderator
    $group_pending_invitations = GroupPortalManager::get_groups_by_user(api_get_user_id(), GROUP_USER_PERMISSION_PENDING_INVITATION,false);
    $group_pending_invitations = count($group_pending_invitations);
        
    $total_invitations = $number_of_new_messages_of_friend + $group_pending_invitations;
    $cant_msg  = '';
    if ($number_of_new_messages > 0) {
        $cant_msg = ' ('.$number_of_new_messages.')';
    }
    //<h2 class="message-title">'.get_lang('Messages').'</h2>
    echo '<div class="clear"></div>';
    echo '<div class="message-content"> <p>';
    $link = '';
    if (api_get_setting('show_tabs', 'social') == 'true') {
        $link = '?f=social';
    }
    echo '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php'.$link.'" class="message-body">'.get_lang('Inbox').$cant_msg.' </a><br />';                    
    echo '<a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php'.$link.'" class="message-body">'.get_lang('Compose').' </a><br />';
    //echo '<a href="'.api_get_path(WEB_PATH).'main/auth/profile.php" class="message-body">'.get_lang('EditMyProfile').' </a><br />';
    
    if ($total_invitations > 0) {       
        echo '<a href="'.api_get_path(WEB_PATH).'main/social/invitations.php" class="message-body">'.get_lang('PendingInvitations').' ('.$total_invitations.') </a><br />';
    }
            
    echo '</p>';
    echo '</div>';  
    echo '</div><div class="clear"></div>';
}  


// My account section
if ($show_menu) {
	echo '<ul class="menulist">';
	if ($show_create_link) {
		display_create_course_link();
	}
	if ($show_course_link) {
		display_edit_course_list_links();
		display_history_course_session();
	}
	if ($show_digest_link) {
		display_digest($toolsList, $digest, $orderKey, $courses);
	}
	echo '</ul>';
}

echo '</div>'; //close menusection


//deleting the myprofile link
if (api_get_setting('allow_social_tool') == true) {
	unset($menu_navigation['myprofile']);	
}

// Main navigation section
// tabs that are deactivated are added here
if (!empty($menu_navigation)) {
	echo '<div class="menusection">';
	echo '<span class="menusectioncaption">'.get_lang('MainNavigation').'</span>';
	echo '<ul class="menulist">';
	foreach ($menu_navigation as $section => $navigation_info) {
		$current = $section == $GLOBALS['this_section'] ? ' id="current"' : '';
		echo '<li'.$current.'>';
		echo '<a href="'.$navigation_info['url'].'" target="_self">'.$navigation_info['title'].'</a>';
		echo '</li>';
		echo "\n";
		
	}
	echo '</ul>';
	echo '</div>';
}

// plugins for the my courses menu
if (isset($_plugins['mycourses_menu']) && is_array($_plugins['mycourses_menu'])) {
	echo '<div class="note">';
	api_plugin('mycourses_menu');
	echo '</div>';
}

if (api_get_setting('allow_reservation') == 'true' && api_is_allowed_to_create_course() ){
	echo '<div class="menusection">';
	echo '<span class="menusectioncaption">'.get_lang('Booking').'</span>';
	echo '<ul class="menulist">';
	echo '<a href="main/reservation/reservation.php">'.get_lang('ManageReservations').'</a><br />';
	echo '</ul>';
	echo '</div>';
}

// search textbox
if (api_get_setting('search_enabled') == 'true') {
	echo '<div class="searchbox">';
	$search_btn = get_lang('Search');
	$search_text_default = get_lang('YourTextHere');
echo <<<EOD
<br />
<form action="main/search/" method="post">
&nbsp;&nbsp;<input type="text" id="query" size="15" name="query" value="" />
&nbsp;&nbsp;<button class="save" type="submit" name="submit" value="$search_btn"/>$search_btn </button>
</form>
EOD;
	echo '</div>';
}

echo '</div>'; // end of menu

//footer
Display :: display_footer();
