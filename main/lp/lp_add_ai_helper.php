<?php
/* For licensing terms, see /license.txt */

$this_section = SECTION_COURSES;
api_protect_course_script();

$interbreadcrumb[] = [
    'url' => 'lp_controller.php?action=list&'.api_get_cidreq(),
    'name' => get_lang('LearningPaths'),
];

Display::display_header(get_lang('LpAiGenerator'), 'Learnpath');

echo '<div class="actions">';
echo '<a href="lp_controller.php?'.api_get_cidreq().'">'.
    Display::return_icon(
        'back.png',
        get_lang('ReturnToLearningPaths'),
        '',
        ICON_SIZE_MEDIUM
    ).'</a>';
echo '</div>';

$aiHelper = new LpAiHelper();

$aiHelper->aiHelperForm();

Display::display_footer();
