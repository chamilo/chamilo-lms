<?php
/**
 * This file can be used to quickly check and make sure the SOAP service you are developing works. In the future, it should be extended to be
 * a set of automatic tests
 */

ini_set('soap.wsdl_cache_enabled', 0);

require_once(dirname(__FILE__).'/../main/inc/global.inc.php');

$security_key = $_configuration['security_key'];
$ip_address = '::1';
$secret_key = sha1($ip_address.$security_key);

$client = new SoapClient($_configuration['root_web'].'main/webservices/registration.soap.php?wsdl');

$params = array('secret_key' => $secret_key, 'ids' => array(3));
$client->WSEnableUsers($params);

