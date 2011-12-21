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

require_once api_get_path(LIBRARY_PATH).'course_home.lib.php';

//	MAIN CODE

if (api_is_allowed_to_edit(null, true)) {
	// HIDE
	if (!empty($_GET['hide'])) { // visibility 1 -> 0
		/* -- session condition for visibility
		if (!empty($session_id)) {
			$sql = "select session_id FROM $tool_table WHERE id='".intval($_GET["id"])."' AND session_id = '".intval($session_id)."'";
			$rs = Database::query($sql);
			if (Database::num_rows($rs) > 0) {
	 			$sql="UPDATE $tool_table SET visibility=0 WHERE id='".intval($_GET["id"])."' AND session_id = '".intval($session_id)."'";
			} else {
				$sql_select = "select * FROM $tool_table WHERE id='".$_GET["id"]."'";
				$res_select = Database::query($sql_select);
				$row_select = Database::fetch_array($res_select);
				$sql = "INSERT INTO $tool_table(name,link,image,visibility,admin,address,added_tool,target,category,session_id)
						VALUES('{$row_select['name']}','{$row_select['link']}','{$row_select['image']}','0','{$row_select['admin']}','{$row_select['address']}','{$row_select['added_tool']}','{$row_select['target']}','{$row_select['category']}','$session_id')";
			}
		} else {
			$sql="UPDATE $tool_table SET visibility=0 WHERE id='".intval($_GET["id"])."'";
		}*/
		$sql = "UPDATE $tool_table SET visibility=0 WHERE id='".intval($_GET["id"])."'";
		Database::query($sql);
		Display::display_confirmation_message(get_lang('ToolIsNowHidden'));
	} elseif (!empty($_GET['restore'])) {
		// visibility 0,2 -> 1
		// REACTIVATE
		$sql = "UPDATE $tool_table SET visibility=1 WHERE id='".intval($_GET["id"])."'";
		Database::query($sql);
		Display::display_confirmation_message(get_lang('ToolIsNowVisible'));
	}
}

// Work with data post askable by admin of course
if (api_is_platform_admin()) {
	// Show message to confirm that a tool it to be hidden from available tools
	// visibility 0,1->2
	if (!empty($_GET['askDelete'])) {
?>
			<div id="toolhide"><?php echo get_lang('DelLk'); ?><br />&nbsp;&nbsp;&nbsp;
			<a href="<?php echo api_get_self(); ?>"><?php echo get_lang('No'); ?></a>&nbsp;|&nbsp;
			<a href="<?php echo api_get_self(); ?>?delete=yes&id=<?php echo Security::remove_XSS($_GET['id']); ?>"><?php echo get_lang('Yes'); ?></a>
			</div>
<?php
	}
	/*
	 * Process hiding a tools from available tools.
	 */
	elseif (isset($_GET['delete']) && $_GET['delete']) {
		//where $id is set?
		$id = intval($id);
		Database::query("DELETE FROM $tool_table WHERE id='$id' AND added_tool=1");
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

?>
	<div class="courseadminview" style="border:0px; margin-top: 0px;padding:5px 0px;">
		<div class="normal-message" id="id_normal_message" style="display:none">
<?php
			echo '<img src="'.api_get_path(WEB_PATH).'main/inc/lib/javascript/indicator.gif"/>&nbsp;&nbsp;';
			echo get_lang('PleaseStandBy');
?>
		</div>
		<div class="confirmation-message" id="id_confirmation_message" style="display:none"></div>
	</div>

<div id="activity-3col">

<?php
	if (api_get_setting('show_session_data') == 'true' && $id_session > 0) {
?>
	<div class="courseadminview-activity-3col">
		<span class="viewcaption"><?php echo get_lang('SessionData'); ?></span>
		<table width="100%">
<?php
			echo show_session_data($id_session);
?>
		</table>
	</div>
<?php
	}
?>
	<div class="courseadminview-activity-3col">
		<span class="viewcaption"><?php echo get_lang('Authoring'); ?></span>
	
<?php
			$my_list = CourseHome::get_tools_category(TOOL_AUTHORING);
			CourseHome::show_tools_category($my_list);
?>	
	</div>
	<div class="courseadminview-activity-3col">
		<span class="viewcaption"><?php echo get_lang('Interaction'); ?></span>		
<?php
			$my_list = CourseHome::get_tools_category(TOOL_INTERACTION);
			CourseHome::show_tools_category($my_list);
?>	
	</div>
	<div class="courseadminview-activity-3col">
		<span class="viewcaption"><?php echo get_lang('Administration'); ?></span>	
<?php
			$my_list = CourseHome::get_tools_category(TOOL_ADMIN_PLATEFORM);
			CourseHome::show_tools_category($my_list);
?>
	</div>
<?php
} elseif (api_is_coach()) {

	if (api_get_setting('show_session_data') == 'true' && $id_session > 0) {
?>
		<div class="courseadminview-activity-3col">
			<span class="viewcaption"><?php echo get_lang('SessionData'); ?></span>
			<table width="100%">
<?php
				echo CourseHome::show_session_data($id_session);
?>
			</table>
		</div>
<?php
	}
?>
		<div class="Authoringview">
<?php
				$my_list = CourseHome::get_tools_category(TOOL_STUDENT_VIEW);
				CourseHome::show_tools_category($my_list);
?>
		</div>		
<?php

//	TOOLS AUTHORING

} else {
	$my_list = CourseHome::get_tools_category(TOOL_STUDENT_VIEW);
	if (count($my_list) > 0) {
        echo '<div class="course-student-view-activity-3col">';
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
		CourseHome::show_tools_category($my_list1);
		CourseHome::show_tools_category($my_list2);
        echo '</div>';
	}
}
?>
</div>
