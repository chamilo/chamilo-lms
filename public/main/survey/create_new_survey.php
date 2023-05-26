<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

/**
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup,
 *  refactoring and rewriting large parts (if not all) of the code
 * @author Julio Montoya Armas <gugli100@gmail.com>, Chamilo: Personality
 * Test modification and rewriting large parts of the code
 *
 * @todo   only the available platform languages should be used => need an
 *  api get_languages and and api_get_available_languages (or a parameter)
 */

require_once __DIR__.'/../inc/global.inc.php';

$repo = Container::getSurveyRepository();

$_course = api_get_course_info();
$this_section = SECTION_COURSES;
$table_gradebook_link = Database::get_main_table(TABLE_MAIN_GRADEBOOK_LINK);

/** @todo this has to be moved to a more appropriate place (after the display_header of the code) */
// If user is not teacher or if he's a coach trying to access an element out of his session
if (!api_is_allowed_to_edit()) {
    if (!api_is_session_general_coach() ||
        (!empty($_GET['survey_id']) &&
            !api_is_element_in_the_session(TOOL_SURVEY, $_GET['survey_id']))
    ) {
        api_not_allowed(true);
    }
}

// Getting the survey information
$survey_id = isset($_GET['survey_id']) ? (int) $_GET['survey_id'] : null;
$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : '';
$survey_data = SurveyManager::get_survey($survey_id);

// Additional information
$course_id = api_get_course_id();
$session_id = api_get_session_id();
$gradebook_link_type = 8;
$urlname = isset($survey_data['title']) ? strip_tags($survey_data['title']) : null;

// Breadcrumbs
if ('add' === $action) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq(),
        'name' => get_lang('Survey list'),
    ];
    $tool_name = get_lang('Create survey');
}
if ('edit' === $action && is_numeric($survey_id)) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq(),
        'name' => get_lang('Survey list'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$survey_id.'&'.api_get_cidreq(),
        'name' => Security::remove_XSS($urlname),
    ];
    $tool_name = get_lang('Edit survey');
}
$gradebook_link_id = null;
// Getting the default values
if ('edit' === $action && isset($survey_id) && is_numeric($survey_id)) {
    $defaults = $survey_data;
    $defaults['survey_id'] = $survey_id;
    $defaults['anonymous'] = $survey_data['anonymous'];
    $defaults['avail_from'] = api_get_local_time($defaults['avail_from'], null, 'UTC');
    $defaults['avail_till'] = api_get_local_time($defaults['avail_till'], null, 'UTC');
    $defaults['start_date'] = $defaults['avail_from'];
    $defaults['end_date'] = $defaults['avail_till'];

    $link_info = GradebookUtils::isResourceInCourseGradebook(
        $course_id,
        $gradebook_link_type,
        $survey_id,
        $session_id
    );

    if ($link_info) {
        $gradebook_link_id = $link_info['id'];
        $defaults['category_id'] = $link_info['category_id'];
        $gradebook_link_id = (int) $gradebook_link_id;
        $sql = "SELECT weight FROM $table_gradebook_link WHERE id = $gradebook_link_id";
        $result = Database::query($sql);
        $gradeBookData = Database::fetch_array($result);
        if ($gradeBookData) {
            $defaults['survey_qualify_gradebook'] = $gradebook_link_id;
            $defaults['survey_weight'] = number_format($gradeBookData['weight'], 2, '.', '');
        }
    }
} else {
    $defaults['survey_language'] = $_course['language'];
    $defaults['start_date'] = date('Y-m-d 00:00:00', api_strtotime(api_get_local_time()));
    $startdateandxdays = time() + 864000; // today + 10 days
    $defaults['end_date'] = date('Y-m-d 23:59:59', $startdateandxdays);
    $defaults['anonymous'] = 0;
}

// Initialize the object
$form = new FormValidator(
    'survey',
    'post',
    api_get_self().'?action='.$action.'&survey_id='.$survey_id.'&'.api_get_cidreq()
);

$form->addElement('header', $tool_name);

// Setting the form elements
if ('edit' === $action && isset($survey_id) && is_numeric($survey_id)) {
    $form->addElement('hidden', 'survey_id');
}

$surveyCodeElement = $form->addElement(
    'text',
    'survey_code',
    get_lang('Code'),
    ['size' => '20', 'maxlength' => '20', 'autofocus' => 'autofocus']
);

