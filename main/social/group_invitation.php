<?php
/* For licensing terms, see /dokeos_license.txt */

$language_file = array('admin');
require '../inc/global.inc.php';
require_once api_get_path(CONFIGURATION_PATH).'profile.conf.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'image.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';
Display :: display_header($tool_name, "User");

$group_id	= intval($_GET['id']);
	
Display :: display_footer();
?>