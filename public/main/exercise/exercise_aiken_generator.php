<?php

/* For licensing terms, see /license.txt */

/**
 * AI-powered Aiken question generator.
 */

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$main_path = api_get_path(SYS_CODE_PATH);

require_once $main_path.'exercise/export/aiken/aiken_import.inc.php';
require_once $main_path.'exercise/export/aiken/aiken_classes.php';

// Section (for the tabs)
$this_section = SECTION_COURSES;

// Only teachers can access this page
if (!api_is_allowed_to_edit(null, true)) {
    api_not_allowed();
}

$interbreadcrumb[] = [
    'url' => 'exercise.php?'.api_get_cidreq(),
    'name' => get_lang('Exercises'),
];

if (isset($_REQUEST['submit_aiken_generated'])) {
    $id = aiken_import_exercise(null, $_REQUEST);
    if (is_numeric($id) && !empty($id)) {
        header('Location: admin.php?'.api_get_cidreq().'&exerciseId='.$id);
        exit;
    }
}

Display::display_header(get_lang('AI Aiken Generator'), 'Exercises');

// Generate Aiken form directly on this page
generateAikenForm();

Display::display_footer();
