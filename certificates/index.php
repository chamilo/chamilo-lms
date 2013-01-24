<?php
/* For licensing terms, see /license.txt */
/**
 * Show specified user certificate
 * @package chamilo.certificate
 */

/**
 * Initialization
 */

$language_file= array('admin', 'gradebook', 'document');

require_once '../main/inc/global.inc.php';

$certificate = new Certificate($_GET['id']);

//Show certificate HTML
$certificate->show();