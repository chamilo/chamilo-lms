<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/config.php';

api_protect_admin_script();

$returnURL = 'user.php';

// the section (for the tabs)
$this_section = SECTION_MYPROFILE;

include "meeting.php";
