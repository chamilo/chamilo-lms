<?php // $Id: authldap.php 13366 2007-10-01 01:52:09Z yannoo $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Universite Jean Monnet de Saint Etienne
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
=======================================================================
*	LDAP module functions
*
*	If the application uses LDAP, these functions are used
*	for logging in, searching user info, adding this info
*	to the Dokeos database...
=======================================================================
	- function loginWithLdap($login, $password)
	- function findUserInfoInLdap ($login)
	- function putUserInfoInDokeos ($login, $infoArray)

	known bugs
	----------
	- (fixed 18 june 2003) code has been internationalized
	- (fixed 07/05/2003) fixed some non-relative urls or includes
	- (fixed 28/04/2003) we now use global config.inc variables instead of local ones
	- (fixed 22/04/2003) the last name of a user was restricted to the first part
	- (fixed 11/04/2003) the user was never registered as a course manager

	version history
	---------------
	3.0	- updated to use ldap_var.inc.php instead of ldap_var.inc (deprecated)
		(November 2003)
	2.9	- further changes for new login procedure
		- (busy) translating french functions to english
		(October 2003)
	2.8	- adapted for new Claroline login procedure
		- ldap package now becomes a standard, in auth/ldap
	2.7 - uses more standard LDAP field names: mail, sn, givenname
			instead of mail, preferredsn, preferredgivenname
			there are still
		- code cleanup
		- fixed bug: dc = xx, dc = yy was configured for UGent
			and put literally in the code, this is now a variable
			in configuration.php ($ldapDc)

	with thanks to
	- Stefan De Wannemacker (Ghent University)
	- Université Jean Monet (J Dubois / Michel Courbon)
	- Michel Panckoucke for reporting and fixing a bug
	- Patrick Cool: fixing security hole

	*	@author Roan Embrechts
	*	@version 3.0
	*	@package dokeos.auth.ldap
=======================================================================
*/

include ('ldap_var.inc.php');

/**
===============================================================
	function
	CHECK LOGIN & PASSWORD WITH LDAP
*	@return true when login & password both OK, false otherwise
===============================================================
*	@author Roan Embrechts (based on code from Université Jean Monet)
*/
//include_once("$includePath/../connect/authldap.php");

function loginWithLdap($login, $password)
{
	$res = Authentif($login, $password);

	// res=-1 -> the user does not exist in the ldap database
	// res=1 -> invalid password (user does exist)

	if ($res==1) //WRONG PASSWORD
	{
		//$errorMessage = "LDAP Username or password incorrect, please try again.<br>";
		if (isset($log)) unset($log); if (isset($uid)) unset($uid);
		$loginLdapSucces = false;
	}
	if ($res==-1) //WRONG USERNAME
	{
		//$errorMessage =  "LDAP Username or password incorrect, please try again.<br>";
		$loginLdapSucces = false;
	}
	if ($res==0) //LOGIN & PASSWORD OK - SUCCES
	{
		//$errorMessage = "Successful login w/ LDAP.<br>";
		$loginLdapSucces = true;
	}

	//$result = "This is the result: $errorMessage";
	$result = $loginLdapSucces;
	return $result;
}


/**
===============================================================
	function
	FIND USER INFO IN LDAP
*	@return an array with positions "firstname", "name", "email", "employeenumber"
===============================================================
*	@author Stefan De Wannemacker
*	@author Roan Embrechts
*/
function findUserInfoInLdap ($login)
{
	global $ldaphost, $ldapport, $ldapDc;
	// basic sequence with LDAP is connect, bind, search,
	// interpret search result, close connection

	// using ldap bind
	$ldaprdn  = 'uname';     // ldap rdn or dn
	$ldappass = 'password';  // associated password

	//echo "<h3>LDAP query</h3>";
	//echo "Connecting ...";
	$ldapconnect = ldap_connect( $ldaphost, $ldapport);
	LDAPSetVersion($ldapconnect);
	if ($ldapconnect) {
	    	//echo " Connect to LDAP server successful ";
	    	//echo "Binding ...";

			// this is an "anonymous" bind, typically read-only access:
	    	$ldapbind = ldap_bind($ldapconnect);

	    	if ($ldapbind)
			{
	  	  	//echo " LDAP bind successful... ";
	    	  	//echo " Searching for uid... ";
	    		// Search surname entry
	    		//OLD: $sr=ldap_search($ldapconnect,"dc=rug, dc=ac, dc=be", "uid=$login");
				//echo "<p> ldapDc = '$ldapDc' </p>";
	    		$sr=ldap_search($ldapconnect, $ldapDc, "uid=$login");

				//echo " Search result is ".$sr;
	    		//echo " Number of entries returned is ".ldap_count_entries($ldapconnect,$sr);

	    		//echo " Getting entries ...";
	    		$info = ldap_get_entries($ldapconnect, $sr);
	    		//echo "Data for ".$info["count"]." items returned:<p>";

	    	}
		else
		{
			//echo "LDAP bind failed...";
	    }
    	//echo "Closing LDAP connection<hr>";
    	ldap_close($ldapconnect);
	}
	else
	{
		//echo "<h3>Unable to connect to LDAP server</h3>";
	}

	//DEBUG: $result["firstname"] = "Jan"; $result["name"] = "De Test"; $result["email"] = "email@ugent.be";
	$result["firstname"] = $info[0]["givenname"][0];
	$result["name"] = $info[0]["sn"][0];
	$result["email"] = $info[0]["mail"][0];
	$result["employeenumber"] = $info[0]["employeenumber"][0];

	return $result;
}


