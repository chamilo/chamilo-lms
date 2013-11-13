<?php
/* For licensing terms, see /license.txt */

/**
        HOME PAGE FOR EACH COURSE
*
*	This page, included in every course's index.php is the home
*	page. To make administration simple, the teacher edits his
*	course from the home page. Only the login detects that the
*	visitor is allowed to activate, deactivate home page links,
*	access to the teachers tools (statistics, edit forums...).
*
* Edit visibility of tools
*
*   visibility = 1 - everybody
*   visibility = 0 - course admin (teacher) and platform admin
*
* Who can change visibility ?
*
*   admin = 0 - course admin (teacher) and platform admin
*   admin = 1 - platform admin
*
* Show message to confirm that a tools must be hide from available tools
*
*   visibility 0,1
*
*
*	@package chamilo.course_home
*/

/* 		INIT SECTION		*/

use \ChamiloSession as Session;

// Name of the language file that needs to be included.
$language_file = array('course_home','courses');
$use_anonymous = true;

// Including the global initialization file.
require_once dirname(__FILE__).'/../inc/global.inc.php';

// Delete LP sessions - commented out after seeing that normal
// users in their first learnpath step (1st SCO of a SCORM)
// cannot have their data saved if they "Return to course homepage"
// before any LMSFinish()
//unset($_SESSION['oLP']);
//unset($_SESSION['lpobject']);

// The section for the tabs
$this_section = SECTION_COURSES;

/*	Constants */

define('TOOL_PUBLIC', 'Public');
define('TOOL_PUBLIC_BUT_HIDDEN', 'PublicButHide');
define('TOOL_COURSE_ADMIN', 'courseAdmin');
define('TOOL_PLATFORM_ADMIN', 'platformAdmin');
define('TOOL_AUTHORING', 'toolauthoring');
define('TOOL_INTERACTION', 'toolinteraction');
define('TOOL_COURSE_PLUGIN', 'toolcourseplugin'); //all plugins that can be enabled in courses
define('TOOL_ADMIN', 'tooladmin');
define('TOOL_ADMIN_PLATFORM', 'tooladminplatform');
define('TOOL_STUDENT_VIEW', 'toolstudentview');
define('TOOL_ADMIN_VISIBLE', 'tooladminvisible');

$user_id = api_get_user_id();
$show_message = '';

//Deleting group session
Session::erase('toolgroup');
Session::erase('_gid');

$is_specialcourse = CourseManager::is_special_course($course_code);

if ($is_specialcourse) {
    $autoreg = isset($_GET['autoreg']) ? Security::remove_XSS($_GET['autoreg']) : null;
    if ($autoreg == 1) {
        CourseManager::subscribe_user($user_id, $course_code, $status = STUDENT);
    }
}
if (isset($_GET['action']) && $_GET['action'] == 'subscribe') {
    if (Security::check_token('get')) {
        Security::clear_token();
        $auth = new AuthLib();
        $msg = $auth->subscribe_user($course_code);
        if (!empty($msg)) {
            $show_message .= Display::return_message(get_lang($msg));
        }
    }
}

/*	Is the user allowed here? */
api_protect_course_script(true);

/*  STATISTICS */

if (!isset($coursesAlreadyVisited[$course_code])) {
    event_access_course();
    $coursesAlreadyVisited[$course_code] = 1;
    Session::write('coursesAlreadyVisited', $coursesAlreadyVisited);
}

$show_autolaunch_exercise_warning = false;

// Exercise auto-launch
$auto_launch = api_get_course_setting('enable_exercise_auto_launch');

if (!empty($auto_launch)) {
    $session_id = api_get_session_id();

     //Exercise list
    if ($auto_launch == 2) {
        if (api_is_platform_admin() || api_is_allowed_to_edit()) {
            $show_autolaunch_exercise_warning = true;
        } else {
            $session_key = 'exercise_autolunch_'.$session_id.'_'.api_get_course_int_id().'_'.api_get_user_id();
            if (!isset($_SESSION[$session_key])) {
                //redirecting to the Exercise
                $url = api_get_path(WEB_CODE_PATH).'exercice/exercice.php?'.api_get_cidreq().'&id_session='.$session_id;
                Session::write($session_key, true);
                //$_SESSION[$session_key] = true;
                header("Location: $url");
                exit;
            }
        }
    } else {
        $table = Database::get_course_table(TABLE_QUIZ_TEST);
        $course_id = api_get_course_int_id();
        $condition = '';
        if (!empty($session_id)) {
            $condition =  api_get_session_condition($session_id);
            $sql = "SELECT iid FROM $table WHERE c_id = $course_id AND autolaunch = 1 $condition LIMIT 1";
            $result = Database::query($sql);
            //If we found nothing in the session we just called the session_id =  0 autolaunch
            if (Database::num_rows($result) ==  0) {
                $condition = '';
            } else {
            	//great, there is an specific auto lunch for this session we leave the $condition
            }
        }

        $sql = "SELECT iid FROM $table WHERE c_id = $course_id AND autolaunch = 1 $condition LIMIT 1";
        $result = Database::query($sql);
        if (Database::num_rows($result) >  0) {
            $data = Database::fetch_array($result,'ASSOC');
            if (!empty($data['iid'])) {
                if (api_is_platform_admin() || api_is_allowed_to_edit()) {
                	$show_autolaunch_exercise_warning = true;
                } else {
                    $session_key = 'exercise_autolunch_'.$session_id.'_'.api_get_course_int_id().'_'.api_get_user_id();
                    if (!isset($_SESSION[$session_key])) {
                        //redirecting to the LP
                        $url = api_get_path(WEB_CODE_PATH).'exercice/overview.php?'.api_get_cidreq().'&exerciseId='.$data['iid'];

                        //$_SESSION[$session_key] = true;
                        Session::write($session_key, true);
                        header("Location: $url");
                        exit;
                    }
                }
            }
        }
    }
}

