<?php //$id: $
/**
 * This script allows compatibility between the New SCORM tool and both version 1.6.3
 * and 1.8 of Dokeos by loading libraries in a different way
 */
/**
 * Have a switch looking for the version. If 1.6, load first bit, if 1.8, load second
 */
//$my_version = $_GLOBALS['dokeosVersion'];
$my_version = '1.8';
switch($my_version){
	case '1.6.3':
	case '1.6.4':
	case '1.6.5':
		require('../inc/claro_init_global.inc.php');
		require_once(api_get_library_path() . '/events.lib.inc.php');
		require_once(api_get_library_path() . '/database.lib.php');
		require_once(api_get_library_path() . '/document.lib.php');
		require_once(api_get_library_path() . '/fileDisplay.lib.php');
		require_once(api_get_library_path() . '/fileUpload.lib.php'); //replace_dangerous_char()
		require_once(api_get_library_path() . '/fileManage.lib.php'); //check_name_exists()
		include_once(api_get_library_path() . '/pclzip/pclzip.lib.php');
		break;
	case '1.8':
	default:
		require('../inc/global.inc.php');
		require_once(api_get_path(LIBRARY_PATH) . 'events.lib.inc.php');
		require_once(api_get_path(LIBRARY_PATH) . 'database.lib.php');
		require_once(api_get_path(LIBRARY_PATH) . 'document.lib.php');
		require_once(api_get_path(LIBRARY_PATH) . 'fileDisplay.lib.php');
		require_once(api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php'); //replace_dangerous_char()
		require_once(api_get_path(LIBRARY_PATH) . 'fileManage.lib.php'); //check_name_exists()
		include_once (api_get_path(LIBRARY_PATH) . 'pclzip/pclzip.lib.php');
		break;
}
?>