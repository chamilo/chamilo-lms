<?php
/* For licensing terms, see /license.txt */

//Initialization
require_once('language.php');
require_once('../inc/global.inc.php');
require_once api_get_path(CONFIGURATION_PATH).'profile.conf.php';

//View
if (!isset($_GET['hide_headers']) || $_GET['hide_headers'] != 1) {
    Display::display_header('aaa');
}

echo $content['info'];

if (!isset($_GET['hide_headers']) || $_GET['hide_headers'] != 1) {
    Display::display_footer();
}
