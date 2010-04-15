<?php // $Id: $
/* For licensing terms, see /license.txt */
$language_file= 'gradebook';

require_once '../inc/global.inc.php';
api_block_anonymous_users();

$this_section = SECTION_COURSES;
Display :: display_header(get_lang('ScoreEdit'));

echo '<div class="maincontent">';
Display::display_normal_message(sprintf(get_lang('GradebookScoringSystemRedirect'), api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Gradebook'), false);
echo '</div>';
Display :: display_footer();
