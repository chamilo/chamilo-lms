<?php

/* For licensing terms, see /license.txt */

/**
 * View (MVC patter) for attendance calendar (list, edit, add).
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 */

// protect a course script
api_protect_course_script(true);

if (!$is_locked_attendance || api_is_platform_admin()) {
    echo '<div class="actions">';
    if ('calendar_add' == $action) {
        echo '<a href="index.php?'.api_get_cidreq().'&action=calendar_list&attendance_id='.$attendance_id.'">'.
            Display::return_icon('back.png', get_lang('Attendance calendar'), '', ICON_SIZE_MEDIUM).'</a>';
    } else {
        echo '<a href="index.php?'.api_get_cidreq().'&action=attendance_sheet_list&attendance_id='.$attendance_id.'">'.
            Display::return_icon('back.png', get_lang('Attendance sheet'), '', ICON_SIZE_MEDIUM).'</a>';
        if (api_is_allowed_to_edit()) {
            echo '<a href="index.php?'.api_get_cidreq().'&action=calendar_add&attendance_id='.$attendance_id.'">'.
                Display::return_icon('add.png', get_lang('Add a date and time'), '', ICON_SIZE_MEDIUM).'</a>';
            echo '<a onclick="javascript:if(!confirm(\''.get_lang('Are you sure you want to delete all dates?').'\')) return false;" href="index.php?'.api_get_cidreq().'&action=calendar_all_delete&attendance_id='.$attendance_id.'">'.
                Display::return_icon('clean.png', get_lang('Clean the calendar of all lists'), '', ICON_SIZE_MEDIUM).'</a>';
        }
    }
    echo '</div>';
}

$message_information = get_lang('Attendance calendarDescription');
if (!empty($message_information)) {
    $message = '<strong>'.get_lang('Information').'</strong><br />';
    $message .= $message_information;
    echo Display::return_message($message, 'normal', false);
}

if (isset($error_repeat_date) && $error_repeat_date) {
    $message = get_lang('End date must be more than the start date');
    echo Display::return_message($message, 'error', false);
}

if (isset($error_checkdate) && $error_checkdate) {
    $message = get_lang('Invalid date');
    echo Display::return_message($message, 'error', false);
}

if (isset($action) && 'calendar_add' == $action) {
    $groupList = GroupManager::get_group_list(null, null, 1);
    $groupIdList = ['--'];
    foreach ($groupList as $group) {
        $groupIdList[$group['id']] = $group['name'];
    }

    // calendar add form
    $form = new FormValidator(
        'attendance_calendar_add',
        'POST',
        'index.php?action=calendar_add&attendance_id='.$attendance_id.'&'.api_get_cidreq(),
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

    $form->addElement(
        'date_picker',
        'end_date_time',
        get_lang('Repeat end date'),
        ['form_name' => 'attendance_calendar_add']
    );
    $defaults['end_date_time'] = date('Y-m-d');
    $form->addElement('html', '</div>');

    $defaults['repeat_type'] = 'weekly';

    $form->addSelect('groups', get_lang('Group'), $groupIdList);

    $form->addButtonCreate(get_lang('Save'));
    $form->setDefaults($defaults);
    $form->display();
} else {
    // Calendar list

    $groupList = GroupManager::get_group_list();
    $groupIdList = ['--'];
    foreach ($groupList as $group) {
        $groupIdList[$group['id']] = $group['name'];
    }

    echo Display::page_subheader(get_lang('Calendar list of attendances'));
    echo '<ul class="list-group">';
    if (!empty($attendance_calendar)) {
        foreach ($attendance_calendar as $calendar) {
            echo '<li class="list-group-item">';
            if ((isset($action) && 'calendar_edit' === $action) &&
                (isset($calendar_id) && $calendar_id == $calendar['id'])
            ) {
                // calendar edit form
                echo '<div class="attendance-calendar-edit">';
                $form = new FormValidator(
                    'attendance_calendar_edit',
                    'POST',
                    'index.php?action=calendar_edit&attendance_id='.$attendance_id.'&calendar_id='.$calendar_id.'&'.api_get_cidreq(),
                    ''
                );
                $form->addDateTimePicker(
                    'date_time',
                    [get_lang('Date')],
                    ['form_name' => 'attendance_calendar_edit'],
                    5
                );
                $defaults['date_time'] = $calendar['date_time'];
                $form->addButtonSave(get_lang('Save'));
                $form->addButtonCancel(get_lang('Cancel'), 'cancel');
                $form->setDefaults($defaults);
                $form->display();
                echo '</div>';
            } else {
                echo Display::return_icon(
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
                        echo '&nbsp;'.Display::label($groupIdList[$group['group_id']]);
                    }
                }

                if (!$is_locked_attendance || api_is_platform_admin()) {
                    if (api_is_allowed_to_edit()) {
                        echo '<div class="pull-right">';
                        echo '<a href="index.php?'.api_get_cidreq().'&action=calendar_edit&calendar_id='.(int) ($calendar['id']).'&attendance_id='.$attendance_id.'">'.
                            Display::return_icon('edit.png', get_lang('Edit'), ['style' => 'vertical-align:middle'], ICON_SIZE_SMALL).'</a>&nbsp;';
                        echo '<a onclick="javascript:if(!confirm(\''.get_lang('Are you sure you want to delete').'\')) return false;" href="index.php?'.api_get_cidreq().'&action=calendar_delete&calendar_id='.(int) ($calendar['id']).'&attendance_id='.$attendance_id.'">'.
                            Display::return_icon('delete.png', get_lang('Delete'), ['style' => 'vertical-align:middle'], ICON_SIZE_SMALL).'</a>';
                        echo '</div>';
                    }
                }
            }
            echo '</li>';
        }
    } else {
        echo Display::return_message(get_lang('There is no date/time registered yet'), 'warning');
    }
    echo '</ul>';
}
