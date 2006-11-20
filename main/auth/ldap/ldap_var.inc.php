<?php // $Id: ldap_var.inc.php 10060 2006-11-20 19:18:00Z pcool $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Roan Embrechts (Vrije Universiteit Brussel)

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
*	LDAP settings
*	In the older code, there was a distinction between
*	the teacher and student LDAP server. Later I decided not
*	to make this distinction. However, it could be built in
*	in the future but then perhaps in a more general way.
*
*	Originally, Thomas and I agreed to store all settings in one file
*	(configuration.php) to make it easier for claroline admins to make changes.
*	Since October 2003, this changed: the include directory has been
*	changed to be called "inc", and all tools should have their own file(s).
*
*	This file "ldap_var.inc.php" was already used by the
*	older french authentification functions. I have moved the new
*	variables from the configuration.php to here as well.
*
*	@author Roan Embrechts
*	@package dokeos.auth.ldap
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/

//parameters for LDAP module
$usesLDAP						=	TRUE;
$usesCurriculum					=	FALSE;
// your ldap server
$ldaphost = "adresse du serveur LDAP";
// your ldap server's port number
$ldapport = "port";
//domain
$ldapDc = "OU";

//older variables for French Univ. Jean Monet code

// Variable pour l'annuaire LDAP Enseignant
$LDAPserv = $ldaphost;
$LDAPport = $ldapport;
$LDAPbasedn = $ldapDc;

$critereRechercheEtu = "employeeType";

//ajout C2M pour utiliser replica en cas de problème
$LDAPserv2 = "adresse replica LDAP";
$LDAPport2 = "port replica";


?>