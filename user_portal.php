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

/**
 * INIT SECTION
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

// Language files that should be included.
$language_file = array('courses', 'index','admin');

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

//$load_dirs = api_get_setting('courses_list_document_dynamic_dropdown');
$load_dirs = true;


// This is the main function to get the course list.
$personal_course_list = UserManager::get_personal_session_course_list(api_get_user_id());

// Check if a user is enrolled only in one course for going directly to the course after the login.
if (api_get_setting('go_to_course_after_login') == 'true') {
    $my_session_list = array();
    $count_of_courses_no_sessions = 0;
    $count_of_courses_with_sessions = 0;
    foreach($personal_course_list as $course) {       
        if (!empty($course['id_session'])) {
            $my_session_list[$course['id_session']] = true;
            $count_of_courses_with_sessions++;
        } else {
            $count_of_courses_no_sessions++;
        }
    }
    $count_of_sessions = count($my_session_list);    

    //echo $count_of_sessions.' '.$count_of_courses_with_sessions.' '.$count_of_courses_no_sessions;
    //!isset($_SESSION['coursesAlreadyVisited'])
    if ($count_of_sessions == 1 && $count_of_courses_no_sessions == 0) {
     
        $key              = array_keys($personal_course_list);
        $course_info      = $personal_course_list[$key[0]];
        $course_directory = $course_info['d'];
        $id_session       = isset($course_info['id_session']) ? $course_info['id_session'] : 0;

        $url = api_get_path(WEB_CODE_PATH).'session/?session_id='.$id_session; 

        header('location:'.$url);            
        exit;
    }
    
    if (!isset($_SESSION['coursesAlreadyVisited']) && $count_of_sessions == 0 && $count_of_courses_no_sessions == 1) {
        $key              = array_keys($personal_course_list);
        $course_info      = $personal_course_list[$key[0]];
        $course_directory = $course_info['d'];
        $id_session       = isset($course_info['id_session']) ? $course_info['id_session'] : 0;
       
        $url = api_get_path(WEB_COURSE_PATH).$course_directory.'/?id_session='.$id_session;
        header('location:'.$url);            
        exit;
    }
   /*
        if (api_get_setting('hide_courses_in_sessions') == 'true') {
            //Check sessions
            $session_list = array();
            $only_session_id = 0;
            foreach($personal_course_list as $course_item) {
                $session_list[$course_item['id_session']] = $course_item;
                $only_session_id = $course_item['id_session'];
            }        
            if (count($session_list) == 1 && !empty($only_session_id)) {            
                header('Location:'.api_get_path(WEB_CODE_PATH).'session/?session_id='.$session_list[$only_session_id]['id_session']);    
            }
        }
    */    
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

if ($load_dirs) {
	$url 			= api_get_path(WEB_AJAX_PATH).'document.ajax.php?a=document_preview';
	$folder_icon 	= api_get_path(WEB_IMG_PATH).'icons/22/folder.png';
	$close_icon 	= api_get_path(WEB_IMG_PATH).'loading1.gif';
	
	$htmlHeadXtra[] =  '<script type="text/javascript">
	
	$(document).ready( function() {
		
		$(".document_preview_container").hide();
		
		$(".document_preview").click(function() {
			var my_id = this.id;
			var course_id  = my_id.split("_")[2];
			var session_id = my_id.split("_")[3];
			
			//showing div
			$(".document_preview_container").hide();
					
			$("#document_result_" +course_id+"_" + session_id).show();	
			
			//Loading		
			var image = $("img", this);
			image.attr("src", "'.$close_icon.'");		
					
			$.ajax({
				url: "'.$url.'",
				data: "course_id="+course_id+"&session_id="+session_id,
	            success: function(return_value) {
	            	image.attr("src", "'.$folder_icon.'");
	            	$("#document_result_" +course_id+"_" + session_id).html(return_value);
	            	
	            },
	        });
	        
		});
	});
	</script>';
}


Display :: display_header($nameTools);

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

echo '<div class="maincontent" id="maincontent">'; // Start of content for logged in users.
// Plugins for the my courses main area.
echo '<div id="plugin-mycourses_main">';
api_plugin('mycourses_main');
echo '</div>';

