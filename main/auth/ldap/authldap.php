<?php // $Id: authldap.php 20443 2009-05-10 08:41:46Z ivantcholakov $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos SPRL
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

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
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
	- function ldap_authentication_check()
	- function ldap_find_user_info()
	- function ldap_login()
	- function ldap_put_user_info_locally()
	- ldap_set_version()

	known bugs
	----------
	- (fixed 18 june 2003) code has been internationalized
	- (fixed 07/05/2003) fixed some non-relative urls or includes
	- (fixed 28/04/2003) we now use global config.inc variables instead of local ones
	- (fixed 22/04/2003) the last name of a user was restricted to the first part
	- (fixed 11/04/2003) the user was never registered as a course manager

	version history
	---------------
	3.2 - updated to allow for specific term search for teachers identification
	3.1 - updated code to use database settings, to respect coding conventions as much as possible (camel-case removed) and to allow for non-anonymous login
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
			in configuration.php ($LDAPbasedn)

	with thanks to
	- Stefan De Wannemacker (Ghent University)
	- Universite Jean Monet (J Dubois / Michel Courbon)
	- Michel Panckoucke for reporting and fixing a bug
	- Patrick Cool: fixing security hole

	*	@author Roan Embrechts
	*	@version 3.0
	*	@package dokeos.auth.ldap
=======================================================================
*/

require('ldap_var.inc.php');

/**
===============================================================
	function
	CHECK LOGIN & PASSWORD WITH LDAP
*	@return true when login & password both OK, false otherwise
===============================================================
*	@author Roan Embrechts (based on code from Universit� Jean Monet)
*/
//require_once(api_get_path(INCLUDE_PATH).'../connect/authldap.php');

function ldap_login($login, $password)
{
	//error_log('Entering ldap_login('.$login.','.$password.')',0);
	$res = ldap_authentication_check($login, $password);

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
		$login_ldap_success = false;
	}
	if ($res==0) //LOGIN & PASSWORD OK - SUCCES
	{
		//$errorMessage = "Successful login w/ LDAP.<br>";
		$login_ldap_success = true;
	}

	//$result = "This is the result: $errorMessage";
	$result = $login_ldap_success;
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
function ldap_find_user_info ($login)
{
	//error_log('Entering ldap_find_user_info('.$login.')',0);
	global $ldap_host, $ldap_port, $ldap_basedn, $ldap_rdn, $ldap_pass, $ldap_search_dn;
	// basic sequence with LDAP is connect, bind, search,
	// interpret search result, close connection

	//echo "Connecting ...";
	$ldap_connect = ldap_connect( $ldap_host, $ldap_port);
	ldap_set_version($ldap_connect);
	if ($ldap_connect) {
	    	//echo " Connect to LDAP server successful ";
	    	//echo "Binding ...";
			$ldap_bind = false;
			$ldap_bind_res = ldap_handle_bind($ldap_connect,$ldap_bind);
	    	if ($ldap_bind_res)
			{
	  	  	//echo " LDAP bind successful... ";
	    	  	//echo " Searching for uid... ";
	    		// Search surname entry
	    		//OLD: $sr=ldap_search($ldapconnect,"dc=rug, dc=ac, dc=be", "uid=$login");
				//echo "<p> ldapDc = '$LDAPbasedn' </p>";
				if(!empty($ldap_search_dn))
				{
	    			$sr=ldap_search($ldap_connect, $ldap_search_dn, "uid=$login");
				}
				else
				{
	    			$sr=ldap_search($ldap_connect, $ldap_basedn, "uid=$login");
				}

				//echo " Search result is ".$sr;
	    		//echo " Number of entries returned is ".ldap_count_entries($ldapconnect,$sr);

	    		//echo " Getting entries ...";
	    		$info = ldap_get_entries($ldap_connect, $sr);
	    		//echo "Data for ".$info["count"]." items returned:<p>";

	    	}
		else
		{
			//echo "LDAP bind failed...";
	    }
    	//echo "Closing LDAP connection<hr>";
    	ldap_close($ldap_connect);
	}
	else
	{
		//echo "<h3>Unable to connect to LDAP server</h3>";
	}

	//DEBUG: $result["firstname"] = "Jan"; $result["name"] = "De Test"; $result["email"] = "email@ugent.be";
	$result["firstname"] = $info[0]["givenname"][0];
	$result["name"] = $info[0]["sn"][0];
	$result["email"] = $info[0]["mail"][0];
	$tutor_field = api_get_setting('ldap_filled_tutor_field');
	$result[$tutor_field] = $info[0][$tutor_field]; //employeenumber by default

	return $result;
}


