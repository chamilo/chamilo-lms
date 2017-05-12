<?php
/* For licensing terms, see /license.txt */

/**
 * This is a learning path creation and player tool in Chamilo - previously learnpath_handler.php
 *
 * @author Patrick Cool
 * @author Denes Nagy
 * @author Roan Embrechts, refactoring and code cleaning
 * @author Yannick Warnier <ywarnier@beeznest.org> - cleaning and update for new SCORM tool
 * @author Julio Montoya <gugli100@gmail.com> Adding formvalidator support
 *
 * @package chamilo.learnpath
 */

$this_section = SECTION_COURSES;
api_protect_course_script();

/* Header and action code */
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

$isStudentView = isset($_REQUEST['isStudentView']) ? $_REQUEST['isStudentView'] : null;
$learnpath_id = isset($_REQUEST['lp_id']) ? $_REQUEST['lp_id'] : null;

/* MAIN CODE */
if ((!$is_allowed_to_edit) || ($isStudentView)) {
    header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
    exit;
}
// From here on, we are admin because of the previous condition, so don't check anymore.

/*
    Course admin section
    - all the functions not available for students - always available in this case (page only shown to admin)
*/
if (isset($_SESSION['gradebook'])) {
    $gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array(
        'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
        'name' => get_lang('ToolGradebook')
    );
}

$interbreadcrumb[] = array('url' => 'lp_controller.php?action=list', 'name' => get_lang('LearningPaths'));

Display::display_header(get_lang('LearnpathAddLearnpath'), 'Path');

echo '<div class="actions">';
echo '<a href="lp_controller.php?'.api_get_cidreq().'">'.
        Display::return_icon('back.png', get_lang('ReturnToLearningPaths'), '', ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

echo Display::return_message(get_lang('AddLpIntro'), 'normal', false);

if ($_POST && empty($_REQUEST['lp_name'])) {
    echo Display::return_message(get_lang('FormHasErrorsPleaseComplete'), 'error', false);
}

$form = new FormValidator(
    'lp_add',
    'post',
    api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'.api_get_cidreq()
);

// Form title
$form->addElement('header', get_lang('AddLpToStart'));

// Title
$form->addElement('text', 'lp_name', api_ucfirst(get_lang('LPName')), array('autofocus' => 'autofocus'));
$form->applyFilter('lp_name', 'html_filter');
$form->addRule('lp_name', get_lang('ThisFieldIsRequired'), 'required');

$form->addElement('hidden', 'post_time', time());
$form->addElement('hidden', 'action', 'add_lp');

$form->addButtonAdvancedSettings('advanced_params');
$form->addElement('html', '<div id="advanced_params_options" style="display:none">');

$items = learnpath::getCategoryFromCourseIntoSelect(api_get_course_int_id(), true);
$form->addElement('select', 'category_id', get_lang('Category'), $items);

// accumulate_scorm_time
$form->addElement(
    'checkbox',
    'accumulate_scorm_time',
    [get_lang('AccumulateScormTime'), get_lang('AccumulateScormTimeInfo')]
);

// Start date
$form->addElement('checkbox', 'activate_start_date_check', null, get_lang('EnableStartTime'), array('onclick' => 'activate_start_date()'));
$form->addElement('html', '<div id="start_date_div" style="display:block;">');
$form->addDatePicker('publicated_on', get_lang('PublicationDate'));
$form->addElement('html', '</div>');

//End date
$form->addElement('checkbox', 'activate_end_date_check', null, get_lang('EnableEndTime'), array('onclick' => 'activate_end_date()'));
$form->addElement('html', '<div id="end_date_div" style="display:none;">');
$form->addDatePicker('expired_on', get_lang('ExpirationDate'));
$form->addElement('html', '</div>');

$form->addElement('html', '</div>');

$defaults['activate_start_date_check']  = 1;

if (api_get_setting('scorm_cumulative_session_time') == 'true') {
    $defaults['accumulate_scorm_time']  = 1;
} else {
    $defaults['accumulate_scorm_time']  = 0;
}

$defaults['publicated_on'] = date('Y-m-d 08:00:00');
$defaults['expired_on'] = date('Y-m-d 08:00:00', time() + 86400);

$form->setDefaults($defaults);
$form->addButtonCreate(get_lang('CreateLearningPath'));

$form->display();
// Footer
Display::display_footer();