/**
===============================================================
*	function
*	PUT USER INFO IN CLAROLINE
*	this function uses the data from findUserInfoInLdap()
*	to add the userdata to Claroline
*
*	the "rugid" field is specifically for the Ghent University.
*
*	"firstname", "name", "email", "isEmployee"
===============================================================
*	@author Roan Embrechts
*/
function putUserInfoInDokeos ($login, $infoArray)
{
	global $_POST;
	global $PLACEHOLDER;
	global $submitRegistration, $submit, $uname, $email,
			$nom, $prenom, $password, $password1, $status;
	global $includePath, $platformLanguage;
	global $loginFailed, $uidReset, $_user;

	/*----------------------------------------------------------
		1. set the necessary variables
	------------------------------------------------------------ */

	$uname      = $login;
	$email      = $infoArray["email"];
	$nom        = $infoArray["name"];
	$prenom     = $infoArray["firstname"];
	$password   = $PLACEHOLDER;
	$password1  = $PLACEHOLDER;

	define ("STUDENT",5);
	define ("COURSEMANAGER",1);

	if (empty($infoArray["employeenumber"]))
	{
		$status = STUDENT;
	}
	else
	{
		$status = COURSEMANAGER;
	}

	//$official_code = xxx; //example: choose an attribute

	/*----------------------------------------------------------
		2. add info to Dokeos
	------------------------------------------------------------ */


	include_once("$includePath/lib/usermanager.lib.php");



	$_userId = UserManager::create_user($prenom, $nom, $status,
					 $email, $uname, $password, $official_code,
					 'english','', '', 'ldap');

	//echo "new user added to claroline, id = $_userId";

	//user_id, username, password, auth_source

	/*----------------------------------------------------------
		3. register session
	------------------------------------------------------------ */

	$uData['user_id'] = $_userId;
	$uData['username'] = $uname;
	$uData['auth_source'] = "ldap";

	$loginFailed = false;
	$uidReset = true;
	$_user['user_id'] = $uData['user_id'];
	api_session_register('_uid');
}

/* >>>>>>>>>>>>>>>> end of UGent LDAP routines <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<< */

/* >>>>> Older but necessary code of Université Jean-Monet <<<<< */

/*
===========================================================
	The code of UGent uses these functions to authenticate.
	* function AuthVerifEnseignant ($uname, $passwd)
	* function AuthVerifEtudiant ($uname, $passwd)
	* function Authentif ($uname, $passwd)
===========================================================
	To Do
	* translate the comments and code to english
	* let these functions use the variables in config.inc instead of ldap_var.inc
*/

//*** variables en entrée
// $uname : username entré au clavier
// $passwd : password fournit par l'utilisateur

//*** en sortie : 3 valeurs possibles
// 0 -> authentif réussie
// 1 -> password incorrect
// -1 -> ne fait partie du LDAP

//---------------------------------------------------
// verification de l'existence du membre dans le LDAP
function AuthVerif ($uname, $passwd)
{
	global $LDAPserv, $LDAPport, $LDAPbasedn, $LDAPserv2, $LDAPport2;
	// Establish anonymous connection with LDAP server
	// Etablissement de la connexion anonyme avec le serveur LDAP
	$ds=ldap_connect($LDAPserv,$LDAPport);
	LDAPSetVersion($ds);
	$TestBind=ldap_bind($ds);
   	//en cas de probleme on utlise le replica
   	if(!$TestBind){
    	$ds=ldap_connect($LDAPserv2,$LDAPport2);
    	LDAPSetVersion($ds);
   	}
 	if ($ds) {
		// Creation du filtre contenant les valeurs saisies par l'utilisateur
	    $filter="(uid=$uname)";
		// Open anonymous LDAP connection
		// Ouverture de la connection anonyme ldap
	    $result=ldap_bind($ds);
		// Execution de la recherche avec $filtre en parametre
		$sr=ldap_search($ds,"$LDAPbasedn", "$filter");
		// La variable $info recoit le resultat de la requete
		$info = ldap_get_entries($ds, $sr);
		$dn=($info[0]["dn"]);
		//affichage debug !!	echo"<br> dn = $dn<br> pass = $passwd<br>";
		// fermeture de la 1ere connexion
		ldap_close($ds);
	}

	// teste le Distinguish Name de la 1ere connection
  	if ($dn==""){
		 return (-1);		// ne fait pas partie de l'annuaire
	}
	//bug ldap.. si password vide.. retourne vrai !!
	if ($passwd=="") {
		return(1);
	}
	// Ouverture de la 2em connection Ldap : connexion user pour verif mot de passe
	$ds=ldap_connect($LDAPserv,$LDAPport);
	LDAPSetVersion($ds);
	if(!$TestBind){
    	$ds=ldap_connect($LDAPserv2,$LDAPport2);
    	LDAPSetVersion($ds);
   	}
	// retour en cas d'erreur de connexion password incorrecte
 	if (!(@ldap_bind( $ds, $dn , $passwd)) == true) {
		return (1); // mot passe invalide
	}
	// connection correcte
	else
	{
		return (0);
	}
} // fin de la verif

//-------------------------------------------------------
//  authentification

function Authentif ($uname, $passwd)
{
    $res=AuthVerif($uname,$passwd);
    return($res); // fait partie du LDAP enseignant
} // fin Authentif

/**
 * Set the protocol version with version from config file (enables LDAP version 3)
 */
function LDAPSetVersion (&$resource)
{
	global $LDAPversion;
	if($LDAPversion>2)
	{
		if(ldap_set_option($resource, LDAP_OPT_PROTOCOL_VERSION, 3))
		{
			//ok - don't do anything
		}
		else
		{
			//failure - should switch back to version 2 by default
		}
	}
}
?>