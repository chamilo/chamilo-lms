<?php
/* For licensing terms, see /license.txt */

/**
 * Template (front controller in MVC pattern) used for dispatching
 * to the controllers depend on the current action.
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 * @author Julio Montoya <gugli100@gmail.com> Bug fixing, sql improvements
 *
 * @package chamilo.attendance
 */
require_once __DIR__.'/../inc/global.inc.php';
require_once 'attendance_controller.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/fe/exportgradebook.php';

$current_course_tool = TOOL_ATTENDANCE;

// current section
$this_section = SECTION_COURSES;

// protect a course script
api_protect_course_script(true);

// Get actions
$actions = [
    'attendance_list',
    'attendance_sheet_list',
    'attendance_sheet_add',
    'attendance_add',
    'attendance_edit',
    'attendance_delete',
    'attendance_delete_select',
    'attendance_set_invisible',
    'attendance_set_invisible_select',
    'attendance_set_visible',
    'attendance_set_visible_select',
    'attendance_restore',
    'attendance_sheet_export_to_pdf',
    'attendance_sheet_export_to_xls',
    'attendance_sheet_list_no_edit',
    'calendar_logins',
    'lock_attendance',
    'unlock_attendance',
    'attendance_sheet_qrcode',
];

$actions_calendar = [
    'calendar_list',
    'calendar_add',
    'calendar_edit',
    'calendar_delete',
    'calendar_all_delete',
];

$action = 'attendance_list';

$course_id = '';
if (isset($_GET['cidReq'])) {
    $course_id = $_GET['cidReq'];
}

