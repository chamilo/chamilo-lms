<?php
/*
 * This script insert session extra fields
 */

//exit;

require_once '../../main/inc/global.inc.php';

api_protect_admin_script();

$teachingHours = new ExtraField('session');
$teachingHours->save(array(
    'value_type' => ExtraField::FIELD_TYPE_INTEGER,
    'variable' => 'teaching_hours',
    'display_text' => get_lang('TeachingHours'),
    'visible' => 1,
    'changeable' => 1
));

$cost = new ExtraField('session');
$cost->save(array(
    'value_type' => ExtraField::FIELD_TYPE_FLOAT,
    'variable' => 'cost',
    'display_text' => get_lang('Cost'),
    'visible' => 1,
    'changeable' => 1
));

$vacancies = new ExtraField('session');
$vacancies->save(array(
    'value_type' => ExtraField::FIELD_TYPE_INTEGER,
    'variable' => 'vacancies',
    'display_text' => get_lang('Vacancies'),
    'visible' => 1,
    'changeable' => 1
));

$recommendedNumberOfParticipants = new ExtraField('session');
$recommendedNumberOfParticipants->save(array(
    'value_type' => ExtraField::FIELD_TYPE_INTEGER,
    'variable' => 'recommended_number_of_participants',
    'display_text' => get_lang('RecommendedNumberOfParticipants'),
    'visible' => 1,
    'changeable' => 1
));

$place = new ExtraField('session');
$place->save(array(
    'value_type' => ExtraField::FIELD_TYPE_ALPHANUMERIC,
    'variable' => 'place',
    'display_text' => get_lang('Place'),
    'visible' => 1,
    'changeable' => 1
));

$schedule = new ExtraField('session');
$schedule->save(array(
    'value_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'schedule',
    'display_text' => get_lang('Schedule'),
    'visible' => 1,
    'changeable' => 1
));

$allowVisitors = new ExtraField('session');
$allowVisitors->save(array(
    'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
    'variable' => 'allow_visitors',
    'display_text' => get_lang('AllowVisitors'),
    'visible' => 1,
    'changeable' => 1
));

$modeOptions = array(
    get_lang('Online'),
    get_lang('Presencial'),
    get_lang('B-Learning')
);

$mode = new ExtraField('session');
$mode->save(array(
    'value_type' => ExtraField::FIELD_TYPE_SELECT,
    'variable' => 'mode',
    'display_text' => get_lang('Mode'),
    'visible' => 1,
    'changeable' => 1,
    'field_options' => implode('; ', $modeOptions)
));

$isInductionSession = new ExtraField('session');
$isInductionSession->save(array(
    'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
    'variable' => 'is_induccion_session',
    'display_text' => get_lang('IsInductionSession'),
    'visible' => 1,
    'changeable' => 1
));

$isOpenSession = new ExtraField('session');
$isOpenSession->save(array(
    'value_type' => ExtraField::FIELD_TYPE_CHECKBOX,
    'variable' => 'is_open_session',
    'display_text' => get_lang('IsOpenSession'),
    'visible' => 1,
    'changeable' => 1
));

$duration = new ExtraField('session');
$duration->save(array(
    'value_type' => ExtraField::FIELD_TYPE_LETTERS_ONLY,
    'variable' => 'human_text_duration',
    'display_text' => get_lang('DurationInWords'),
    'visible' => 1,
    'changeable' => 1
));

$showStatusOptions = array(
    get_lang('Open'),
    get_lang('InProcess'),
    get_lang('Closed')
);

$showStatus = new ExtraField('session');
$showStatus->save(array(
    'value_type' => ExtraField::FIELD_TYPE_SELECT,
    'variable' => 'show_status',
    'display_text' => get_lang('ShowStatus'),
    'visible' => 1,
    'changeable' => 1,
    'field_options' => implode('; ', $showStatusOptions)
));

$publicationStartDate = new ExtraField('session');
$publicationStartDate->save(array(
    'value_type' => ExtraField::FIELD_TYPE_DATE,
    'variable' => 'publication_start_date',
    'display_text' => get_lang('PublicationStartDate'),
    'visible' => 1,
    'changeable' => 1
));

$publicationEndDate = new ExtraField('session');
$publicationEndDate->save(array(
    'value_type' => ExtraField::FIELD_TYPE_DATE,
    'variable' => 'publication_end_date',
    'display_text' => get_lang('PublicationEndDate'),
    'visible' => 1,
    'changeable' => 1
));

$banner = new ExtraField('session');
$banner->save(array(
    'value_type' => ExtraField::FIELD_TYPE_FILE_IMAGE,
    'variable' => 'banner',
    'display_text' => get_lang('SessionBanner'),
    'visible' => 1,
    'changeable' => 1
));

$brochure = new ExtraField('session');
$brochure->save(array(
    'value_type' => ExtraField::FIELD_TYPE_FILE,
    'variable' => 'brochure',
    'display_text' => get_lang('Brochure'),
    'visible' => 1,
    'changeable' => 1
));

$targetOptions = array(
    get_lang('Minedu'),
    get_lang('Regiones')
);

$target = new ExtraField('session');
$target->save(array(
    'value_type' => ExtraField::FIELD_TYPE_SELECT,
    'variable' => 'target',
    'display_text' => get_lang('Target'),
    'visible' => 1,
    'changeable' => 1,
    'field_options' => implode('; ', $targetOptions)
));

$shortDescription = new ExtraField('session');
$shortDescription->save(array(
    'value_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'short_description',
    'display_text' => get_lang('ShortDescription'),
    'visible' => 1,
    'changeable' => 1
));

$id = new ExtraField('session');
$id->save(array(
    'value_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'code',
    'display_text' => get_lang('Code'),
    'visible' => 1,
    'changeable' => 1
));
