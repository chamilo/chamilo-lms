<?php
/* For licensing terms, see /license.txt */
/**
 * Configures the WSUser SOAP service.
 *
 * @package chamilo.webservices
 */
require_once __DIR__.'/webservice_user.php';
require_once __DIR__.'/soap.php';

/**
 * Configures the WSUser SOAP service.
 *
 * @package chamilo.webservices
 */
$s = WSSoapServer::singleton();