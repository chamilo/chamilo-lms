<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Script allowing simple edition of learnpath information (title, description, etc).
 *
 * @author  Yannick Warnier <ywarnier@beeznest.org>
 */
require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';

api_protect_course_script();

/** @var learnpath $learnPath */
$learnPath = Session::read('oLP');

$nameTools = get_lang('Doc');
$this_section = SECTION_COURSES;
Event::event_access_tool(TOOL_LEARNPATH);

$lpId = $learnPath->get_id();

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
$interbreadcrumb[] = [
    'url' => api_get_self()."?action=build&lp_id=".$lpId.'&'.api_get_cidreq(),
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
    'lp_controller.php?'.api_get_cidreq()
);

// Form title
$form->addElement('header', get_lang('Edit'));

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
    $form->addElement('text', 'lp_name', api_ucfirst(get_lang('LearnpathTitle')), ['size' => 43]);
}
$form->applyFilter('lp_name', 'html_filter');
$form->addRule('lp_name', get_lang('ThisFieldIsRequired'), 'required');
$form->addElement('hidden', 'lp_encoding');
$items = learnpath::getCategoryFromCourseIntoSelect(api_get_course_int_id(), true);
$form->addElement('select', 'category_id', get_lang('Category'), $items);

// Hide toc frame
$form->addElement(
    'checkbox',
    'hide_toc_frame',
    null,
    get_lang('HideTocFrame')
);

if (api_get_setting('allow_course_theme') === 'true') {
    $mycourselptheme = api_get_course_setting('allow_learning_path_theme');
    if (!empty($mycourselptheme) && $mycourselptheme != -1 && $mycourselptheme == 1) {
        //LP theme picker
        $theme_select = $form->addElement('SelectTheme', 'lp_theme', get_lang('Theme'));
        $form->applyFilter('lp_theme', 'trim');
        $s_theme = $learnPath->get_theme();
        $theme_select->setSelected($s_theme); //default
    }
}

// Author
$form->addElement(
    'html_editor',
    'lp_author',
    get_lang('Author'),
    ['size' => 80],
    ['ToolbarSet' => 'LearningPathAuthor', 'Width' => '100%', 'Height' => '200px']
);
$form->applyFilter('lp_author', 'html_filter');

// LP image
if (strlen($learnPath->get_preview_image()) > 0) {
    $show_preview_image = '<img src='.api_get_path(WEB_COURSE_PATH).api_get_course_path()
        .'/upload/learning_path/images/'.$learnPath->get_preview_image().'>';
    $form->addElement('label', get_lang('ImagePreview'), $show_preview_image);
    $form->addElement('checkbox', 'remove_picture', null, get_lang('DelImage'));
}
$label = $learnPath->get_preview_image() != '' ? get_lang('UpdateImage') : get_lang('AddImage');
$form->addElement('file', 'lp_preview_image', [$label, get_lang('ImageWillResizeMsg')]);
$form->addRule('lp_preview_image', get_lang('OnlyImagesAllowed'), 'filetype', ['jpg', 'jpeg', 'png', 'gif']);

