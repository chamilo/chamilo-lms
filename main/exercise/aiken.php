<?php

/* For licensing terms, see /license.txt */

/**
 * Code for Aiken import integration.
 *
 * @author Ronny Velasquez <ronny.velasquez@beeznest.com>
 * @author César Perales <cesar.perales@gmail.com> Updated function names and import files for Aiken format support
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$lib_path = api_get_path(LIBRARY_PATH);
$main_path = api_get_path(SYS_CODE_PATH);

require_once $main_path.'exercise/export/aiken/aiken_import.inc.php';
require_once $main_path.'exercise/export/aiken/aiken_classes.php';

// section (for the tabs)
$this_section = SECTION_COURSES;

// access restriction: only teachers are allowed here
if (!api_is_allowed_to_edit(null, true)) {
    api_not_allowed();
}

$interbreadcrumb[] = [
    'url' => 'exercise.php?'.api_get_cidreq(),
    'name' => get_lang('Exercises'),
];
$is_allowedToEdit = api_is_allowed_to_edit(null, true);

if ((api_is_allowed_to_edit(null, true))) {
    if (isset($_POST['submit'])) {
        $id = aiken_import_file($_FILES['userFile']);
        if (is_numeric($id) && !empty($id)) {
            header('Location: admin.php?'.api_get_cidreq().'&exerciseId='.$id);
            exit;
        }
    }
    if (isset($_REQUEST['submit_aiken_generated'])) {
        $id = aikenImportExercise(null, $_REQUEST);
        if (is_numeric($id) && !empty($id)) {
            header('Location: admin.php?'.api_get_cidreq().'&exerciseId='.$id);
            exit;
        }
    }
}

Display::display_header(get_lang('ImportAikenQuiz'), 'Exercises');
aiken_display_form();
generateAikenForm();
Display::display_footer();
