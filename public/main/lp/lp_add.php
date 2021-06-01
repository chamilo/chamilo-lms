<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

/**
 * This is a learning path creation and player tool in Chamilo - previously learnpath_handler.php.
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

/* Constants and variables */

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
$isStudentView = $_REQUEST['isStudentView'] ?? null;
$lpId = $_REQUEST['lp_id'] ?? null;

if ((!$is_allowed_to_edit) || $isStudentView) {
    header('location:lp_controller.php?action=view&lp_id='.$lpId.'&'.api_get_cidreq());
    exit;
}

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}

$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('Learning paths'),
];

$lpRepo = Container::getLpRepository();
$form = new FormValidator(
    'lp_add',
    'post',
    api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq()
);

// Form title
$form->addHeader(get_lang('To start, give a title to your course'));

// Title
if (api_get_configuration_value('save_titles_as_html')) {
    $form->addHtmlEditor(
        'lp_name',
        get_lang('Learning path name'),
        true,
        false,
        ['ToolbarSet' => 'TitleAsHtml']
    );
} else {
    $form->addElement(
        'text',
        'lp_name',
        api_ucfirst(get_lang('Learning path name')),
        ['autofocus' => 'autofocus']
    );
}
$form->applyFilter('lp_name', 'html_filter');
$form->addRule('lp_name', get_lang('Required field'), 'required');

$form->addElement('hidden', 'post_time', time());
$form->addElement('hidden', 'action', 'add_lp');

$form->addButtonAdvancedSettings('advanced_params');
$form->addHtml('<div id="advanced_params_options" style="display:none">');

$items = learnpath::getCategoryFromCourseIntoSelect(
    api_get_course_int_id(),
    true
);
$form->addSelect('category_id', get_lang('Category'), $items);

// accumulate_scorm_time
$form->addCheckBox(
    'accumulate_scorm_time',
    [
        null,
        get_lang(
            'When enabled, the session time for SCORM Learning Paths will be cumulative, otherwise, it will only be counted from the last update time.'
        ),
    ],
    get_lang('Accumulate SCORM session time')
);

// Start date
$form->addCheckBox(
    'activate_start_date_check',
    null,
    get_lang('Enable start time'),
    ['onclick' => 'activate_start_date()']
);
$form->addElement('html', '<div id="start_date_div" style="display:block;">');
$form->addDatePicker('publicated_on', get_lang('Publication date'));
$form->addElement('html', '</div>');

//End date
$form->addCheckBox(
    'activate_end_date_check',
    null,
    get_lang('Enable end time'),
    ['onclick' => 'activate_end_date()']
);
$form->addElement('html', '<div id="end_date_div" style="display:none;">');
$form->addDatePicker('expired_on', get_lang('Expiration date'));
$form->addElement('html', '</div>');

$subscriptionSettings = learnpath::getSubscriptionSettings();
if ($subscriptionSettings['allow_add_users_to_lp']) {
    $form->addElement(
        'checkbox',
        'subscribe_users',
        null,
        get_lang('Subscribe users to learning path')
    );
}

$extraField = new ExtraField('lp');
$extra = $extraField->addElements($form, 0, ['lp_icon']);

SkillModel::addSkillsToForm($form, ITEM_TYPE_LEARNPATH, 0);

$form->addElement('html', '</div>');

$defaults['activate_start_date_check'] = 1;
$defaults['accumulate_scorm_time'] = 0;
if ('true' === api_get_setting('scorm_cumulative_session_time')) {
    $defaults['accumulate_scorm_time'] = 1;
}

$defaults['publicated_on'] = api_get_local_time();
$defaults['expired_on'] = api_get_local_time(time() + 86400);

$form->setDefaults($defaults);
$form->addButtonCreate(get_lang('Continue'));

if ($form->validate()) {
    $publicated_on = null;
    if (isset($_REQUEST['activate_start_date_check']) &&
        1 == $_REQUEST['activate_start_date_check']
    ) {
        $publicated_on = $_REQUEST['publicated_on'];
    }

    $expired_on = null;
    if (isset($_REQUEST['activate_end_date_check']) &&
        1 == $_REQUEST['activate_end_date_check']
    ) {
        $expired_on = $_REQUEST['expired_on'];
    }

    $lp = learnpath::add_lp(
        api_get_course_id(),
        $_REQUEST['lp_name'],
        '',
        'chamilo',
        'manual',
        '',
        $publicated_on,
        $expired_on,
        $_REQUEST['category_id']
    );
    $lpId = $lp->getIid();
    if ($lpId) {
        // Create temp form validator to save skills
        $form = new FormValidator('lp_add');
        $form->addSelect('skills', 'skills');
        SkillModel::saveSkills($form, ITEM_TYPE_LEARNPATH, $lpId);

        $extraFieldValue = new ExtraFieldValue('lp');
        $_REQUEST['item_id'] = $lpId;
        $extraFieldValue->saveFieldValues($_REQUEST);

        // TODO: Maybe create a first directory directly to avoid bugging the user with useless queries
        /*$_SESSION['oLP'] = new learnpath(
            api_get_course_id(),
            $lp,
            api_get_user_id()
        );*/

        $lp->setSubscribeUsers(isset($_REQUEST['subscribe_users']) ? 1 : 0);
        $lp->setAccumulateScormTime(1 === (int) $_REQUEST['accumulate_scorm_time'] ? 1 : 0);
        $lpRepo->update($lp);

        $url = api_get_self().'?action=add_item&type=step&lp_id='.$lpId.'&'.api_get_cidreq();
        header("Location: $url&isStudentView=false");
        exit;
    }

    $url = api_get_self().'?action=list&'.api_get_cidreq();
    header("Location: $url&isStudentView=false");
    exit;
}

Display::display_header(get_lang('Create new learning path'), 'Path');

$actions = '<a href="lp_controller.php?'.api_get_cidreq().'">'.
    Display::return_icon(
        'back.png',
        get_lang('ReturnToLearning paths'),
        '',
        ICON_SIZE_MEDIUM
    ).'</a>';
echo Display::toolbarAction('toolbar', [$actions]);

echo Display::return_message(
    get_lang(
        '<strong>Welcome</strong> to the Chamilo Course authoring tool.<br />Create your courses step-by-step. The table of contents will appear to the left.'
    ),
    'normal',
    false
);

if ($_POST && empty($_REQUEST['lp_name'])) {
    echo Display::return_message(
        get_lang('The form contains incorrect or incomplete data. Please check your input.'),
        'error',
        false
    );
}

$form->display();

Display::display_footer();