/**
*	This function uses the data from ldap_find_user_info()
*	to add the userdata to Dokeos
*
*	"firstname", "name", "email", "isEmployee"
===============================================================
*	@author Roan Embrechts
*/
function ldap_put_user_info_locally($login, $info_array)
{
	//error_log('Entering ldap_put_user_info_locally('.$login.',info_array)',0);
	global $ldap_pass_placeholder;
	global $submitRegistration, $submit, $uname, $email,
			$nom, $prenom, $password, $password1, $status;
	global $platformLanguage;
	global $loginFailed, $uidReset, $_user;

	/*----------------------------------------------------------
		1. set the necessary variables
	------------------------------------------------------------ */

	$uname      = $login;
	$email      = $info_array["email"];
	$nom        = $info_array["name"];
	$prenom     = $info_array["firstname"];
	$password   = $ldap_pass_placeholder;
	$password1  = $ldap_pass_placeholder;
	$official_code = '';

	define ("STUDENT",5);
	define ("COURSEMANAGER",1);

	$tutor_field = api_get_setting('ldap_filled_tutor_field');
	$tutor_value = api_get_setting('ldap_filled_tutor_field_value');
	if(empty($tutor_field))
	{
		$status = STUDENT;
	}
	else
	{
		if(empty($tutor_value))
		{
			//in this case, we are assuming that the admin didn't give a criteria
			// so that if the field is not empty, it is a tutor
			if(!empty($info_array[$tutor_field]))
			{
				$status = COURSEMANAGER;
			}
			else
			{
				$status = STUDENT;
			}
		}
		else
		{
			//the tutor_value is filled, so we need to check the contents of the LDAP field
			if (is_array($info_array[$tutor_field]) && in_array($tutor_value,$info_array[$tutor_field]))
			{
				$status = COURSEMANAGER;
			}
			else
			{
				$status = STUDENT;
			}
		}
	}
	//$official_code = xxx; //example: choose an attribute

	/*----------------------------------------------------------
		2. add info to Dokeos
	------------------------------------------------------------ */

	require_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');
	$_userId = UserManager::create_user($prenom, $nom, $status,
					 $email, $uname, $password, $official_code,
					 'english','', '', 'ldap');

	//echo "new user added to Dokeos, id = $_userId";

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

/* >>>>> Older but necessary code of Universite Jean-Monet <<<<< */

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

//*** variables en entree
// $uname : username entre au clavier
// $passwd : password fournit par l'utilisateur

//*** en sortie : 3 valeurs possibles
// 0 -> authentif reussie
// 1 -> password incorrect
// -1 -> ne fait partie du LDAP

//---------------------------------------------------
// verification de l'existence du membre dans le LDAP
function ldap_authentication_check ($uname, $passwd)
{
	//error_log('Entering ldap_authentication_check('.$uname.','.$passwd.')',0);
	global $ldap_host, $ldap_port, $ldap_basedn, $ldap_host2, $ldap_port2,$ldap_rdn,$ldap_pass;
	//error_log('Entering ldap_authentication_check('.$uname.','.$passwd.')',0);
	// Establish anonymous connection with LDAP server
	// Etablissement de la connexion anonyme avec le serveur LDAP
	$ds=ldap_connect($ldap_host,$ldap_port);
	ldap_set_version($ds);

	$test_bind = false;
	$test_bind_res = ldap_handle_bind($ds,$test_bind);
   	//en cas de probleme on utlise le replica
   	if($test_bind_res===false){
    	$ds=ldap_connect($ldap_host2,$ldap_port2);
    	ldap_set_version($ds);
   	}
   	else
   	{
   		//error_log('Connected to server '.$ldap_host);
   	}
 	if ($ds!==false) {
		// Creation du filtre contenant les valeurs saisies par l'utilisateur
	    $filter="(uid=$uname)";
		// Open anonymous LDAP connection
		// Ouverture de la connection anonyme ldap
	    $result=false;
		$ldap_bind_res = ldap_handle_bind($ds,$result);
		// Execution de la recherche avec $filtre en parametre
		//error_log('Searching for '.$filter.' on LDAP server',0);
		$sr=ldap_search($ds,$ldap_basedn,$filter);
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
	$ds=ldap_connect($ldap_host,$ldap_port);
	ldap_set_version($ds);
	if(!$test_bind){
    	$ds=ldap_connect($ldap_host2,$ldap_port2);
    	ldap_set_version($ds);
   	}
	// retour en cas d'erreur de connexion password incorrecte
 	if (@ldap_bind( $ds, $dn , $passwd) === false) {
		return (1); // mot passe invalide
	}
	// connection correcte
	else
	{
		return (0);
	}
} // end of check
/**
 * Set the protocol version with version from config file (enables LDAP version 3)
 * @param	resource	The LDAP connexion resource, passed by reference.
 * @return	void
 */
function ldap_set_version(&$resource)
{
	//error_log('Entering ldap_set_version(&$resource)',0);
	global $ldap_version;
	if($ldap_version>2)
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
/**
 * Handle bind (whether authenticated or not)
 * @param	resource	The LDAP handler to which we are connecting (by reference)
 * @param	resource	The LDAP bind handler we will be modifying
 * @return	boolean		Status of the bind assignment. True for success, false for failure.
 */
function ldap_handle_bind(&$ldap_handler,&$ldap_bind)
{
	//error_log('Entering ldap_handle_bind(&$ldap_handler,&$ldap_bind)',0);
	global $ldap_rdn,$ldap_pass;
	if(!empty($ldap_rdn) and !empty($ldap_pass))
	{
		//error_log('Trying authenticated login :'.$ldap_rdn.'/'.$ldap_pass,0);
    	$ldap_bind = ldap_bind($ldap_handler,$ldap_rdn,$ldap_pass);
    	if(!$ldap_bind)
    	{
    		//error_log('Authenticated login failed',0);
    		//try in anonymous mode, you never know...
	    	$ldap_bind = ldap_bind($ldap_handler);
    	}
	}
	else
	{
		// this is an "anonymous" bind, typically read-only access:
    	$ldap_bind = ldap_bind($ldap_handler);
	}
	if(!$ldap_bind)
	{
		return false;
	}
	else
	{
		//error_log('Login finally OK',0);
		return true;
	}
}
/**
 * Get the total number of users on the platform
 * @see SortableTable#get_total_number_of_items()
 * @author	Mustapha Alouani
 */
function ldap_get_users()
{

	global $ldap_basedn, $ldap_host, $ldap_port, $ldap_rdn, $ldap_pass;

	$keyword_firstname = trim(Database::escape_string($_GET['keyword_firstname']));
	$keyword_lastname = trim(Database::escape_string($_GET['keyword_lastname']));
	$keyword_username = trim(Database::escape_string($_GET['keyword_username']));
	$keyword_type = Database::escape_string($_GET['keyword_type']);

	$ldap_query=array();

	if ($keyword_username != "") {
		$ldap_query[]="(uid=".$keyword_username."*)";
	} else if ($keyword_lastname!=""){
		$ldap_query[]="(sn=".$keyword_lastname."*)";
		if ($keyword_firstname!="") {
			$ldap_query[]="(givenName=".$keyword_firstname."*)";
		}
	}
	if ($keyword_type !="" && $keyword_type !="all") {
		$ldap_query[]="(employeeType=".$keyword_type.")";
	}

	if (count($ldap_query)>1){
		$str_query.="(& ";
		foreach ($ldap_query as $query){
			$str_query.=" $query";
		}
		$str_query.=" )";
	} else {
		$str_query=$ldap_query[0];
	}

	$ds = ldap_connect($ldap_host, $ldap_port);
	ldap_set_version($ds);
	if ($ds && count($ldap_query)>0) {
		$r = false;
		$res = ldap_handle_bind($ds, $r);
		//$sr = ldap_search($ds, "ou=test-ou,$ldap_basedn", $str_query);
		$sr = ldap_search($ds, $ldap_basedn, $str_query);
		//echo "Le nombre de resultats est : ".ldap_count_entries($ds,$sr)."<p>";
		$info = ldap_get_entries($ds, $sr);
		return $info;

	} else {
		if (count($ldap_query)!=0)
			Display :: display_error_message(get_lang('LDAPConnectionError'));
		return array();
	}
}



/**
 * Get the total number of users on the platform
 * @see SortableTable#get_total_number_of_items()
 * @author	Mustapha Alouani
 */
function ldap_get_number_of_users()
{

	$info = ldap_get_users();
	if (count($info)>0)
		return $info['count'];
	else
		return 0;

}

/**
 * Get the users to display on the current page.
 * @see SortableTable#get_table_data($from)
 * @author	Mustapha Alouani
 */
function ldap_get_user_data($from, $number_of_items, $column, $direction)
{
	$users = array();
	$is_western_name_order = api_is_western_name_order();
	if (isset($_GET['submit']))
	{
		$info = ldap_get_users();
		if ($info['count']>0)
		{
			for ($key = 0; $key < $info["count"]; $key ++)
			{
				$user=array();
				// Get uid from dn
				//YW: this might be a variation between LDAP 2 and LDAP 3, but in LDAP 3, the uid is in
				//the corresponding index of the array
				//$dn_array=ldap_explode_dn($info[$key]["dn"],1);
				//$user[] = $dn_array[0]; // uid is first key
				//$user[] = $dn_array[0]; // uid is first key
				$user[] = $info[$key]['uid'][0];
				$user[] = $info[$key]['uid'][0];
				if ($is_western_name_order)
				{
					$user[] = api_convert_encoding($info[$key]['givenname'][0], api_get_system_encoding(), 'UTF-8');
					$user[] = api_convert_encoding($info[$key]['sn'][0], api_get_system_encoding(), 'UTF-8');
				}
				else
				{
					$user[] = api_convert_encoding($info[$key]['sn'][0], api_get_system_encoding(), 'UTF-8');
					$user[] = api_convert_encoding($info[$key]['givenname'][0], api_get_system_encoding(), 'UTF-8');
				}
				$user[] = $info[$key]['mail'][0];
				$outab[] = $info[$key]['eduPersonPrimaryAffiliation'][0]; // Ici "student"
				$users[] = $user;
			}

		}
		else
		{
			Display :: display_error_message(get_lang('NoUser'));
		}
	}
	return $users;
}

/**
 * Build the modify-column of the table
 * @param int $user_id The user id
 * @param string $url_params
 * @return string Some HTML-code with modify-buttons
 * @author	Mustapha Alouani
 */
function modify_filter($user_id,$url_params, $row)
{
	$url_params_id="id[]=".$row[0];
	//$url_params_id="id=".$row[0];
	$result .= '<a href="ldap_users_list.php?action=add_user&amp;user_id='.$user_id.'&amp;id_session='.Security::remove_XSS($_GET['id_session']).'&amp;'.$url_params_id.'&amp;sec_token='.$_SESSION['sec_token'].'"  onclick="javascript:if(!confirm('."'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"), ENT_QUOTES, api_get_system_encoding()))."'".')) return false;">'.Display::return_icon('add_user.gif', get_lang('AddUsers')).'</a>';
	return $result;
}

