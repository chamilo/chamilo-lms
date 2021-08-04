<?php
/* See license terms in /license.txt */

/**
 * This is a script used to automatically import a list of users from
 * a CSV file into Dokeos.
 * It is triggered by a cron task configured on the server.
 *
 * @uses /main/webservices/user_import/
 *
 * @author Eric Marguin <eric.marguin@dokeos.com>
 *
 * @package chamilo.cron
 */
/**
 * Global cycle: init, execute, output.
 */
require_once __DIR__.'/../../inc/global.inc.php';
// check if this client has been called by php_cli (command line or cron)
if (php_sapi_name() != 'cli') {
    echo 'You can\'t call this service through a browser';
    exit();
}

// create client
$client = new nusoap_client(api_get_path(WEB_CODE_PATH).'cron/user_import/service.php');

// call import_user method
$response = $client->call(
    'import_users',
    [
        'filepath' => api_get_path(SYS_UPLOAD_PATH)."users_import.csv",
        'security_key' => api_get_configuration_value('security_key'),
    ]
);
echo $response;
