<?php
/* For licensing terms, see /license.txt */

/**
 * Import a backup from moodle system.
 *
 * @author José Loguercio <jose.loguercio@beeznest.com>
 * @package chamilo.backup
 */

require_once '../inc/global.inc.php';
require_once '../inc/lib/MoodleImport.class.php';

$current_course_tool = TOOL_COURSE_MAINTENANCE;
api_protect_course_script(true);

// Check access rights (only teachers are allowed here)
if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

// Remove memory and time limits as much as possible as this might be a long process...
if (function_exists('ini_set')) {
    api_set_memory_limit('256M');
    ini_set('max_execution_time', 1800);
}

// Section for the tabs
$this_section = SECTION_COURSES;

// Breadcrumbs
$interbreadcrumb[] = array(
    'url' => '../course_info/maintenance.php',
    'name' => get_lang('Maintenance')
);

$form = new FormValidator('import_moodle');
$form->addFile('moodle_file', get_lang('MoodleFile'));
$form->addButtonImport(get_lang('Import'));

if ($form->validate()) {
    $file = $_FILES['moodle_file'];
    $moodleImport = new MoodleImport();
    $moodleImport->readMoodleFile($file);
}

$templateName = get_lang('ImportFromMoodle');

$template = new Template($templateName);
$infoMsg = Display::return_message(get_lang('ImportFromMoodleInstructions'));
$template->assign('info_msg', $infoMsg);
$template->assign('form', $form->returnForm());
$content = $template->fetch('default/coursecopy/import_moodle.tpl');

$template->assign('header', $templateName);
$template->assign('content', $content);

$template->display_one_col_template();