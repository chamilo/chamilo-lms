<?php
/* For licensing terms, see /license.txt */

/**
* Layout (principal view) used for structuring other views
* @author Christian Fasanando <christian1827@gmail.com> - Beeznest
* @package chamilo.auth
*/

// Header
Display::display_header('');

// Display
echo $content;

// Footer
Display::display_footer();