/**
 * Adds a user to the Dokeos database or updates its data
 * @param	string	username (and uid inside LDAP)
 * @author	Mustapha Alouani
 */
function ldap_add_user($login)
{
	global $ldap_basedn, $ldap_host, $ldap_port, $ldap_rdn, $ldap_pass;

	$ds = ldap_connect($ldap_host, $ldap_port);
	ldap_set_version($ds);
	if ($ds)
	{
		$str_query="(uid=".$login.")";
		$r = false;
		$res = ldap_handle_bind($ds, $r);
		$sr = ldap_search($ds, $ldap_basedn, $str_query);
		//echo "Le nombre de resultats est : ".ldap_count_entries($ds,$sr)."<p>";
		$info = ldap_get_entries($ds, $sr);

		for ($key = 0; $key < $info['count']; $key ++)
		{
			$lastname = api_convert_encoding($info[$key]['sn'][0], api_get_system_encoding(), 'UTF-8');
			$firstname = api_convert_encoding($info[$key]['givenname'][0], api_get_system_encoding(), 'UTF-8');
			$email = $info[$key]['mail'][0];
			// Get uid from dn
			$dn_array=ldap_explode_dn($info[$key]['dn'],1);
			$username = $dn_array[0]; // uid is first key
			$outab[] = $info[$key]['edupersonprimaryaffiliation'][0]; // Ici "student"
			//$val = ldap_get_values_len($ds, $entry, "userPassword");
			//$val = ldap_get_values_len($ds, $info[$key], "userPassword");
			//$password = $val[0];
			// TODO the password, if encrypted at the source, will be encrypted twice, which makes it useless. Try to fix that.
			$password = $info[$key]['userPassword'][0];
			$structure=$info[$key]['edupersonprimaryorgunitdn'][0];
			$array_structure=explode(",", $structure);
			$array_val=explode("=", $array_structure[0]);
			$etape=$array_val[1];
			$array_val=explode("=", $array_structure[1]);
			$annee=$array_val[1];
			// Pour faciliter la gestion on ajoute le code "etape-annee"
			$official_code=$etape."-".$annee;
			$auth_source='ldap';
			// Pas de date d'expiration d'etudiant (a recuperer par rapport au shadow expire LDAP)
			$expiration_date='0000-00-00 00:00:00';
			$active=1;
			if(empty($status)){$status = 5;}
			if(empty($phone)){$phone = '';}
			if(empty($picture_uri)){$picture_uri = '';}
			// Ajout de l'utilisateur
			if (UserManager::is_username_available($username))
			{
				$user_id = UserManager::create_user($firstname,$lastname,$status,$email,$username,$password,$official_code,api_get_setting('platformLanguage'),$phone,$picture_uri,$auth_source,$expiration_date,$active);
			}
			else
			{
				$user = UserManager::get_user_info($username);
				$user_id=$user['user_id'];
				UserManager::update_user($user_id, $firstname, $lastname, $username, null, null, $email, $status, $official_code, $phone, $picture_uri, $expiration_date, $active);
			}
		}

	}
	else
	{
		Display :: display_error_message(get_lang('LDAPConnectionError'));
	}
	return $user_id;;
}

