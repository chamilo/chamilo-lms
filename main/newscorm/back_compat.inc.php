<?php //$id: $
/**
 * This script used to allow compatibility between the New SCORM tool and both 
 * version 1.6.3 and 1.8 of Dokeos by loading libraries in a different way.
 * The switch is now deprecated and this file will be renamed later on to
 * something like lp_includes.inc.php
 * @package dokeos.learnpath
 */
require_once('../inc/global.inc.php');
require_once(api_get_path(LIBRARY_PATH) . 'database.lib.php');
require_once(api_get_path(LIBRARY_PATH) . 'document.lib.php');
require_once(api_get_path(LIBRARY_PATH) . 'fileDisplay.lib.php');
require_once(api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php'); //replace_dangerous_char()
require_once(api_get_path(LIBRARY_PATH) . 'fileManage.lib.php'); //check_name_exists()
require_once(api_get_path(LIBRARY_PATH) . 'pclzip/pclzip.lib.php');
?>