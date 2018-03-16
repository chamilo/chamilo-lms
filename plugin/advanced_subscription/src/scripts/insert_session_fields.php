<?php
/* For license terms, see /license.txt */
/**
 * This script generates session fields needed for this plugin.
 *
 * @package chamilo.plugin.advanced_subscription
 */

//exit;

require_once __DIR__.'/../../config.php';

api_protect_admin_script();

$teachingHours = new ExtraField('session');
$teachingHours->save([
    'field_type' => ExtraField::FIELD_TYPE_INTEGER,
    'variable' => 'teaching_hours',
    'display_text' => get_lang('TeachingHours'),
    'visible_to_self' => 1,
    'changeable' => 1,
]);

$cost = new ExtraField('session');
$cost->save([
    'field_type' => ExtraField::FIELD_TYPE_FLOAT,
    'variable' => 'cost',
    'display_text' => get_lang('Cost'),
    'visible_to_self' => 1,
    'changeable' => 1,
]);

$vacancies = new ExtraField('session');
$vacancies->save([
    'field_type' => ExtraField::FIELD_TYPE_INTEGER,
    'variable' => 'vacancies',
    'display_text' => get_lang('Vacancies'),
    'visible_to_self' => 1,
    'changeable' => 1,
]);

$recommendedNumberOfParticipants = new ExtraField('session');
$recommendedNumberOfParticipants->save([
    'field_type' => ExtraField::FIELD_TYPE_INTEGER,
    'variable' => 'recommended_number_of_participants',
    'display_text' => get_lang('RecommendedNumberOfParticipants'),
    'visible_to_self' => 1,
    'changeable' => 1,
]);

$place = new ExtraField('session');
$place->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'place',
    'display_text' => get_lang('Place'),
    'visible_to_self' => 1,
    'changeable' => 1,
]);

$schedule = new ExtraField('session');
$schedule->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'schedule',
    'display_text' => get_lang('Schedule'),
    'visible_to_self' => 1,
    'changeable' => 1,
]);

$allowVisitors = new ExtraField('session');
$allowVisitors->save([
    'field_type' => ExtraField::FIELD_TYPE_CHECKBOX,
    'variable' => 'allow_visitors',
    'display_text' => get_lang('AllowVisitors'),
    'visible_to_self' => 1,
    'changeable' => 1,
]);

$modeOptions = [
    get_lang('Online'),
    get_lang('Presencial'),
    get_lang('B-Learning'),
];

$mode = new ExtraField('session');
$mode->save([
    'field_type' => ExtraField::FIELD_TYPE_SELECT,
    'variable' => 'mode',
    'display_text' => get_lang('Mode'),
    'visible_to_self' => 1,
    'changeable' => 1,
    'field_options' => implode('; ', $modeOptions),
]);

$isInductionSession = new ExtraField('session');
$isInductionSession->save([
    'field_type' => ExtraField::FIELD_TYPE_CHECKBOX,
    'variable' => 'is_induction_session',
    'display_text' => get_lang('IsInductionSession'),
    'visible_to_self' => 1,
    'changeable' => 1,
]);

$isOpenSession = new ExtraField('session');
$isOpenSession->save([
    'field_type' => ExtraField::FIELD_TYPE_CHECKBOX,
    'variable' => 'is_open_session',
    'display_text' => get_lang('IsOpenSession'),
    'visible_to_self' => 1,
    'changeable' => 1,
]);

$duration = new ExtraField('session');
$duration->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'human_text_duration',
    'display_text' => get_lang('DurationInWords'),
    'visible_to_self' => 1,
    'changeable' => 1,
]);

$showStatusOptions = [
    get_lang('Open'),
    get_lang('InProcess'),
    get_lang('Closed'),
];

$showStatus = new ExtraField('session');
$showStatus->save([
    'field_type' => ExtraField::FIELD_TYPE_SELECT,
    'variable' => 'show_status',
    'display_text' => get_lang('ShowStatus'),
    'visible_to_self' => 1,
    'changeable' => 1,
    'field_options' => implode('; ', $showStatusOptions),
]);

$publicationStartDate = new ExtraField('session');
$publicationStartDate->save([
    'field_type' => ExtraField::FIELD_TYPE_DATE,
    'variable' => 'publication_start_date',
    'display_text' => get_lang('PublicationStartDate'),
    'visible_to_self' => 1,
    'changeable' => 1,
]);

$publicationEndDate = new ExtraField('session');
$publicationEndDate->save([
    'field_type' => ExtraField::FIELD_TYPE_DATE,
    'variable' => 'publication_end_date',
    'display_text' => get_lang('PublicationEndDate'),
    'visible_to_self' => 1,
    'changeable' => 1,
]);

$banner = new ExtraField('session');
$banner->save([
    'field_type' => ExtraField::FIELD_TYPE_FILE_IMAGE,
    'variable' => 'banner',
    'display_text' => get_lang('SessionBanner'),
    'visible_to_self' => 1,
    'changeable' => 1,
]);

$brochure = new ExtraField('session');
$brochure->save([
    'field_type' => ExtraField::FIELD_TYPE_FILE,
    'variable' => 'brochure',
    'display_text' => get_lang('Brochure'),
    'visible_to_self' => 1,
    'changeable' => 1,
]);

$targetOptions = [
    get_lang('Minedu'),
    get_lang('Regiones'),
];

$target = new ExtraField('session');
$target->save([
    'field_type' => ExtraField::FIELD_TYPE_SELECT,
    'variable' => 'target',
    'display_text' => get_lang('TargetAudience'),
    'visible_to_self' => 1,
    'changeable' => 1,
    'field_options' => implode('; ', $targetOptions),
]);

$shortDescription = new ExtraField('session');
$shortDescription->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'short_description',
    'display_text' => get_lang('ShortDescription'),
    'visible_to_self' => 1,
    'changeable' => 1,
]);

$id = new ExtraField('session');
$id->save([
    'field_type' => ExtraField::FIELD_TYPE_TEXT,
    'variable' => 'code',
    'display_text' => get_lang('Code'),
    'visible_to_self' => 1,
    'changeable' => 1,
]);
