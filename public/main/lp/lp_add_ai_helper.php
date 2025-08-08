<?php
/* For licensing terms, see /license.txt */

$this_section = SECTION_COURSES;
api_protect_course_script();

$mainPath = api_get_path(SYS_CODE_PATH);

require_once $mainPath.'exercise/export/aiken/aiken_import.inc.php';
require_once $mainPath.'exercise/export/aiken/aiken_classes.php';

$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('Learning paths'),
];

Display::display_header(get_lang('AI generator'), 'Learnpath');

echo '<div class="actions">';
echo '<a href="lp_controller.php?'.api_get_cidreq().'">'.
    Display::getMdiIcon('arrow-left-bold-box', 'ch-tool-icon', '', 32, get_lang('Back to learning paths')).'</a>';
echo '</div>';

$aiHelper = new LpAiHelper();

$aiHelper->aiHelperForm();

Display::display_footer();

