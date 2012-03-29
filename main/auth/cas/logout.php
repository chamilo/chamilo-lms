<?php
    /* For licensing terms, see /license.txt */
    /*
    Call this file to disconnect from CAS session.
    logoutWithUrl() not used because with CAS v3 you cannot redirect your logout to a specific URL
    because of security reason.
    */
    require('../..//inc/global.inc.php');
    require_once (api_get_path(LIBRARY_PATH).'events.lib.inc.php');
    require_once('authcas.php');


    global $cas_auth_ver, $cas_auth_server, $cas_auth_port, $cas_auth_uri; 

	phpCAS::client($cas_auth_ver,$cas_auth_server,$cas_auth_port,$cas_auth_uri);
    phpCAS::logout();
    
?>