// Search terms (only if search is activated).
if (api_get_setting('search_enabled') === 'true') {
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

$hideTableOfContents = $learnPath->getHideTableOfContents();
$defaults['lp_encoding'] = Security::remove_XSS($learnPath->encoding);
$defaults['lp_name'] = Security::remove_XSS($learnPath->get_name());
$defaults['lp_author'] = Security::remove_XSS($learnPath->get_author());
$defaults['hide_toc_frame'] = $hideTableOfContents;
$defaults['category_id'] = $learnPath->getCategoryId();
$defaults['accumulate_scorm_time'] = $learnPath->getAccumulateScormTime();

$expired_on = $learnPath->expired_on;
$publicated_on = $learnPath->publicated_on;

// Prerequisites
$form->addElement('html', '<div class="form-group">');
$items = $learnPath->display_lp_prerequisites_list();
$form->addElement('html', '<label class="col-md-2">'.get_lang('LearnpathPrerequisites').'</label>');
$form->addElement('html', '<div class="col-md-8">');
$form->addElement('html', $items);
$form->addElement('html', '<div class="help-block">'.get_lang('LpPrerequisiteDescription').'</div>');
$form->addElement('html', '</div>');
$form->addElement('html', '<div class="col-md-2"></div>');
$form->addElement('html', '</div>');
// Time Control
if (Tracking::minimumTimeAvailable(api_get_session_id(), api_get_course_int_id())) {
    $accumulateTime = $_SESSION['oLP']->getAccumulateWorkTime();
    $form->addText('accumulate_work_time', [get_lang('LpMinTime'), get_lang('LpMinTimeDescription')]);
    $defaults['accumulate_work_time'] = $accumulateTime;
}

// Start date
$form->addElement(
    'checkbox',
    'activate_start_date_check',
    null,
    get_lang('EnableStartTime'),
    ['onclick' => 'activate_start_date()']
);

$display_date = 'none';
if (!empty($publicated_on) && $publicated_on !== '0000-00-00 00:00:00') {
    $display_date = 'block';
    $defaults['activate_start_date_check'] = 1;
}

$form->addElement('html', '<div id="start_date_div" style="display:'.$display_date.';">');
$form->addDateTimePicker('publicated_on', get_lang('PublicationDate'));
$form->addElement('html', '</div>');

//End date
$form->addElement(
    'checkbox',
    'activate_end_date_check',
    null,
    get_lang('EnableEndTime'),
    ['onclick' => 'activate_end_date()']
);
$display_date = 'none';
if (!empty($expired_on)) {
    $display_date = 'block';
    $defaults['activate_end_date_check'] = 1;
}

$form->addElement('html', '<div id="end_date_div" style="display:'.$display_date.';">');
$form->addDateTimePicker('expired_on', get_lang('ExpirationDate'));
$form->addElement('html', '</div>');

if (api_is_platform_admin()) {
    $form->addElement('checkbox', 'use_max_score', null, get_lang('UseMaxScore100'));
    $defaults['use_max_score'] = $learnPath->use_max_score;
}

$subscriptionSettings = learnpath::getSubscriptionSettings();
if ($subscriptionSettings['allow_add_users_to_lp']) {
    $form->addElement(
        'checkbox',
        'subscribe_users',
        null,
        get_lang('SubscribeUsersToLp')
    );
}

// accumulate_scorm_time
$form->addElement(
    'checkbox',
    'accumulate_scorm_time',
    [null, get_lang('AccumulateScormTimeInfo')],
    get_lang('AccumulateScormTime')
);

$scoreAsProgressSetting = api_get_configuration_value('lp_score_as_progress_enable');
$countItems = $learnPath->get_total_items_count();
$lpType = $learnPath->get_type();
// This option is only usable for SCORM, if there is only 1 item, otherwise
// using the score as progress would not work anymore (we would have to divide
// between the two without knowing if the second has any score at all)
// TODO: automatically cancel this setting if items >= 2
if ($scoreAsProgressSetting && $countItems < 2 && $lpType == 2) {
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

if ($form->hasElement('extra_authors')) {
    /** @var HTML_QuickForm_select $author */
    $author = $form->getElement('extra_authors');
    $conditions = [
        'enabled' => 1,
        'status' => COURSEMANAGER,
    ];
    $teachers = UserManager::get_user_list($conditions);
    $options = [];
    foreach ($teachers as $teacher) {
        $options[$teacher['id']] = $teacher['complete_name'];
    }
    $author->setOptions($options);
}

Skill::addSkillsToForm($form, api_get_course_int_id(), api_get_session_id(), ITEM_TYPE_LEARNPATH, $lpId);

// select the next lp
if (true === api_get_configuration_value('lp_enable_flow')) {
    $nextLpsOptions = learnpath::getNextLpsAvailable(api_get_course_int_id(), $lpId);
    $nextLpId = learnpath::getFlowNextLpId($lpId, api_get_course_int_id());
    if (!empty($nextLpId)) {
        $nextLpsOptions[$nextLpId] = learnPath::getLpNameById($nextLpId);
    }
    if (!empty($nextLpsOptions)) {
        $form->addSelect(
            'next_lp_id',
            get_lang('SelectTheNextLp'),
            $nextLpsOptions
        );
        $defaults['next_lp_id'] = $nextLpId;
    }
}

// Submit button
$form->addButtonSave(get_lang('SaveLPSettings'));

// Hidden fields
$form->addElement('hidden', 'action', 'update_lp');
$form->addElement('hidden', 'lp_id', $lpId);

$htmlHeadXtra[] = '<script>
$(function() {
    '.$extra['jquery_ready_content'].'
});
</script>';

$htmlHeadXtra[] = '<script>'.$learnPath->get_js_dropdown_array().'</script>';

$defaults['publicated_on'] = !empty($publicated_on) && $publicated_on !== '0000-00-00 00:00:00'
    ? api_get_local_time($publicated_on)
    : null;
$defaults['expired_on'] = (!empty($expired_on))
    ? api_get_local_time($expired_on)
    : date('Y-m-d 12:00:00', time() + 84600);
$defaults['subscribe_users'] = $learnPath->getSubscribeUsers();

$display = api_get_configuration_value('lp_view_settings')['display'] ?? [];

if (!empty($display)) {
    $addExtraQuitToHomeIcon = $display['add_extra_quit_to_home_icon'] ?? false;
    $value = (new ExtraFieldValue('lp'))->get_values_by_handler_and_field_variable($lpId, 'add_extra_quit_button');

    if (!is_array($value) && $addExtraQuitToHomeIcon) {
        $defaults['extra_add_extra_quit_button[extra_add_extra_quit_button]'] = true;
    }
}

$form->setDefaults($defaults);

Display::display_header(get_lang('CourseSettings'), 'Path');

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
