<?php
/* For licensing terms, see /dokeos_license.txt */

/**
* Layout (principal view) used for structuring other views  
* @package dokeos.course_description
* @author Christian Fasanando <christian1827@gmail.com>
*/

// Header
api_protect_course_script(true);
$nameTools = get_lang('CourseProgram');
Display :: display_header('');

// Constants and variables
$nameTools = get_lang(TOOL_COURSE_DESCRIPTION);

// Introduction section
Display::display_introduction_section(TOOL_COURSE_DESCRIPTION);

// Tracking
event_access_tool(TOOL_COURSE_DESCRIPTION);

// Display
echo $content;

// Footer
Display :: display_footer();
?>