<?php
/* For licensing terms, see /license.txt */

/**
 *   HOME PAGE FOR EACH COURSE
 *
 *	This page, included in every course's index.php is the home
 *	page. To make administration simple, the teacher edits his
 *	course from the home page. Only the login detects that the
 *	visitor is allowed to activate, deactivate home page links,
 *	access to the teachers tools (statistics, edit forums...).
 *
 *	@package chamilo.course_home
 */

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$course_id = api_get_course_int_id();

//	MAIN CODE

if (api_is_allowed_to_edit(null, true)) {
	// HIDE
	if (!empty($_GET['hide'])) {
		$sql = "UPDATE $tool_table SET visibility=0 WHERE c_id = $course_id AND id=".$id;
		Database::query($sql);
		$show_message = Display::return_message(get_lang('ToolIsNowHidden'), 'confirmation');
	} elseif (!empty($_GET['restore'])) {
		// visibility 0,2 -> 1
		// REACTIVATE
		$sql = "UPDATE $tool_table SET visibility=1 WHERE c_id = $course_id AND id=".$id;
		Database::query($sql);
		//$show_message = Display::return_message(get_lang('ToolIsNowVisible'),'confirmation');
	}
}

// Work with data post askable by admin of course
if (api_is_platform_admin()) {
	// Show message to confirm that a tool it to be hidden from available tools
	// visibility 0,1->2
	if (!empty($_GET['askDelete'])) {
        $content .='<div id="toolhide">'.get_lang('DelLk').'<br />&nbsp;&nbsp;&nbsp;
            <a href="'.api_get_self().'">'.get_lang('No').'</a>&nbsp;|&nbsp;
            <a href="'.api_get_self().'?delete=yes&id='.intval($_GET['id']).'">'.get_lang('Yes').'</a>
        </div>';
	} elseif (isset($_GET['delete']) && $_GET['delete']) {
        /*
        * Process hiding a tools from available tools.
        */
		//where $id is set?
		$id = intval($id);
		Database::query("DELETE FROM $tool_table WHERE c_id = $course_id AND id='$id' AND added_tool=1");
	}
}

//	COURSE ADMIN ONLY VIEW

// Start of tools for CourseAdmins (teachers/tutors)
if (api_is_allowed_to_edit(null, true) && !api_is_coach()) {

	$current_protocol = $_SERVER['SERVER_PROTOCOL'];
	$current_host = $_SERVER['HTTP_HOST'];
	$server_protocol = substr($current_protocol, 0, strrpos($current_protocol, '/'));
	$server_protocol = $server_protocol.'://';
	if ($current_host == 'localhost') {
		// Get information of path
		$info = explode('courses', api_get_self());
		$path_work = substr($info[0], 1);
	} else {
		$path_work = '';
	}
    
	$content .=  '<div class="courseadminview" style="border:0px; margin-top: 0px;padding:0px;">
		<div class="normal-message" id="id_normal_message" style="display:none">';
			$content .=  '<img src="'.api_get_path(WEB_PATH).'main/inc/lib/javascript/indicator.gif"/>&nbsp;&nbsp;';
			$content .=  get_lang('PleaseStandBy');

		$content .=  '</div>
		<div class="confirmation-message" id="id_confirmation_message" style="display:none"></div>
	</div>';


	if (api_get_setting('show_session_data') == 'true' && $id_session > 0) {
        $content .= '<div class="courseadminview">
            <span class="viewcaption">'.get_lang('SessionData').'</span>
            <table class="course_activity_home">'.CourseHome::show_session_data($id_session).'
            </table>
        </div>';
	}

    $my_list = CourseHome::get_tools_category(TOOL_AUTHORING);    
	$items = CourseHome::show_tools_category($my_list);    
    $content .= return_block(get_lang('Authoring'),  $items);
    
	
    $my_list = CourseHome::get_tools_category(TOOL_INTERACTION);
    $list2 = CourseHome::get_tools_category(TOOL_COURSE_PLUGIN);
    $my_list = array_merge($my_list,$list2);
    $items =  CourseHome::show_tools_category($my_list);
	
    $content .= return_block(get_lang('Interaction'),  $items);
        
	
    $my_list = CourseHome::get_tools_category(TOOL_ADMIN_PLATFORM);
    $items = CourseHome::show_tools_category($my_list);	
        
    $content .= return_block(get_lang('Administration'),  $items);
    
} elseif (api_is_coach()) {
	if (api_get_setting('show_session_data') == 'true' && $id_session > 0) {

		$content .= '<div class="row">
			<span class="viewcaption">'.get_lang('SessionData').'</span>
			<table class="course_activity_home">';
				$content .= CourseHome::show_session_data($id_session);
             $content .=  '</table></div>';
	}

    $content .=  '<div class="row">';
				$my_list = CourseHome::get_tools_category(TOOL_STUDENT_VIEW);
				$content .= CourseHome::show_tools_category($my_list);
    $content .= '</div>';
    //	TOOLS AUTHORING

} else {
	$my_list = CourseHome::get_tools_category(TOOL_STUDENT_VIEW);
	if (count($my_list) > 0) {
        $content .= '<div class="row">';
        $content .= CourseHome::show_tools_category($my_list);
        $content .= '</div>'; 
	}
}

function return_block($title, $content) {    
    $html = '<div class="row"><div class="span12"><div class="page-header"><h3>'.$title.'</h3></div></div></div><div class="row">'.$content.'</div>';
    return $html;
}