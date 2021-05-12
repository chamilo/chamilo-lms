<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
*/
/**
 * Code
 */
exit(); //not yet functional, needs to be revised

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
require_once('../auth/ldap/authldap.php');
$annee_base = date('Y');
// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;
//api_protect_admin_script(); // on vire la secu... qui n'a pas lieu d'etre ici (script de synchro)

// setting breadcrumbs
$interbreadcrumb[] = array('url' => 'index.php', "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => api_get_self(), "name" => "Liste des sessions");

// Database Table Definitions
$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);

$tbl_session_rel_etape = "session_rel_etape";

$message = "";

$result = Database::query("SELECT id, name FROM $tbl_session");
$Sessions = Database::store_result($result);

$result = Database::query($sql);
$users = Database::store_result($result);

foreach ($Sessions as $session) {
	$id_session = $session['id'];
	$name_session = $session['name'];
	$UserList = array();
	$UserUpdate = array();
	$UserAdd = array();

	// Parse des code etape de la session
	/*
	$sql = "SELECT  id_session, code_etape, etape_description, code_ufr, annee
		FROM $tbl_session_rel_etape
		WHERE id_session='$id_session'
		ORDER BY code_ufr, code_etape";
	$result = Database::query($sql);
	*/
	$ds = ldap_connect($ldap_host, $ldap_port) or die(get_lang('LDAPConnectionError'));
	ldap_set_version($ds);
	// Import des utilisateurs des etapes dans la session
	if ($ds)
	{
		$r = false;
		$res = ldap_handle_bind($ds, $r);
		$UserList = array();
		if ($result !== false)
		{
			//while($row = Database::fetch_array($result))
			//{
				/*
				$annee = $row['annee'];
				$code_ufr = $row['code_ufr'];
				$etape = $row['code_etape'];
				*/
				// LDAP Query
				// edupersonorgunitdn=ou=12CI1,ou=2006,ou=diploma,o=Paris1,dc=univ-paris1,dc=fr
				//etapescommented
				//$sr = @ ldap_search($ds, "ou=people,$LDAPbasedn", "edupersonorgunitdn=ou=$etape,ou=$annee,ou=diploma,$LDAPbasedn");
				$sr = @ ldap_search($ds, $ldap_basedn, '(uid=*)');
				$info = ldap_get_entries($ds, $sr);
				for ($key = 0; $key < $info["count"]; $key++)
				{
					echo "<pre>";
					print_r($info[$key]);
					echo "</pre>";
					$lastname = api_utf8_decode($info[$key]["sn"][0]);
					$firstname = api_utf8_decode($info[$key]["givenname"][0]);
					$email = $info[$key]["mail"][0];
					// Get uid from dn
					$dn_array = ldap_explode_dn($info[$key]["dn"], 1);
					$username = $dn_array[0]; // uid is first key
					$outab[] = $info[$key]["edupersonprimaryaffiliation"][0]; // Ici "student"
					$val = ldap_get_values_len($ds, $sr, "userPassword");
					$password = $val[0];
					// Pour faciliter la gestion on ajoute le code "etape-annee"
					$official_code = $etape."-".$annee;
					$auth_source = "ldap";
					// Pas de date d'expiration d'etudiant (a recuperer par rapport au shadow expire LDAP)
					$expiration_date = '';
					$active = 1;
					// Ajout de l'utilisateur
					if (UserManager::is_username_available($username)) {
                        $user_id = UserManager::create_user(
                            $firstname,
                            $lastname,
                            $status,
                            $email,
                            $username,
                            $password,
                            $official_code,
                            api_get_setting('platformLanguage'),
                            $phone,
                            $picture_uri,
                            $auth_source,
                            $expiration_date,
                            $active
                        );
						$UserAdd[] = $user_id;
					} else {
						$user = api_get_user_info_from_username($username);
						$user_id = $user['user_id'];
                        UserManager::update_user(
                            $user_id,
                            $firstname,
                            $lastname,
                            $username,
                            null,
                            null,
                            $email,
                            $status,
                            $official_code,
                            $phone,
                            $picture_uri,
                            $expiration_date,
                            $active
                        );
						$UserUpdate[] = $user_id;
					}
					$UserList[] = $user_id;
				}
			//}
		}
		if (isset($included) && ($included))
		{
			$message .= "> $name_session: ".count($UserAdd)." ".get_lang('Added').' '.get_lang('And').' '.count($UserUpdate).' '.get_lang('Modified').'<br/>';
		}
		else
		{
			print "> $name_session: ".count($UserAdd).get_lang('Added').' '.get_lang('And').' '.count($UserUpdate).' '.get_lang('Modified')."\n";
		}

		// Une fois les utilisateurs importer dans la base des utilisateurs, on peux les affecter la session
		$result = Database::query("SELECT c_id FROM $tbl_session_rel_course WHERE session_id='$id_session'");
		$CourseList = array();
		while ($row = Database::fetch_array($result)) {
			$CourseList[] = $row['c_id'];
		}

		SessionManager::insertUsersInCourses($UserList, $CourseList, $id_session);
	}
}
