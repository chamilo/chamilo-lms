<?php
/* For licensing terms, see /license.txt */

// name of the language file that needs to be included
$language_file = array('registration', 'tracking', 'exercice', 'admin');

$cidReset = true;

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'tracking.lib.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.lib.php';
      
$this_section = SECTION_TRACKING;
$nameTools = get_lang('MyProgress');

api_block_anonymous_users();

Display :: display_header($nameTools);

// Database table definitions
$tbl_course 				= Database :: get_main_table(TABLE_MAIN_COURSE);
$tbl_user 					= Database :: get_main_table(TABLE_MAIN_USER);
$tbl_session 				= Database :: get_main_table(TABLE_MAIN_SESSION);


echo Tracking::show_user_progress(api_get_user_id());
echo '<br /><br />';
echo Tracking::show_course_detail(api_get_user_id(), $_GET['course'], $_GET['session_id']);
    
Display :: display_footer();