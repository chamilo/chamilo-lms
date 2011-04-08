<?php
/* For licensing terms, see /license.txt */

/**
* Layout (principal view) used for structuring other views  
* @author Christian Fasanando <christian1827@gmail.com>
* @package chamilo.dashboard
*/

// protect script
api_block_anonymous_users();
$tool_name = get_lang('Dashboard');

// Header
Display :: display_header($tool_name);

// Display
echo $content;

// Footer
Display :: display_footer();