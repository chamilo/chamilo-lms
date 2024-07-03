<?php
/* For licensing terms, see /license.txt */

/**
 * View (MVC patter) for listing attendances.
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 *
 * @package chamilo.attendance
 */

// protect a course script
api_protect_course_script(true);

if (api_is_allowed_to_edit(null, true)) {
    echo '<div class="actions">';
    echo '<a href="index.php?'.api_get_cidreq().'&action=attendance_add">'.
        Display::return_icon('new_attendance_list.png', get_lang('CreateANewAttendance'), '', ICON_SIZE_MEDIUM).'</a>';
    echo '</div>';
}
$attendance = new Attendance();
if ($attendance->getNumberOfAttendances() == 0) {
    $attendance->set_name(get_lang('Attendances'));
    $attendance->set_description(get_lang('Attendances'));
    $attendance->attendance_add();
}
$default_column = $default_column ?? null;
$parameters = $parameters ?? [];
$table = new SortableTable(
    'attendance_list',
    ['Attendance', 'getNumberOfAttendances'],
    ['Attendance', 'get_attendance_data'],
    $default_column
);
$table->set_additional_parameters($parameters);
$table->set_header(0, '', false, ['style' => 'width:20px;']);
$table->set_header(1, get_lang('Name'));
$table->set_header(2, get_lang('Description'));
$table->set_header(3, get_lang('CountDoneAttendance'), true, ['style' => 'width:90px;']);

if (api_is_allowed_to_edit(null, true)) {
    $table->set_header(4, get_lang('Actions'), false, ['style' => 'text-align:center']);
    $actions = [
        'attendance_set_invisible_select' => get_lang('SetInvisible'),
        'attendance_set_visible_select' => get_lang('SetVisible'),
    ];

    $allow = api_get_setting('allow_delete_attendance');
    if ($allow === 'true') {
        $actions['attendance_delete_select'] = get_lang('DeleteAllSelectedAttendances');
    }
    $table->set_form_actions($actions);
}
if ($table->get_total_number_of_items() > 0) {
    $table->display();
}
