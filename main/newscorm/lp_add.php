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
/** 
 * Code
 */
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';

$this_section = SECTION_COURSES;
api_protect_course_script();

/* Libraries */

// The main_api.lib.php, database.lib.php and display.lib.php
// libraries are included by default.

require 'learnpath_functions.inc.php';
//include '../resourcelinker/resourcelinker.inc.php';
require 'resourcelinker.inc.php';
// Rewrite the language file, sadly overwritten by resourcelinker.inc.php.
// Name of the language file that needs to be included.
$language_file = 'learnpath';

/* Header and action code */

$currentstyle = api_get_setting('stylesheets');
//$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.api_get_path(WEB_CODE_PATH).'css/'.$currentstyle.'/learnpath.css"/>';
//$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="learnpath.css" />'; //will be a merged with original learnpath.css
$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="dtree.css" />'; //will be moved
$htmlHeadXtra[] = '<script type="text/javascript">
function setFocus(){
$("#learnpath_title").focus();
}
$(document).ready(function () {
  setFocus();
});
        
function timelimit() {
    if(document.getElementById(\'options2\').style.display == \'none\')
    {
        document.getElementById(\'options2\').style.display = \'block\';
    } else {
        document.getElementById(\'options2\').style.display = \'none\';
    }
}
     
</script>';

/* Constants and variables */

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

$tbl_lp      = Database::get_course_table(TABLE_LP_MAIN);
$tbl_lp_item = Database::get_course_table(TABLE_LP_ITEM);
$tbl_lp_view = Database::get_course_table(TABLE_LP_VIEW);

$isStudentView  = (int) $_REQUEST['isStudentView'];
$learnpath_id   = (int) $_REQUEST['lp_id'];
$submit			= $_POST['submit_button'];

/* MAIN CODE */

// Using the resource linker as a tool for adding resources to the learning path.
if ($action == 'add' && $type == 'learnpathitem') {
     $htmlHeadXtra[] = "<script language='JavaScript' type='text/javascript'> window.location=\"../resourcelinker/resourcelinker.php?source_id=5&action=$action&learnpath_id=$learnpath_id&chapter_id=$chapter_id&originalresource=no\"; </script>";
}

if ((!$is_allowed_to_edit) || ($isStudentView)) {
    error_log('New LP - User not authorized in lp_add.php');
    header('location:lp_controller.php?action=view&lp_id='.$learnpath_id);
}
// From here on, we are admin because of the previous condition, so don't check anymore.

$sql_query = "SELECT * FROM $tbl_lp WHERE id = $learnpath_id";
$result = Database::query($sql_query);
$therow = Database::fetch_array($result);

/*
    Course admin section
    - all the functions not available for students - always available in this case (page only shown to admin)
*/
if (isset($_SESSION['gradebook'])){
    $gradebook =	$_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook=='view') {
    $interbreadcrumb[]= array (
            'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
            'name' => get_lang('ToolGradebook')
        );
}

$interbreadcrumb[] = array('url' => 'lp_controller.php?action=list', 'name' => get_lang('LearningPaths'));

Display::display_header(get_lang('_add_learnpath'), 'Path');

echo '<div class="actions">';
echo '<a href="lp_controller.php?cidReq='.$_course['sysCode'].'">'.Display::return_icon('back.png', get_lang('ReturnToLearningPaths'),'','32').'</a>';
echo '</div>';

Display::display_normal_message(get_lang('AddLpIntro'), false);

if ($_POST AND empty($_REQUEST['lp_name'])) {
    Display::display_error_message(get_lang('FormHasErrorsPleaseComplete'), false);
}


$form = new FormValidator('lp_add', 'post', 'lp_controller.php');

// Form title
$form->addElement('header', null, get_lang('AddLpToStart'));

// Title
$form->addElement('text', 'lp_name', api_ucfirst(get_lang('LPName')), array('size' => 43));
$form->applyFilter('lp_name', 'html_filter');
$form->addRule('lp_name', get_lang('ThisFieldIsRequired'), 'required');

$form->addElement('hidden', 'post_time', time());
$form->addElement('hidden', 'action', 'add_lp');

$form->addElement('checkbox', 'enabletimelimit',get_lang('EnableTimeLimits'),null,'onclick = "  return timelimit() "');
    
$form->addElement('html','<div id="options2" style="display:none;">');

$form->addElement('datepicker', 'publicated_on', get_lang('PublicationDate'), array('form_name'=>'exercise_admin'), 5);
$form->addElement('datepicker', 'expired_on', get_lang('ExpirationDate'), array('form_name'=>'exercise_admin'), 5);

$form->addElement('html','</div>');
            
            
$defaults['publicated_on']  = date('Y-m-d 12:00:00');
$defaults['expired_on']     = date('Y-m-d 12:00:00',time()+84600);

$form->setDefaults($defaults);                  
$form->addElement('style_submit_button', 'Submit',get_lang('CreateLearningPath'),'class="save"');


$form->display();
// Footer
Display::display_footer();
