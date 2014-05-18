<?php
//----------------------------------------------------------------------
// CAS PLUGIN
//----------------------------------------------------------------------
// Copyright (c) 2006-2007 University Marc Bloch (UMB)
//----------------------------------------------------------------------
// This program is under the terms of the GENERAL PUBLIC LICENSE (GPL)
// as published by the FREE SOFTWARE FOUNDATION. The GPL is available
// through the world-wide-web at http://www.gnu.org/copyleft/gpl.html
//----------------------------------------------------------------------
// Authors: Pierre Cahard
//----------------------------------------------------------------------
// Load required
require('../../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH).'events.lib.inc.php');
require_once('authcas.php');
use \ChamiloSession as Session;
global $cas_auth_ver, $cas_auth_server, $cas_auth_port, $cas_auth_uri;
// phpCAS

/*
If we are not logged and in our browser enter an URL with a name of a course
e.g. http://www.chamilo.fr/chamilo/courses/COURSTESTOSETE/?id_session=0
We go to page api_not_allowed :
> You are not allowed to see this page.
> Sorry, you are not allowed to access this page, or maybe your connection has expired.
> Please click your browser's \"Back\" button or follow the link below to return to the previous page
If we click on the link to go to homepage, some datas are entered in $_SESSION and if we enter our CAS loggin, we go to api_not_allowad_page again
and again
As a result, if we are not logged on, we have to destroy the session variables, before calling CAS page
*/
if (api_is_anonymous()) {
    Session::destroy();
}

if (cas_configured()) {
    $firstpage = "";
    if (isset($_GET['firstpage'])) {
        $firstpage = $_GET['firstpage'];
        setcookie("GotoCourse", $firstpage);
    }
    if (!is_object($PHPCAS_CLIENT) ) {
        phpCAS::client($cas_auth_ver,$cas_auth_server,$cas_auth_port,$cas_auth_uri);
        phpCAS::setNoCasServerValidation();
    }
    phpCAS::forceAuthentication();
    header('Location: '.api_get_path(WEB_PATH).api_get_setting('page_after_login'));
} else {
    header('Location: '.api_get_path(WEB_PATH));
}
