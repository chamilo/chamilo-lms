<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/fe/exportgradebook.php';

$current_course_tool = TOOL_ATTENDANCE;

// current section
$this_section = SECTION_COURSES;

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
    'attendance_sheet_list_no_edit',
    'calendar_logins',
    'calendar_list',
    'calendar_add',
    'calendar_edit',
    'calendar_delete',
    'calendar_all_delete',
];

$action = 'attendance_list';
if (isset($_REQUEST['action']) && (in_array($_REQUEST['action'], $actions))) {
    $action = $_REQUEST['action'];
}

if (isset($_GET['isStudentView']) && 'true' === $_GET['isStudentView']) {
    $action = 'attendance_list';
}

$repo = Container::getAttendanceRepository();
$attendanceEntity = null;

// get attendance id
$attendanceId = 0;
if (isset($_GET['attendance_id'])) {
    $attendanceId = (int) ($_GET['attendance_id']);
    /** @var CAttendance $attendanceEntity */
    $attendanceEntity = $repo->find($attendanceId);
}

// get calendar id
$calendarId = '';
$calendarEntity = null;
if (isset($_GET['calendar_id'])) {
    $calendarId = (int) ($_GET['calendar_id']);
    /** @var CAttendanceCalendar $calendarEntity */
    $calendarEntity = Database::getManager()->getRepository(CAttendanceCalendar::class)->find($calendarId);
}

$token = Security::get_token();

// instance attendance object for using like library here
$attendance = new Attendance();

