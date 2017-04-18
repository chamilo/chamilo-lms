<?php
/* For licensing terms, see /license.txt */
/**
 * Script allowing simple edition of learnpath information (title, description, etc)
 * @package chamilo.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
*/

require_once api_get_path(LIBRARY_PATH).'specific_fields_manager.lib.php';

api_protect_course_script();

$show_description_field = false; //for now
$nameTools = get_lang('Doc');
$this_section = SECTION_COURSES;
Event::event_access_tool(TOOL_LEARNPATH);

if (isset($_SESSION['gradebook'])) {
    $gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array(
        'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
        'name' => get_lang('ToolGradebook')
    );
}
$interbreadcrumb[] = array('url' => 'lp_controller.php?action=list&'.api_get_cidreq(), 'name' => get_lang('LearningPaths'));
$interbreadcrumb[] = array(
    'url' => api_get_self()."?action=build&lp_id=".$_SESSION['oLP']->get_id().'&'.api_get_cidreq(),
    'name' => $_SESSION['oLP']->get_name()
);

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

$gradebook = isset($_GET['gradebook']) ? Security::remove_XSS($_GET['gradebook']) : null;

$defaults = array();
$form = new FormValidator('form1', 'post', 'lp_controller.php?'.api_get_cidreq());

// Form title
$form->addElement('header', get_lang('EditLPSettings'));

// Title
$form->addElement('text', 'lp_name', api_ucfirst(get_lang('LearnpathTitle')), array('size' => 43));
$form->applyFilter('lp_name', 'html_filter');
$form->addRule('lp_name', get_lang('ThisFieldIsRequired'), 'required');
$form->addElement('hidden', 'lp_encoding');
$items = learnpath::getCategoryFromCourseIntoSelect(api_get_course_int_id(), true);
$form->addElement('select', 'category_id', get_lang('Category'), $items);

// Hide toc frame
$hide_toc_frame = $form->addElement(
    'checkbox',
    'hide_toc_frame',
    null,
    get_lang('HideTocFrame'),
    array('onclick' => '$("#lp_layout_column").toggle()')
);
if (api_get_setting('allow_course_theme') === 'true') {
    $mycourselptheme = api_get_course_setting('allow_learning_path_theme');
    if (!empty($mycourselptheme) && $mycourselptheme != -1 && $mycourselptheme == 1) {
        //LP theme picker
        $theme_select = $form->addElement('SelectTheme', 'lp_theme', get_lang('Theme'));
        $form->applyFilter('lp_theme', 'trim');

        $s_theme = $_SESSION['oLP']->get_theme();
        $theme_select->setSelected($s_theme); //default
    }
}

// Author
$form->addElement(
    'html_editor',
    'lp_author',
    get_lang('Author'),
    array('size' => 80),
    array('ToolbarSet' => 'LearningPathAuthor', 'Width' => '100%', 'Height' => '150px')
);
$form->applyFilter('lp_author', 'html_filter');

// LP image
if (strlen($_SESSION['oLP']->get_preview_image()) > 0) {
    $show_preview_image = '<img src='.api_get_path(WEB_COURSE_PATH).api_get_course_path().'/upload/learning_path/images/'.$_SESSION['oLP']->get_preview_image().'>';
    $form->addElement('label', get_lang('ImagePreview'), $show_preview_image);
    $form->addElement('checkbox', 'remove_picture', null, get_lang('DelImage'));
}
$label = ($_SESSION['oLP']->get_preview_image() != '' ? get_lang('UpdateImage') : get_lang('AddImage'));
$form->addElement('file', 'lp_preview_image', array($label, get_lang('ImageWillResizeMsg')));

$form->addRule('lp_preview_image', get_lang('OnlyImagesAllowed'), 'filetype', array('jpg', 'jpeg', 'png', 'gif'));