if ('edit' === $action) {
    $surveyCodeElement->freeze();
    $form->applyFilter('survey_code', 'api_strtoupper');
}

$form->addElement(
    'html_editor',
    'survey_title',
    get_lang('Survey title'),
    null,
    ['ToolbarSet' => 'Survey', 'Width' => '100%', 'Height' => '200']
);
$form->addElement(
    'html_editor',
    'survey_subtitle',
    get_lang('Survey subtitle'),
    null,
    [
        'ToolbarSet' => 'Survey',
        'Width' => '100%',
        'Height' => '100',
        'ToolbarStartExpanded' => false,
    ]
);

// Pass the language of the survey in the form
$form->addElement('hidden', 'survey_language');
$startDateElement = $form->addDateTimePicker('start_date', get_lang('Start Date'));
$endDateElement = $form->addDateTimePicker('end_date', get_lang('End Date'));
$form->addRule('start_date', get_lang('Invalid date'), 'datetime');
$form->addRule('end_date', get_lang('Invalid date'), 'datetime');

$form->setRequired($startDateElement);
$form->setRequired($endDateElement);

$form->addElement('checkbox', 'anonymous', null, get_lang('Anonymous'));
$visibleResults = [
    SURVEY_VISIBLE_TUTOR => get_lang('Coach'),
    SURVEY_VISIBLE_TUTOR_STUDENT => get_lang('Coach and student'),
    SURVEY_VISIBLE_PUBLIC => get_lang('Everyone'),
];

if ('true' === api_get_setting('survey.hide_survey_reporting_button')) {
    $form->addLabel(get_lang('Results visibility'), get_lang('Feature disabled by administrator'));
} else {
    $form->addSelect('visible_results', get_lang('Results visibility'), $visibleResults);
}
//$defaults['visible_results'] = 0;
$form->addElement(
    'html_editor',
    'survey_introduction',
    get_lang('Survey introduction'),
    null,
    ['ToolbarSet' => 'Survey', 'Width' => '100%', 'Height' => '130', 'ToolbarStartExpanded' => false]
);
$form->addElement(
    'html_editor',
    'survey_thanks',
    get_lang('Final thanks'),
    null,
    ['ToolbarSet' => 'Survey', 'Width' => '100%', 'Height' => '130', 'ToolbarStartExpanded' => false]
);

$extraField = new ExtraField('survey');
$extraField->addElements($form, $survey_id, ['group_id']);

if ($extraField->get_handler_field_info_by_field_variable('group_id')) {
    $extraFieldValue = new ExtraFieldValue('survey');
    $groupData = $extraFieldValue->get_values_by_handler_and_field_variable($survey_id, 'group_id');
    $groupValue = [];
    if ($groupData && !empty($groupData['value'])) {
        $groupInfo = GroupManager::get_group_properties($groupData['value']);
        $groupValue = [$groupInfo['iid'] => $groupInfo['name']];
    }

    $form->addSelectAjax(
        'extra_group_id',
        get_lang('Group'),
        $groupValue,
        [
            'url' => api_get_path(WEB_AJAX_PATH).'group.ajax.php?a=search&'.api_get_cidreq(),
        ]
    );
}

// Additional Parameters
$form->addButtonAdvancedSettings('advanced_params');
$form->addElement('html', '<div id="advanced_params_options" style="display:none">');

if (Gradebook::is_active()) {
    // An option: Qualify the fact that survey has been answered in the gradebook
    $form->addCheckBox(
        'survey_qualify_gradebook',
        null,
        get_lang('Grade in the assessment tool'),
        ['onclick' => 'javascript: if (this.checked) { document.getElementById(\'gradebook_options\').style.display = \'block\'; } else { document.getElementById(\'gradebook_options\').style.display = \'none\'; }'],
    );
    $form->addHtml('<div id="gradebook_options"'.($gradebook_link_id ? '' : ' style="display:none"').'>');
    $form->addText(
        'survey_weight',
        get_lang('Weight in Report'),
        false,
        [
            'value' => "0.00",
            'style' => "width: 40px;",
            'onfocus' => 'javascript: this.select();',
        ]
    );
    $form->applyFilter('survey_weight', 'html_filter');

    // Loading Gradebook select
    GradebookUtils::load_gradebook_select_in_tool($form);
    if ('edit' === $action) {
        $element = $form->getElement('category_id');
        $element->freeze();
    }
    $form->addElement('html', '</div>');
}

