<?php
/* For licensing terms, see /license.txt */

/**
* Template (front controller in MVC pattern) used for distpaching to the controllers depend on the current action  
* @author Christian Fasanando <christian1827@gmail.com>
* @author Julio Montoya <gugli100@gmail.com> Bug fixing, sql improvements
* 
* @package chamilo.attendance
*/

// name of the language file that needs to be included
$language_file = array ('course_description', 'course_info', 'pedaSuggest', 'userInfo', 'admin', 'agenda','tracking', 'trad4all');

// including files 
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'attendance.lib.php';
require_once api_get_path(LIBRARY_PATH).'app_view.php';
require_once api_get_path(LIBRARY_PATH).'ezpdf/class.ezpdf.php';
require_once 'attendance_controller.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'sortabletable.class.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/fe/exportgradebook.php';

// current section
$this_section = SECTION_COURSES;

// protect a course script
api_protect_course_script(true);

// get actions
$actions = array('attendance_list', 'attendance_sheet_list', 'attendance_sheet_print', 'attendance_sheet_add', 'attendance_add', 'attendance_edit', 'attendance_delete', 'attendance_delete_select');
$actions_calendar = array('calendar_list', 'calendar_add', 'calendar_edit', 'calendar_delete', 'calendar_all_delete');
$action  = 'attendance_list';

$course_id = '';
if (isset($_GET['cidReq'])){
    $course_id = $_GET['cidReq'];
}

if (isset($_GET['action']) && (in_array($_GET['action'],$actions) || in_array($_GET['action'],$actions_calendar))) {
	$action = $_GET['action'];
}
if (isset($_GET['isStudentView']) && $_GET['isStudentView'] == 'true') {
	$action = 'attendance_list';
}

// get attendance id
$attendance_id = 0; 
if (isset($_GET['attendance_id'])) {
	$attendance_id = intval($_GET['attendance_id']);
}

// get calendar id
$calendar_id = '';
if (isset($_GET['calendar_id'])) {
	$calendar_id = intval($_GET['calendar_id']);
}

// instance attendance object for using like library here
$attendance = new Attendance();

// attendance controller object
$attendance_controller = new AttendanceController();

// get attendance data
if (!empty($attendance_id)) {
	// attendance data by id
	$attendance_data = $attendance->get_attendance_by_id($attendance_id);
}

$htmlHeadXtra[] = '<script language="javascript">
		
