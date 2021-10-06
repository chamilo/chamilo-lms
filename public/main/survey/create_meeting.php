<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq(),
    'name' => get_lang('Survey list'),
];

$courseInfo = api_get_course_info();

$tool_name = get_lang('Create meeting poll');

$form = new FormValidator(
    'survey',
    'post',
    api_get_self().'?action=add&'.api_get_cidreq()
);

$form->addHeader($tool_name);
$form->addHidden('anonymous', 0);
$form->addHidden('survey_language', $courseInfo['language']);
$form->addHidden('survey_subtitle', '');
$form->addHidden('survey_thanks', '');
$form->addHidden('visible_results', '0');
$form->addHidden('survey_type', 3);
$text = $form->addText('survey_title', get_lang('Title'));

$startDateElement = $form->addDateTimePicker('start_date', get_lang('Start Date'));
$endDateElement = $form->addDateTimePicker('end_date', get_lang('End Date'));
$form->addRule('start_date', get_lang('Invalid date'), 'datetime');
$form->addRule('end_date', get_lang('Invalid date'), 'datetime');

$form->addRule(
    ['start_date', 'end_date'],
    get_lang('Start DateShouldBeBeforeEnd Date'),
    'date_compare',
    'lte'
);

$form->setRequired($startDateElement);
$form->setRequired($endDateElement);

$form->addHtmlEditor('survey_introduction', get_lang('Description'), false);
$form->setRequired($text);

$hideList = '';
$maxEvents = 20;
for ($i = 1; $i <= $maxEvents; $i++) {
    $name = 'time_'.$i;
    $form->addDateTimeRangePicker($name, get_lang('Date'));
    if ($i > 3) {
        $hideList .= "$('#".$name."_date_time_wrapper').hide();";
    }
}

$form->addHtml('<script>
$(function() {
    '.$hideList.'
    var number = 3;
    $("#add_button").on("click", function() {
        number++;
        $("#time_" + number + "_date_time_wrapper").show();
    });

    $("#remove_button").on("click", function() {
        if (number > 1) {
            $("#time_" + number + "_date_time_wrapper").hide();
            number--;
        }
    });
});
</script>');

$form->addLabel(
    '',
    Display::url(get_lang('Add'), 'javascript:void(0)', ['id' => 'add_button', 'class' => 'btn btn-default'])
    .' '.
    Display::url(
        get_lang('Remove'),
        'javascript:void(0)',
        ['id' => 'remove_button', 'class' => 'btn btn-danger']
    )
);

$form->addButtonCreate(get_lang('Create survey'), 'submit_survey');

$defaults = [];
$form->setDefaults($defaults);

// The validation or display
if ($form->validate()) {
    // Exporting the values
    $values = $form->getSubmitValues();
    $values['survey_code'] = SurveyManager::generateSurveyCode($values['survey_title']);
    // Storing the survey
    $surveyData = SurveyManager::store_survey($values);

    $dates = [];
    for ($i = 1; $i <= $maxEvents; $i++) {
        $name = 'time_'.$i;
        if (isset($values[$name]) && !empty($values[$name])) {
            $date = $values[$name];

            $start = $name.'_time_range_start';
            $end = $name.'_time_range_end';

            $start = $values[$start];
            $end = $values[$end];

            $start = api_get_utc_datetime($values[$name].' '.$start, true);
            $end = api_get_utc_datetime($values[$name].' '.$end, true);

            if (!empty($start) && !empty($start)) {
                $row = [
                    'start' => $start,
                    'end' => $end,
                ];
                $dates[] = $row;
            }
        }
    }

    $questionTable = Database::get_course_table(TABLE_SURVEY_QUESTION);
    $counter = 1;
    if (!empty($surveyData['iid'])) {
        foreach ($dates as $date) {
            $params = [
                'c_id' => api_get_course_int_id(),
                'survey_id' => $surveyData['id'],
                'survey_question' => $date['start'].'@@'.$date['end'],
                'survey_question_comment' => '',
                'type' => 'doodle',
                'display' => 'horizontal',
                'sort' => $counter,
                'shared_question_id' => '0',
                'max_value' => 0,
            ];
            Database::insert($questionTable, $params);
            $counter++;
        }
    }

    // Redirecting to the survey page (whilst showing the return message)
    header('Location: '.api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq());
    exit;
} else {
    // Displaying the header
    Display::display_header($tool_name);
    $form->display();
}

Display::display_footer();
