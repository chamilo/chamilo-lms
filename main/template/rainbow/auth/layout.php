<?php
/* For licensing terms, see /license.txt */

/**
* Layout (principal view) used for structuring other views
* @author Christian Fasanando <christian1827@gmail.com> - Beeznest
* @package chamilo.auth
*/

// Access rights: anonymous users can't do anything useful here.
api_block_anonymous_users();

// Header
Display::display_header('');

// Display
echo $content;

// Footer
Display::display_footer();
