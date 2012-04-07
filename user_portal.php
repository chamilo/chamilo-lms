<?php
/* For licensing terms, see /license.txt */

/**
 * This is the index file displayed when a user is logged in on Chamilo.
 *
 * It displays:
 * - personal course list
 * - menu bar
 * Search for CONFIGURATION parameters to modify settings
 * @package chamilo.main
 * @todo Shouldn't the SCRIPTVAL_ and CONFVAL_ constant be moved to the config page? Has anybody any idea what the are used for?
 *       If these are really configuration settings then we can add those to the dokeos config settings.
 * @todo check for duplication of functions with index.php (user_portal.php is orginally a copy of index.php)
 * @todo display_digest, shouldn't this be removed and be made into an extension?
 */

/**
 * INIT SECTION
 */

// Language files that should be included.
$language_file = array('courses', 'index','admin');

$cidReset = true; /* Flag forcing the 'current course' reset,
                    as we're not inside a course anymore  */

if (isset($_SESSION['this_section']))
    unset($_SESSION['this_section']); // For HTML editor repository.

/* Included libraries */

require_once './main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'userportal.lib.php';

api_block_anonymous_users(); // Only users who are logged in can proceed.

/* Constants and CONFIGURATION parameters */
$load_dirs = api_get_setting('show_documents_preview');

// Check if a user is enrolled only in one course for going directly to the course after the login.
if (api_get_setting('go_to_course_after_login') == 'true') {
    
    // Get the courses list
    $personal_course_list 	= UserManager::get_personal_session_course_list(api_get_user_id());
    
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
        $course_directory = $course_info['course_info']['path'];
        $id_session       = isset($course_info['id_session']) ? $course_info['id_session'] : 0;
       
        $url = api_get_path(WEB_COURSE_PATH).$course_directory.'/?id_session='.$id_session;
        header('location:'.$url);            
        exit;
    } 
}
$nameTools = get_lang('MyCourses');
$this_section = SECTION_COURSES;

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
	            	
	            }
	        });
	        
		});
	});
	</script>';
}

/* Sniffing system */

//store posts to sessions
if ($_SESSION['sniff_navigator']!="checked") {
	$_SESSION['sniff_navigator']=Security::remove_XSS($_POST['sniff_navigator']);
	$_SESSION['sniff_screen_size_w']=Security::remove_XSS($_POST['sniff_navigator_screen_size_w']);
	$_SESSION['sniff__screen_size_h']=Security::remove_XSS($_POST['sniff_navigator_screen_size_h']);
	$_SESSION['sniff_type_mimetypes']=Security::remove_XSS($_POST['sniff_navigator_type_mimetypes']);
	$_SESSION['sniff_suffixes_mimetypes']=Security::remove_XSS($_POST['sniff_navigator_suffixes_mimetypes']);
	$_SESSION['sniff_list_plugins']=Security::remove_XSS($_POST['sniff_navigator_list_plugins']);
	$_SESSION['sniff_check_some_activex']=Security::remove_XSS($_POST['sniff_navigator_check_some_activex']);
	$_SESSION['sniff_check_some_plugins']=Security::remove_XSS($_POST['sniff_navigator_check_some_plugins']);
	$_SESSION['sniff_java']=Security::remove_XSS($_POST['sniff_navigator_java']);
	$_SESSION['sniff_java_sun_ver']=Security::remove_XSS($_POST['sniff_navigator_java_sun_ver']);
}

/* MAIN CODE */

$controller = new IndexManager(get_lang('MyCourses'));

$user_id = api_get_user_id();

// Main courses and session list
$courses_and_sessions = $controller->return_courses_and_sessions($user_id);
$controller->tpl->assign('content', $courses_and_sessions);
 
if (api_get_setting('allow_browser_sniffer') == 'true') {
	if ($_SESSION['sniff_navigator']!="checked") {
		$controller->tpl->assign('show_sniff', 	1);
	} else {
		$controller->tpl->assign('show_sniff', 	0);
	}
}

//check for flash and message
$sniff_notification = '';
$some_activex=$_SESSION['sniff_check_some_activex'];
$some_plugins=$_SESSION['sniff_check_some_plugins'];
if(!empty($some_activex) || !empty($some_plugins)){
	if (! preg_match("/flash_yes/", $some_activex) && ! preg_match("/flash_yes/", $some_plugins)) {
		$sniff_notification = Display::return_message(get_lang('NoFlash'), 'warning', true);
		//js verification - To annoying of redirecting every time the page
		$controller->tpl->assign('sniff_notification',  $sniff_notification);
	}  
}

$controller->tpl->assign('profile_block', 				$controller->return_profile_block());
$controller->tpl->assign('account_block',				$controller->return_account_block());
$controller->tpl->assign('navigation_course_links', 	$controller->return_navigation_course_links());
$controller->tpl->assign('reservation_block', 			$controller->return_reservation_block());
$controller->tpl->assign('search_block', 				$controller->return_search_block());
$controller->tpl->assign('classes_block', 				$controller->return_classes_block());

//if (api_is_platform_admin() || api_is_drh()) {
    $controller->tpl->assign('skills_block',            $controller->return_skills_links());
//}
$controller->tpl->display_two_col_template();

// Deleting the session_id.
api_session_unregister('session_id');