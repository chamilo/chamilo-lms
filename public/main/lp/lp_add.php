<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

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

$request = Container::getRequest();
$lpId = $request->query->getInt('lp_id', $request->request->getInt('lp_id'));
$is_allowed_to_edit = api_is_allowed_to_edit(false, true, false, false);

// Only treat student view as enabled if it was explicitly passed in the URL query string.
// $request->query reads only from GET params, not cookies, so no further guard is needed.
$isStudentView = filter_var($request->query->get('isStudentView', 'false'), FILTER_VALIDATE_BOOLEAN);

if (!$is_allowed_to_edit || $isStudentView) {
    $course = api_get_course_entity(api_get_course_int_id());
    $nodeId = method_exists($course, 'getResourceNode') && $course->getResourceNode()
        ? (int) $course->getResourceNode()->getId()
        : 0;

    $cid = $request->query->getInt('cid', $request->request->getInt('cid', api_get_course_int_id()));
    $sid = $request->query->getInt('sid', $request->request->getInt('sid', api_get_session_id()));

    $qs = ['cid' => $cid];
    if ($sid > 0) {
        $qs['sid'] = $sid;
    }
    if ($request->query->has('gid') || $request->request->has('gid')) {
        $qs['gid'] = $request->query->getInt('gid', $request->request->getInt('gid'));
    }
    if ($request->query->has('gradebook') || $request->request->has('gradebook')) {
        $qs['gradebook'] = $request->query->getInt('gradebook', $request->request->getInt('gradebook'));
    }

    // Preserve student view only if it was explicitly requested.
    if ($isStudentView) {
        $qs['isStudentView'] = 'true';
    }

    $listUrl = api_get_path(WEB_PATH).'resources/lp/'.$nodeId.'?'.http_build_query($qs);
    header('Location: '.$listUrl);

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
if ('true' === api_get_setting('editor.save_titles_as_html')) {
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
    null,
    get_lang('When enabled, the session time for SCORM Learning Paths will be cumulative, otherwise, it will only be counted from the last update time.'),
    []
);

// Start date
$form->addCheckBox(
    'activate_start_date_check',
    null,
    get_lang('Enable start time'),
    ['onclick' => 'activate_start_date()']
);
$form->addElement('html', '<div id="start_date_div" style="display:block;">');
$form->addDateTimePicker('published_on', get_lang('Publication date'));
$form->addElement('html', '</div>');

// End date
$form->addCheckBox(
    'activate_end_date_check',
    null,
    get_lang('Enable end time'),
    ['onclick' => 'activate_end_date()']
);
$form->addElement('html', '<div id="end_date_div" style="display:none;">');
$form->addDateTimePicker('expired_on', get_lang('Expiration date'));
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

$defaults['published_on'] = api_get_local_time();
$defaults['expired_on'] = api_get_local_time(time() + 86400);

$form->setDefaults($defaults);
$form->addButtonCreate(get_lang('Continue'));

if ($form->validate()) {
    $published_on = null;
    if (1 === $request->request->getInt('activate_start_date_check')) {
        $published_on = $request->request->get('published_on');
    }

    $expired_on = null;
    if (1 === $request->request->getInt('activate_end_date_check')) {
        $expired_on = $request->request->get('expired_on');
    }

    $lp = learnpath::add_lp(
        api_get_course_id(),
        $request->request->get('lp_name'),
        '',
        'chamilo',
        'manual',
        '',
        $published_on,
        $expired_on,
        $request->request->get('category_id')
    );
    $lpId = $lp->getIid();
    if ($lpId) {
        // Create temp form validator to save skills
        $form = new FormValidator('lp_add');
        $form->addSelect('skills', 'skills');
        SkillModel::saveSkills($form, ITEM_TYPE_LEARNPATH, $lpId);

        $extraFieldValue = new ExtraFieldValue('lp');
        $requestData = array_merge($request->request->all(), ['item_id' => $lpId]);
        $extraFieldValue->saveFieldValues($requestData);

        // TODO: Maybe create a first directory directly to avoid bugging the user with useless queries
        /*$_SESSION['oLP'] = new learnpath(
            api_get_course_id(),
            $lp,
            api_get_user_id()
        );*/

        $lp->setSubscribeUsers((int) (null !== $request->request->get('subscribe_users')));
        $lp->setAccumulateScormTime((int) (null !== $request->request->get('accumulate_scorm_time')));
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
    Display::getMdiIcon('arrow-left-bold-box', 'ch-tool-icon', '', 32, get_lang('Back to learning paths'))
    .'</a>';
echo Display::toolbarAction('toolbar', [$actions]);

echo Display::return_message(
    get_lang(
        '<strong>Welcome</strong> to the Chamilo Course authoring tool.<br />Create your courses step-by-step. The table of contents will appear to the left.'
    ),
    'normal',
    false
);

if ($request->isMethod('POST') && empty($request->request->get('lp_name'))) {
    echo Display::return_message(
        get_lang('The form contains incorrect or incomplete data. Please check your input.'),
        'error',
        false
    );
}

$form->display();

Display::display_footer();
