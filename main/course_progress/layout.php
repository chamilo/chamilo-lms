<?php
/* For licensing terms, see /license.txt */

/**
* Layout (principal view) used for structuring other views  
* @author Christian Fasanando <christian1827@gmail.com>
* @package chamilo.course_progress
*/

// protect a course script
api_protect_course_script(true);

// Header
$tool = TOOL_COURSE_PROGRESS;
Display :: display_header('');

// Introduction section
Display::display_introduction_section($tool);

// Tracking
event_access_tool($tool);

// Display
echo $content;

// Footer
Display :: display_footer();