// Search terms (only if search is activated).
if (api_get_setting('search_enabled') === 'true') {
    $specific_fields = get_specific_field_list();
    foreach ($specific_fields as $specific_field) {
        $form->addElement('text', $specific_field['code'], $specific_field['name']);
        $filter = array(
            'c_id' => "'".api_get_course_int_id()."'",
            'field_id' => $specific_field['id'],
            'ref_id' => $_SESSION['oLP']->lp_id,
            'tool_id' => '\''.TOOL_LEARNPATH.'\'',
        );
        $values = get_specific_field_values_list($filter, array('value'));
        if (!empty($values)) {
            $arr_str_values = array();
            foreach ($values as $value) {
                $arr_str_values[] = $value['value'];
            }
            $defaults[$specific_field['code']] = implode(', ', $arr_str_values);
        }
    }
}

$defaults['lp_encoding'] = Security::remove_XSS($_SESSION['oLP']->encoding);
$defaults['lp_name'] = Security::remove_XSS($_SESSION['oLP']->get_name());
$defaults['lp_author'] = Security::remove_XSS($_SESSION['oLP']->get_author());
$defaults['hide_toc_frame'] = Security::remove_XSS($_SESSION['oLP']->get_hide_toc_frame());
$defaults['category_id'] = intval($_SESSION['oLP']->getCategoryId());
$defaults['accumulate_scorm_time'] = $_SESSION['oLP']->getAccumulateScormTime();

$expired_on = $_SESSION['oLP']->expired_on;
$publicated_on = $_SESSION['oLP']->publicated_on;

// Prerequisites
$form->addElement('html', '<div class="form-group">');
$items = $_SESSION['oLP']->display_lp_prerequisites_list();
$form->addElement('html', '<label class="col-md-2">'.get_lang('LearnpathPrerequisites').'</label>');
$form->addElement('html', '<div class="col-md-10">');
$form->addElement('html', $items);
$form->addElement('html', '<div class="help-block">'.get_lang('LpPrerequisiteDescription').'</div></div></div>');

//Start date
$form->addElement(
    'checkbox',
    'activate_start_date_check',
    null,
    get_lang('EnableStartTime'),
    array('onclick' => 'activate_start_date()')
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
    array('onclick' => 'activate_end_date()')
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
    $defaults['use_max_score'] = $_SESSION['oLP']->use_max_score;
}

$form->addElement('checkbox', 'subscribe_users', null, get_lang('SubscribeUsersToLp'));

// accumulate_scorm_time
$form->addElement(
    'checkbox',
    'accumulate_scorm_time',
    [get_lang('AccumulateScormTime'), get_lang('AccumulateScormTimeInfo')]
);

$enableLpExtraFields = false;
if ($enableLpExtraFields) {
    $extraField = new ExtraField('lp');
    $extra = $extraField->addElements($form, $_SESSION['oLP']->get_id());
}

// Submit button
$form->addButtonSave(get_lang('SaveLPSettings'));

// Hidden fields
$form->addElement('hidden', 'action', 'update_lp');
$form->addElement('hidden', 'lp_id', $_SESSION['oLP']->get_id());

if ($enableLpExtraFields) {
    $htmlHeadXtra[] = '<script>
    $(function() {
        ' . $extra['jquery_ready_content'].'
    });
    </script>';
}


$defaults['publicated_on'] = !empty($publicated_on) && $publicated_on !== '0000-00-00 00:00:00' ? api_get_local_time($publicated_on) : null;
$defaults['expired_on'] = (!empty($expired_on)) ? api_get_local_time($expired_on) : date('Y-m-d 12:00:00', time() + 84600);
$defaults['subscribe_users'] = $_SESSION['oLP']->getSubscribeUsers();
$form->setDefaults($defaults);

Display::display_header(get_lang('CourseSettings'), 'Path');

echo $_SESSION['oLP']->build_action_menu(false, false, true, false);
echo '<div class="row">';
if ($_SESSION['oLP']->get_hide_toc_frame() == 1) {
    echo '<div class="col-md-12">';
    $form -> display();
    echo '</div>';
} else {
    echo '<div class="col-md-8">';
    $form -> display();
    echo '</div>';
    echo '<div class="col-md-4" align="center">';
    echo Display::return_icon('course_setting_layout.png');
    echo '</div>';
}
echo '</div>';
Display::display_footer();
