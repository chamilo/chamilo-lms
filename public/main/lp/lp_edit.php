<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CLp;
use ChamiloSession as Session;

/**
 * Script allowing simple edition of learnpath information (title, description, etc).
 *
 * @author  Yannick Warnier <ywarnier@beeznest.org>
 */
api_protect_course_script();

/** @var learnpath $learnPath */
$learnPath = Session::read('oLP');
$lpRepo = Container::getLpRepository();

$lpId = $_REQUEST['lp_id'] ?? 0;
if (empty($lpId)) {
    api_not_allowed(true);
}
$lpId = (int) $lpId;

/** @var CLp $lp */
$lp = $lpRepo->find($lpId);

$nameTools = get_lang('Document');
$this_section = SECTION_COURSES;
Event::event_access_tool(TOOL_LEARNPATH);

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
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=add_item&lp_id=".$lpId.'&'.api_get_cidreq(),
    'name' => $learnPath->getNameNoTags(),
];

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

$defaults = [];
$form = new FormValidator(
    'form1',
    'post',
    'lp_controller.php?'.api_get_cidreq().'&lp_id='.$lpId
);

$form->addHeader(get_lang('Edit'));

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
    $form->addElement('text', 'lp_name', api_ucfirst(get_lang('Title')), ['size' => 43]);
}
$form->applyFilter('lp_name', 'html_filter');
$form->addRule('lp_name', get_lang('Required field'), 'required');
$form->addElement('hidden', 'lp_encoding');
$items = learnpath::getCategoryFromCourseIntoSelect(api_get_course_int_id(), true);
$form->addSelect('category_id', get_lang('Category'), $items);

// Hide toc frame
$form->addElement(
    'checkbox',
    'hide_toc_frame',
    null,
    get_lang('Hide table of contents frame')
);

if ('true' === api_get_setting('allow_course_theme')) {
    $mycourselptheme = api_get_course_setting('allow_learning_path_theme');
    if (!empty($mycourselptheme) && -1 != $mycourselptheme && 1 == $mycourselptheme) {
        // LP theme picker
        $themeSelect = $form->addSelectTheme('lp_theme', get_lang('Graphical theme'));
        $form->applyFilter('lp_theme', 'trim');
        $s_theme = $learnPath->get_theme();
        $themeSelect->setSelected($s_theme); //default
    }
}

// Author
$form->addHtmlEditor(
    'html_editor',
    'lp_author',
    get_lang('Author'),
    ['size' => 80],
    ['ToolbarSet' => 'LearningPathAuthor', 'Width' => '100%', 'Height' => '200px']
);
$form->applyFilter('lp_author', 'html_filter');

// LP image
$label = get_lang('Add image');
if ($lp->getResourceNode()->hasResourceFile()) {
    $label = get_lang('Update image');
    $imageUrl = $lpRepo->getResourceFileUrl($lp);
    $form->addElement('label', get_lang('Image preview'), '<img src="'.$imageUrl.'"/>');
    $form->addElement('checkbox', 'remove_picture', null, get_lang('Remove picture'));
}

$form->addFile('lp_preview_image', [$label, get_lang('Trainer picture will resize if needed')]);
$form->addRule(
    'lp_preview_image',
    get_lang('Only PNG, JPG or GIF images allowed'),
    'filetype',
    ['jpg', 'jpeg', 'png', 'gif']
);

// Search terms (only if search is activated).
if ('true' === api_get_setting('search_enabled')) {
    $specific_fields = get_specific_field_list();
    foreach ($specific_fields as $specific_field) {
        $form->addElement('text', $specific_field['code'], $specific_field['name']);
        $filter = [
            'c_id' => "'".api_get_course_int_id()."'",
            'field_id' => $specific_field['id'],
            'ref_id' => $learnPath->lp_id,
            'tool_id' => '\''.TOOL_LEARNPATH.'\'',
        ];
        $values = get_specific_field_values_list($filter, ['value']);
        if (!empty($values)) {
            $arr_str_values = [];
            foreach ($values as $value) {
                $arr_str_values[] = $value['value'];
            }
            $defaults[$specific_field['code']] = implode(', ', $arr_str_values);
        }
    }
}

$hideTableOfContents = (int) $lp->getHideTocFrame();
$defaults['lp_encoding'] = Security::remove_XSS($learnPath->encoding);
$defaults['lp_name'] = Security::remove_XSS($learnPath->get_name());
$defaults['lp_author'] = Security::remove_XSS($lp->getAuthor());
$defaults['hide_toc_frame'] = $hideTableOfContents;
$defaults['category_id'] = $learnPath->getCategoryId();
$defaults['accumulate_scorm_time'] = $learnPath->getAccumulateScormTime();

$expired_on = $learnPath->expired_on;
$publicated_on = $learnPath->publicated_on;

// Prerequisites
$learnPath->display_lp_prerequisites_list($form);

