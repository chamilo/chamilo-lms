<?php

//Adding extra fields

$extra_field = new ExtraField('user');
$params = array(
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'field_variable' => 'exam_room',
    'field_display_text' => 'Aula',
    'field_visible' => false
);
$extra_field->save($params);

$params = array(
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'field_variable' => 'exam_schedule',
    'field_display_text' => 'Horario',
    'field_visible' => false
);
$extra_field->save($params);

$params = array(
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'field_variable' => 'exam_password',
    'field_display_text' => 'Password',
    'field_visible' => false
);
$extra_field->save($params);