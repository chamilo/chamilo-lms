<?php

//Removing extra fields

$extra_fields = array('exam_room', 'exam_schedule', 'exam_password');

foreach($extra_fields as $extra) {
    $extra_field = new ExtraField('user');
    $field_info = $extra_field->get_handler_field_info_by_field_variable($extra);
    if (isset($field_info['id'])) {
        $extra_field->delete($field_info['id']);
    }
}