<?php

/* For licensing terms, see /license.txt */

/**
 * This is a learning path creation and player tool in Chamilo.
 *
 * @author Patrick Cool
 * @author Denes Nagy
 * @author Roan Embrechts, refactoring and code cleaning
 * @author Yannick Warnier <ywarnier@beeznest.org> - cleaning and update for new SCORM tool
 * @author Julio Montoya <gugli100@gmail.com> Adding formvalidator support
 */
$this_section = SECTION_COURSES;
api_protect_course_script();

$currentstyle = api_get_setting('stylesheets');
$htmlHeadXtra[] = '<script>
function activate_start_date() {
	if(document.getElementById(\'start_date_div\').style.display == \'none\') {
		document.getElementById(\'start_date_div\').style.display = \'block\';
	} else {
		document.getElementById(\'start_date_div\').style.display = \'none\';
	}
}

function activate_end_date() {
    if(document.getElementById(\'end_date_div\').style.display == \'none\') {
        document.getElementById(\'end_date_div\').style.display = \'block\';
    } else {
        document.getElementById(\'end_date_div\').style.display = \'none\';
    }
}
</script>';

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
$isStudentView = isset($_REQUEST['isStudentView']) ? $_REQUEST['isStudentView'] : null;
$learnpath_id = isset($_REQUEST['lp_id']) ? $_REQUEST['lp_id'] : null;
$sessionId = api_get_session_id();

if (!$is_allowed_to_edit || $isStudentView) {
    header('location:lp_controller.php?action=view&lp_id='.$learnpath_id.'&'.api_get_cidreq());
    exit;
}

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('ToolGradebook'),
    ];
}

$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('LearningPaths'),
];

Display::display_header(get_lang('LearnpathAddLearnpath'), 'Path');

echo '<div class="actions">';
echo '<a href="lp_controller.php?'.api_get_cidreq().'">'.
    Display::return_icon(
        'back.png',
        get_lang('ReturnToLearningPaths'),
        '',
        ICON_SIZE_MEDIUM
    ).'</a>';
echo '</div>';

echo Display::return_message(get_lang('AddLpIntro'), 'normal', false);

if ($_POST && empty($_REQUEST['lp_name'])) {
    echo Display::return_message(
        get_lang('FormHasErrorsPleaseComplete'),
        'error',
        false
    );
}

$form = new FormValidator(
    'lp_add',
    'post',
    api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq()
);

$form->addHeader(get_lang('AddLpToStart'));

// Title
if (api_get_configuration_value('save_titles_as_html')) {
    $form->addHtmlEditor(
        'lp_name',
        get_lang('LPName'),
        true,
        false,
        ['ToolbarSet' => 'TitleAsHtml']
    );
} else {
    $form->addElement(
        'text',
        'lp_name',
        api_ucfirst(get_lang('LPName')),
        ['autofocus' => 'autofocus']
    );
}
$form->applyFilter('lp_name', 'html_filter');
$form->addRule('lp_name', get_lang('ThisFieldIsRequired'), 'required');

$allowCategory = true;
if (!empty($sessionId)) {
    $allowCategory = false;
    if (api_get_configuration_value('allow_session_lp_category')) {
        $allowCategory = true;
    }
}

if ($allowCategory) {
    $items = learnpath::getCategoryFromCourseIntoSelect(
        api_get_course_int_id(),
        true
    );
    $form->addElement('select', 'category_id', get_lang('Category'), $items);
}

$form->addElement('hidden', 'post_time', time());
$form->addElement('hidden', 'action', 'add_lp');

$form->addButtonAdvancedSettings('advanced_params');
$form->addHtml('<div id="advanced_params_options" style="display:none">');

// accumulate_scorm_time
$form->addElement(
    'checkbox',
    'accumulate_scorm_time',
    [null, get_lang('AccumulateScormTimeInfo')],
    get_lang('AccumulateScormTime')
);

// Start date.
$form->addElement(
    'checkbox',
    'activate_start_date_check',
    null,
    get_lang('EnableStartTime'),
    ['onclick' => 'activate_start_date()']
);
$form->addElement('html', '<div id="start_date_div" style="display:block;">');
$form->addDateTimePicker('publicated_on', get_lang('PublicationDate'));
$form->addElement('html', '</div>');

// End date.
$form->addElement(
    'checkbox',
    'activate_end_date_check',
    null,
    get_lang('EnableEndTime'),
    ['onclick' => 'activate_end_date()']
);
$form->addElement('html', '<div id="end_date_div" style="display:none;">');
$form->addDateTimePicker('expired_on', get_lang('ExpirationDate'));
$form->addElement('html', '</div>');

$subscriptionSettings = learnpath::getSubscriptionSettings();
if ($subscriptionSettings['allow_add_users_to_lp']) {
    $form->addElement(
        'checkbox',
        'subscribe_users',
        null,
        get_lang('SubscribeUsersToLp')
    );
}

$extraField = new ExtraField('lp');
$extra = $extraField->addElements($form, 0, ['lp_icon']);
Skill::addSkillsToForm($form, api_get_course_int_id(), api_get_session_id(), ITEM_TYPE_LEARNPATH, 0);

$form->addElement('html', '</div>');

$defaults['activate_start_date_check'] = 1;

$defaults['accumulate_scorm_time'] = 0;
if (api_get_setting('scorm_cumulative_session_time') === 'true') {
    $defaults['accumulate_scorm_time'] = 1;
}

$defaults['publicated_on'] = api_get_local_time();
$defaults['expired_on'] = api_get_local_time(time() + 86400);

$form->setDefaults($defaults);
$form->addButtonCreate(get_lang('CreateLearningPath'));
$form->display();

Display::display_footer();
