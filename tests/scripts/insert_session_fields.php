<?php
/*
 * This script insert session extra fields
 */

//exit;

require_once '../../main/inc/global.inc.php';

api_protect_admin_script();

$teachingHours = new ExtraField('session');
$teachingHours->save(array(
    'field_type' => ExtraField::FIELD_TYPE_INTEGER,
    'field_variable' => 'teaching_hours',
    'field_display_text' => get_lang('TeachingHours'),
    'field_visible' => 1,
    'field_changeable' => 1
));

$cost = new ExtraField('session');
$cost->save(array(
    'field_type' => ExtraField::FIELD_TYPE_FLOAT,
    'field_variable' => 'cost',
    'field_display_text' => get_lang('Cost'),
    'field_visible' => 1,
    'field_changeable' => 1
));

$vacancies = new ExtraField('session');
$vacancies->save(array(
    'field_type' => ExtraField::FIELD_TYPE_INTEGER,
    'field_variable' => 'vacancies',
    'field_display_text' => get_lang('Vacancies'),
    'field_visible' => 1,
    'field_changeable' => 1
));

$recommendedNumberOfParticipants = new ExtraField('session');
$recommendedNumberOfParticipants->save(array(
    'field_type' => ExtraField::FIELD_TYPE_INTEGER,
    'field_variable' => 'recommended_number_of_participants',
    'field_display_text' => get_lang('RecommendedNumberOfParticipants'),
    'field_visible' => 1,
    'field_changeable' => 1
));

$place = new ExtraField('session');
$place->save(array(
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'field_variable' => 'place',
    'field_display_text' => get_lang('Place'),
    'field_visible' => 1,
    'field_changeable' => 1
));

$schedule = new ExtraField('session');
$schedule->save(array(
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'field_variable' => 'schedule',
    'field_display_text' => get_lang('Schedule'),
    'field_visible' => 1,
    'field_changeable' => 1
));

$allowVisitors = new ExtraField('session');
$allowVisitors->save(array(
    'field_type' => ExtraField::FIELD_TYPE_CHECKBOX,
    'field_variable' => 'allow_visitors',
    'field_display_text' => get_lang('AllowVisitors'),
    'field_visible' => 1,
    'field_changeable' => 1
));

$modeOptions = array(
    get_lang('Online'),
    get_lang('Presencial'),
    get_lang('B-Learning')
);

$mode = new ExtraField('session');
$mode->save(array(
    'field_type' => ExtraField::FIELD_TYPE_SELECT,
    'field_variable' => 'mode',
    'field_display_text' => get_lang('Mode'),
    'field_visible' => 1,
    'field_changeable' => 1,
    'field_options' => implode('; ', $modeOptions)
));

$isInductionSession = new ExtraField('session');
$isInductionSession->save(array(
    'field_type' => ExtraField::FIELD_TYPE_CHECKBOX,
    'field_variable' => 'is_induccion_session',
    'field_display_text' => get_lang('IsInductionSession'),
    'field_visible' => 1,
    'field_changeable' => 1
));

$isOpenSession = new ExtraField('session');
$isOpenSession->save(array(
    'field_type' => ExtraField::FIELD_TYPE_CHECKBOX,
    'field_variable' => 'is_open_session',
    'field_display_text' => get_lang('IsOpenSession'),
    'field_visible' => 1,
    'field_changeable' => 1
));

$duration = new ExtraField('session');
$duration->save(array(
    'field_type' => ExtraField::FIELD_TYPE_INTEGER,
    'field_variable' => 'duration',
    'field_display_text' => get_lang('Duration'),
    'field_visible' => 1,
    'field_changeable' => 1
));

$showStatusOptions = array(
    get_lang('Open'),
    get_lang('InProcess'),
    get_lang('Closed')
);

$showStatus = new ExtraField('session');
$showStatus->save(array(
    'field_type' => ExtraField::FIELD_TYPE_SELECT,
    'field_variable' => 'show_status',
    'field_display_text' => get_lang('ShowStatus'),
    'field_visible' => 1,
    'field_changeable' => 1,
    'field_options' => implode('; ', $showStatusOptions)
));

$publicationStartDate = new ExtraField('session');
$publicationStartDate->save(array(
    'field_type' => ExtraField::FIELD_TYPE_DATE,
    'field_variable' => 'publication_start_date',
    'field_display_text' => get_lang('PublicationStartDate'),
    'field_visible' => 1,
    'field_changeable' => 1
));

$publicationEndDate = new ExtraField('session');
$publicationEndDate->save(array(
    'field_type' => ExtraField::FIELD_TYPE_DATE,
    'field_variable' => 'publication_end_date',
    'field_display_text' => get_lang('PublicationEndDate'),
    'field_visible' => 1,
    'field_changeable' => 1
));

$banner = new ExtraField('session');
$banner->save(array(
    'field_type' => ExtraField::FIELD_TYPE_FILE_IMAGE,
    'field_variable' => 'banner',
    'field_display_text' => get_lang('SessionBanner'),
    'field_visible' => 1,
    'field_changeable' => 1
));

$brochure = new ExtraField('session');
$brochure->save(array(
    'field_type' => ExtraField::FIELD_TYPE_FILE,
    'field_variable' => 'brochure',
    'field_display_text' => get_lang('Brochure'),
    'field_visible' => 1,
    'field_changeable' => 1
));

$targetOptions = array(
    get_lang('Minedu'),
    get_lang('Regiones')
);

$target = new ExtraField('session');
$target->save(array(
    'field_type' => ExtraField::FIELD_TYPE_SELECT,
    'field_variable' => 'target',
    'field_display_text' => get_lang('Target'),
    'field_visible' => 1,
    'field_changeable' => 1,
    'field_options' => implode('; ', $targetOptions)
));

$shortDescription = new ExtraField('session');
$shortDescription->save(array(
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'field_variable' => 'short_description',
    'field_display_text' => get_lang('ShortSescription'),
    'field_visible' => 1,
    'field_changeable' => 1
));

$id = new ExtraField('session');
$id->save(array(
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'field_variable' => 'id',
    'field_display_text' => get_lang('Id'),
    'field_visible' => 1,
    'field_changeable' => 1
));
