<?php
/* For licensing terms, see /license.txt */

/**
 * Layout (principal view) used for structuring other views.
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 */

// protect a course script
api_protect_course_script(true);

// Header
Display::display_header('');

// Introduction section
Display::display_introduction_section(TOOL_COURSE_DESCRIPTION);

// Tracking
Event::event_access_tool(TOOL_COURSE_DESCRIPTION);

// Display
echo $content;

// Footer
Display::display_footer();
