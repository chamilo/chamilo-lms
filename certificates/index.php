<?php
/* For licensing terms, see /license.txt */
/**
 * Show specified user certificate
 * @package chamilo.certificate
 */

/**
 * Initialization
 * @todo replace me with a controller
 */

$language_file= array('admin', 'gradebook', 'document');

require_once '../main/inc/global.inc.php';

$certificate = new Certificate($_GET['id']);

//Show certificate HTML
$certificate->show();