if (isset($_GET['action']) &&
    (in_array($_GET['action'], $actions) || in_array($_GET['action'], $actions_calendar))
) {
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
$attendanceController = new AttendanceController();
$attendance_data = [];
// get attendance data
if (!empty($attendance_id)) {
    // attendance data by id
    $attendance_data = $attendance->get_attendance_by_id($attendance_id);
}

$htmlHeadXtra[] = '<script>
$(function() {
    $("table th .attendance_lock img").click(function() {
        var col_id = this.id;
        var col_split = col_id.split("_");
        var calendar_id = col_split[2];
        var class_img = $(this).attr("class");

        if (class_img == "img_unlock") {
            //lock
            $(".checkbox_head_"+calendar_id).attr("disabled", true);

            $(".row_odd  td.checkboxes_col_"+calendar_id).css({
                "opacity":"1",
                "background-color":"#F9F9F9",
                "border-left":"none",
                "border-right":"none"
            });
            $(".row_even td.checkboxes_col_"+calendar_id).css({
                "opacity":"1","background-color":"#FFF",      "border-left":"none","border-right":"none"
            });
            $(".checkboxes_col_"+calendar_id+" input:checkbox").attr("disabled",true);
            $(this).attr("src","'.Display::returnIconPath('lock-closed.png').'");
            $(this).attr("title","'.get_lang('DateUnLock').'");
            $(this).attr("alt","'.get_lang('DateUnLock').'");
            $(this).attr("class","img_lock");
            $("#hidden_input_"+calendar_id).attr("value","");
            $("#hidden_input_"+calendar_id).attr("disabled",true);
            return false;
        } else {
            //Unlock
            $(".checkbox_head_"+calendar_id).attr("disabled", false);
            $(".checkbox_head_"+calendar_id).removeAttr("disabled");

            $(".row_odd  td.checkboxes_col_"+calendar_id).css({
                "opacity":"1",
                "background-color":"#dcdcdc",
                "border-left":"1px #bbb solid",
                "border-right":"1px #bbb solid",
                "z-index":"1"
            });
            $(".row_even td.checkboxes_col_"+calendar_id).css({
                "opacity":"1",
                "background-color":"#eee",
                "border-left":"1px #bbb solid",
                "border-right":"1px #bbb solid",
                "z-index":"1"
            });

            $(".checkboxes_col_"+calendar_id).mouseover(function() {
                //$(".checkbox_head_"+calendar_id).removeAttr("opacity");
                //$("row_even td.checkboxes_col_"+calendar_id).css({"opacity":"1","background-color":"red", "border-left":"1px #EEEE00 solid", "border-right":"1px #EEEE00 solid" , "border-bottom":"1px #ccc solid" });
                //$("row_odd  td.checkboxes_col_"+calendar_id).css({"opacity":"1","background-color":"#FFF",       "border-left":"1px #EEEE00 solid", "border-right":"1px #EEEE00 solid" , "border-bottom":"1px #ccc solid" });
            });

            $(".checkboxes_col_"+calendar_id).mouseout(function() {
                //    $("row_even td.checkboxes_col_"+calendar_id).css({"opacity":"1","background-color":"#F9F9F9", "border-left":"1px #EEEE00 solid", "border-right":"1px #EEEE00 solid" , "border-bottom":"1px #ccc solid" });
                //    $("row_odd  td.checkboxes_col_"+calendar_id).css({"opacity":"1","background-color":"#FFF",       "border-left":"1px #EEEE00 solid", "border-right":"1px #EEEE00 solid" , "border-bottom":"1px #ccc solid" });
            });

            $(".checkboxes_col_"+calendar_id+" input:checkbox").attr("disabled",false);
            $(this).attr("src","'.Display::returnIconPath('lock-open.png').'");
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
            $(".checkboxes_col_"+calendar_id+" input:checkbox").prop("checked",true);
            $(".checkboxes_col_"+calendar_id+"").addClass("row_selected");
        } else {
            $(".checkboxes_col_"+calendar_id+" input:checkbox").prop("checked",false);
            $(".checkboxes_col_"+calendar_id+"").removeClass("row_selected");
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
});

</script>';

$allowSignature = api_get_configuration_value('enable_sign_attendance_sheet');
if ($allowSignature) {
    $htmlHeadXtra[] = api_get_asset('signature_pad/signature_pad.umd.js');
    $htmlHeadXtra[] = '<style>
        #search-user {
          background-image: url("/main/img/icons/22/sn-search.png");
          background-position: 10px 12px;
          background-repeat: no-repeat;
          width: 100%;
          font-size: 16px;
          padding: 12px 20px 12px 40px;
          border: 1px solid #ddd;
          margin: 12px 0px;
        }
    </style>';
}

$student_param = '';
$student_id = null;

if (api_is_drh() && isset($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);
    $student_param = '&student_id='.$student_id;
    $student_info = api_get_user_info($student_id);
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?student='.$student_id,
        'name' => $student_info['complete_name'],
    ];
}
if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'gradebook/index.php?'.api_get_cidreq(),
        'name' => get_lang('ToolGradebook'),
    ];
}
$interbreadcrumb[] = [
    'url' => 'index.php?'.api_get_cidreq().'&action=attendance_list&'.$student_param,
    'name' => get_lang('ToolAttendance'),
];
if ($action == 'attendance_add') {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('CreateANewAttendance')];
}
if ($action == 'attendance_edit') {
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Edit')];
}
if ($action == 'attendance_sheet_list' || $action == 'attendance_sheet_add') {
    $interbreadcrumb[] = ['url' => '#', 'name' => $attendance_data['name']];
}
if ($action == 'calendar_list' || $action == 'calendar_edit' || $action == 'calendar_delete' ||
    $action == 'calendar_all_delete'
) {
    $interbreadcrumb[] = [
        'url' => 'index.php?'.api_get_cidreq().'&action=attendance_sheet_list&attendance_id='.$attendance_id,
        'name' => $attendance_data['name'],
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('AttendanceCalendar')];
}
if ($action == 'calendar_add') {
    $interbreadcrumb[] = [
        'url' => 'index.php?'.api_get_cidreq().'&action=attendance_sheet_list&attendance_id='.$attendance_id,
        'name' => $attendance_data['name'],
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('AddDateAndTime')];
}

$allowToEdit = api_is_allowed_to_edit(null, true);

// Delete selected attendance
if (isset($_POST['action']) && $_POST['action'] == 'attendance_delete_select' && $allowToEdit) {
    $attendanceController->attendance_delete($_POST['id']);
}

if (isset($_POST['action']) && $_POST['action'] == 'attendance_set_invisible_select' && $allowToEdit) {
    $attendanceController->attendanceSetInvisible($_POST['id']);
}

