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

//	MAIN CODE
$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$course_id = api_get_course_int_id();
$session_id = api_get_session_id();

if (api_is_allowed_to_edit(null, true)) {
	// HIDE
	if (!empty($_GET['hide'])) { // visibility 1 -> 0
		$sql = "UPDATE $tool_table SET visibility=0 WHERE c_id = $course_id AND id='".$id."'";
		Database::query($sql);
		$show_message = Display::return_message(get_lang('ToolIsNowHidden'), 'confirmation');
	} elseif (!empty($_GET['restore'])) {
		// visibility 0,2 -> 1
		// REACTIVATE
		$sql = "UPDATE $tool_table SET visibility=1  WHERE c_id = $course_id AND id='".$id."'";
		Database::query($sql);
		$show_message = Display::return_message(get_lang('ToolIsNowVisible'), 'confirmation');
	}
}

// Work with data post askable by admin of course
if (api_is_platform_admin()) {
	// Show message to confirm that a tool it to be hidden from available tools
	// visibility 0,1->2
	if (!empty($_GET['askDelete'])) {
        $content .='<div id="toolhide">'.get_lang('DelLk').'<br />&nbsp;&nbsp;&nbsp;
            <a href="'.api_get_self().'">'.get_lang('No').'</a>&nbsp;|&nbsp;
            <a href="'.api_get_self().'?delete=yes&id='.$id.'">'.get_lang('Yes').'</a>
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
    $content .=  '<div class="courseadminview" style="border:0px; margin-top: 0px;padding:5px;">
    <div class="normal-message" id="id_normal_message" style="display:none">';
        $content .=  '<img src="'.api_get_path(WEB_PATH).'main/inc/lib/javascript/indicator.gif"/>&nbsp;&nbsp;';
        $content .=  get_lang('PleaseStandBy');

    $content .=  '</div>
        <div class="confirmation-message" id="id_confirmation_message" style="display:none"></div></div>';
    $content .=  '<div id="activity-3col">';

    if (api_get_setting('show_session_data') == 'true' && $session_id > 0) {
        $content .= '<div class="courseadminview-activity-3col"><span class="viewcaption">'.get_lang('SessionData').'</span>
            <table width="100%">'.CourseHome::show_session_data($session_id).'</table>
        </div>';
    }

    $content .= '<div class="courseadminview-activity-3col"><span class="viewcaption">'.get_lang('Authoring').'</span>';
    $my_list  = CourseHome::get_tools_category(TOOL_AUTHORING);
    $content .= CourseHome::show_tools_category($my_list);
    $content .= '</div>';

    $content .= '<div class="courseadminview-activity-3col"><span class="viewcaption">'.get_lang('Interaction').'</span>';
    $my_list  = CourseHome::get_tools_category(TOOL_INTERACTION);
    $content .= CourseHome::show_tools_category($my_list);
    $content .= '</div>';

    $content .= '<div class="courseadminview-activity-3col"><span class="viewcaption">'.get_lang('Administration').'</span>';
    $my_list = CourseHome::get_tools_category(TOOL_ADMIN_PLATFORM);
    $content .= CourseHome::show_tools_category($my_list);
    $content .= '</div>';

} elseif (api_is_coach()) {
	if (api_get_setting('show_session_data') == 'true' && $session_id > 0) {
        $content .= '<div class="courseadminview-activity-3col"><span class="viewcaption">'.get_lang('SessionData').'</span>
			<table width="100%">';
				$content .= CourseHome::show_session_data($session_id);
             $content .=  '</table></div>';
	}

    $content .=  '<div class="Authoringview">';
				$my_list = CourseHome::get_tools_category(TOOL_STUDENT_VIEW);
				$content .= CourseHome::show_tools_category($my_list);
    $content .= '</div>';
    //	TOOLS AUTHORING

} else {
	$my_list = CourseHome::get_tools_category(TOOL_STUDENT_VIEW);
	if (count($my_list) > 0) {
        $content .= '<div class="course-student-view-activity-3col">';
		//ordering by get_lang name
		$order_tool_list = array();
		foreach($my_list as $key=>$new_tool) {
			$tool_name = CourseHome::translate_tool_name($new_tool);
			$order_tool_list [$key]= $tool_name;
		}		
		natsort($order_tool_list);		
		$my_temp_tool_array = array();
		foreach($order_tool_list as $key=>$new_tool) {
			$my_temp_tool_array[] = $my_list[$key];
		}
		$my_list = $my_temp_tool_array;

		$i = 0;
		foreach($my_list as $new_tool) {
			if ($i >= 10) {
				$my_list2[] = $new_tool;
			} else {
				$my_list1[] = $new_tool;
			}
			$i++;
		}
		$content .=CourseHome::show_tools_category($my_list1);
		$content .=CourseHome::show_tools_category($my_list2);
        $content .= '</div>';
	}
}
$content .= '</div>';
