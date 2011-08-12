<?php
/* For licensing terms, see /license.txt */
/**
 * Show specified user certificate
 * @package chamilo.certificate
 */

/**
 * Initialization
 */

$language_file= 'gradebook';

require_once '../main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'certificate.lib.php';

/**
 * Retrieve certificate
 */
$certificate = new Certificate($_GET['id']);

/**
 * Show results
 */
$certificate->show();