if (isset($_POST['action']) && $_POST['action'] == 'attendance_set_visible_select' && $allowToEdit) {
    $attendanceController->attendanceSetVisible($_POST['id']);
}

switch ($action) {
    case 'attendance_sheet_qrcode':
        header("Content-Type: image/png");
        header("Content-Disposition: attachment; filename=AttendanceSheetQRcode.png");
        $renderer = new \BaconQrCode\Renderer\Image\Png();
        $renderer->setHeight(256);
        $renderer->setWidth(256);
        $writer = new \BaconQrCode\Writer($renderer);
        $attendanceSheetLink = api_get_path(WEB_CODE_PATH).'attendance/index.php?'.api_get_cidreq().'&action=attendance_sheet_list_no_edit&attendance_id='.$attendance_id;
        echo $writer->writeString($attendanceSheetLink);
        exit;
    case 'attendance_list':
        $attendanceController->attendance_list();
        break;
    case 'attendance_add':
        if ($allowToEdit) {
            $attendanceController->attendance_add();
        } else {
            api_not_allowed(true);
        }
        break;
    case 'attendance_edit':
        if ($allowToEdit) {
            $attendanceController->attendance_edit($attendance_id);
        } else {
            api_not_allowed(true);
        }
        break;
    case 'attendance_delete':
        if ($allowToEdit) {
            $attendanceController->attendance_delete($attendance_id);
            Display::addFlash(Display::return_message(get_lang('Deleted')));
        } else {
            api_not_allowed(true);
        }
        break;
    case 'attendance_set_invisible':
        if ($allowToEdit) {
            $attendanceController->attendanceSetInvisible($attendance_id);
        } else {
            api_not_allowed(true);
        }
        break;
    case 'attendance_set_visible':
        if ($allowToEdit) {
            $attendanceController->attendanceSetVisible($attendance_id);
        } else {
            api_not_allowed(true);
        }
        break;
    /*case 'attendance_restore':
        if ($allowToEdit) {
            $attendanceController->attendance_restore($attendance_id);
        } else {
            api_not_allowed(true);
        }
        break;*/
    case 'attendance_sheet_list':
        $attendanceController->attendance_sheet(
            $action,
            $attendance_id,
            $student_id,
            true
        );
        break;
    case 'attendance_sheet_list_no_edit':
        $attendanceController->attendance_sheet(
            $action,
            $attendance_id,
            $student_id,
            false
        );
        break;
    case 'attendance_sheet_export_to_pdf':
        $attendanceController->attendance_sheet_export_to_pdf(
            $action,
            $attendance_id,
            $student_id,
            $course_id
        );
        break;
    case 'attendance_sheet_export_to_xls':
        $groupId = isset($_REQUEST['group_id']) ? (int) $_REQUEST['group_id'] : null;
        $filter = isset($_REQUEST['filter']) ? $_REQUEST['filter'] : null;
        $attendanceController->attendanceSheetExportToXls(
            $attendance_id,
            (int) $student_id,
            (string) $course_id,
            $groupId,
            $filter
        );

        break;
    case 'attendance_sheet_add':
        if ($allowToEdit) {
            $attendanceController->attendance_sheet($action, $attendance_id);
        } else {
            api_not_allowed(true);
        }
        break;
    case 'lock_attendance':
    case 'unlock_attendance':
        if ($allowToEdit) {
            $attendanceController->lock_attendance($action, $attendance_id);
        } else {
            api_not_allowed(true);
        }
        break;
    case 'calendar_add':
    case 'calendar_edit':
    case 'calendar_all_delete':
    case 'calendar_delete':
        if (!$allowToEdit) {
            api_not_allowed(true);
        }
        //no break
    case 'calendar_list':
        $attendanceController->attendance_calendar(
            $action,
            $attendance_id,
            $calendar_id
        );
        break;
    case 'calendar_logins':
        if (api_is_course_admin() || api_is_drh()) {
            $attendanceController->getAttendanceBaseInLogin(false, true);
        }
        break;
    default:
        $attendanceController->attendance_list();
}
