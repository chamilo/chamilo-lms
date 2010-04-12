<?php
/* For licensing terms, see /chamilo_license.txt */
/**
 * Responses to AJAX calls for install
 */

//require_once '../global.inc.php';

$action = $_GET['a'];

switch ($action) {
	case 'send_contact_information':
			if (!empty($_POST)) {                            
                            echo 1;
                        }
			break;	
	default:
		echo '';
}
exit;

?>
