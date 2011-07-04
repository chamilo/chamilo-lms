<?php //$Id: $
/*
 * See license terms in /dokeos_license.txt
 * Copyright (c) 2008 Dokeos SPRL
 * Copyright (c) 2008 Eric Marguin <eric.marguin@dokeos.com>
 */
/**
 * This is a script used to automatically import a list of users from
 * a CSV file into Dokeos.
 * It is triggered by a cron task configured on the server
 * @uses /main/webservices/user_import/
 * @author Eric Marguin <eric.marguin@dokeos.com>
 * @package chamilo.cron
 */
/**
 * Global cycle: init, execute, output
 */
require_once dirname(__FILE__).'/../../inc/global.inc.php';
// check if this client has been called by php_cli (command line or cron)
if (php_sapi_name()!='cli') {
    echo 'You can\'t call this service through a browser';
    die();
}

// include nusoap library
require_once(api_get_path(LIBRARY_PATH).'nusoap/nusoap.php');

// create client
$client = new nusoap_client(api_get_path(WEB_CODE_PATH).'cron/user_import/service.php');

// call import_user method
$response = $client->call('import_users', array('filepath'	=> api_get_path(SYS_CODE_PATH)."upload/users_import.csv", 'security_key'=>$_configuration['security_key']));
echo $response;