$(function() {										
	$("table th img").click(function() {					
		var col_id = this.id;
		var col_split = col_id.split("_");							
		var calendar_id = col_split[2];					
		var class_img = $(this).attr("class");
							
		if (class_img == "img_unlock") {
			//lock 
			$(".checkbox_head_"+calendar_id).attr("disabled", true);						
			
			$(".row_odd  td.checkboxes_col_"+calendar_id).css({"background-color":"#F9F9F9", "border-left":"none","border-right":"none"});
			$(".row_even td.checkboxes_col_"+calendar_id).css({"background-color":"#FFF", "border-left":"none","border-right":"none"});
			$(".checkboxes_col_"+calendar_id+" input:checkbox").attr("disabled",true);							 						
			$(this).attr("src","'.api_get_path(WEB_CODE_PATH).'img/lock.gif"); 
			$(this).attr("title","'.get_lang('DateUnLock').'");
			$(this).attr("alt","'.get_lang('DateUnLock').'");
			$(this).attr("class","img_lock");
			$("#hidden_input_"+calendar_id).attr("value","");
			$("#hidden_input_"+calendar_id).attr("disabled",true);
			return false;
		} else {
			//unlock
			$(".checkbox_head_"+calendar_id).attr("disabled", false);
			$(".checkbox_head_"+calendar_id).removeAttr("disabled");
			
			
			$(".checkboxes_col_"+calendar_id).css({"background-color":"#e1e1e1", "border-left":"1px #CCC solid", "border-right":"1px #CCC solid" });
			$(".checkboxes_col_"+calendar_id+" input:checkbox").attr("disabled",false);						
			$(this).attr("src","'.api_get_path(WEB_CODE_PATH).'img/unlock.gif");
			$(this).attr("title","'.get_lang('DateLock').'");
			$(this).attr("alt","'.get_lang('DateLock').'");
			$(this).attr("class","img_unlock");												
			$("#hidden_input_"+calendar_id).attr("disabled",false);
			$("#hidden_input_"+calendar_id).attr("value",calendar_id);						
			return false;
		}						
	});				
		
	$("table th input:checkbox").click(function() {
		var col_id = this.id;
		var col_split = col_id.split("_");							
		var calendar_id = col_split[2];
		
		if (this.checked) {
			$(".checkboxes_col_"+calendar_id+" input:checkbox").attr("checked",true);			
		} else {
			$(".checkboxes_col_"+calendar_id+" input:checkbox").attr("checked",false);
		}
	});					
	
	$(".attendance-sheet-content .row_odd, .attendance-sheet-content .row_even").mouseover(function() {
		$(".row_odd").css({"background-color":"#F9F9F9"});
		$(".row_even").css({"background-color":"#FFF"});
	});	
	$(".attendance-sheet-content .row_odd, .attendance-sheet-content .row_even").mouseout(function() {
		$(".row_odd").css({"background-color":"#F9F9F9"});
		$(".row_even").css({"background-color":"#FFF"});
	});				
								
	$(".advanced_parameters").click(function() {				
		if ($("#id_qualify").css("display") == "none") {
			$("#id_qualify").css("display","block");
			$("#img_plus_and_minus").html(\'&nbsp;'.Display::return_icon('div_hide.gif',get_lang('Hide'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'\');
		} else {
			$("#id_qualify").css("display","none");
			$("#img_plus_and_minus").html(\'&nbsp;'.Display::return_icon('div_show.gif',get_lang('Show'),array('style'=>'vertical-align:middle')).'&nbsp;'.get_lang('AdvancedParameters').'\');
		}	
	});																							
});		

</script>';

// interbreadcrumbs
if (!empty($_GET['gradebook']) && $_GET['gradebook']=='view' ) {
	$_SESSION['gradebook']=Security::remove_XSS($_GET['gradebook']);
	$gradebook=	$_SESSION['gradebook'];
} elseif (empty($_GET['gradebook'])) {
	unset($_SESSION['gradebook']);
	$gradebook=	'';
}
$param_gradebook = '';
if (isset($_SESSION['gradebook'])) {
	$param_gradebook = '&gradebook='.$gradebook;
}
$student_param = '';
if (api_is_drh() && isset($_GET['student_id'])) {			
	$student_id = intval($_GET['student_id']);
	$student_param = '&student_id='.$student_id;
	$student_info  = api_get_user_info($student_id);
	$student_name  =  api_get_person_name($student_info['firstname'],$student_info['lastname']);
	$interbreadcrumb[] = array ('url' => api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?student='.$student_id, 'name' => $student_name);	
}
if (!empty($gradebook)) {
	$interbreadcrumb[] = array ('url' => api_get_path(WEB_CODE_PATH).'gradebook/index.php', 'name' => get_lang('ToolGradebook'));	
}
$interbreadcrumb[] = array ('url' => 'index.php?'.api_get_cidreq().'&action=attendance_list'.$param_gradebook.$student_param, 'name' => get_lang('ToolAttendance'));
if ($action == 'attendance_add') {
	$interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('CreateANewAttendance'));
}
if ($action == 'attendance_edit') {
	$interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('Edit'));
}
if ($action == 'attendance_sheet_list' || $action == 'attendance_sheet_add') {
	$interbreadcrumb[] = array ('url' => '#', 'name' => $attendance_data['name']);
}
if ($action == 'calendar_list' || $action == 'calendar_edit' || $action == 'calendar_delete' || $action == 'calendar_all_delete') {
	$interbreadcrumb[] = array ('url' => 'index.php?'.api_get_cidreq().'&action=attendance_sheet_list&attendance_id='.$attendance_id.$param_gradebook, 'name' => $attendance_data['name']);
	$interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('AttendanceCalendar'));	
}
if ($action == 'calendar_add') {
	$interbreadcrumb[] = array ('url' => 'index.php?'.api_get_cidreq().'&action=attendance_sheet_list&attendance_id='.$attendance_id.$param_gradebook, 'name' => $attendance_data['name']);
	$interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('AddDateAndTime'));	
}

// delete selected attendance
if (isset($_POST['action']) && $_POST['action'] == 'attendance_delete_select') {
	$attendance_controller->attendance_delete($_POST['id']);
}

// distpacher actions to controller

switch ($action) {	
	case 'attendance_list':	
        $attendance_controller->attendance_list();
		break;
	case 'attendance_add':
        if (api_is_allowed_to_edit(null, true)) {
            $attendance_controller->attendance_add();
        } else {
        	api_not_allowed();
        }
        break;
	case 'attendance_edit'		:	
        if (api_is_allowed_to_edit(null, true)) {
            $attendance_controller->attendance_edit($attendance_id);
        } else {
            api_not_allowed();
        }
		break;
	case 'attendance_delete'	:
    	if (api_is_allowed_to_edit(null, true)) {
        $attendance_controller->attendance_delete($attendance_id);
        } else { api_not_allowed();}
		 break;									
	case 'attendance_sheet_list':	
        $attendance_controller->attendance_sheet($action, $attendance_id, $student_id);
		break;
    case 'attendance_sheet_print':
        $attendance_controller->attendance_sheet_print($action, $attendance_id, $student_id, $course_id);
        break;
	case 'attendance_sheet_add' 	:	
        if (api_is_allowed_to_edit(null, true)) {
        $attendance_controller->attendance_sheet($action, $attendance_id);
        } else { api_not_allowed();}
		break;
    case 'lock_attendance'          :
    case 'unlock_attendance'        :
        if (api_is_allowed_to_edit(null, true)) {       
        $attendance_controller->lock_attendance($action, $attendance_id);
        } else { 
            api_not_allowed();
        }
        break;		  
	case 'calendar_add'  		:
	case 'calendar_edit' 		:
	case 'calendar_all_delete' 	:
	case 'calendar_delete' 		:
        if (!api_is_allowed_to_edit(null, true)) {
            api_not_allowed();
        }   
    case 'calendar_list'        :    	
        $attendance_controller->attendance_calendar($action, $attendance_id, $calendar_id);        
		break;
	default		  		:	
        $attendance_controller->attendance_list();
}

?>