/* System Announcements */

$announcement = isset($_GET['announcement']) ? $_GET['announcement'] : -1;
$visibility = api_is_allowed_to_create_course() ? VISIBLE_TEACHER : VISIBLE_STUDENT;
SystemAnnouncementManager :: display_announcements($visibility, $announcement);

if (!empty ($_GET['include']) && preg_match('/^[a-zA-Z0-9_-]*\.html$/',$_GET['include'])) {
    include './home/'.$_GET['include'];
    $pageIncluded = true;
} else {

    /* DISPLAY COURSES */

    // Compose a structured array of session categories, sessions and courses
    // for the current user.

    if (isset($_GET['history']) && intval($_GET['history']) == 1) {
        $courses_tree = UserManager::get_sessions_by_category(api_get_user_id(), true, true, true);
        if (empty($courses_tree[0]) && count($courses_tree) == 1) {
            $courses_tree = null;
        }
    } else {
        $courses_tree = UserManager::get_sessions_by_category(api_get_user_id(), true, false, true);
    }    
    
  
    
    if (!empty($courses_tree)) {
        foreach ($courses_tree as $cat => $sessions) {
            $courses_tree[$cat]['details'] = SessionManager::get_session_category($cat);
            if ($cat == 0) {
                $courses_tree[$cat]['courses'] = CourseManager::get_courses_list_by_user_id(api_get_user_id(), false);
            }
            $courses_tree[$cat]['sessions'] = array_flip(array_flip($sessions));
            if (count($courses_tree[$cat]['sessions']) > 0) {
                foreach ($courses_tree[$cat]['sessions'] as $k => $s_id) {
                    $courses_tree[$cat]['sessions'][$k] = array('details' => SessionManager::fetch($s_id));
                    $courses_tree[$cat]['sessions'][$k]['courses'] = UserManager::get_courses_list_by_session(api_get_user_id(), $s_id);
                }
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

    } // End while mycourse...
}

if (isset($_GET['history']) && intval($_GET['history']) == 1) {
    echo Display::tag('h2', get_lang('HistoryTrainingSession'));    
    //if (empty($courses_tree[0]['sessions'])){    
    if (empty($courses_tree)){
        echo get_lang('YouDoNotHaveAnySessionInItsHistory');
    }
}

if (is_array($courses_tree)) {
    foreach ($courses_tree as $key => $category) {
        if ($key == 0) {
            // Sessions and courses that are not in a session category.
            if (!isset($_GET['history'])) { 
               // If we're not in the history view...
                CourseManager :: display_special_courses(api_get_user_id(), $load_dirs);
                CourseManager :: display_courses(api_get_user_id(), $load_dirs);
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
                    $allowed_time = 0;
                    if ($date_session_start != '0000-00-00') {
                        if ($is_coach_course) {                        
                            $allowed_time = api_strtotime($date_session_start) - $days_access_before_beginning;
                        } else {
                            $allowed_time = api_strtotime($date_session_start);
                        }                    
                    }
                    if ($session_now > $allowed_time) { //read only and accesible
                        if (api_get_setting('hide_courses_in_sessions') == 'false') {  
                            $c = CourseManager :: get_logged_user_course_html($course, $session['details']['id'], 'session_course_item', true, $load_dirs);
                            //$c = CourseManager :: get_logged_user_course_html($course, $session['details']['id'], 'session_course_item',($session['details']['visibility']==3?false:true));
                            $html_courses_session .= $c[1];
                        }
                        $count_courses_session++;
                    }
                }
                
                if ($count_courses_session > 0) {
                    echo '<div class="userportal-session-item"><ul class="session_box">';
                        echo '<li class="session_box_title" id="session_'.$session['details']['id'].'" >';
                        echo Display::return_icon('window_list.png', get_lang('Expand').'/'.get_lang('Hide'), array('width' => '48px', 'align' => 'absmiddle', 'id' => 'session_img_'.$session['details']['id'])) . ' ';

                        $s = Display :: get_session_title_box($session['details']['id']);
                        $extra_info = (!empty($s['coach']) ? $s['coach'].' | ' : '').$s['dates'];
                        /*if ($session['details']['visibility'] == 3) {
                            $session_link = $s['title'];
                        } else {*/
                            $session_link = Display::tag('a',$s['title'], array('href'=>api_get_path(WEB_CODE_PATH).'session/?session_id='.$session['details']['id']));
                        //}
                        echo Display::tag('span',$session_link. ' </span> <span style="padding-left: 10px; font-size: 90%; font-weight: normal;">'.$extra_info);
                        if (api_is_platform_admin()) {
                            echo '<div style="float:right;"><a href="'.api_get_path(WEB_CODE_PATH).'admin/resume_session.php?id_session='.$session['details']['id'].'">'.
                            Display::return_icon('edit.png', get_lang('Edit'), array('align' => 'absmiddle'),22).'</a></div>';
                        }
                        echo '</li>';
                        if (api_get_setting('hide_courses_in_sessions') == 'false') {
                            echo $html_courses_session;
                        }
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
                            $allowed_time = api_strtotime($date_session_start) - $days_access_before_beginning;
                        } else {
                            $allowed_time = api_strtotime($date_session_start);
                        }
                        if ($session_now > $allowed_time) {
                            $c = CourseManager :: get_logged_user_course_html($course, $session['details']['id'], 'session_course_item');
                            $html_courses_session .= $c[1];
                            $count_courses_session++;
                            $count++;
                        }
                    }

                    if ($count > 0) {
                        $s = Display :: get_session_title_box($session['details']['id']);
                        $html_sessions .= '<ul class="sub_session_box" id="session_'.$session['details']['id'].'">';
                        $html_sessions .= '<li class="sub_session_box_title" id="session_'.$session['details']['id'].'">';
                        //$html_sessions .= Display::return_icon('div_hide.gif', get_lang('Expand').'/'.get_lang('Hide'), array('align' => 'absmiddle', 'id' => 'session_img_'.$session['details']['id'])) . ' ';
                        $html_sessions .= Display::return_icon('window_list.png', get_lang('Expand').'/'.get_lang('Hide'), array('width' => '48px', 'align' => 'absmiddle', 'id' => 'session_img_'.$session['details']['id'])) . ' ';

                        $session_link = Display::tag('a',$s['title'], array('href'=>api_get_path(WEB_CODE_PATH).'session/?session_id='.$session['details']['id']));                        
                        $html_sessions .=  '<span>' . $session_link. ' </span> ';
                        $html_sessions .=  '<span style="padding-left: 10px; font-size: 90%; font-weight: normal;">';
                        $html_sessions .=  (!empty($s['coach']) ? $s['coach'].' | ' : '').$s['dates'];
                        $html_sessions .=  '</span>';

                        if (api_is_platform_admin()) {
                            $html_sessions .=  '<div style="float: right;"><a href="'.api_get_path(WEB_CODE_PATH).'admin/resume_session.php?id_session='.$session['details']['id'].'">'.Display::return_icon('edit.png', get_lang('Edit'), array('align' => 'absmiddle'),22).'</a></div>';
                        }

                        $html_sessions .= '</li>';
                        $html_sessions .= $html_courses_session;
                        $html_sessions .= '</ul>';
                    }
                }

                if ($count_courses_session > 0) {

                    echo '<div class="userportal-session-category-item" id="session_category_'.$category['details']['id'].'">';
                    echo '<div class="session_category_title_box" id="session_category_title_box_'.$category['details']['id'].'" style="color: #555555;">';
            
                    echo Display::return_icon('folder_blue.png', get_lang('SessionCategory'), array('width'=>'48px', 'align' => 'absmiddle'));

                    if (api_is_platform_admin()) {
                        echo'<div style="float: right;"><a href="'.api_get_path(WEB_CODE_PATH).'admin/session_category_edit.php?&id='.$category['details']['id'].'">'.Display::return_icon('edit.png', get_lang('Edit'), array(),22).'</a></div>';
                    }

                    echo '<span id="session_category_title">';
                    echo $category['details']['name'];
                    echo '</span>';

                    echo '<span style="padding-left: 10px; font-size: 90%; font-weight: normal;">';                    
                    if ($category['details']['date_end'] != '0000-00-00') {
                        printf(get_lang('FromDateXToDateY'),$category['details']['date_start'],$category['details']['date_end']);
                    }
                    echo '</span></div>';

                    echo $html_sessions;
                    echo '</div>';
                }
            }
        }
    }
}