$form->addHtml(
    '<div class="help-block">'.
    get_lang(
        'Selecting another learning path as a prerequisite will hide the current prerequisite until the one in prerequisite is fully completed (100%)'
    ).
    '</div>'
);

// Time Control
if (Tracking::minimumTimeAvailable(api_get_session_id(), api_get_course_int_id())) {
    $form->addText(
        'accumulate_work_time',
        [get_lang('Minimum time (minutes)'), get_lang('Minimum time (minutes)Description')]
    );
    $defaults['accumulate_work_time'] = $lp->getAccumulateWorkTime();
}

// Start date
$form->addElement(
    'checkbox',
    'activate_start_date_check',
    null,
    get_lang('Enable start time'),
    ['onclick' => 'activate_start_date()']
);

$display_date = 'none';
if (!empty($publicated_on) && '0000-00-00 00:00:00' !== $publicated_on) {
    $display_date = 'block';
    $defaults['activate_start_date_check'] = 1;
}

$form->addElement('html', '<div id="start_date_div" style="display:'.$display_date.';">');
$form->addDateTimePicker('publicated_on', get_lang('Publication date'));
$form->addElement('html', '</div>');

//End date
$form->addCheckBox(
    'activate_end_date_check',
    null,
    get_lang('Enable end time'),
    ['onclick' => 'activate_end_date()']
);
$display_date = 'none';
if (!empty($expired_on)) {
    $display_date = 'block';
    $defaults['activate_end_date_check'] = 1;
}

$form->addElement('html', '<div id="end_date_div" style="display:'.$display_date.';">');
$form->addDateTimePicker('expired_on', get_lang('Expiration date'));
$form->addElement('html', '</div>');

if (api_is_platform_admin()) {
    $form->addElement('checkbox', 'use_max_score', null, get_lang('Use default maximum score of 100'));
    $defaults['use_max_score'] = $learnPath->use_max_score;
}

$subscriptionSettings = learnpath::getSubscriptionSettings();
if ($subscriptionSettings['allow_add_users_to_lp']) {
    $form->addElement(
        'checkbox',
        'subscribe_users',
        null,
        get_lang('Subscribe users to learning path')
    );
}

// accumulate_scorm_time
$form->addElement(
    'checkbox',
    'accumulate_scorm_time',
    [
        null,
        get_lang('When enabled, the session time for SCORM Learning Paths will be cumulative, otherwise, it will only be counted from the last update time.'),
    ],
    get_lang('Accumulate SCORM session time')
);

$scoreAsProgressSetting = api_get_configuration_value('lp_score_as_progress_enable');
$countItems = $learnPath->get_total_items_count();
$lpType = $learnPath->get_type();
// This option is only usable for SCORM, if there is only 1 item, otherwise
// using the score as progress would not work anymore (we would have to divide
// between the two without knowing if the second has any score at all)
// TODO: automatically cancel this setting if items >= 2
if ($scoreAsProgressSetting && $countItems < 2 && 2 == $lpType) {
    $scoreAsProgress = $learnPath->getUseScoreAsProgress();
    $form->addElement(
        'checkbox',
        'extra_use_score_as_progress',
        [null, get_lang('LearnpathUseScoreAsProgressComment')],
        get_lang('LearnpathUseScoreAsProgress')
    );
    $defaults['extra_use_score_as_progress'] = $scoreAsProgress;
}

$options = learnpath::getIconSelect();

if (!empty($options)) {
    $form->addSelect(
        'extra_lp_icon',
        get_lang('Icon'),
        $options
    );
    $defaults['extra_lp_icon'] = learnpath::getSelectedIcon($lpId);
}

$extraField = new ExtraField('lp');
$extra = $extraField->addElements(
    $form,
    $lpId,
    ['lp_icon', 'use_score_as_progress']
);

$skillList = SkillModel::addSkillsToForm($form, ITEM_TYPE_LEARNPATH, $lpId);

// Submit button
$form->addButtonSave(get_lang('Save course settings'));

// Hidden fields
$form->addHidden('action', 'edit');
$form->addHidden('lp_id', $lpId);

$htmlHeadXtra[] = '<script>
$(function() {
    '.$extra['jquery_ready_content'].'
});
</script>';

$htmlHeadXtra[] = '<script>'.$learnPath->get_js_dropdown_array().'</script>';

$defaults['publicated_on'] = !empty($publicated_on) && '0000-00-00 00:00:00' !== $publicated_on
    ? api_get_local_time($publicated_on)
    : null;
$defaults['expired_on'] = (!empty($expired_on))
    ? api_get_local_time($expired_on)
    : date('Y-m-d 12:00:00', time() + 84600);
$defaults['subscribe_users'] = $learnPath->getSubscribeUsers();
$defaults['skills'] = array_keys($skillList);
$form->setDefaults($defaults);

