<?php
/* For licensing terms, see /license.txt */

$this_section = SECTION_COURSES;
api_protect_course_script();

$mainPath = api_get_path(SYS_CODE_PATH);

require_once $mainPath.'exercise/export/aiken/aiken_import.inc.php';
require_once $mainPath.'exercise/export/aiken/aiken_classes.php';

$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('LearningPaths'),
];

Display::display_header(get_lang('LpAiGenerator'), 'Learnpath');

echo '<div class="actions">';
echo '<a href="lp_controller.php?'.api_get_cidreq().'">'.
    Display::getMdiIcon('arrow-left-bold-box', 'ch-tool-icon', '', 32, get_lang('Return to Learningpaths')).'</a>';
echo '</div>';

$aiHelper = new LpAiHelper();

$aiHelper->aiHelperForm();

Display::display_footer();

