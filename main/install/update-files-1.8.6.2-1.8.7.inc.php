<?php
/* For licensing terms, see /license.txt */
/**
==============================================================================
* Chamilo LMS
*
* Updates the Chamilo files from version 1.8.6.2 to version 1.8.7
* This script operates only in the case of an update, and only to change the
* active version number (and other things that might need a change) in the
* current configuration file.
* @package chamilo.install
==============================================================================
*/

require_once '../inc/lib/main_api.lib.php';
require_once '../inc/lib/fileUpload.lib.php';
require_once '../inc/lib/database.lib.php';

if (defined('SYSTEM_INSTALLATION') || defined('DOKEOS_COURSE_UPDATE')) {

	// Start coding here...

} else {

	echo 'You are not allowed here !';

}
