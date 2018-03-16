<?php
/* For licensing terms, see /license.txt */
/**
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
 *
 *	@package chamilo.auth.ldap
 */
/**
 * Configuration settings.
 */
// your ldap server
$ldap_host = $extldap_config['host'][0];
// your ldap server's port number
$ldap_port = @$extldap_config['port'] ?: null;
//domain
$ldap_basedn = $extldap_config['base_dn'];

//search term for students
$ldap_search_dn = $extldap_config['user_search'];

//additional server params for use of replica in case of problems
$ldap_host2 = count($extldap_config['host']) > 1 ? $extldap_config['host'][1] : null;
$ldap_port2 = $extldap_config['port'];

//protocol version - set to 3 for LDAP 3
$ldap_version = $extldap_config['protocol_version'];

//non-anonymous LDAP mode
$ldap_rdn = $extldap_config['admin_dn'];
$ldap_pass = $extldap_config['admin_password'];

$ldap_pass_placeholder = "PLACEHOLDER";
