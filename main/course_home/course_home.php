<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

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

$use_anonymous = true;

require_once __DIR__.'/../inc/global.inc.php';

$htmlHeadXtra[] ='<script>
/* option show/hide thematic-block */
$(document).ready(function(){
    $("#thematic-show").click(function(){
        $(".btn-hide-thematic").hide();
        $(".btn-show-thematic").show(); //show using class
        $("#pross").fadeToggle(); //Not working collapse for Chrome
    });
    $("#thematic-hide").click(function(){
        $(".btn-show-thematic").hide(); //show using class
        $(".btn-hide-thematic").show();
        $("#pross").fadeToggle(); //Not working collapse for Chrome
    });
});

$(document).ready(function() {
	$(".make_visible_and_invisible").attr("href", "javascript:void(0);");
	$(".make_visible_and_invisible > img").click(function () {

		make_visible = "visible.gif";
		make_invisible = "invisible.gif";
		path_name = $(this).attr("src");
		list_path_name = path_name.split("/");
		image_link = list_path_name[list_path_name.length - 1];
		tool_id = $(this).attr("id");
		tool_info = tool_id.split("_");
		my_tool_id = tool_info[1];
        $("#id_normal_message").attr("class", "normal-message alert alert-success");

		$.ajax({
			contentType: "application/x-www-form-urlencoded",
			beforeSend: function(objeto) {
				$(".normal-message").show();
				$("#id_confirmation_message").hide();
			},
			type: "GET",
			url: "'.api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?'.api_get_cidreq().'&a=set_visibility",
			data: "id=" + my_tool_id + "&sent_http_request=1",
			success: function(data) {
				eval("var info=" + data);
				new_current_tool_image = info.image;
				new_current_view       = "'.api_get_path(WEB_IMG_PATH).'" + info.view;
				//eyes
				$("#" + tool_id).attr("src", new_current_view);
				//tool
				$("#toolimage_" + my_tool_id).attr("src", new_current_tool_image);
				//clase
				$("#tooldesc_" + my_tool_id).attr("class", info.tclass);
				$("#istooldesc_" + my_tool_id).attr("class", info.tclass);

				if (image_link == "visible.gif") {
					$("#" + tool_id).attr("alt", "'.get_lang('Activate', '').'");
					$("#" + tool_id).attr("title", "'.get_lang('Activate', '').'");
				} else {
					$("#" + tool_id).attr("alt", "'.get_lang('Deactivate', '').'");
					$("#" + tool_id).attr("title", "'.get_lang('Deactivate', '').'");
				}
				if (info.message == "is_active") {
					message = "'.get_lang('ToolIsNowVisible', '').'";
				} else {
					message = "'.get_lang('ToolIsNowHidden', '').'";
				}
				$(".normal-message").hide();
				$("#id_confirmation_message").html(message);
				$("#id_confirmation_message").show();
			}
		});
	});
});

</script>';

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
define('TOOL_DRH', 'tool_drh');
define('TOOL_STUDENT_VIEW', 'toolstudentview');
define('TOOL_ADMIN_VISIBLE', 'tooladminvisible');

$user_id = api_get_user_id();
$course_code = api_get_course_id();
$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();
$show_message = '';

if (api_is_invitee()) {
    $isInASession = $sessionId > 0;
    $isSubscribed = CourseManager::is_user_subscribed_in_course(
        $user_id,
        $course_code,
        $isInASession,
        $sessionId
    );

    if (!$isSubscribed) {
        api_not_allowed(true);
    }
}

// Deleting group session
Session::erase('toolgroup');
Session::erase('_gid');

$isSpecialCourse = CourseManager::isSpecialCourse($courseId);

