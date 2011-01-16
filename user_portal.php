<?php
/* For licensing terms, see /license.txt */

/**
 * This is the index file displayed when a user is logged in on Chamilo.
 *
 * It displays:
 * - personal course list
 * - menu bar
 * Search for CONFIGURATION parameters to modify settings
 * @todo rewrite code to separate display, logic, database code
 * @package chamilo.main
 * @todo Shouldn't the SCRIPTVAL_ and CONFVAL_ constant be moved to the config page? Has anybody any idea what the are used for?
 *       If these are really configuration settings then we can add those to the dokeos config settings.
 * @todo move display_courses and some other functions to a more appripriate place course.lib.php or user.lib.php
 * @todo use api_get_path instead of $rootAdminWeb
 * @todo check for duplication of functions with index.php (user_portal.php is orginally a copy of index.php)
 * @todo display_digest, shouldn't this be removed and be made into an extension?
 */

/* INIT SECTION    */

// Don't change these settings
define('SCRIPTVAL_No', 0);
define('SCRIPTVAL_InCourseList', 1);
define('SCRIPTVAL_UnderCourseList', 2);
define('SCRIPTVAL_Both', 3);
define('SCRIPTVAL_NewEntriesOfTheDay', 4);
define('SCRIPTVAL_NewEntriesOfTheDayOfLastLogin', 5);
define('SCRIPTVAL_NoTimeLimit', 6);
// End 'don't change' section

// Language files that should be included.
$language_file = array('courses', 'index');

$cidReset = true; /* Flag forcing the 'current course' reset,
                    as we're not inside a course anymore  */

if (isset($_SESSION['this_section']))
    unset($_SESSION['this_section']); // For HTML editor repository.

/* Included libraries */

require_once './main/inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'course.lib.php';
require_once $libpath.'system_announcements.lib.php';
require_once $libpath.'groupmanager.lib.php';
require_once $libpath.'usermanager.lib.php';
require_once 'main/survey/survey.lib.php';
require_once $libpath.'sessionmanager.lib.php';

api_block_anonymous_users(); // Only users who are logged in can proceed.

//$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript" language="javascript"></script>';
//$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.toggle.js" type="text/javascript" language="javascript"></script>';

/* Table definitions */

// Database table definitions.
$main_user_table        = Database :: get_main_table(TABLE_MAIN_USER);
$main_admin_table       = Database :: get_main_table(TABLE_MAIN_ADMIN);
$main_course_table      = Database :: get_main_table(TABLE_MAIN_COURSE);
$main_course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$main_category_table    = Database :: get_main_table(TABLE_MAIN_CATEGORY);

/* Constants and CONFIGURATION parameters */

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
// SCRIPTVAL_InCourseList        // best choice if $orderKey[0] == 'keyCourse'
// SCRIPTVAL_UnderCourseList    // best choice
// SCRIPTVAL_Both // probably only for debug
//define('CONFVAL_dateFormatForInfosFromCourses', get_lang('dateFormatShort'));
define('CONFVAL_dateFormatForInfosFromCourses', get_lang('dateFormatLong'));
//define("CONFVAL_limitPreviewTo",SCRIPTVAL_NewEntriesOfTheDay);
//define("CONFVAL_limitPreviewTo",SCRIPTVAL_NoTimeLimit);
define("CONFVAL_limitPreviewTo", SCRIPTVAL_NewEntriesOfTheDayOfLastLogin);

// This is the main function to get the course list.
$personal_course_list = UserManager::get_personal_session_course_list($_user['user_id']);

