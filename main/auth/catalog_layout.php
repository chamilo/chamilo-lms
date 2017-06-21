<?php
/* For licensing terms, see /license.txt */
/**
 * Layout (principal view) used for structuring course/session catalog
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.auth
 */

if (api_get_setting('course_catalog_published') !== 'true') {
    // Access rights: anonymous users can't do anything usefull here.
    api_block_anonymous_users();
}

// Header
Display::display_header('');

// Display
echo $content;

// Footer
Display::display_footer();