/**
 * Adds a list of users to one session
 * @param	array	Array of user ids
 * @param	string	Course code
 * @return	void
 */
function ldap_add_user_to_session($UserList, $id_session)
{

	// Database Table Definitions
	$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
	$tbl_session_rel_class				= Database::get_main_table(TABLE_MAIN_SESSION_CLASS);
	$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
	$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
	$tbl_course							= Database::get_main_table(TABLE_MAIN_COURSE);
	$tbl_user							= Database::get_main_table(TABLE_MAIN_USER);
	$tbl_session_rel_user				= Database::get_main_table(TABLE_MAIN_SESSION_USER);
	$tbl_class							= Database::get_main_table(TABLE_MAIN_CLASS);
	$tbl_class_user						= Database::get_main_table(TABLE_MAIN_CLASS_USER);

	$id_session = (int) $id_session;
	// Une fois les utilisateurs importer dans la base des utilisateurs, on peux les affecter a� la session
	$result=Database::query("SELECT course_code FROM $tbl_session_rel_course " .
			"WHERE id_session='$id_session'",__FILE__,__LINE__);
	$CourseList=array();
	while($row=Database::fetch_array($result))
	{
		$CourseList[]=$row['course_code'];
	}
	foreach($CourseList as $enreg_course)
	{
		foreach($UserList as $enreg_user)
		{
			$enreg_user = (int) $enreg_user;
			Database::query("INSERT IGNORE INTO $tbl_session_rel_course_rel_user(id_session,course_code,id_user) VALUES('$id_session','$enreg_course','$enreg_user')",__FILE__,__LINE__);
		}
		$sql = "SELECT COUNT(id_user) as nbUsers FROM $tbl_session_rel_course_rel_user " .
				"WHERE id_session='$id_session' AND course_code='$enreg_course'";
		$rs = Database::query($sql, __FILE__, __LINE__);
		list($nbr_users) = Database::fetch_array($rs);
		Database::query("UPDATE $tbl_session_rel_course  SET nbr_users=$nbr_users " .
				"WHERE id_session='$id_session' AND course_code='$enreg_course'",__FILE__,__LINE__);
	}
	foreach($UserList as $enreg_user)
	{
		$enreg_user = (int) $enreg_user;
		Database::query("INSERT IGNORE INTO $tbl_session_rel_user(id_session, id_user) " .
				"VALUES('$id_session','$enreg_user')",__FILE__,__LINE__);
	}
	// On mets a jour le nombre d'utilisateurs dans la session
	$sql = "SELECT COUNT(id_user) as nbUsers FROM $tbl_session_rel_user WHERE id_session='$id_session'";
	$rs = Database::query($sql, __FILE__, __LINE__);
	list($nbr_users) = Database::fetch_array($rs);
	Database::query("UPDATE $tbl_session SET nbr_users=$nbr_users WHERE id='$id_session'",__FILE__,__LINE__);
}
?>