echo '</div>'; // End of content main-section




// Register whether full admin or null admin course
// by course through an array dbname x user status.
api_session_register('status');

/* RIGHT MENU */

$show_menu        = false;
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

//Always show the user image
$img_array = UserManager::get_user_picture_path_by_id(api_get_user_id(), 'web', true, true);
$no_image = false;
if ($img_array['file'] == 'unknown.jpg') {
    $no_image = true;
}
$img_array = UserManager::get_picture_user(api_get_user_id(), $img_array['file'], 50, USER_IMAGE_SIZE_MEDIUM, ' width="90" height="90" ');

$profile_content = '<div id="social_widget">';

$profile_content .= '<div id="social_widget_image">';
if (api_get_setting('allow_social_tool') == 'true') {
    if (!$no_image) {
        $profile_content .='<a href="'.api_get_path(WEB_PATH).'main/social/home.php"><img src="'.$img_array['file'].'"  '.$img_array['style'].' border="1"></a>';
    } else {
        $profile_content .='<a href="'.api_get_path(WEB_PATH).'main/auth/profile.php"><img title="'.get_lang('EditProfile').'" src="'.$img_array['file'].'" '.$img_array['style'].' border="1"></a>';
    }
} else {
    $profile_content .='<a href="'.api_get_path(WEB_PATH).'main/auth/profile.php"><img title="'.get_lang('EditProfile').'" src="'.$img_array['file'].'" '.$img_array['style'].' border="1"></a>';
}
$profile_content .= ' </div></div>';

