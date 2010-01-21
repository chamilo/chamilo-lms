<?php
/* See license terms in /dokeos_license.txt */
/**
==============================================================================
* Updates the Dokeos files from version 1.8.6.2 to version 1.8.7
* This script operates only in the case of an update, and only to change the
* active version number (and other things that might need a change) in the
* current configuration file.
* @package dokeos.install
==============================================================================
*/
require_once("../inc/lib/main_api.lib.php");
require_once("../inc/lib/fileUpload.lib.php");
require_once('../inc/lib/database.lib.php');

if (defined('DOKEOS_INSTALL') || defined('DOKEOS_COURSE_UPDATE'))
{

}
else
{
	echo 'You are not allowed here !';
}
?>
