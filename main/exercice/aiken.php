<?php
/* For licensing terms, see /license.txt */
/**
* Code for Aiken import integration.
* @package chamilo.exercise
* @author Ronny Velasquez <ronny.velasquez@beeznest.com>
* @author César Perales <cesar.perales@gmail.com> Updated function names and import files for Aiken format support
*/
/**
 * Code
 */
// name of the language file that needs to be included
$language_file = array('exercice', 'gradebook');

// including the global Chamilo file
require_once '../inc/global.inc.php';
$lib_path = api_get_path(LIBRARY_PATH);
$main_path = api_get_path(SYS_CODE_PATH);
require_once $lib_path.'document.lib.php';
require_once $lib_path.'fileUpload.lib.php';
require_once $lib_path.'fileManage.lib.php';

// including additional libraries
require_once 'exercise.class.php';
require_once 'question.class.php';
require_once 'answer.class.php';
require_once 'unique_answer.class.php';
require_once $main_path.'exercice/export/aiken/aiken_import.inc.php';
require_once $main_path.'exercice/export/aiken/aiken_classes.php';

// section (for the tabs)
$this_section = SECTION_COURSES;

// access restriction: only teachers are allowed here
if (!api_is_allowed_to_edit(null, true)) {
    api_not_allowed();
}

// the breadcrumbs
$interbreadcrumb[]= array ("url"=>"exercice.php", "name"=> get_lang('Exercices'));
$is_allowedToEdit = api_is_allowed_to_edit(null, true);

// display header
Display::display_header(get_lang('ImportAikenQuiz'), 'Exercises');

$msg = '';
// import file
if ((api_is_allowed_to_edit(null, true))) {
    if (isset($_POST['submit'])) {
        $msg = aiken_import_file($_FILES['userFile']);
    }
}

// display Aiken form
aiken_display_form($msg);

// display the footer
Display::display_footer();
