<?php
/* For licensing terms, see /license.txt */

/**
 * View (MVC patter) for thematic advance.
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 * @author Julio Montoya <gugli100@gmail.com> BeezNest 2011 Bug fixing
 *
 * @package chamilo.course_progress
 */

// protect a course script
api_protect_course_script(true);

if ($action === 'thematic_advance_add' || $action === 'thematic_advance_edit') {
    $header_form = get_lang('NewThematicAdvance');
    if ($action === 'thematic_advance_edit') {
        $header_form = get_lang('EditThematicAdvance');
    }

    // display form
    $form = new FormValidator(
        'thematic_advance',
        'POST',
        api_get_self().'?'.api_get_cidreq()
    );
    $form->addElement('header', $header_form);
    //$form->addElement('hidden', 'thematic_advance_token',$token);
    $form->addElement('hidden', 'action', $action);

    if (!empty($thematic_advance_id)) {
        $form->addElement('hidden', 'thematic_advance_id', $thematic_advance_id);
    }
    if (!empty($thematic_id)) {
        $form->addElement('hidden', 'thematic_id', $thematic_id);
    }

    $radios = [];
    $radios[] = $form->createElement(
        'radio',
        'start_date_type',
        null,
        get_lang('StartDateFromAnAttendance'),
        '1',
        [
            'onclick' => 'check_per_attendance(this)',
            'id' => 'from_attendance',
        ]
    );
    $radios[] = $form->createElement(
        'radio',
        'start_date_type',
        null,
        get_lang('StartDateCustom'),
        '2',
        [
            'onclick' => 'check_per_custom_date(this)',
            'id' => 'custom_date',
        ]
    );
    $form->addGroup($radios, null, get_lang('StartDateOptions'));

    if (empty($thematic_advance_data['attendance_id'])
    ) {
        $form->addElement('html', '<div id="div_custom_datetime" style="display:block">');
    } else {
        $form->addElement('html', '<div id="div_custom_datetime" style="display:none">');
    }

    $form->addElement('DateTimePicker', 'custom_start_date', get_lang('StartDate'));
    $form->addElement('html', '</div>');

    if (empty($thematic_advance_data['attendance_id'])
    ) {
        $form->addElement('html', '<div id="div_datetime_by_attendance" style="display:none">');
    } else {
        $form->addElement('html', '<div id="div_datetime_by_attendance" style="display:block">');
    }

    if (count($attendance_select) > 1) {
        $form->addElement(
            'select',
            'attendance_select',
            get_lang('Attendances'),
            $attendance_select,
            ['id' => 'id_attendance_select', 'onchange' => 'datetime_by_attendance(this.value)']
        );
    } else {
        $form->addElement(
            'label',
            get_lang('Attendances'),
            '<strong><em>'.get_lang('ThereAreNoAttendancesInsideCourse').'</em></strong>'
        );
    }

    $form->addElement('html', '<div id="div_datetime_attendance">');
    if (!empty($calendar_select)) {
        $form->addElement(
            'select',
            'start_date_by_attendance',
            get_lang('StartDate'),
            $calendar_select,
            ['id' => 'start_date_select_calendar']
        );
    }
    $form->addElement('html', '</div>');
    $form->addElement('html', '</div>');

    $form->addText(
        'duration_in_hours',
        get_lang('DurationInHours'),
        false,
        [
            'size' => '3',
            'id' => 'duration_in_hours_element',
            'autofocus' => 'autofocus',
        ]
    );

    $form->addHtmlEditor(
        'content',
        get_lang('Content'),
        false,
        false,
        [
            'ToolbarStartExpanded' => 'false',
            'ToolbarSet' => 'Basic',
            'Height' => '150',
        ]
    );

    if ($action == 'thematic_advance_add') {
        $form->addButtonSave(get_lang('Save'));
    } else {
        $form->addButtonUpdate(get_lang('Save'));
    }

    $attendance_select_item_id = null;
    if (count($attendance_select) > 1) {
        $i = 1;
        foreach ($attendance_select as $key => $attendance_select_item) {
            if ($i == 2) {
                $attendance_select_item_id = $key;
                break;
            }
            $i++;
        }
        if (!empty($attendance_select_item_id)) {
            $default['attendance_select'] = $attendance_select_item_id;
            if ($thematic_advance_id) {
                echo '<script> datetime_by_attendance("'.$attendance_select_item_id.'", "'.$thematic_advance_id.'"); </script>';
            } else {
                echo '<script> datetime_by_attendance("'.$attendance_select_item_id.'", 0); </script>';
            }
        }
    }

    $default['start_date_type'] = 2;
    $default['custom_start_date'] = date('Y-m-d H:i:s', api_strtotime(api_get_local_time()));
    $default['duration_in_hours'] = 1;

    if (!empty($thematic_advance_data)) {
        // set default values
        if ('thematic_advance_edit' == $action) {
            $default['content'] = isset($thematic_advance_data['content']) ? $thematic_advance_data['content'] : null;
            $default['duration_in_hours'] = isset($thematic_advance_data['duration']) ? $thematic_advance_data['duration'] : 1;
            if (empty($thematic_advance_data['attendance_id'])) {
                $default['start_date_type'] = 2;
                $default['custom_start_date'] = null;
                if (isset($thematic_advance_data['start_date'])) {
                    $default['custom_start_date'] = date(
                        'Y-m-d H:i:s',
                        api_strtotime(api_get_local_time($thematic_advance_data['start_date']))
                    );
                }
            } else {
                $default['start_date_type'] = 1;
                if (!empty($thematic_advance_data['start_date'])) {
                    $default['start_date_by_attendance'] = api_get_local_time($thematic_advance_data['start_date']);
                }

                $default['attendance_select'] = $thematic_advance_data['attendance_id'];
            }
        } else {
            $default['start_date_type'] = 2;
            $default['custom_start_date'] = date('Y-m-d H:i:s', api_strtotime(api_get_local_time()));
            $default['duration_in_hours'] = 1;
        }
    }
    $form->setDefaults($default);

    if ($form->validate()) {
        $values = $form->exportValues();

        if (isset($_POST['start_date_by_attendance'])) {
            $values['start_date_by_attendance'] = $_POST['start_date_by_attendance'];
        }

        $thematic = new Thematic();
        $thematic->set_thematic_advance_attributes(
            isset($values['thematic_advance_id']) ? $values['thematic_advance_id'] : null,
            $values['thematic_id'],
            $values['start_date_type'] == 1 && isset($values['attendance_select']) ? $values['attendance_select'] : 0,
            $values['content'],
            $values['start_date_type'] == 2 ? $values['custom_start_date'] : $values['start_date_by_attendance'],
            $values['duration_in_hours']
        );

        $affected_rows = $thematic->thematic_advance_save();

        if ($affected_rows) {
            // get last done thematic advance before move thematic list
            $last_done_thematic_advance = $thematic->get_last_done_thematic_advance();
            // update done advances with de current thematic list
            if (!empty($last_done_thematic_advance)) {
                $thematic->update_done_thematic_advances($last_done_thematic_advance);
            }
        }

        $redirectUrlParams = 'course_progress/index.php?'.api_get_cidreq().'&'.
            http_build_query([
                'action' => 'thematic_advance_list',
                'thematic_id' => $values['thematic_id'],
            ]);

        Display::addFlash(Display::return_message(get_lang('Updated')));

        header('Location: '.api_get_path(WEB_CODE_PATH).$redirectUrlParams);
        exit;
    }

    $form->display();
} elseif ($action == 'thematic_advance_list') {
    // thematic advance list
    echo '<div class="actions">';
    echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=thematic_details">'.
            Display::return_icon('back.png', get_lang("BackTo"), '', ICON_SIZE_MEDIUM).'</a>';
    if (api_is_allowed_to_edit(false, true)) {
        echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&action=thematic_advance_add&thematic_id='.$thematic_id.'"> '.
            Display::return_icon('add.png', get_lang('NewThematicAdvance'), '', ICON_SIZE_MEDIUM).'</a>';
    }
    echo '</div>';
    $table = new SortableTable(
        'thematic_advance_list',
        ['Thematic', 'get_number_of_thematic_advances'],
        ['Thematic', 'get_thematic_advance_data']
    );
    //$table->set_additional_parameters($parameters);
    $table->set_header(0, '', false, ['style' => 'width:20px;']);
    $table->set_header(1, get_lang('StartDate'), false);
    $table->set_header(2, get_lang('DurationInHours'), false, ['style' => 'width:80px;']);
    $table->set_header(3, get_lang('Content'), false);

    if (api_is_allowed_to_edit(null, true)) {
        $table->set_header(
            4,
            get_lang('Actions'),
            false,
            ['style' => 'text-align:center']
        );
    }
    $table->display();
}