if ($form->validate()) {
    $em = Database::getManager();
    $hide_toc_frame = 0;
    if (isset($_REQUEST['hide_toc_frame']) && 1 == $_REQUEST['hide_toc_frame']) {
        $hide_toc_frame = 1;
    }

    $publicated_on = null;
    if (isset($_REQUEST['activate_start_date_check']) && 1 == $_REQUEST['activate_start_date_check']) {
        $publicated_on = $_REQUEST['publicated_on'];
    }

    $expired_on = null;
    if (isset($_REQUEST['activate_end_date_check']) && 1 == $_REQUEST['activate_end_date_check']) {
        $expired_on = $_REQUEST['expired_on'];
    }

    if (isset($_REQUEST['remove_picture']) && $_REQUEST['remove_picture']) {
        if ($lp->getResourceNode()->hasResourceFile()) {
            $lp->getResourceNode()->setResourceFile(null);
        }
    }

    $lpCategoryRepo = Container::getLpCategoryRepository();
    $category = null;
    if (isset($_REQUEST['category_id'])) {
        $category = $lpCategoryRepo->find($_REQUEST['category_id']);
    }

    $lp
        ->setName($_REQUEST['lp_name'])
        ->setAuthor($_REQUEST['lp_author'])
        ->setTheme($_REQUEST['lp_theme'])
        ->setHideTocFrame($hide_toc_frame)
        ->setPrerequisite($_POST['prerequisites'] ?? 0)
        ->setAccumulateWorkTime($_REQUEST['accumulate_work_time'] ?? 0)
        ->setContentMaker($_REQUEST['lp_maker'] ?? '')
        ->setContentLocal($_REQUEST['lp_proximity'] ?? '')
        ->setUseMaxScore(isset($_POST['use_max_score']) ? 1 : 0)
        ->setDefaultEncoding($_REQUEST['lp_encoding'])
        ->setAccumulateScormTime(isset($_REQUEST['accumulate_scorm_time']) ? 1 : 0)
        ->setPublicatedOn(api_get_utc_datetime($publicated_on, true, true))
        ->setExpiredOn(api_get_utc_datetime($expired_on, true, true))
        ->setCategory($category)
        ->setSubscribeUsers(isset($_REQUEST['subscribe_users']) ? 1 : 0)
    ;

    $extraFieldValue = new ExtraFieldValue('lp');
    $_REQUEST['item_id'] = $lpId;
    $extraFieldValue->saveFieldValues($_REQUEST);

    $request = Container::getRequest();
    if ($request->files->has('lp_preview_image')) {
        $file = $request->files->get('lp_preview_image');
        if (!empty($file)) {
            $lpRepo->addFile($lp, $file);
        }
    }

    $lpRepo->update($lp);

    $form = new FormValidator('form1');
    $form->addSelect('skills', 'skills');
    SkillModel::saveSkills($form, ITEM_TYPE_LEARNPATH, $lpId);

    if ('true' === api_get_setting('search_enabled')) {
        $specific_fields = get_specific_field_list();
        foreach ($specific_fields as $specific_field) {
            $learnPath->set_terms_by_prefix($_REQUEST[$specific_field['code']], $specific_field['code']);
            $new_values = explode(',', trim($_REQUEST[$specific_field['code']]));
            if (!empty($new_values)) {
                array_walk($new_values, 'trim');
                delete_all_specific_field_value(
                    api_get_course_id(),
                    $specific_field['id'],
                    TOOL_LEARNPATH,
                    $lpId
                );

                foreach ($new_values as $value) {
                    if (!empty($value)) {
                        add_specific_field_value(
                            $specific_field['id'],
                            api_get_course_id(),
                            TOOL_LEARNPATH,
                            $lpId,
                            $value
                        );
                    }
                }
            }
        }
    }
    Display::addFlash(Display::return_message(get_lang('Update successful')));
    $url = api_get_self().'?action=add_item&type=step&lp_id='.$lpId.'&'.api_get_cidreq();
    header('Location: '.$url);
    exit;
}

Display::display_header(get_lang('Course settings'), 'Path');

echo $learnPath->build_action_menu(false, false, true, false);
echo '<div class="row">';
echo '<div class="'.($hideTableOfContents ? 'col-md-12' : 'col-md-8').'" id="pnl-frm">';
$form->display();
echo '</div>';
echo '<div class="'.($hideTableOfContents ? 'hide' : 'col-md-4').' text-right" id="pnl-toc">';
echo Display::return_icon('course_setting_layout.png');
echo '</div>';
echo '</div>';
echo "
<script>
    $(function() {
        $('[name=\'hide_toc_frame\']').on('change', function() {
            $('#pnl-frm').toggleClass('col-md-8').toggleClass('col-sm-12');
            $('#pnl-toc').toggleClass('col-md-4').toggleClass('hide');
        });
    });
</script>
";
Display::display_footer();