/* Auto launch code */
$show_autolaunch_lp_warning = false;
$auto_launch = api_get_course_setting('enable_lp_auto_launch');
if (!empty($auto_launch)) {
    $session_id = api_get_session_id();
     //LP list
    if ($auto_launch == 2) {
        if (api_is_platform_admin() || api_is_allowed_to_edit()) {
            $show_autolaunch_lp_warning = true;
        } else {
            $session_key = 'lp_autolunch_'.$session_id.'_'.api_get_course_int_id().'_'.api_get_user_id();
            if (!isset($_SESSION[$session_key])) {
                //redirecting to the LP
                $url = api_get_path(WEB_CODE_PATH).'newscorm/lp_controller.php?'.api_get_cidreq().'&id_session='.$session_id;
                //$_SESSION[$session_key] = true;
                Session::write($session_key, true);
                header("Location: $url");
                exit;
            }
        }
    } else {
        $lp_table = Database::get_course_table(TABLE_LP_MAIN);
        $course_id = api_get_course_int_id();
        $condition = '';
        if (!empty($session_id)) {
            $condition =  api_get_session_condition($session_id);
            $sql = "SELECT id FROM $lp_table WHERE c_id = $course_id AND autolunch = 1 $condition LIMIT 1";
            $result = Database::query($sql);
            //If we found nothing in the session we just called the session_id =  0 autolunch
            if (Database::num_rows($result) ==  0) {
                $condition = '';
            } else {
            	//great, there is an specific auto lunch for this session we leave the $condition
            }
        }

        $sql = "SELECT id FROM $lp_table WHERE c_id = $course_id AND autolunch = 1 $condition LIMIT 1";
        $result = Database::query($sql);
        if (Database::num_rows($result) >  0) {
            $lp_data = Database::fetch_array($result,'ASSOC');
            if (!empty($lp_data['id'])) {
                if (api_is_platform_admin() || api_is_allowed_to_edit()) {
                	$show_autolaunch_lp_warning = true;
                } else {
                    $session_key = 'lp_autolunch_'.$session_id.'_'.api_get_course_int_id().'_'.api_get_user_id();
                    if (!isset($_SESSION[$session_key])) {
                        //redirecting to the LP
                        $url = api_get_path(WEB_CODE_PATH).'newscorm/lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$lp_data['id'];

                        //$_SESSION[$session_key] = true;
                        Session::write($session_key, true);
                        header("Location: $url");
                        exit;
                    }
                }
            }
        }
    }
}

$tool_table = Database::get_course_table(TABLE_TOOL_LIST);
$temps = time();
$reqdate = "&reqdate=$temps";

/*	MAIN CODE */

/*	Introduction section (editable by course admins) */

$content = Display::return_introduction_section(TOOL_COURSE_HOMEPAGE, array(
    'CreateDocumentWebDir' => api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/',
    'CreateDocumentDir'    => 'document/',
    'BaseHref'             => api_get_path(WEB_COURSE_PATH).api_get_course_path().'/'
));

/*	SWITCH TO A DIFFERENT HOMEPAGE VIEW
	the setting homepage_view is adjustable through
	the platform administration section */

if ($show_autolaunch_lp_warning) {
    $show_message .= Display::return_message(get_lang('TheLPAutoLaunchSettingIsONStudentsWillBeRedirectToAnSpecificLP'),'warning');
}
if ($show_autolaunch_exercise_warning) {
    $show_message .= Display::return_message(get_lang('TheExerciseAutoLaunchSettingIsONStudentsWillBeRedirectToAnSpecificExercise'),'warning');
}
if (api_get_setting('homepage_view') == 'activity' || api_get_setting('homepage_view') == 'activity_big') {
    require 'activity.php';
} elseif (api_get_setting('homepage_view') == '2column') {
    require '2column.php';
} elseif (api_get_setting('homepage_view') == '3column') {
    require '3column.php';
} elseif (api_get_setting('homepage_view') == 'vertical_activity') {
    require 'vertical_activity.php';
}
$content = '<div id="course_tools">'.$content.'</div>';
Session::erase('_gid');

return array('content' => $content, 'message' => $show_message);
