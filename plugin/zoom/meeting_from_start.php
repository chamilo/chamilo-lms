<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/config.php';

api_protect_course_script(true);

$returnURL = 'start.php?cId='.api_get_course_id().'&sessionId='.api_get_session_id();

// the section (for the tabs)
$this_section = SECTION_COURSES;

include "meeting.php";
