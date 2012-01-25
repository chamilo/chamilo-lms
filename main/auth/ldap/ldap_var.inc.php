<?php // $Id: ldap_var.inc.php 14966 2008-04-20 23:03:11Z yannoo $
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
*	@package chamilo.auth.ldap
*/
/**
 * Configuration settings
 */
// your ldap server
$ldap_host = api_get_setting('ldap_main_server_address');
// your ldap server's port number
$ldap_port = api_get_setting('ldap_main_server_port');
//domain
$ldap_basedn = api_get_setting('ldap_domain');

//search term for students
$ldap_search_dn = api_get_setting('ldap_search_string');

//additional server params for use of replica in case of problems
$ldap_host2 = api_get_setting('ldap_replicate_server_address');
$ldap_port2 = api_get_setting('ldap_replicate_server_port');

//protocol version - set to 3 for LDAP 3
$ldap_version = api_get_setting('ldap_version');

//non-anonymous LDAP mode
$ldap_rdn = api_get_setting('ldap_authentication_login');
$ldap_pass = api_get_setting('ldap_authentication_password');

$ldap_pass_placeholder = "PLACEHOLDER";
