<?php # $Id: newUser.php 14965 2008-04-20 23:01:17Z yannoo $
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
*	Users trying to login, who do not yet exist in the Dokeos database,
*	can be added by this script which tries to retrieve ldap information about
*   them.
*
*	@author Roan Embrechts
*	@package dokeos.auth.ldap
==============================================================================
*/

/*
==================================================
	when a user does not exist yet in dokeos,
	but he or she does exist in the LDAP,
	we add him to the dokeos database
==================================================
*/
//require_once('../../inc/global.inc.php'); - this script should be loaded by the /index.php script anyway, so global is already loaded
require_once('authldap.php');

//error_log('Trying to register new user '.$login.' with pass '.$password,0);

$ldap_login_success = ldap_login($login, $password);

if ($ldap_login_success)
{
	//error_log('Found user '.$login.' on LDAP server',0);
	/*
		In here, we know that
		- the user does not exist in dokeos
		- the users login and password are correct
	*/
	$info_array = ldap_find_user_info($login);
	ldap_put_user_info_locally($login, $info_array);
}
else
{
	//error_log('Could not find '.$login.' on LDAP server',0);
	$loginFailed = true;
	unset($_user['user_id']);
	$uidReset = false;
}
?>