// Check if a user is enrolled only in one course for going directly to the course after the login.
if (api_get_setting('go_to_course_after_login') == 'true') {
    if (!isset($_SESSION['coursesAlreadyVisited']) && is_array($personal_course_list) && count($personal_course_list) == 1) {

        $key = array_keys($personal_course_list);
        $course_info = $personal_course_list[$key[0]];

        $course_directory = $course_info['d'];
        $id_session = isset($course_info['id_session']) ? $course_info['id_session'] : 0;
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

/* Check configuration parameters integrity */

if (CONFVAL_showExtractInfo != SCRIPTVAL_UnderCourseList and $orderKey[0] != 'keyCourse') {
    // CONFVAL_showExtractInfo must be SCRIPTVAL_UnderCourseList to accept $orderKey[0] != 'keyCourse'
    if (DEBUG || api_is_platform_admin()){ // Show bug if admin. Else force a new order.
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
                            $orderKey[0] != \'keyCourse\'
                            (actually : '.$orderKey[0].')
                        </li>
                    </ul>');
    } else {
        $orderKey = array('keyCourse', 'keyTools', 'keyTime');
    }
}

/*
    Header
    Include the HTTP, HTML headers plus the top banner.
*/
Display :: display_header($nameTools);


/*
        FUNCTIONS
        get_logged_user_course_html($my_course)
*/



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
function get_logged_user_course_html($course, $session_id = 0, $class = 'courses') {
    global $nosession, $nbDigestEntries, $digest, $thisCourseSysCode, $orderKey;
    $charset = api_get_system_encoding();

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
        $my_course['status'] = empty($session_id) ? $status_course : 5;
    }

    if (api_get_setting('use_session_mode') == 'true' && !$nosession) {
        global $now, $date_start, $date_end;
    }

    // Initialise.
    $result = '';

    // Table definitions
    //$statistic_database = Database::get_statistic_database();
    $main_user_table            = Database :: get_main_table(TABLE_MAIN_USER);
    $tbl_session                = Database :: get_main_table(TABLE_MAIN_SESSION);
    $tbl_session_category       = Database :: get_main_table(TABLE_MAIN_SESSION_CATEGORY);
    $course_database            = $my_course['db'];
    $course_tool_table          = Database :: get_course_table(TABLE_TOOL_LIST, $course_database);
    $tool_edit_table            = Database :: get_course_table(TABLE_ITEM_PROPERTY, $course_database);
    $course_group_user_table    = Database :: get_course_table(TOOL_USER, $course_database);

    $user_id                = api_get_user_id();
    $course_system_code     = $my_course['k'];
    $course_visual_code     = $my_course['c'];
    $course_title           = $my_course['i'];
    $course_directory       = $my_course['d'];
    $course_teacher         = $my_course['t'];
    $course_teacher_email   = isset($my_course['email']) ? $my_course['email'] : '';
    $course_info = Database :: get_course_info($course_system_code);
    $course_access_settings = CourseManager :: get_access_settings($course_system_code);
    $course_id = isset($course_info['course_id']) ? $course_info['course_id'] : null;
    $course_visibility = $course_access_settings['visibility'];

    $user_in_course_status = CourseManager :: get_user_in_course_status(api_get_user_id(), $course_system_code);

    // Function logic - act on the data.
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

    $s_htlm_status_icon = '';

    $s_htlm_status_icon =Display::return_icon('blackboard_blue.png', get_lang('Course'), array('width' => '48px'));
    /*
    if ($s_course_status == 1) {
        $s_htlm_status_icon = Display::return_icon('course.gif', get_lang('Course')).' '.Display::return_icon('teachers.gif', get_lang('Status').': '.get_lang('Teacher'), array('style' => 'width: 11px; height: 11px;'));
    }
    if ($s_course_status == 2 || ($is_coach && $s_course_status != 1)) {
        $s_htlm_status_icon = Display::return_icon('course.gif', get_lang('Course')).' '.Display::return_icon('coachs.gif', get_lang('Status').': '.get_lang('GeneralCoach'), array('style' => 'width: 11px; height: 11px;'));
    }
    if (($s_course_status == 5 && !$is_coach) || empty($s_course_status)) {
        $s_htlm_status_icon = Display::return_icon('course.gif', get_lang('Course')).' '.Display::return_icon('students.gif', get_lang('Status').': '.get_lang('Student'), array('style' => 'width: 11px; height: 11px;'));
    }
    */
    // Display course entry.
    $result.="\n\t";
    $result .= '<li class="'.$class.'"><div class="coursestatusicons">'.$s_htlm_status_icon.'</div>';
    // Show a hyperlink to the course, unless the course is closed and user is not course admin.
    if ($course_visibility != COURSE_VISIBILITY_CLOSED || $user_in_course_status == COURSEMANAGER) {
        if (api_get_setting('use_session_mode') == 'true' && !$nosession) {
            if (empty($my_course['id_session'])) {
                $my_course['id_session'] = 0;
            }
            if ($user_in_course_status == COURSEMANAGER || ($date_start <= $now && $date_end >= $now) || $date_start == '0000-00-00') {
                $result .= '<a href="'.api_get_path(WEB_COURSE_PATH).$course_directory.'/?id_session='.$my_course['id_session'].'">'.$course_display_title.'</a>';
            }
        } else {
            $result .= '<a href="'.api_get_path(WEB_COURSE_PATH).$course_directory.'/">'.$course_display_title.'</a>';
        }
    } else {
        $result .= $course_display_title.'  '.get_lang('CourseClosed');
    }

    // Show the course_code and teacher if chosen to display this.
    if (api_get_setting('display_coursecode_in_courselist') == 'true' || api_get_setting('display_teacher_in_courselist') == 'true') {
        $result .= '<br />';
    }

    if (api_get_setting('display_coursecode_in_courselist') == 'true') {
        $result .= $course_display_code;
    }

    if (api_get_setting('display_teacher_in_courselist') == 'true') {
        if (api_get_setting('use_session_mode')=='true' && !$nosession) {
            $coachs_course = api_get_coachs_from_course($my_course['id_session'], $course['code']);
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
                if (is_array($course_coachs) && count($course_coachs)> 0 ) {
                    if (api_get_setting('display_coursecode_in_courselist') == 'true' && api_get_setting('display_teacher_in_courselist') == 'true') {
                        $result .= ' &ndash; ';
                    }
                    $result .= get_lang('Coachs').': '.implode(', ',$course_coachs);
                }
            }

        } else {
            $result .= $course_teacher;
        }

        if(!empty($course_teacher_email)) {
            $result .= ' ('.$course_teacher_email.')';
        }
    }

    $result .= isset($course['special_course']) ? ' '.Display::return_icon('klipper.png', get_lang('CourseAutoRegister')) : '';

    $current_course_settings = CourseManager :: get_access_settings($my_course['k']);

    // Display the "what's new" icons.
    $result .= Display :: show_notification($my_course);

    if ((CONFVAL_showExtractInfo == SCRIPTVAL_InCourseList || CONFVAL_showExtractInfo == SCRIPTVAL_Both) && $nbDigestEntries > 0) {

        reset($digest);
        $result .= '
                    <ul>';
        while (list($key2) = each($digest[$thisCourseSysCode])) {
            $result .= '<li>';
            if ($orderKey[1] == 'keyTools') {
                $result .= "<a href=\"$toolsList[$key2] [\"path\"] $thisCourseSysCode \">";
                $result .= "$toolsList[$key2][\"name\"]</a>";
            } else {
                $result .= api_convert_and_format_date($key2, DATE_FORMAT_LONG, date_default_timezone_get());
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
                    $result .= api_convert_and_format_date($key3, DATE_FORMAT_LONG, date_default_timezone_get());
                }
                $result .= '<ul compact="compact">';
                reset($digest[$thisCourseSysCode][$key2][$key3]);
                while (list ($key4, $dataFromCourse) = each($digest[$thisCourseSysCode][$key2][$key3])) {
                    $result .= '<li>';
                    $result .= @htmlspecialchars(api_substr(strip_tags($dataFromCourse), 0, CONFVAL_NB_CHAR_FROM_CONTENT), ENT_QUOTES, $charset);
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

            $rs = Database::query($sql);
            $sessioncoach = Database::store_result($rs);
            $sessioncoach = $sessioncoach[0];

            $session = array();
            $session['title'] = $my_course['session_name'];
            $session_category_id = CourseManager::get_session_category_id_by_session_id($my_course['id_session']);
            $session['category'] = $sessioncoach['name'];
            if ($my_course['date_start'] == '0000-00-00') {
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
                $active = ($date_start <= $now && $date_end >= $now);
            }
        }
        $output = array ($my_course['user_course_cat'], $result, $my_course['id_session'], $session, 'active' => $active, 'session_category_id' => $session_category_id);
    } else {
        $output = array ($my_course['user_course_cat'], $result);
    }

    return $output;
}

/**
 * Get the session box details as an array
 * @param int       Session ID
 * @return array    Empty array or session array ['title'=>'...','category'=>'','dates'=>'...','coach'=>'...','active'=>true/false,'session_category_id'=>int]
 */
function get_session_title_box($session_id) {
    global $nosession;

    if (api_get_setting('use_session_mode') == 'true' && !$nosession) {
        global $now, $date_start, $date_end;
    }

    $output = array();
    if (api_get_setting('use_session_mode') == 'true' && !$nosession) {
        $main_user_table        = Database :: get_main_table(TABLE_MAIN_USER);
        $tbl_session            = Database :: get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_category   = Database :: get_main_table(TABLE_MAIN_SESSION_CATEGORY);
        $active = false;
        // Request for the name of the general coach
        $sql ='SELECT tu.lastname, tu.firstname, ts.name, ts.date_start, ts.date_end, ts.session_category_id
                FROM '.$tbl_session.' ts
                LEFT JOIN '.$main_user_table .' tu
                ON ts.id_coach = tu.user_id
                WHERE ts.id='.intval($session_id);
        $rs = Database::query($sql);
        $session_info = Database::store_result($rs);
        $session_info = $session_info[0];
        $session = array();
        $session['title'] = $session_info[2];
        $session['coach'] = '';

        if ($session_info[3] == '0000-00-00') {
            $session['dates'] = get_lang('WithoutTimeLimits');
            if (api_get_setting('show_session_coach') === 'true') {
                $session['coach'] = get_lang('GeneralCoach').': '.api_get_person_name($session_info[1], $session_info[0]);
            }
            $active = true;
        } else {
            $session ['dates'] = get_lang('From').' '.$session_info[3].' '.get_lang('Until').' '.$session_info[4];
            if ( api_get_setting('show_session_coach') === 'true' ) {
                $session['coach'] = get_lang('GeneralCoach').': '.api_get_person_name($session_info[1], $session_info[0]);
            }
            $active = ($date_start <= $now && $date_end >= $now);
        }
        $session['active'] = $active;
        $session['session_category_id'] = $session_info[5];
        $output = $session;
    }
    return $output;
}

/* MAIN CODE */

/* PERSONAL COURSE LIST */

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
$maxCourse = (int) $maxCourse; // 0 if invalid.
if ($maxCourse > 0) {
    unset ($allentries); // We shall collect all summary$key1 entries in here:
    $toolsList['agenda']['name'] = get_lang('Agenda');
    $toolsList['agenda']['path'] = api_get_path(WEB_CODE_PATH).'calendar/agenda.php?cidReq=';
    $toolsList['valvas']['name'] = get_lang('Valvas');
    $toolsList['valvas']['path'] = api_get_path(WEB_CODE_PATH).'announcements/announcements.php?cidReq=';
}

echo '    <div class="maincontent" id="maincontent">'; // Start of content for logged in users.
// Plugins for the my courses main area.
echo '<div id="plugin-mycourses_main">';
api_plugin('mycourses_main');
echo '</div>';

/* System Announcements */

$announcement = isset($_GET['announcement']) ? $_GET['announcement'] : -1;
$visibility = api_is_allowed_to_create_course() ? VISIBLE_TEACHER : VISIBLE_STUDENT;
SystemAnnouncementManager :: display_announcements($visibility, $announcement);

if (!empty ($_GET['include']) && preg_match('/^[a-zA-Z0-9_-]*\.html$/',$_GET['include'])) {
    include ('./home/'.$_GET['include']);
    $pageIncluded = true;
} else {

    /* DISPLAY COURSES */

    // Compose a structured array of session categories, sessions and courses
    // for the current user.

    if (isset($_GET['history']) && intval($_GET['history']) == 1) {
        $courses_tree = UserManager::get_sessions_by_category($_user['user_id'], true, true);
    } else {
        $courses_tree = UserManager::get_sessions_by_category($_user['user_id'], true);
    }
    foreach ($courses_tree as $cat => $sessions) {
        $courses_tree[$cat]['details'] = SessionManager::get_session_category($cat);
        if ($cat == 0) {
            $courses_tree[$cat]['courses'] = CourseManager::get_courses_list_by_user_id($_user['user_id'], false);
        }
        $courses_tree[$cat]['sessions'] = array_flip(array_flip($sessions));
        if (count($courses_tree[$cat]['sessions']) > 0) {
            foreach ($courses_tree[$cat]['sessions'] as $k => $s_id) {
                $courses_tree[$cat]['sessions'][$k] = array('details' => SessionManager::fetch($s_id));
                $courses_tree[$cat]['sessions'][$k]['courses'] = UserManager::get_courses_list_by_session($_user['user_id'], $s_id);
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
        $status = array();
        $status[$dbname] = $my_course['s'];

        $nbDigestEntries = 0; // Number of entries already collected.
        if ($maxCourse < $maxValvas) {
            $maxValvas = $maxCourse;
        }
        if ($maxCourse > 0) {
            $courses[$thisCourseSysCode]['coursePath'] = $thisCoursePath;
            $courses[$thisCourseSysCode]['courseCode'] = $thisCoursePublicCode;
        }

        /*  Announcements */

        $course_database = $my_course['db'];
        $course_tool_table = Database::get_course_table(TABLE_TOOL_LIST, $course_database);
        $query = "SELECT visibility FROM $course_tool_table WHERE link = 'announcements/announcements.php' AND visibility = 1";
        $result = Database::query($query);
        // Collect from announcements, but only if tool is visible for the course.
        if ($result && $maxValvas > 0 && Database::num_rows($result) > 0) {
            // Search announcements table.
            // Take the entries listed at the top of advalvas/announcements tool.
            $course_announcement_table = Database::get_course_table(TABLE_ANNOUNCEMENT);
            $sqlGetLastAnnouncements = "SELECT end_date publicationDate, content
                                            FROM ".$course_announcement_table;
            switch (CONFVAL_limitPreviewTo) {
                case SCRIPTVAL_NewEntriesOfTheDay :
                    $sqlGetLastAnnouncements .= "WHERE DATE_FORMAT(end_date,'%Y %m %d') >= '".date('Y m d')."'";
                    break;
                case SCRIPTVAL_NoTimeLimit :
                    break;
                case SCRIPTVAL_NewEntriesOfTheDayOfLastLogin :
                    // take care mysql -> DATE_FORMAT(time,format) php -> date(format,date)
                    $sqlGetLastAnnouncements .= "WHERE DATE_FORMAT(end_date,'%Y %m %d') >= '".date('Y m d', $_user['lastLogin'])."'";
            }
            $sqlGetLastAnnouncements .= "ORDER BY end_date DESC LIMIT ".$maxValvas;
            $resGetLastAnnouncements = Database::query($sqlGetLastAnnouncements);
            if ($resGetLastAnnouncements) {
                while ($annoncement = Database::fetch_array($resGetLastAnnouncements)) {
                    $keyTools = 'valvas';
                    $keyTime = $annoncement['publicationDate'];
                    $keyCourse = $thisCourseSysCode;
                    $digest[$$orderKey[0]][$$orderKey[1]][$$orderKey[2]][] = @htmlspecialchars(api_substr(strip_tags($annoncement['content']), 0, CONFVAL_NB_CHAR_FROM_CONTENT), ENT_QUOTES, $charset);
                    $nbDigestEntries ++; // summary has same order as advalvas
                }
            }
        }

        /* Agenda */

        $course_database = $my_course['db'];
        $course_tool_table = Database :: get_course_table(TABLE_TOOL_LIST, $course_database);
        $query = "SELECT visibility FROM $course_tool_table WHERE link = 'calendar/agenda.php' AND visibility = 1";
        $result = Database::query($query);
        $thisAgenda = $maxCourse - $nbDigestEntries; // New max entries for agenda.
        if ($maxAgenda < $thisAgenda) {
            $thisAgenda = $maxAgenda;
        }
        // Collect from agenda, but only if tool is visible for the course.
        if ($result && $thisAgenda > 0 && Database::num_rows($result) > 0) {
            $tableCal = $courseTablePrefix.$thisCourseDbName.$_configuration['db_glue'].'calendar_event';
            $sqlGetNextAgendaEvent = "SELECT start_date, title content, start_time
                                            FROM $tableCal
                                            WHERE start_date >= CURDATE()
                                            ORDER BY start_date, start_time
                                            LIMIT $maxAgenda";
            $resGetNextAgendaEvent = Database::query($sqlGetNextAgendaEvent);
            if ($resGetNextAgendaEvent) {
                while ($agendaEvent = Database::fetch_array($resGetNextAgendaEvent)) {
                    $keyTools = 'agenda';
                    $keyTime = $agendaEvent['start_date'];
                    $keyCourse = $thisCourseSysCode;
                    $digest[$$orderKey[0]][$$orderKey[1]][$$orderKey[2]][] = @htmlspecialchars(api_substr(strip_tags($agendaEvent['content']), 0, CONFVAL_NB_CHAR_FROM_CONTENT), ENT_QUOTES, $charset);
                    $nbDigestEntries ++; // Summary has same order as advalvas.
                }
            }
        }

        /*
            Digest Display
            Take collected data and display it.
        */

        //$list[] = get_logged_user_course_html($my_course);
    } // End while mycourse...
}

if (isset($_GET['history']) && intval($_GET['history']) == 1) {
    echo '<h3>'.get_lang('HistoryTrainingSession').'</h3>';
    if (empty($courses_tree[0]['sessions'])){
        echo get_lang('YouDoNotHaveAnySessionInItsHistory');
    }
}

if (is_array($courses_tree)) {
    foreach ($courses_tree as $key => $category) {
        if ($key == 0) {
            // Sessions and courses that are not in a session category.
            if (!isset($_GET['history'])) { // Check if it's not history trainnign session list.
                CourseManager :: display_special_courses(api_get_user_id());
                CourseManager :: display_courses(api_get_user_id());
            }
            // Independent sessions.
            foreach ($category['sessions'] as $session) {

                // Don't show empty sessions.
                if (count($session['courses']) < 1) { continue; }

                // Courses inside the current session.
                $date_session_start = $session['details']['date_start'];
                $days_access_before_beginning  = $session['details']['nb_days_access_before_beginning'] * 24 * 3600;
                $session_now = time();
                $html_courses_session = '';
                $count_courses_session = 0;
                foreach ($session['courses'] as $course) {
                    $is_coach_course = api_is_coach($session['details']['id'], $course['code']);
                    if ($is_coach_course) {
                        $allowed_time = strtotime($date_session_start) - $days_access_before_beginning;
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
                    //echo '<div class="clear"></div>';
                    echo '<div class="userportal-session-item"><ul class="session_box">';
                        echo '<li class="session_box_title" id="session_'.$session['details']['id'].'" >';

                        //echo Display::return_icon('div_hide.gif', get_lang('Expand').'/'.get_lang('Hide'), array('align' => 'absmiddle', 'id' => 'session_img_'.$session['details']['id'])) . ' ';

                        echo Display::return_icon('window_list.png', get_lang('Expand').'/'.get_lang('Hide'), array('width' => '48px', 'align' => 'absmiddle', 'id' => 'session_img_'.$session['details']['id'])) . ' ';

                        $s = get_session_title_box($session['details']['id']);
                        $extra_info = (!empty($s['coach']) ? $s['coach'].' | ' : '').$s['dates'];
                        //var_dump($s);
                        //echo get_lang('SessionName') . ': ' . $s['title']. ' - '.(!empty($s['coach']) ? $s['coach'].' - ' : '').$s['dates'];
                        $session_link = Display::tag('a',$s['title'], array('href'=>api_get_path(WEB_CODE_PATH).'session/?session_id='.$session['details']['id']));
                        echo '<span>' . $session_link. ' </span> <span style="padding-left: 10px; font-size: 90%; font-weight: normal;">'.$extra_info.'</span>';
                        if (api_is_platform_admin()) {
                            echo '<div style="float:right;"><a href="'.api_get_path(WEB_CODE_PATH).'admin/resume_session.php?id_session='.$session['details']['id'].'">'.Display::return_icon('edit.gif', get_lang('Edit'), array('align' => 'absmiddle')).'</a></div>';
                        }
                        echo '</li>';
                        echo $html_courses_session;

                    echo '</ul></div>';

                }
            }

        } else {

            // All sessions included in.
            if (!empty($category['details'])) {
                $count_courses_session = 0;
                $html_sessions = '';
                foreach ($category['sessions'] as $session) {
                    // Don't show empty sessions.
                    if (count($session['courses']) < 1) { continue; }
                    $date_session_start = $session['details']['date_start'];
                    $days_access_before_beginning  = $session['details']['nb_days_access_before_beginning'] * 24 * 3600;
                    $session_now = time();
                    $html_courses_session = '';
                    $count = 0;
                    foreach ($session['courses'] as $course) {
                        $is_coach_course = api_is_coach($session['details']['id'], $course['code']);
                        if ($is_coach_course) {
                            $allowed_time = strtotime($date_session_start) - $days_access_before_beginning;
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
                        $html_sessions .= '<ul class="sub_session_box" id="session_'.$session['details']['id'].'">';
                        $html_sessions .= '<li class="sub_session_box_title" id="session_'.$session['details']['id'].'">';
                        //$html_sessions .= Display::return_icon('div_hide.gif', get_lang('Expand').'/'.get_lang('Hide'), array('align' => 'absmiddle', 'id' => 'session_img_'.$session['details']['id'])) . ' ';
                        $html_sessions .= Display::return_icon('window_list.png', get_lang('Expand').'/'.get_lang('Hide'), array('width' => '48px', 'align' => 'absmiddle', 'id' => 'session_img_'.$session['details']['id'])) . ' ';

                        $html_sessions .=  '<span>' . $s['title']. ' </span> ';
                        $html_sessions .=  '<span style="padding-left: 10px; font-size: 90%; font-weight: normal;">';
                        $html_sessions .=  (!empty($s['coach']) ? $s['coach'].' | ' : '').$s['dates'];
                        $html_sessions .=  '</span>';

                        if (api_is_platform_admin()) {
                            $html_sessions .=  '<div style="float: right;"><a href="'.api_get_path(WEB_CODE_PATH).'admin/resume_session.php?id_session='.$session['details']['id'].'">'.Display::return_icon('edit.gif', get_lang('Edit'), array('align' => 'absmiddle')).'</a></div>';
                        }

                        $html_sessions .= '</li>';
                        $html_sessions .= $html_courses_session;
                        $html_sessions .= '</ul>';
                    }
                }

                if ($count_courses_session > 0) {

                    echo '<div class="userportal-session-category-item" id="session_category_'.$category['details']['id'].'">';
                    echo '<div class="session_category_title_box" id="session_category_title_box_'.$category['details']['id'].'" style="color: #555555;">';
                    //echo Display::return_icon('div_hide.gif', get_lang('Expand').'/'.get_lang('Hide'), array('align' => 'absmiddle', 'id' => 'category_img_'.$category['details']['id']));

                    echo Display::return_icon('folder_blue.png', get_lang('SessionCategory'), array('width'=>'48px', 'align' => 'absmiddle'));

                    if (api_is_platform_admin()) {
                        echo'<div style="float: right;"><a href="'.api_get_path(WEB_CODE_PATH).'admin/session_category_edit.php?&id='.$category['details']['id'].'">'.Display::return_icon('edit.gif', get_lang('Edit'), array('align' => 'absmiddle')).'</a></div>';
                    }

                    echo '<span id="session_category_title">';
                    echo $category['details']['name'];
                    echo '</span>';

                    echo '<span style="padding-left: 10px; font-size: 90%; font-weight: normal;">';
                    echo get_lang('From').' '.$category['details']['date_start'].' '.get_lang('Until').' '.$category['details']['date_end'].'</div>';
                    echo '</span>';

                    echo $html_sessions;
                    echo '</div>';
                }
            }


        }
    }
}

echo '</div>'; // End of content section.
// Register whether full admin or null admin course
// by course through an array dbname x user status.
api_session_register('status');

/* RIGHT MENU */

echo '    <div id="menu-wrapper">';
echo '    <div id="menu" class="menu">';
// api_display_language_form(); // Moved to the profile page.

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
    if (api_get_setting('allow_students_to_browse_courses') == 'true') {
        $show_menu = true;
        $show_course_link = true;
    }
}

if (isset($toolsList) && is_array($toolsList) && isset($digest)) {
    $show_digest_link = true;
    $show_menu = true;
}

echo '<div class="menusection">';

echo '<span class="menusectioncaption">'.get_lang('Profile').'</span>';

//Always show the user image

$img_array = UserManager::get_user_picture_path_by_id(api_get_user_id(), 'web', true, true);
$no_image = false;
if ($img_array['file'] == 'unknown.jpg') {
    $no_image = true;
}
$img_array = UserManager::get_picture_user(api_get_user_id(), $img_array['file'], 50, USER_IMAGE_SIZE_MEDIUM, ' width="90" height="90" ');
echo '<div class="clear"></div>';

echo '<div id="social_widget">';

echo '  <div id="social_widget_image">';
if (api_get_setting('allow_social_tool') == 'true') {
    if (!$no_image) {
        echo '<a href="'.api_get_path(WEB_PATH).'main/social/home.php"><img src="'.$img_array['file'].'"  '.$img_array['style'].' border="1"></a>';
    } else {
        echo '<a href="'.api_get_path(WEB_PATH).'main/auth/profile.php"><img title="'.get_lang('EditProfile').'" src="'.$img_array['file'].'" '.$img_array['style'].' border="1"></a>';
    }
} else {
    echo '<a href="'.api_get_path(WEB_PATH).'main/auth/profile.php"><img title="'.get_lang('EditProfile').'" src="'.$img_array['file'].'" '.$img_array['style'].' border="1"></a>';
}
echo '</div>';


//  @todo Add a platform setting to add the user image.
if (api_get_setting('allow_social_tool') == 'true' && api_get_setting('allow_message_tool') == 'true') {

    require_once api_get_path(LIBRARY_PATH).'message.lib.php';
    require_once api_get_path(LIBRARY_PATH).'social.lib.php';
    require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

    // New messages.
    $number_of_new_messages             = MessageManager::get_new_messages();
    // New contact invitations.
    $number_of_new_messages_of_friend   = SocialManager::get_message_number_invitation_by_user_id(api_get_user_id());

    // New group invitations sent by a moderator.
    $group_pending_invitations = GroupPortalManager::get_groups_by_user(api_get_user_id(), GROUP_USER_PERMISSION_PENDING_INVITATION, false);
    $group_pending_invitations = count($group_pending_invitations);

    $total_invitations = $number_of_new_messages_of_friend + $group_pending_invitations;
    $cant_msg  = '';
    if ($number_of_new_messages > 0) {
        $cant_msg = ' ('.$number_of_new_messages.')';
    }
    //<h2 class="message-title">'.get_lang('Messages').'</h2>
    echo '<div class="clear"></div>';
    echo '<div class="message-content"><ul class="menulist">';
    $link = '';
    if (api_get_setting('show_tabs', 'social') == 'true') {
        $link = '?f=social';
    }
    echo '<li><a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php'.$link.'" class="message-body">'.get_lang('Inbox').$cant_msg.' </a></li>';
    echo '<li><a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php'.$link.'" class="message-body">'.get_lang('Compose').' </a></li>';
    //echo '<a href="'.api_get_path(WEB_PATH).'main/auth/profile.php" class="message-body">'.get_lang('EditMyProfile').' </a><br />';

    //if ($total_invitations > 0) {
        echo '<li><a href="'.api_get_path(WEB_PATH).'main/social/invitations.php" class="message-body">'.get_lang('PendingInvitations').' ('.$total_invitations.') </a></li>';
    //}

    echo '</ul>';
    echo '</div>';
}
echo '</div>'; // End

    echo '</div>';
echo '</div>'; // End of menu

echo '    <div id="menu" class="menu">';

echo '<div class="menusection">';
echo '<span class="menusectioncaption">'.get_lang('MenuUser').'</span>';


// My account section.
if ($show_menu) {
    echo '<ul class="menulist">';
    if ($show_create_link) {
        Display :: display_create_course_link();
    }
    if ($show_course_link) {
        if (!api_is_drh()) {
            Display :: display_edit_course_list_links();
            Display :: display_history_course_session();
        } else {
            Display :: display_dashboard_link();
        }
    }
    if ($show_digest_link) {
        Display :: display_digest($toolsList, $digest, $orderKey, $courses);
    }
    echo '</ul>';
}

echo '</div>'; // Close menusection.


// Deleting the myprofile link.
if (api_get_setting('allow_social_tool') == 'true') {
    unset($menu_navigation['myprofile']);
}

// Main navigation section.
// Tabs that are deactivated are added here.
if (!empty($menu_navigation)) {
    echo '<div class="menusection">';
    echo '<span class="menusectioncaption">'.get_lang('MainNavigation').'</span>';
    echo '<ul class="menulist">';
    foreach ($menu_navigation as $section => $navigation_info) {
        $current = $section == $GLOBALS['this_section'] ? ' id="current"' : '';
        echo '<li'.$current.'>';
        echo '<a href="'.$navigation_info['url'].'" target="_self">'.$navigation_info['title'].'</a>';
        echo '</li>';
    }
    echo '</ul>';
    echo '</div>';
}

// Plugins for the my courses menu.
if (isset($_plugins['mycourses_menu']) && is_array($_plugins['mycourses_menu'])) {
    echo '<div class="note">';
    echo '<div id="plugin-mycourses_menu">';
    api_plugin('mycourses_menu');
    echo '</div>';
}

if (api_get_setting('allow_reservation') == 'true' && api_is_allowed_to_create_course()) {
    echo '<div class="menusection">';
    echo '<span class="menusectioncaption">'.get_lang('Booking').'</span>';
    echo '<ul class="menulist">';
    echo '<a href="main/reservation/reservation.php">'.get_lang('ManageReservations').'</a><br />';
    echo '</ul>';
    echo '</div>';
}

// Deleting the session_id.
api_session_unregister('session_id');

// Search textbox.
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
echo '<div class="clear"></div>';
echo '</div>'; // End of menu

echo '</div>'; // End of menu wrapper


// Footer
Display :: display_footer();