if ($isSpecialCourse) {
    if (isset($_GET['autoreg'])) {
        $autoRegistration = Security::remove_XSS($_GET['autoreg']);
        if ($autoRegistration == 1) {
            if (CourseManager::subscribe_user($user_id, $course_code, STUDENT)) {
                Session::write('is_allowed_in_course', true);
            }
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'subscribe') {
    if (Security::check_token('get')) {
        Security::clear_token();
        $auth = new Auth();
        $msg = $auth->subscribe_user($course_code);
        if (CourseManager::is_user_subscribed_in_course($user_id, $course_code)) {
            Session::write('is_allowed_in_course', true);
        }
        if (!empty($msg)) {
            $show_message .= Display::return_message(
                get_lang($msg['message']),
                'info',
                false
            );
        }
    }
}

/*	Is the user allowed here? */
api_protect_course_script(true);

/*  STATISTICS */

if (!isset($coursesAlreadyVisited[$course_code])) {
    Event::accessCourse();
    $coursesAlreadyVisited[$course_code] = 1;
    Session::write('coursesAlreadyVisited', $coursesAlreadyVisited);
}

/*Auto launch code */
$show_autolaunch_lp_warning = false;
$auto_launch = api_get_course_setting('enable_lp_auto_launch');
$session_id = api_get_session_id();
if (!empty($auto_launch)) {
    if ($auto_launch == 2) { //LP list
        if (api_is_platform_admin() || api_is_allowed_to_edit()) {
            $show_autolaunch_lp_warning = true;
        } else {
            $session_key = 'lp_autolaunch_'.$session_id.'_'.api_get_course_int_id().'_'.api_get_user_id();
            if (!isset($_SESSION[$session_key])) {
                //redirecting to the LP
                $url = api_get_path(WEB_CODE_PATH) . 'lp/lp_controller.php?' . api_get_cidreq() . '&id_session=' . $session_id;
                $_SESSION[$session_key] = true;
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
            $sql = "SELECT id FROM $lp_table
                    WHERE c_id = $course_id AND autolaunch = 1 $condition
                    LIMIT 1";
            $result = Database::query($sql);
            //If we found nothing in the session we just called the session_id =  0 autolaunch
            if (Database::num_rows($result) ==  0) {
                $condition = '';
            } else {
            	//great, there is an specific auto launch for this session we leave the $condition
            }
        }

        $sql = "SELECT id FROM $lp_table
                WHERE c_id = $course_id AND autolaunch = 1 $condition
                LIMIT 1";
        $result = Database::query($sql);
        if (Database::num_rows($result) >  0) {
            $lp_data = Database::fetch_array($result,'ASSOC');
            if (!empty($lp_data['id'])) {
                if (api_is_platform_admin() || api_is_allowed_to_edit()) {
                	$show_autolaunch_lp_warning = true;
                } else {
                    $session_key = 'lp_autolaunch_'.$session_id.'_'.api_get_course_int_id().'_'.api_get_user_id();
                    if (!isset($_SESSION[$session_key])) {
                        //redirecting to the LP
                        $url = api_get_path(WEB_CODE_PATH) . 'lp/lp_controller.php?' . api_get_cidreq() . '&action=view&lp_id=' . $lp_data['id'];

                        $_SESSION[$session_key] = true;
                        header("Location: $url");
                        exit;
                    }
                }
            }
        }
    }
}

$forumAutoLaunch = api_get_course_setting('enable_forum_auto_launch');
if ($forumAutoLaunch == 1) {
    if (api_is_platform_admin() || api_is_allowed_to_edit()) {
        Display::addFlash(Display::return_message(
            get_lang('TheForumAutoLaunchSettingIsOnStudentsWillBeRedirectToTheForumTool'),
            'warning'
        ));
    } else {
        //$forumKey = 'forum_auto_launch_'.$session_id.'_'.api_get_course_int_id().'_'.api_get_user_id();
        //if (!isset($_SESSION[$forumKey])) {
            //redirecting to the LP
            $url = api_get_path(WEB_CODE_PATH).'forum/index.php?'.api_get_cidreq().'&id_session='.$session_id;
          //  $_SESSION[$forumKey] = true;
            header("Location: $url");
            exit;
        //}
    }
}


$tool_table = Database::get_course_table(TABLE_TOOL_LIST);
$temps = time();
$reqdate = "&reqdate=$temps";

/*	MAIN CODE */

/*	Introduction section (editable by course admins) */
$content = Display::return_introduction_section(
    TOOL_COURSE_HOMEPAGE,
    array(
        'CreateDocumentWebDir' => api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/',
        'CreateDocumentDir' => 'document/',
        'BaseHref' => api_get_path(WEB_COURSE_PATH).api_get_course_path().'/',
    )
);

/*	SWITCH TO A DIFFERENT HOMEPAGE VIEW
	the setting homepage_view is adjustable through
	the platform administration section */

if ($show_autolaunch_lp_warning) {
    $show_message .= Display::return_message(
        get_lang('TheLPAutoLaunchSettingIsONStudentsWillBeRedirectToAnSpecificLP'),
        'warning'
    );
}

if (api_get_setting('homepage_view') === 'activity' || api_get_setting('homepage_view') === 'activity_big') {
	require 'activity.php';
} elseif (api_get_setting('homepage_view') === '2column') {
	require '2column.php';
} elseif (api_get_setting('homepage_view') === '3column') {
	require '3column.php';
} elseif (api_get_setting('homepage_view') === 'vertical_activity') {
	require 'vertical_activity.php';
}

$content = '<div id="course_tools">'.$content.'</div>';

$tpl = new Template(null);
$tpl->assign('message', $show_message);
$tpl->assign('content', $content);

// Direct login to course
$tpl->assign('course_code', $course_code);

$tpl->display_one_col_template();

// Deleting the objects
Session::erase('_gid');
Session::erase('oLP');
Session::erase('lpobject');
api_remove_in_gradebook();
DocumentManager::removeGeneratedAudioTempFile();
