<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.main
 */

//Temporal hack to redirect calls to the new web/index.php
header('Location: web/index');
exit;

define('CHAMILO_HOMEPAGE', true);

$language_file = array('courses', 'index');

// Flag forcing the 'current course' reset, as we're not inside a course anymore.
// Maybe we should change this into an api function? an example: CourseManager::unset();
$cidReset = true;

$app = require_once 'main/inc/global.inc.php';
require_once 'main/chat/chat_functions.lib.php';

// The section (for the tabs).
$this_section = SECTION_CAMPUS;



