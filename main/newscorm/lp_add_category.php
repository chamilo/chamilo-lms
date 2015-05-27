<?php
/* For licensing terms, see /license.txt */

/**
 * @author Julio Montoya <gugli100@gmail.com> Adding formvalidator support
 *
 * @package chamilo.learnpath
 */
$this_section = SECTION_COURSES;
api_protect_course_script();

require 'learnpath_functions.inc.php';
require 'resourcelinker.inc.php';

$language_file = 'learnpath';

/* Header and action code */

$currentstyle = api_get_setting('stylesheets');
$htmlHeadXtra[] = '<script>
function setFocus(){
    $("#learnpath_title").focus();
}

$(document).ready(function () {
    setFocus();
});

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

$isStudentView  = isset($_REQUEST['isStudentView']) ? $_REQUEST['isStudentView'] : null;
$learnpath_id   = isset($_REQUEST['lp_id']) ? $_REQUEST['lp_id'] : null;

/* MAIN CODE */

if ((!$is_allowed_to_edit) || ($isStudentView)) {
    //error_log('New LP - User not authorized in lp_add.php');
    header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
    exit;
}

$interbreadcrumb[] = array('url' => 'lp_controller.php?action=list', 'name' => get_lang('LearningPaths'));

$form = new FormValidator('lp_add_category', 'post', 'lp_controller.php');

// Form title
$form->addElement('header', null, get_lang('AddLPCategory'));

// Title
$form->addElement('text', 'name', api_ucfirst(get_lang('Name')), array('class' => 'span6'));
$form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');

$form->addElement('hidden', 'action', 'add_lp_category');
$form->addElement('hidden', 'c_id', api_get_course_int_id());
$form->addElement('hidden', 'id', 0);

$form->addElement('style_submit_button', 'Submit', get_lang('Save'),'class="save"');

if ($form->validate()) {
    $values = $form->getSubmitValues();
    if (!empty($values['id'])) {
        learnpath::updateCategory($values);
        $url = api_get_self().'?action=list&'.api_get_cidreq();
        header('Location: '.$url);
        exit;
    } else {
        learnpath::createCategory($values);
        $url = api_get_self().'?action=list&'.api_get_cidreq();
        header('Location: '.$url);
        exit;
    }
} else {
    $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : null;

    if ($id) {
        $item = learnpath::getCategory($id);
        $defaults = array(
            'id' => $item->getId(),
            'name' => $item->getName()
        );
        $form->setDefaults($defaults);
    }
}

Display::display_header(get_lang('LearnpathAddLearnpath'), 'Path');

echo '<div class="actions">';
echo '<a href="lp_controller.php?'.api_get_cidreq().'">'.
    Display::return_icon('back.png', get_lang('ReturnToLearningPaths'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

$form->display();

Display::display_footer();
