<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script(true);
api_protect_teacher_script();

$plugin = SurveyExportCsvPlugin::create();

$courseCode = api_get_course_id();

// Create a sortable table with survey-data
$table = new SortableTable(
    'surveys',
    ['SurveyUtil', 'get_number_of_surveys'],
    ['SurveyUtil', 'get_survey_data'],
    2
);
$table->set_additional_parameters(['cidReq' => $courseCode]);
$table->set_header(0, '', false);
$table->setHideColumn(0);
$table->set_header(1, get_lang('SurveyName'));
$table->set_header(2, get_lang('SurveyCode'), true, ['class' => 'text-center'], ['class' => 'text-center']);
$table->set_header(3, get_lang('NumberOfQuestions'), true, ['class' => 'text-right'], ['class' => 'text-right']);
$table->set_header(4, get_lang('Author'));
$table->set_header(5, get_lang('AvailableFrom'), true, ['class' => 'text-center'], ['class' => 'text-center']);
$table->set_header(6, get_lang('AvailableUntil'), true, ['class' => 'text-center'], ['class' => 'text-center']);
$table->set_header(7, get_lang('Invite'), true, ['class' => 'text-right'], ['class' => 'text-right']);
$table->set_header(8, get_lang('Anonymous'), true, ['class' => 'text-center'], ['class' => 'text-center']);
$table->set_column_filter(8, ['SurveyUtil', 'anonymous_filter']);

if (api_get_configuration_value('allow_mandatory_survey')) {
    $table->set_header(9, get_lang('IsMandatory'), true, ['class' => 'text-center'], ['class' => 'text-center']);
    $table->set_header(10, get_lang('Export'), false, ['class' => 'text-center'], ['class' => 'text-center']);
    $table->set_column_filter(10, ['SurveyExportCsvPlugin', 'filterModify']);
} else {
    $table->set_header(9, get_lang('Export'), false, ['class' => 'text-center'], ['class' => 'text-center']);
    $table->set_column_filter(9, ['SurveyExportCsvPlugin', 'filterModify']);
}

$pageTitle = $plugin->get_title();

$template = new Template($pageTitle);

$content = $table->return_table();

$template->assign('header', $pageTitle);
$template->assign('content', $content);
$template->display_one_col_template();
