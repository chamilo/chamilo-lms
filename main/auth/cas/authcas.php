<?php
/*Written by Noel Dieschburg <noel@cblue.be> for the paris5 university

* Checks if the user is already logged in via the cas system
* Gets all the info via the ldap module (ldap has to work)

*/
require_once(api_get_path(SYS_PATH).'main/auth/cas/cas_var.inc.php');
require_once(api_get_path(SYS_PATH).'main/auth/ldap/authldap.php');

/**
* checks if the user already get a session
* @return the user login if the user already has a session ,false otherwise
**/


function cas_is_authenticated()
{
	global $cas_auth_ver, $cas_auth_server, $cas_auth_port, $cas_auth_uri; 
	global $PHPCAS_CLIENT;
	global $logout;


	if (!is_object($PHPCAS_CLIENT) ) 
	{
		phpCAS::client($cas_auth_ver,$cas_auth_server,$cas_auth_port,$cas_auth_uri);
//		die("phpCAS::client($cas_auth_ver,$cas_auth_server,$cas_auth_port,$cas_auth_uri);");
		phpCAS::setNoCasServerValidation();
	}
	$auth = phpCAS::checkAuthentication(); 
  
	if ($auth) {
		$login= trim(phpCAS::getUser());
		/*
		   Get user  attributes. Here are the attributes for crdp platform
		   sn => name
		   ENTPersonMailInterne => mail
		   ENTPersonAlias => login
		   ENTPersonProfils => profil
		   givenName => first name
		 */
		/*$user=phpCAS::getAttributes();
		$firstName = trim($user['givenName']);
		$lastName = trim($user['sn']);
		$login = trim($user['ENTPersonAlias']);
		$profil = trim($user['ENTPersonProfils']);
		$email = trim($user['ENTPersonMailInterne']);
		$satus=5;
		switch ($profil){
			case 'admin_etab':
				$status=3; //Session admin
				break;
			case 'admin_sie':
				$status=3; //Session admin
				break;
			case 'National_3':
				$status=1; // Teacher
				break;
			case 'National_1':
				$status=5; // Student
				break;
			default:
				$status=5; // Student
		}*/
		//If the user is in the dokeos database and we are ,not in a logout request, we upgrade his infomration by ldap
		if (! $logout){
			$user_table = Database::get_main_table(TABLE_MAIN_USER);
			$sql = "SELECT user_id, username, password, auth_source, active, expiration_date ".
				"FROM $user_table ".
				"WHERE username = '$login' ";

			$result = api_sql_query($sql,__FILE__,__LINE__);
			if(mysql_num_rows($result) == 0) { 
				require_once(api_get_path(SYS_PATH).'main/inc/lib/usermanager.lib.php');
				$rnumber=rand(0,256000);
				UserManager::create_user($firstName, $lastName, $status, $email, $login, md5('casplaceholder'.$rnumber), $official_code='',$language='',$phone='',$picture_uri='',$auth_source = PLATFORM_AUTH_SOURCE);
			}
			else {
				$user = mysql_fetch_assoc($result);
				$user_id = intval($user['user_id']);
				//echo "deb : $status";
				UserManager::update_user ($user_id, $firstname, $lastname, $login, null, null, $email, $status, '', '', '', '', 1, null, 0, null,'') ;

			}
		}
		return($login);
	}
	else 
	{ 
		return(false);
	}
}

/**
* Logs out the user of the cas 
* The user MUST be logged in with cas to use this function 
**/ 
function cas_logout()
{
	
	//phpCAS::logoutWithRedirectService("fmc.univ-paris5.fr");		
	phpCAS::logoutWithRedirectService(api_get_path(WEB_PATH));		
}

?>
