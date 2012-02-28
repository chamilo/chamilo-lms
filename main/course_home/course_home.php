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

// Name of the language file that needs to be included.
$language_file = 'course_home';
$use_anonymous = true;

// Inlcuding the global initialization file.
require dirname(__FILE__).'/../inc/global.inc.php';

// Delete LP sessions - commented out after seeing that normal
// users in their first learnpath step (1st SCO of a SCORM)
// cannot have their data saved if they "Return to course homepage"
// before any LMSFinish()
//unset($_SESSION['oLP']);
//unset($_SESSION['lpobject']);

$htmlHeadXtra[] ='<script type="text/javascript">
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

/* toogle for post-it in course home */
$(function() {
	$(".thematic-postit-head").click(function() {
		$(".thematic-postit-center").slideToggle("fast");
	});	
});

</script>';

if (!isset($cidReq)) {
	$cidReq = api_get_course_id(); // To provide compatibility with previous systems.
	global $error_msg,$error_no;
	$classError = 'init';
	$error_no[$classError][] = '2';
	$error_level[$classError][] = 'info';
	$error_msg[$classError][] = "[".__FILE__."][".__LINE__."] cidReq was missing $cidReq take $dbname;";
}

if (isset($_SESSION['_gid'])) {
	unset($_SESSION['_gid']);
}

// The section for the tabs
$this_section = SECTION_COURSES;

/*	Constants */

define('TOOL_PUBLIC',                   'Public');
define('TOOL_PUBLIC_BUT_HIDDEN',        'PublicButHide');
define('TOOL_COURSE_ADMIN',             'courseAdmin');
define('TOOL_PLATFORM_ADMIN',           'platformAdmin');
define('TOOL_AUTHORING',                'toolauthoring');
define('TOOL_INTERACTION',              'toolinteraction');
define('TOOL_COURSE_PLUGIN',            'toolcourseplugin'); //all plugins that can be enabled in courses
define('TOOL_ADMIN',                    'tooladmin');
define('TOOL_ADMIN_PLATFORM',           'tooladminplatform');

//define('TOOL_ADMIN_PLATFORM_VISIBLE', 'tooladminplatformvisible');
//define('TOOL_ADMIN_PLATFORM_INVISIBLE', 'tooladminplatforminvisible');
//define('TOOL_ADMIN_COURS_INVISIBLE', 'tooladmincoursinvisible');
define('TOOL_STUDENT_VIEW',              'toolstudentview');
define('TOOL_ADMIN_VISIBLE',             'tooladminvisible');


/*	Virtual course support code	*/

$user_id 		= api_get_user_id();
$course_code 	= $_course['sysCode'];
$course_info 	= Database::get_course_info($course_code);
$return_result	= CourseManager::determine_course_title_from_course_info($_user['user_id'], $course_info);
$course_title	= $return_result['title'];
$course_code	= $return_result['code'];

$_course['name'] = $course_title;
$_course['official_code'] = $course_code;

api_session_unregister('toolgroup');

$is_speacialcourse = CourseManager::is_special_course($course_code);

if ($is_speacialcourse) {
    $autoreg = Security::remove_XSS($_GET['autoreg']);
    if ($autoreg == 1) {
        CourseManager::subscribe_user($user_id, $course_code, $status = STUDENT);
    }
}

/*	Is the user allowed here? */
if (!$is_allowed_in_course) {
	api_not_allowed(true);
}

/*	Header */

/*  STATISTICS */

if (!isset($coursesAlreadyVisited[$_cid])) {
    event_access_course();
    $coursesAlreadyVisited[$_cid] = 1;
    api_session_register('coursesAlreadyVisited');
}

/*Auto lunch code */
$show_autolunch_lp_warning = false;
$auto_lunch = api_get_course_setting('enable_lp_auto_launch');
if (!empty($auto_lunch)) {
    $session_id = api_get_session_id();
    
    if ($auto_lunch == 2) { //LP list
        if (api_is_platform_admin() || api_is_allowed_to_edit()) {
            $show_autolunch_lp_warning = true;
        } else {
        
            $session_key = 'lp_autolunch_'.$session_id.'_'.api_get_course_int_id().'_'.api_get_user_id();                
            if (!isset($_SESSION[$session_key])) {
                //redirecting to the LP 
                $url = api_get_path(WEB_CODE_PATH).'newscorm/lp_controller.php?'.api_get_cidreq().'&id_session='.$session_id;            
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
            $sql = "SELECT id FROM $lp_table WHERE c_id = $course_id AND autolunch = 1 $condition LIMIT 1";
            $result = Database::query($sql);
            //If we found nothing in the session we just called the session_id =  0 autolunch
            if (Database::num_rows($result) ==  0) {
                $condition = '';
            } else {
            	//great, there is an specific auto lunch for this session we leave the $condition
            }
        }
        
        $sql = "SELECT id FROM $lp_table WHERE autolunch = 1 $condition LIMIT 1";
        $result = Database::query($sql);
        if (Database::num_rows($result) >  0) {
            $lp_data = Database::fetch_array($result,'ASSOC');
            if (!empty($lp_data['id'])) {
                if (api_is_platform_admin() || api_is_allowed_to_edit()) {
                	$show_autolunch_lp_warning = true;
                } else {
                    $session_key = 'lp_autolunch_'.$session_id.'_'.api_get_course_int_id().'_'.api_get_user_id();                
                    if (!isset($_SESSION[$session_key])) {
                        //redirecting to the LP 
                        $url = api_get_path(WEB_CODE_PATH).'newscorm/lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$lp_data['id'];
                    
                        $_SESSION[$session_key] = true;                     
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

//display course title for course home page (similar to toolname for tool pages)
//echo '<h3>'.api_display_tool_title($nameTools) . '</h3>';

/*	Introduction section (editable by course admins) */

$content = Display::return_introduction_section(TOOL_COURSE_HOMEPAGE, array(
		'CreateDocumentWebDir' => api_get_path(WEB_COURSE_PATH).api_get_course_path().'/document/',
		'CreateDocumentDir'    => 'document/',
		'BaseHref'             => api_get_path(WEB_COURSE_PATH).api_get_course_path().'/'
	)
);

/*	SWITCH TO A DIFFERENT HOMEPAGE VIEW
	the setting homepage_view is adjustable through
	the platform administration section */
    
require_once api_get_path(LIBRARY_PATH).'course_home.lib.php';

if ($show_autolunch_lp_warning) {    
    $show_message = Display::return_message(get_lang('TheLPAutoLaunchSettingIsONStudentsWillBeRedirectToAnSpecificLP'),'warning');
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
$tpl = new Template($tool_name);
$tpl->assign('actions', $actions);
$tpl->assign('message', $show_message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();