$htmlHeadXtra[] = '<script>
$(function() {
    $("table th img").click(function() {
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
            $(this).attr("title","'.get_lang('Unlock date').'");
            $(this).attr("alt","'.get_lang('Unlock date').'");
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
            $(this).attr("title","'.get_lang('Lock date').'");
            $(this).attr("alt","'.get_lang('Lock date').'");
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

$tpl = new Template(get_lang('Attendance'));
$student_param = '';
$student_id = null;
if (api_is_drh() && isset($_GET['student_id'])) {
    $student_id = (int) ($_GET['student_id']);
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
        'name' => get_lang('Assessments'),
    ];
}
$interbreadcrumb[] = [
    'url' => 'index.php?'.api_get_cidreq().'&action=attendance_list&'.$student_param,
    'name' => get_lang('Attendances'),
];

if ($attendanceEntity) {
    $interbreadcrumb[] = ['url' => '#', 'name' => $attendanceEntity->getName()];
}

if ('calendar_list' === $action || 'calendar_edit' === $action) {
    $interbreadcrumb[] = [
        'url' => 'index.php?'.api_get_cidreq().'&action=attendance_sheet_list&attendance_id='.$attendanceId,
        'name' => $attendanceEntity->getName(),
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Attendance calendar')];
}

$allowToEdit = api_is_allowed_to_edit(null, true);
$currentUrl = api_get_path(WEB_CODE_PATH).'attendance/index.php?'.api_get_cidreq();
$content = '';
switch ($action) {
    case 'attendance_list':
        if ($allowToEdit) {
            $actions = '<a href="index.php?'.api_get_cidreq().'&action=attendance_add">';
            $actions .= Display::return_icon(
                'new_attendance_list.png',
                get_lang('Create a new attendance list'),
                '',
                ICON_SIZE_MEDIUM
            );
            $actions .= '</a>';
            $content .= Display::toolbarAction('toolbar', [$actions]);
        }

        if (0 === $attendance->getNumberOfAttendances()) {
            $attendance->set_name(get_lang('Attendances'));
            $attendance->set_description(get_lang('Attendances'));
            $attendance->attendance_add();
        }
        $default_column = isset($default_column) ? $default_column : null;
        $parameters = isset($parameters) ? $parameters : null;
        $table = new SortableTable(
            'attendance_list',
            ['Attendance', 'getNumberOfAttendances'],
            ['Attendance', 'getAttendanceData'],
            $default_column
        );
        $table->set_additional_parameters($parameters);
        $table->set_header(0, '', false, ['style' => 'width:20px;']);
        $table->set_header(1, get_lang('Name'), true);
        $table->set_header(2, get_lang('Description'), true);
        $table->set_header(3, get_lang('# attended'), true, ['style' => 'width:90px;']);

        if (api_is_allowed_to_edit(null, true)) {
            $table->set_header(4, get_lang('Detail'), false, ['style' => 'text-align:center']);
            $actions = [
                'attendance_set_invisible_select' => get_lang('Set invisible'),
                'attendance_set_visible_select' => get_lang('Set visible'),
            ];

            $allow = api_get_setting('allow_delete_attendance');
            if ('true' === $allow) {
                $actions['attendance_delete_select'] = get_lang('Delete all selected attendances');
            }
            $table->set_form_actions($actions);
        }

        if ($table->get_total_number_of_items() > 0) {
            $content .= $table->return_table();
        }

        break;
    case 'attendance_add':
        if (!$allowToEdit) {
            api_not_allowed(true);
        }
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Create a new attendance list')];

        $form = new FormValidator(
            'attendance_add',
            'POST',
            $currentUrl.'&action=attendance_add&'
        );
        $attendance->setAttendanceForm($form);

        if ($form->validate()) {
            $attendance->set_name($_POST['title']);
            $attendance->set_description($_POST['description']);
            $attendance->set_attendance_qualify_title($_POST['attendance_qualify_title']);
            $attendance->set_attendance_weight($_POST['attendance_weight']);
            $link_to_gradebook = false;
            if (isset($_POST['attendance_qualify_gradebook']) &&
                1 == $_POST['attendance_qualify_gradebook']
            ) {
                $link_to_gradebook = true;
            }
            $attendance->category_id = isset($_POST['category_id']) ? $_POST['category_id'] : 0;
            $attendanceId = $attendance->attendance_add($link_to_gradebook);

            if ($attendanceId) {
                Skill::saveSkills($form, ITEM_TYPE_ATTENDANCE, $attendanceId);
                header('Location: '.$currentUrl.'&action=calendar_add&attendance_id='.$attendanceId);
                exit;
            }

            header('Location: '.$currentUrl);
            exit;
        } else {
            $content = $form->returnForm();
        }
        break;
    case 'attendance_edit':
        if (!$allowToEdit) {
            api_not_allowed(true);
        }

        $form = new FormValidator(
            'attendance_edit',
            'POST',
            'index.php?action=attendance_edit&'.api_get_cidreq().'&attendance_id='.$attendanceId
        );

        $attendance->setAttendanceForm($form, $attendanceEntity);

        if (!empty($_POST['title'])) {
            $attendance->set_name($_POST['title']);
            $attendance->set_description($_POST['description']);
            if (isset($_POST['attendance_qualify_title'])) {
                $attendance->set_attendance_qualify_title($_POST['attendance_qualify_title']);
            }

            if (isset($_POST['attendance_weight'])) {
                $attendance->set_attendance_weight($_POST['attendance_weight']);
            }

            $attendance->category_id = isset($_POST['category_id']) ? $_POST['category_id'] : '';
            $link_to_gradebook = false;
            if (isset($_POST['attendance_qualify_gradebook']) &&
                1 == $_POST['attendance_qualify_gradebook']
            ) {
                $link_to_gradebook = true;
            }
            $attendance->attendance_edit($attendanceEntity, $link_to_gradebook);

            Skill::saveSkills($form, ITEM_TYPE_ATTENDANCE, $attendanceId);
            Display::addFlash(Display::return_message(get_lang('Update successful')));

            Security::clear_token();
            header('Location:index.php?action=attendance_list&'.api_get_cidreq());
            exit;
        } else {
            $content = $form->returnForm();
        }
        break;
    case 'attendance_set_visible':
    case 'attendance_set_visible_select':
        if (!$allowToEdit) {
            api_not_allowed(true);
        }

        if (isset($_POST['id']) && is_array($_POST['id'])) {
            foreach ($_POST['id'] as $id) {
                $attendanceEntity = $repo->find($id);
                $attendance->changeVisibility($attendanceEntity, 1);
            }
        } else {
            $attendance->changeVisibility($attendanceEntity, 1);
        }

        Display::addFlash(Display::return_message(get_lang('Updated')));
        header('Location: '.$currentUrl);
        exit;
        break;
    case 'attendance_set_invisible':
    case 'attendance_set_invisible_select':
        if (isset($_POST['id']) && is_array($_POST['id'])) {
            foreach ($_POST['id'] as $id) {
                $attendanceEntity = $repo->find($id);
                $attendance->changeVisibility($attendanceEntity, 0);
            }
        } else {
            $attendance->changeVisibility($attendanceEntity, 0);
        }

        Display::addFlash(Display::return_message(get_lang('Updated')));
        header('Location: '.$currentUrl);
        exit;
        break;
    case 'attendance_delete_select':
    case 'attendance_delete':
        if (!$allowToEdit) {
            api_not_allowed(true);
        }

        if (isset($_POST['id']) && is_array($_POST['id'])) {
            foreach ($_POST['id'] as $id) {
                $attendanceEntity = $repo->find($id);
                $attendance->attendance_delete($attendanceEntity);
            }
        } else {
            $attendance->attendance_delete($attendanceEntity);
        }

        Display::addFlash(Display::return_message(get_lang('Deleted')));
        header('Location: '.$currentUrl);
        exit;

        break;
    case 'attendance_sheet_list_no_edit':
    case 'attendance_sheet_list':
        $edit = true;
        if ('attendance_sheet_list_no_edit' === $action) {
            $edit = false;
        }
        $content = $attendance->getCalendarSheet($edit, $attendanceEntity, $student_id);
        $tpl->assign('table', $content);
        $content = $tpl->fetch('@ChamiloCore/Attendance/sheet.html.twig');
        break;
    case 'attendance_sheet_export_to_pdf':
        $attendance->attendance_sheet_export_to_pdf(
            $attendanceId,
            $student_id,
            api_get_course_id()
        );

        break;
    case 'attendance_sheet_add':
        if (!$allowToEdit) {
            api_not_allowed(true);
        }

        if (isset($_POST['hidden_input'])) {
            foreach ($_POST['hidden_input'] as $cal_id) {
                $users_present = [];
                if (isset($_POST['check_presence'][$cal_id])) {
                    $users_present = $_POST['check_presence'][$cal_id];
                }
                $attendance->attendance_sheet_add(
                    $cal_id,
                    $users_present,
                    $attendanceEntity
                );
            }
        }

        Display::addFlash(Display::return_message(get_lang('Updated')));
        header('Location: '.$currentUrl.'&action=attendance_sheet_list&attendance_id='.$attendanceId);
        exit;

        break;
    case 'lock_attendance':
    case 'unlock_attendance':
        if (!$allowToEdit) {
            api_not_allowed(true);
        }

        if ('lock_attendance' === $action) {
            $attendance->lock($attendanceEntity);
        } else {
            $attendance->lock($attendanceEntity, false);
        }

        Display::addFlash(Display::return_message(get_lang('Updated')));
        header('Location: '.$currentUrl);
        exit;

        break;
    case 'calendar_add':
        $groupList = isset($_POST['groups']) ? [$_POST['groups']] : [];
        $interbreadcrumb[] = [
            'url' => 'index.php?'.api_get_cidreq().'&action=attendance_sheet_list&attendance_id='.$attendanceId,
            'name' => $attendanceEntity->getName(),
        ];
        $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Add a date and time')];

        if (!$allowToEdit) {
            api_not_allowed(true);
        }

        if ('POST' === strtoupper($_SERVER['REQUEST_METHOD'])) {
            if (!isset($_POST['cancel'])) {
                if (isset($_POST['repeat'])) {
                    //@todo  check this error_logs
                    $start_datetime = api_strtotime(
                        api_get_utc_datetime($_POST['date_time']),
                        'UTC'
                    );

                    $end_datetime = api_strtotime(api_get_utc_datetime($_POST['end_date_time'].' 23:59:59'), 'UTC');
                    $checkdate = api_is_valid_date(api_get_utc_datetime($_POST['end_date_time'].' 23:59:59'));

                    $repeat_type = $_POST['repeat_type'];
                    if (($end_datetime > $start_datetime) && $checkdate) {
                        $attendance->attendance_repeat_calendar_add(
                            $attendanceEntity,
                            $start_datetime,
                            $end_datetime,
                            $repeat_type,
                            $groupList
                        );
                        $action = 'calendar_list';
                    } else {
                        if (!$checkdate) {
                            $data['error_checkdate'] = true;
                        } else {
                            $data['error_repeat_date'] = true;
                        }
                        $data['repeat'] = true;
                        $action = 'calendar_add';
                    }
                } else {
                    $datetime = $_POST['date_time'];
                    $datetimezone = api_get_utc_datetime($datetime);
                    if (!empty($datetime)) {
                        $attendance->set_date_time($datetimezone);
                        $attendance->attendance_calendar_add($attendanceEntity, $groupList);
                    }
                }
            }

            header('Location: '.$currentUrl.'&action=attendance_sheet_list&attendance_id='.$attendanceId);
            exit;
        } else {
            $groupList = GroupManager::get_group_list(null, null, 1);
            $groupIdList = ['--'];
            foreach ($groupList as $group) {
                $groupIdList[$group['iid']] = $group['name'];
            }

            // calendar add form
            $form = new FormValidator(
                'attendance_calendar_add',
                'POST',
                'index.php?action=calendar_add&attendance_id='.$attendanceId.'&'.api_get_cidreq(),
                ''
            );
            $form->addElement('header', get_lang('Add a date time'));
            $form->addDateTimePicker(
                'date_time',
                [get_lang('Start Date')],
                ['id' => 'date_time']
            );

            $defaults['date_time'] = date('Y-m-d H:i', api_strtotime(api_get_local_time()));

            $form->addElement(
                'checkbox',
                'repeat',
                null,
                get_lang('Repeat date'),
                [
                    'onclick' => "javascript: if(this.checked){document.getElementById('repeat-date-attendance').style.display='block';}else{document.getElementById('repeat-date-attendance').style.display='none';}",
                ]
            );

            $defaults['repeat'] = isset($repeat) ? $repeat : null;
            if ($defaults['repeat']) {
                $form->addElement('html', '<div id="repeat-date-attendance" style="display:block">');
            } else {
                $form->addElement('html', '<div id="repeat-date-attendance" style="display:none">');
            }

            $a_repeat_type = [
                'daily' => get_lang('Daily'),
                'weekly' => get_lang('Weekly'),
                'monthlyByDate' => get_lang('Monthly, by date'),
            ];
            $form->addElement('select', 'repeat_type', get_lang('Repeat type'), $a_repeat_type);

            $form->addDatePicker(
                'end_date_time',
                get_lang('Repeat end date')
            );
            $defaults['end_date_time'] = date('Y-m-d');
            $form->addElement('html', '</div>');

            $defaults['repeat_type'] = 'weekly';

            $form->addSelect('groups', get_lang('Group'), $groupIdList);

            $form->addButtonCreate(get_lang('Save'));
            $form->setDefaults($defaults);
            $content = $form->returnForm();
        }
        break;
    case 'calendar_edit':
        if (!$allowToEdit) {
            api_not_allowed(true);
        }

        if ('POST' === strtoupper($_SERVER['REQUEST_METHOD'])) {
            if (!isset($_POST['cancel'])) {
                $attendance->set_date_time(api_get_utc_datetime($_POST['date_time']));
                $attendance->attendance_calendar_edit($calendarId, $attendanceEntity);

                Display::addFlash(Display::return_message(get_lang('Updated')));
            }

            header('Location: '.$currentUrl.'&action=calendar_list&attendance_id='.$attendanceId);
            exit;
        } else {
            // calendar edit form
            $content .= '<div class="attendance-calendar-edit">';
            $form = new FormValidator(
                'attendance_calendar_edit',
                'POST',
                'index.php?action=calendar_edit&attendance_id='.$attendanceId.'&calendar_id='.$calendarId.'&'.api_get_cidreq(),
                ''
            );
            $form->addDateTimePicker(
                'date_time',
                [get_lang('Date')],
                ['form_name' => 'attendance_calendar_edit'],
                5
            );
            $defaults['date_time'] = $calendarEntity->getDateTime()->format('Y-m-d H:i:s');
            $form->addButtonSave(get_lang('Save'));
            $form->addButtonCancel(get_lang('Cancel'), 'cancel');
            $form->setDefaults($defaults);
            $content .= $form->returnForm();
            $content .= '</div>';
        }
        break;
    case 'calendar_all_delete':
        if (!$allowToEdit) {
            api_not_allowed(true);
        }

        $attendance->attendance_calendar_delete(0, $attendanceEntity, true);
        Display::addFlash(Display::return_message(get_lang('Deleted')));

        header('Location: '.$currentUrl.'&action=calendar_list&attendance_id='.$attendanceId);
        exit;
        break;
    case 'calendar_delete':
        if (!$allowToEdit) {
            api_not_allowed(true);
        }
        $attendance->attendance_calendar_delete($calendarId, $attendanceEntity);
        Display::addFlash(Display::return_message(get_lang('Deleted')));

        header('Location: '.$currentUrl.'&action=calendar_list&attendance_id='.$attendanceId);
        exit;
        break;
    case 'calendar_list':
        $groupList = isset($_POST['groups']) ? [$_POST['groups']] : [];
        $attendance_calendar = $attendance->get_attendance_calendar(
            $attendanceId,
            'all',
            null,
            null,
            true
        );
        $is_locked_attendance = $attendance->is_locked_attendance($attendanceId);

        if (!$is_locked_attendance || api_is_platform_admin()) {
            $actions = '';
            if ('calendar_add' === $action) {
                $actions .= '<a href="index.php?'.api_get_cidreq().'&action=calendar_list&attendance_id='.$attendanceId.'">'.
                    Display::return_icon('back.png', get_lang('Attendance calendar'), '', ICON_SIZE_MEDIUM).'</a>';
            } else {
                $actions .= '<a href="index.php?'.api_get_cidreq().'&action=attendance_sheet_list&attendance_id='.$attendanceId.'">'.
                    Display::return_icon('back.png', get_lang('Attendance sheet'), '', ICON_SIZE_MEDIUM).'</a>';
                if (api_is_allowed_to_edit()) {
                    $actions .= '<a href="index.php?'.api_get_cidreq().'&action=calendar_add&attendance_id='.$attendanceId.'">'.
                        Display::return_icon('add.png', get_lang('Add a date and time'), '', ICON_SIZE_MEDIUM).'</a>';
                    $actions .= '<a onclick="javascript:if(!confirm(\''.get_lang('Are you sure you want to delete all dates?').'\')) return false;" href="index.php?'.api_get_cidreq().'&action=calendar_all_delete&attendance_id='.$attendanceId.'">'.
                        Display::return_icon('clean.png', get_lang('Clean the calendar of all lists'), '', ICON_SIZE_MEDIUM).'</a>';
                }
            }
            $content .= Display::toolbarAction('toolbar', [$actions]);
        }

        $message_information = get_lang('The attendance calendar allows you to register attendance lists (one per real session the students need to attend). Add new attendance lists here.');
        if (!empty($message_information)) {
            $message = '<strong>'.get_lang('Information').'</strong><br />';
            $message .= $message_information;
            $content .= Display::return_message($message, 'normal', false);
        }

        if (isset($error_repeat_date) && $error_repeat_date) {
            $message = get_lang('End date must be more than the start date');
            $content .= Display::return_message($message, 'error', false);
        }

        if (isset($error_checkdate) && $error_checkdate) {
            $message = get_lang('Invalid date');
            $content .= Display::return_message($message, 'error', false);
        }

        // Calendar list
        $groupList = GroupManager::get_group_list();
        $groupIdList = ['--'];
        foreach ($groupList as $group) {
            $groupIdList[$group['iid']] = $group['name'];
        }

        $content .= Display::page_subheader(get_lang('Calendar list of attendances'));
        $content .= '<ul class="list-group">';
        if (!empty($attendance_calendar)) {
            foreach ($attendance_calendar as $calendar) {
                $content .= '<li class="list-group-item">';
                $content .= Display::return_icon(
                        'lp_calendar_event.png',
                        get_lang('Date DateTime time'),
                        null,
                        ICON_SIZE_MEDIUM
                    ).' '.
                    substr(
                        $calendar['date_time'],
                        0,
                        strlen($calendar['date_time']) - 3
                    ).
                    '&nbsp;';

                if (isset($calendar['groups']) && !empty($calendar['groups'])) {
                    foreach ($calendar['groups'] as $group) {
                        $content .= '&nbsp;'.Display::label($groupIdList[$group['group_id']]);
                    }
                }

                if (!$is_locked_attendance || api_is_platform_admin()) {
                    if (api_is_allowed_to_edit()) {
                        $content .= '<div class="pull-right">';
                        $content .= '<a href="index.php?'.api_get_cidreq().'&action=calendar_edit&calendar_id='.(int) ($calendar['iid']).'&attendance_id='.$attendanceId.'">'.
                            Display::return_icon('edit.png', get_lang('Edit'), ['style' => 'vertical-align:middle'], ICON_SIZE_SMALL).'</a>&nbsp;';
                        $content .= '<a onclick="javascript:if(!confirm(\''.get_lang('Are you sure you want to delete').'\')) return false;" href="index.php?'.api_get_cidreq().'&action=calendar_delete&calendar_id='.(int) ($calendar['iid']).'&attendance_id='.$attendanceId.'">'.
                            Display::return_icon('delete.png', get_lang('Delete'), ['style' => 'vertical-align:middle'], ICON_SIZE_SMALL).'</a>';
                        $content .= '</div>';
                    }
                }

                $content .= '</li>';
            }

            /* } else {
                 echo Display::return_message(get_lang('There is no date/time registered yet'), 'warning');
             }*/
            $content .= '</ul>';
        }

        break;
    case 'calendar_logins':
        if (api_is_course_admin() || api_is_drh()) {
            $result = $attendance->getAttendanceBaseInLogin(false, true);
            $actions = '<a href="index.php?'.api_get_cidreq().'&action=calendar_list">'.
                Display::return_icon('back.png', get_lang('AttendanceCalendar'), '', ICON_SIZE_MEDIUM).
                '</a>';
            $content .= Display::toolbarAction('toolbar', [$actions]);
            $content .= $result['form'];
            $content .= $result['table'];
        }
        break;
}

$tpl->assign('content', $content);
//$tpl->assign('actions', $toolbar);
$tpl->display_one_col_template();