//  @todo Add a platform setting to add the user image.
if (api_get_setting('allow_message_tool') == 'true') {

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
    $profile_content .= '<div class="clear"></div>';
    $profile_content .= '<div class="message-content"><ul class="menulist">';
    $link = '';
    if (api_get_setting('allow_social_tool') == 'true') {
        $link = '?f=social';
    }
    $profile_content .= '<li><a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php'.$link.'" class="message-body">'.get_lang('Inbox').$cant_msg.' </a></li>';
    $profile_content .= '<li><a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php'.$link.'" class="message-body">'.get_lang('Compose').' </a></li>';
    
    if (api_get_setting('allow_social_tool') == 'true') {
        if ($total_invitations == 0) {
            $total_invitations = '';
        } else {
           $total_invitations = ' ('.$total_invitations.')';
        }
        $profile_content .= '<li><a href="'.api_get_path(WEB_PATH).'main/social/invitations.php" class="message-body">'.get_lang('PendingInvitations').' '.$total_invitations.' </a></li>';
    }
    $profile_content .= '</ul>';
    $profile_content .= '</div>';      
}

echo '<div id="menu-wrapper">';
//Profile content
echo show_right_block(get_lang('Profile'), $profile_content);

// My account section.
if ($show_menu) {
    $my_account_content = '<ul class="menulist">';
    if ($show_create_link) {
        $my_account_content .= '<li><a href="main/create_course/add_course.php">'.(api_get_setting('course_validation') == 'true' ? get_lang('CreateCourseRequest') : get_lang('CourseCreate')).'</a></li>';
    }
    if ($show_course_link) {
        if (!api_is_drh()) {
            $my_account_content .=  '<li><a href="main/auth/courses.php">'.get_lang('CourseManagement').'</a></li>';
            if (api_get_setting('use_session_mode') == 'true') {
                if (isset($_GET['history']) && intval($_GET['history']) == 1) {
                    $my_account_content .=  '<li><a href="user_portal.php">'.get_lang('DisplayTrainingList').'</a></li>';
                } else {
                    $my_account_content .=  '<li><a href="user_portal.php?history=1">'.get_lang('HistoryTrainingSessions').'</a></li>';
                }
            }
        } else {
             $my_account_content .=  '<li><a href="main/dashboard/index.php">'.get_lang('Dashboard').'</a></li>';
        }
    }
    if ($show_digest_link) {    
       $my_account_content .= Display :: display_digest($toolsList, $digest, $orderKey, $courses);
    }
    $my_account_content .= '</ul>';
}

