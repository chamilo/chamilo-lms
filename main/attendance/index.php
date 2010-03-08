<?php
/* For licensing terms, see /license.txt */

/**
* Template (front controller in MVC pattern) used for distpaching to the controllers depend on the current action  
* @author Christian Fasanando <christian1827@gmail.com>
* @author Carlos Vargas (link to attendance tool )<litox84@gmail.com> 
* @package chamilo.attendance
*/

// name of the language file that needs to be included
$language_file = array ('userInfo', 'admin');

// including files 
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'attendance.lib.php';
require_once api_get_path(LIBRARY_PATH).'app_view.php';
require_once 'attendance_controller.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'sortabletable.class.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';


// current section
$this_section = SECTION_COURSES;

// protect a course script
api_protect_course_script(true);

// defining constants and variables
$calendar_id = '';
if (isset($_GET['calendar_id'])) {
	$calendar_id = intval($_GET['calendar_id']);
}

// get actions
$actions = array('attendance_list', 'attendance_sheet_list', 'attendance_sheet_add', 'attendance_add', 'attendance_edit', 'attendance_delete', 'attendance_delete_select');
$actions_calendar = array('calendar_list', 'calendar_add', 'calendar_edit', 'calendar_delete', 'calendar_all_delete');
$action  = 'attendance_list';
if (isset($_GET['action']) && (in_array($_GET['action'],$actions) || in_array($_GET['action'],$actions_calendar))) {
	$action = $_GET['action'];
}

// get attendance id
$attendance_id = 0;
if (isset($_GET['attendance_id'])) {
	$attendance_id = intval($_GET['attendance_id']);
}

// instance object attendance for using like library here
$attendance = new Attendance();

// attendance controller object
$attendance_controller = new AttendanceController();

if (!empty($attendance_id)) {
	// attendance data by id
	$attendance_data = $attendance->get_attendance_by_id($attendance_id);
}

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.css" type="text/css" media="projection, screen">';
$htmlHeadXtra[] = '<script language="javascript">
		
			$(function() {	
									
				$("table th img").click(function() {
					
					var col_id = this.id;
					var col_split = col_id.split("_");							
					var calendar_id = col_split[2];
					var class_img = $(this).attr("class");
					
					if (class_img == "img_unlock") {
						$("#checkbox_head_"+calendar_id).attr("disabled",true);
						$(".row_odd td.checkboxes_col_"+calendar_id).css({"background-color":"#F2F2F2"});
						$(".row_even td.checkboxes_col_"+calendar_id).css({"background-color":"#FFF"});
						$(".checkboxes_col_"+calendar_id+" input:checkbox").attr("disabled",true);							 						
						$(this).attr("src","'.api_get_path(WEB_CODE_PATH).'img/lock.gif");
						$(this).attr("class","img_lock");
						$("#hidden_input_"+calendar_id).attr("value","");
						$("#hidden_input_"+calendar_id).attr("disabled",true);
						return false;
					} else {
						$("#checkbox_head_"+calendar_id).attr("disabled",false);
						$(".checkboxes_col_"+calendar_id).css({"background-color":"#e1e1e1"});
						$(".checkboxes_col_"+calendar_id+" input:checkbox").attr("disabled",false);						
						$(this).attr("src","'.api_get_path(WEB_CODE_PATH).'img/unlock.gif");
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
					if ($("#"+col_id).is(":checked")) {
						$(".checkboxes_col_"+calendar_id+" input:checkbox").attr("checked",true);
					} else {						
						$(".checkboxes_col_"+calendar_id+" input:checkbox").attr("checked",false);
					}
				});
					
				$(".attendance-sheet-content .row_odd, .attendance-sheet-content .row_even").mouseover(function() {
					$(".row_odd").css({"background-color":"#F2F2F2"});
					$(".row_even").css({"background-color":"#FFF"});
				});	
				$(".attendance-sheet-content .row_odd, .attendance-sheet-content .row_even").mouseout(function() {
					$(".row_odd").css({"background-color":"#F2F2F2"});
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
	$interbreadcrumb[] = array ('url' => '/main/mySpace/myStudents.php?student='.$student_id, 'name' => $student_name);	
}

if (!empty($gradebook)) {
	$interbreadcrumb[] = array ('url' => '/main/gradebook/index.php', 'name' => get_lang('Gradebook'));	
}
$interbreadcrumb[] = array ('url' => 'index.php?action=attendance_list'.$param_gradebook.$student_param, 'name' => get_lang('Attendance'));
if($action == 'attendance_add') $interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('CreateANewAttendance'));
if($action == 'attendance_edit') $interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('Edit'));
if($action == 'attendance_sheet_list' || $action == 'attendance_sheet_add') $interbreadcrumb[] = array ('url' => '#', 'name' => $attendance_data['name']);
if($action == 'calendar_list' || $action == 'calendar_edit' || $action == 'calendar_delete' || $action == 'calendar_all_delete') {
	$interbreadcrumb[] = array ('url' => 'index.php?'.api_get_cidreq().'&action=attendance_sheet_list&attendance_id='.$attendance_id.$param_gradebook, 'name' => $attendance_data['name']);
	$interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('AttendanceCalendar'));	
}
if($action == 'calendar_add') {
	$interbreadcrumb[] = array ('url' => 'index.php?'.api_get_cidreq().'&action=attendance_sheet_list&attendance_id='.$attendance_id.$param_gradebook, 'name' => $attendance_data['name']);
	$interbreadcrumb[] = array ('url' => '#', 'name' => get_lang('AddDateAndTime'));	
}

// delete selected attendance
if (isset($_POST['action']) && $_POST['action'] == 'attendance_delete_select') {
	$attendance_controller->attendance_delete($_POST['id']);
}



// distpacher actions to controller
switch ($action) {	
	case 'attendance_list'			:	$attendance_controller->attendance_list();
										break;
	case 'attendance_add'			:	$attendance_controller->attendance_add();
										break;
	case 'attendance_edit'			:	$attendance_controller->attendance_edit($attendance_id);
										break;
	case 'attendance_delete'		:	$attendance_controller->attendance_delete($attendance_id);
										break;									
	case 'attendance_sheet_list'	:	$attendance_controller->attendance_sheet($action, $attendance_id, $student_id);
										break;
	case 'attendance_sheet_add' 	:	$attendance_controller->attendance_sheet($action, $attendance_id);
										break;	
	case 'calendar_list' 			:	
	case 'calendar_add'  			:
	case 'calendar_edit' 			:
	case 'calendar_all_delete' 		:
	case 'calendar_delete' 			:	$attendance_controller->attendance_calendar($action, $attendance_id, $calendar_id);
										break;
	default		  					:	$attendance_controller->attendance_list();
}

?>