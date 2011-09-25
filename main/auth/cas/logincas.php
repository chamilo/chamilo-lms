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
require('../..//inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH).'events.lib.inc.php');
require_once('authcas.php');
global $cas_auth_ver, $cas_auth_server, $cas_auth_port, $cas_auth_uri; 
// phpCAS
if (!is_object($PHPCAS_CLIENT) ) {
	phpCAS::client($cas_auth_ver,$cas_auth_server,$cas_auth_port,$cas_auth_uri);
	phpCAS::setNoCasServerValidation();
}
phpCAS::forceAuthentication();
//echo 'ici';
header('Location: '.api_get_path(WEB_PATH).api_get_setting('page_after_login'));
