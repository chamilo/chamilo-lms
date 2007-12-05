<?php # $Id: newUser.php 13925 2007-12-05 03:20:28Z yannoo $
/*
==============================================================================
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
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
	
	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
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

include_once('authldap.php');

$loginLdapSucces = loginWithLdap($login, $password);	

if ($loginLdapSucces)
{
	/*
		In here, we know that
		- the user does not exist in dokeos
		- the users login and password are correct
	*/
	$infoArray = findUserInfoInLdap($login);
	putUserInfoInDokeos ($login, $infoArray);
}
else
{
	$loginFailed = true;
	unset($_user['user_id']);
	$uidReset = false;
}
?>