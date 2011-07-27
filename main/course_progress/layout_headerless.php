<?php
/* For licensing terms, see /license.txt */

// protect a course script
api_protect_course_script(true);

Display :: display_reduced_header();

// Tracking
event_access_tool($tool);


// Display
echo $content;
