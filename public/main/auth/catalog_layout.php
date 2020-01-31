<?php
/* For licensing terms, see /license.txt */
/**
 * Layout (principal view) used for structuring course/session catalog.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
if ('true' !== api_get_setting('course_catalog_published')) {
    // Access rights: anonymous users can't do anything usefull here.
    api_block_anonymous_users();
}

// Header
Display::display_header('');

// Display
echo $content;

// Footer
Display::display_footer();
