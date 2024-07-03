<?php
/* For licensing terms, see /license.txt */

/**
 * Layout (principal view) used for structuring other views.
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 *
 * @package chamilo.attendace
 */

// protect a course script
api_protect_course_script(true);

// Header
$tool = TOOL_ATTENDANCE;

$func = isset($_REQUEST['func']) ? $_REQUEST['func'] : null;
if ('fullscreen' === $func) {
    $htmlHeadXtra[] = api_get_css_asset('bootstrap/dist/css/bootstrap.min.css');
    Display::display_reduced_header();
} else {
    Display::display_header('');
}

// Introduction section
Display::display_introduction_section($tool);

// Tracking
Event::event_access_tool($tool);

// Display
echo $content;

// Footer
if ('fullscreen' === $func) {
    Display::display_reduced_footer();
} else {
    Display::display_footer();
}
