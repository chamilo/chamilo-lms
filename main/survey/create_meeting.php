<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$courseInfo = api_get_course_info();

$tool_name = get_lang('CreateMeeting');

$form = new FormValidator(
    'survey',
    'post',
    api_get_self().'?action=add&'.api_get_cidreq()
);

$form->addElement('header', $tool_name);
$form->addHidden('anonymous', 0);
$form->addHidden('survey_language', $courseInfo['language']);
$form->addHidden('survey_subtitle', '');
$form->addHidden('survey_thanks', '');
$form->addHidden('visible_results', '0');

$form->addHidden('survey_type', 3);

// Setting the form elements
/*if ($_GET['action'] == 'edit' && isset($survey_id) && is_numeric($survey_id)) {
    $form->addElement('hidden', 'survey_id');
}*/

$text = $form->addElement(
    'text',
    'survey_title',
    get_lang('Title'),
    null,
    ['ToolbarSet' => 'Survey', 'Width' => '100%', 'Height' => '200']
);

$form->addDateTimePicker('start_date', get_lang('StartDate'));
$form->addDateTimePicker('end_date', get_lang('EndDate'));

$form->addRule('start_date', get_lang('InvalidDate'), 'datetime');
$form->addRule('end_date', get_lang('InvalidDate'), 'datetime');
$form->addRule(
    ['start_date', 'end_date'],
    get_lang('StartDateShouldBeBeforeEndDate'),
    'date_compare',
    'lte'
);

$form->addHtmlEditor('survey_introduction', get_lang('Description'), false);
$form->setRequired($text);

$hideList = '';
$maxEvents = 20;
for ($i = 1; $i <= $maxEvents; $i++) {
    $name = 'time_'.$i;
    $form->addDateTimePicker($name, get_lang('Time'));
    if ($i > 3) {
        $hideList .= "$('#date_time_wrapper_$name').parent().parent().hide();";
    }
}

$form->addHtml('<script>
$(function() {
    '.$hideList.'
    var number = 3;
    
    $("#add_button").on("click", function() {
        number++;
        $("#date_time_wrapper_time_" + number).parent().parent().show();       
        
    });
    
    $("#remove_button").on("click", function() {
        if (number >= 1) {
            number--;
            $("#date_time_wrapper_time_" + number).parent().parent().hide();
        }
    });
});
</script>
');

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

$form->addButtonCreate(get_lang('CreateSurvey'), 'submit_survey');

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
            $dates[] = $values[$name];
        }
    }
    $questionTable = Database::get_course_table(TABLE_SURVEY_QUESTION);
    $counter = 1;
    if (!empty($surveyData['id'])) {
        foreach ($dates as $date) {
            //SurveyManager::save_question();
            $params = [
                'c_id' => api_get_course_int_id(),
                'survey_id' => $surveyData['id'],
                'survey_question' => $date,
                'survey_question_comment' => '',
                'type' => 'doodle',
                'display' => 'horizontal',
                'sort' => $counter,
                'shared_question_id' => '0',
                'max_value' => 0,
            ];
            $questionId = Database::insert($questionTable, $params);
            if ($questionId) {
                $sql = "UPDATE $questionTable SET question_id = $questionId
                        WHERE iid = $questionId";
                Database::query($sql);
            }
            $counter++;
        }
    }

    // Redirecting to the survey page (whilst showing the return message)
    header('Location: '.api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$return['id'].'&'.api_get_cidreq());
    exit;
} else {
    // Displaying the header
    Display::display_header($tool_name);
    $form->display();
}

Display::display_footer();
