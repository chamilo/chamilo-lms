<?php

/* For licensing terms, see /dokeos_license.txt */

/**
 * @package dokeos.glossary
 * @author Christian Fasanando, initial version
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium, refactoring and tighter integration in Dokeos
 */

// name of the language file that needs to be included
$language_file = array('userInfo');

// including the global dokeos file
require_once '../inc/global.inc.php';

// setting the tool constants
$tool = TOOL_ATTENDANCE;
// displaying the header
Display::display_header(get_lang(ucfirst($tool)));
// Tool introduction
Display::display_introduction_section(TOOL_NOTEBOOK);





// footer
Display :: display_footer();