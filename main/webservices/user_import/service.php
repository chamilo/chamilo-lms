<?php //$id: $
/**
 * See license terms in /dokeos_license.txt
 * @author Eric Marguin <eric.marguin@dokeos.com>
 */
require_once '../../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'nusoap/nusoap.php';

/**
 * Import users into database from a file located on the server.
 * Function registered as service.
 * @param  string The csv (only csv) file containing users tom import
 * @param  string Security key (as found in configuration file)
 * @return string Error message
 */
function import_users_from_file($filepath, $security_key) {

	global $_configuration;

   	$errors_returned = array(
	   	0 => 'success',
	   	1 => 'file import does not exist',
	   	2 => 'no users to import',
	   	3 => 'wrong datas in file',
	   	4 => 'security error'
   	);

   	// Check whether this script is launch by server and security key is ok.
   	if (empty($_SERVER['REMOTE_ADDR']) || $_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR'] || $security_key != $_configuration['security_key']) {
   		return $errors_returned[4];
   	}

   	// Libraries
   	require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
	require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
	require_once api_get_path(LIBRARY_PATH).'classmanager.lib.php';
	require_once api_get_path(LIBRARY_PATH).'import.lib.php';
	require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
	require_once 'import.lib.php';

	// Check is users file exists.
	if (!is_file($filepath)) {
		return $errors_returned[1];
	}

	// Get list of users
	$users = parse_csv_data($filepath);
	if (count($users) == 0) {
		return $errors_returned[2];
	}

	// Check the datas for each user
	$errors = validate_data($users);
	if (count($errors) > 0) {
		return $errors_returned[3];
	}

	// Apply modifications in database
	save_data($users);

   	return $errors_returned[0]; // Import successfull
}

$server = new soap_server();

$server->register('import_users_from_file');

$http_request = (isset($HTTP_RAW_POST_DATA)?$HTTP_RAW_POST_DATA:'');
$server->service($http_request);