// Personality/Conditional Test Options
$surveytypes[0] = get_lang('Normal');
$surveytypes[1] = get_lang('Conditional');

if ('add' === $action) {
    $form->addHidden('survey_type', 0);
    $qb = $repo->findAllByCourse(api_get_course_entity(), api_get_session_entity());
    $surveys = $qb->getQuery()->getResult();
    $surveyOptions[0] = '';
    foreach ($surveys as $survey) {
        $surveyOptions[$survey->getIid()] = implode(' > ', $repo->getPath($survey));
    }
    $form->addSelect('parent_id', get_lang('Parent Survey'), $surveyOptions);
    $defaults['parent_id'] = 0;
}

$form->addElement('checkbox', 'one_question_per_page', null, get_lang('One question per page'));
$form->addElement('checkbox', 'shuffle', null, get_lang('Enable shuffle mode'));

$input_name_list = null;

if ('edit' === $action && !empty($survey_id)) {
    if (0 == $survey_data['anonymous']) {
        $form->addCheckBox(
            'show_form_profile',
            null,
            get_lang('Show profile form'),
            ['onclick' => "javascript: if(this.checked){document.getElementById(\'options_field\').style.display = \'block\';}else{document.getElementById(\'options_field\').style.display = \'none\';}"]
        );

        if (1 == $survey_data['show_form_profile']) {
            $form->addElement('html', '<div id="options_field" style="display:block">');
        } else {
            $form->addElement('html', '<div id="options_field" style="display:none">');
        }

        $field_list = SurveyUtil::make_field_list();

        if (is_array($field_list)) {
            // TODO hide and show the list in a fancy DIV
            foreach ($field_list as $key => &$field) {
                if (1 == $field['visibility']) {
                    $form->addElement('checkbox', 'profile_'.$key, ' ', '&nbsp;&nbsp;'.$field['name']);
                    $input_name_list .= 'profile_'.$key.',';
                }
            }

            // Necessary to know the fields
            $form->addElement('hidden', 'input_name_list', $input_name_list);

            // Set defaults form fields
            if ($survey_data['form_fields']) {
                $form_fields = explode('@', $survey_data['form_fields']);
                foreach ($form_fields as &$field) {
                    $field_value = explode(':', $field);
                    if ('' != $field_value[0] && '' != $field_value[1]) {
                        $defaults[$field_value[0]] = $field_value[1];
                    }
                }
            }
        }
        $form->addElement('html', '</div>');
    }
}

$skillList = SkillModel::addSkillsToForm($form, ITEM_TYPE_SURVEY, $survey_id);
$form->addElement('html', '</div><br />');

if (isset($_GET['survey_id']) && 'edit' === $action) {
    $form->addButtonUpdate(get_lang('Edit survey'), 'submit_survey');
} else {
    $form->addButtonCreate(get_lang('Create survey'), 'submit_survey');
}

// Setting the rules
if ('add' === $action) {
    $form->addRule('survey_code', get_lang('Required field'), 'required');
    $form->addRule('survey_code', '', 'maxlength', 20);
}
$form->addRule('survey_title', get_lang('Required field'), 'required');
$form->addRule('start_date', get_lang('Invalid date'), 'datetime');
$form->addRule('end_date', get_lang('Invalid date'), 'datetime');
$form->addRule(
    ['start_date', 'end_date'],
    get_lang('Start DateShouldBeBeforeEnd Date'),
    'date_compare',
    'lte'
);

$defaults['skills'] = array_keys($skillList);

// Setting the default values
$form->setDefaults($defaults);

// The validation or display
if ($form->validate()) {
    // Exporting the values
    $values = $form->getSubmitValues();
    // Storing the survey
    $return = SurveyManager::store_survey($values);
    SkillModel::saveSkills($form, ITEM_TYPE_SURVEY, $return['id']);

    $values['item_id'] = $return['id'];
    $extraFieldValue = new ExtraFieldValue('survey');
    $extraFieldValue->saveFieldValues($values);

    // Redirecting to the survey page (whilst showing the return message)
    header('Location: '.api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$return['id'].'&'.api_get_cidreq());
    exit;
} else {
    // Displaying the header
    Display::display_header($tool_name);
    $form->display();
}

Display::display_footer();
