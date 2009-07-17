<?php // $Id: login.php 22201 2009-07-17 19:57:03Z cfasanando $
/*
==============================================================================
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Roan Embrechts
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	Users trying to login, who already exist in the Dokeos database 
*	and have ldap as authentication type, get verified here.
*
*	@author Roan Embrechts
*	@package dokeos.auth.ldap
==============================================================================
*/

	/*
		An external authentification module
		needs to set
		- $loginFailed
		- $uidReset
		- $_user['user_id']
		- register the $_user['user_id'] in the session
		As the LDAP code shows, this is not as difficult as you might think.
	*/
	/*
	===============================================
		LDAP authentification module
		this calls the loginWithLdap function
		from the LDAP library, and sets a few 
		variables based on the result.
	===============================================
	*/
//require_once('../../inc/global.inc.php'); - this script should be loaded by the /index.php script anyway, so global is already loaded
require_once('authldap.php');

$loginLdapSucces = ldap_login($login, $password);	

if ($loginLdapSucces)
{
	$loginFailed = false;
	$uidReset = true;
	$_user['user_id'] = $uData['user_id'];
	api_session_register('_uid');
    // Jand: copied from event_login in events.lib.php to enable login statistics:    
    event_login(); 
}
else
{
	$loginFailed = true;
	unset($_user['user_id']);
	$uidReset = false;
}
?>