if (!empty($my_account_content)) {
    echo show_right_block(get_lang('MenuUser'), $my_account_content);
}

// Deleting the myprofile link.
if (api_get_setting('allow_social_tool') == 'true') {
    unset($menu_navigation['myprofile']);
}

// Main navigation section.
// Tabs that are deactivated are added here.
if (!empty($menu_navigation)) {    
    $main_navigation_content .= '<ul class="menulist">';
        
    foreach ($menu_navigation as $section => $navigation_info) {
        $current = $section == $GLOBALS['this_section'] ? ' id="current"' : '';
        $main_navigation_content .= '<li'.$current.'>';
        $main_navigation_content .= '<a href="'.$navigation_info['url'].'" target="_self">'.$navigation_info['title'].'</a>';
        $main_navigation_content .= '</li>';
    }
    $main_navigation_content .= '</ul>';
    echo show_right_block(get_lang('MainNavigation'), $main_navigation_content);    
}

// Plugins for the my courses menu.
if (isset($_plugins['mycourses_menu']) && is_array($_plugins['mycourses_menu'])) {    
    ob_start();
    api_plugin('mycourses_menu');
    $plugin_content = ob_get_contents();
    ob_end_clean();
    echo show_right_block('', $plugin_content);
}

if (api_get_setting('allow_reservation') == 'true' && api_is_allowed_to_create_course()) {
    $booking_content .='<ul class="menulist">';
    $booking_content .='<a href="main/reservation/reservation.php">'.get_lang('ManageReservations').'</a><br />';
    $booking_content .='</ul>';    
    echo show_right_block(get_lang('Booking'), $booking_content);
}

// Deleting the session_id.
api_session_unregister('session_id');

// Search textbox.
if (api_get_setting('search_enabled') == 'true') {
    echo '<div class="searchbox">';
    $search_btn = get_lang('Search');
    $search_text_default = get_lang('YourTextHere');
    $search_content = '<br />
    	<form action="main/search/" method="post">
    	<input type="text" id="query" size="15" name="query" value="" />
    	<button class="save" type="submit" name="submit" value="'.$search_btn.'" />'.$search_btn.' </button>
    	</form></div>';    
    echo show_right_block(get_lang('Search'), $search_content);  
}

if (api_get_setting('show_groups_to_users') == 'true') {   
    require_once api_get_path(LIBRARY_PATH).'usergroup.lib.php';
    $usergroup = new Usergroup();
    $usergroup_list = $usergroup->get_usergroup_by_user(api_get_user_id());
    $classes = '';
    if (!empty($usergroup_list)) {
        foreach($usergroup_list as $group_id) {
        	$data = $usergroup->get($group_id);
        	$data['name'] = Display::url($data['name'], api_get_path(WEB_CODE_PATH).'user/classes.php?id='.$data['id']);
            $classes .= Display::tag('li', $data['name']);
        }
    }    
    if (api_is_platform_admin()) {
        $classes .= Display::tag('li',  Display::url(get_lang('AddClasses') ,api_get_path(WEB_CODE_PATH).'admin/usergroups.php?action=add'));
    }    
    if (!empty($classes)) {
        $classes = Display::tag('ul', $classes, array('class'=>'menulist'));  
        echo show_right_block(get_lang('Classes'), $classes);
    }
}
echo '</div>'; // End of menu wrapper

function show_right_block($title, $content) {    
   
        $html= '<div id="menu" class="menu">';    
            $html.= '<div class="menusection">';
                $html.= '<span class="menusectioncaption">'.$title.'</span>';        
                $html.= $content;
            $html.= '</div>';        
        $html.= '</div>';
   
    return $html;
}
// Footer
Display :: display_footer();
