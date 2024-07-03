<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

if (!api_is_allowed_to_edit()) {
    if (!api_is_session_general_coach() ||
        (!empty($_GET['survey_id']) &&
            !api_is_element_in_the_session(TOOL_SURVEY, $_GET['survey_id']))
    ) {
        api_not_allowed(true);
    }
}

$htmlHeadXtra[] = api_get_css_asset('jt.timepicker/jquery.timepicker.css');
$htmlHeadXtra[] = api_get_asset('jt.timepicker/jquery.timepicker.js');
$htmlHeadXtra[] = api_get_asset('datepair.js/dist/datepair.js');
$htmlHeadXtra[] = api_get_asset('datepair.js/dist/jquery.datepair.js');

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq(),
    'name' => get_lang('SurveyList'),
];

$surveyId = isset($_GET['survey_id']) ? (int) $_GET['survey_id'] : null;
$surveyData = SurveyManager::get_survey($surveyId);

if (empty($surveyData)) {
    api_not_allowed(true);
}

$courseInfo = api_get_course_info();

$tool_name = get_lang('Edit');

$form = new FormValidator(
    'survey',
    'post',
    api_get_self().'?action=edit&'.api_get_cidreq().'&survey_id='.$surveyId
);

$form->addElement('header', $tool_name);
$form->addHidden('anonymous', 0);
$form->addHidden('survey_language', $courseInfo['language']);
$form->addHidden('survey_subtitle', '');
$form->addHidden('survey_thanks', '');
$form->addHidden('visible_results', '0');
$form->addHidden('survey_type', 3);

$text = $form->addText(
    'survey_title',
    get_lang('Title')
);

$allowSurveyAvailabilityDatetime = api_get_configuration_value('allow_survey_availability_datetime');

if ($allowSurveyAvailabilityDatetime) {
    $form->addDateTimePicker('start_date', get_lang('StartDate'));
    $form->addDateTimePicker('end_date', get_lang('EndDate'));
    $form->addRule('start_date', get_lang('InvalidDate'), 'datetime');
    $form->addRule('end_date', get_lang('InvalidDate'), 'datetime');
} else {
    $form->addElement('date_picker', 'start_date', get_lang('StartDate'));
    $form->addElement('date_picker', 'end_date', get_lang('EndDate'));
    $form->addRule('start_date', get_lang('InvalidDate'), 'date');
    $form->addRule('end_date', get_lang('InvalidDate'), 'date');
}

$form->addRule(
    ['start_date', 'end_date'],
    get_lang('StartDateShouldBeBeforeEndDate'),
    'date_compare',
    'lte'
);

$form->addHtmlEditor('survey_introduction', get_lang('Description'), false);
$form->setRequired($text);

$questions = SurveyManager::get_questions($surveyData['iid']);
$currentQuestionsCount = count($questions);
$counter = 1;
foreach ($questions as $question) {
    $name = 'time_'.$counter;
    $parts = explode('@@', $question['question']);
    $surveyData[$name] = api_get_local_time($parts[0]).'@@'.api_get_local_time($parts[1]);

    $form->addDateTimeRangePicker($name, get_lang('Date'));
    $form->addHidden($name.'_question_id', $question['question_id']);
    $counter++;
}
$currentQuestionsCount++;

$hideList = '';
$maxEvents = $currentQuestionsCount + 10;
for ($i = $currentQuestionsCount; $i <= $maxEvents; $i++) {
    $name = 'time_'.$i;
    $form->addDateTimeRangePicker($name, get_lang('Date'));
    $hideList .= "$('#".$name."_date_time_wrapper').hide();";
}

$form->addHtml('<script>
$(function() {
    '.$hideList.'
    var number = "'.--$currentQuestionsCount.'";
    $("#add_button").on("click", function() {
        number++;
        $("#time_" + number + "_date_time_wrapper").show();
        $("#time_" + number + "_time_range_start").val("");
        $("#time_" + number + "_time_range_end").val("");
        $("#time_" + number + "_alt").val("");
    });

    $("#remove_button").on("click", function() {
        if (number > 1) {
            console.log("#time_" + number + "_time_range_start");
            $("#time_" + number + "_date_time_wrapper").hide();
            $("#time_" + number).val("delete");

            $("#time_" + number + "_alt").val("delete");
            $("#time_" + number + "_time_range_start").val("delete");
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

$form->addButtonUpdate(get_lang('Edit'), 'submit_survey');
$surveyData['start_date'] = date(
    $allowSurveyAvailabilityDatetime ? 'Y-m-d H:i:s' : 'Y-m-d',
    api_strtotime(api_get_local_time($surveyData['start_date']))
);
$surveyData['end_date'] = date(
    $allowSurveyAvailabilityDatetime ? 'Y-m-d H:i:s' : 'Y-m-d',
    api_strtotime(api_get_local_time($surveyData['end_date']))
);
$form->setDefaults($surveyData);

// The validation or display
if ($form->validate()) {
    // Exporting the values
    $values = $form->getSubmitValues();

    $values['survey_id'] = $surveyId;
    $values['survey_code'] = SurveyManager::generateSurveyCode($values['survey_title']);
    // Storing the survey
    SurveyManager::store_survey($values);

    $dates = [];
    $deleteItems = [];

    for ($i = 1; $i <= $maxEvents; $i++) {
        $name = 'time_'.$i;

        if (isset($values[$name]) && !empty($values[$name])) {
            $id = '';
            if (isset($values[$name.'_question_id'])) {
                $id = $values[$name.'_question_id'];
            }

            $date = $values[$name];

            if ('delete' === $date && !empty($id)) {
                $deleteItems[] = $id;
            }

            if (empty($date)) {
                continue;
            }

            $start = $name.'_time_range_start';
            $end = $name.'_time_range_end';

            $start = $values[$start];
            $end = $values[$end];

            $part = explode('@@', $values[$name]);
            $firstDate = substr($part[0], 0, 10);

            $start = api_get_utc_datetime($firstDate.' '.$start);
            $end = api_get_utc_datetime($firstDate.' '.$end);

            if (!empty($start) && !empty($start)) {
                $row = [
                    'id' => $id,
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
            $formattedDate = $date['start'].'@@'.$date['end'];
            if (!empty($date['id'])) {
                $questionId = $date['id'];
                $sql = "UPDATE $questionTable SET survey_question = '$formattedDate'
                        WHERE iid = $questionId";
                Database::query($sql);
            } else {
                $params = [
                    'c_id' => api_get_course_int_id(),
                    'survey_id' => $surveyData['iid'],
                    'survey_question' => $formattedDate,
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

        foreach ($deleteItems as $deleteId) {
            SurveyManager::delete_survey_question($surveyData['iid'], $deleteId);
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
