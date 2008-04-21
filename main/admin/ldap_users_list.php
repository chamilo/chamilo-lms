<?php //$id: $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos SPRL
	Copyright (c) 2007 Mustapha Alouani (supervised by Michel Moreau-Belliard)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
	@author Mustapha Alouani
*	@package dokeos.admin
==============================================================================
*/

// name of the language file that needs to be included
$language_file[] = 'registration';
$language_file[] = 'admin';
$cidReset = true;
require('../inc/global.inc.php');
require_once(api_get_path(LIBRARY_PATH).'sortabletable.class.php');
require_once(api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once(api_get_path(LIBRARY_PATH).'security.lib.php');
require('../auth/ldap/authldap.php');
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();


/**
 * Get the total number of users on the platform
 * @see SortableTable#get_total_number_of_items()
 */
function get_ldap_users()
{

	global $ldap_basedn, $ldap_host, $ldap_port, $ldap_rdn, $ldap_pass;
	
	$keyword_firstname = trim(Database::escape_string($_GET['keyword_firstname']));
	$keyword_lastname = trim(Database::escape_string($_GET['keyword_lastname']));
	$keyword_username = trim(Database::escape_string($_GET['keyword_username']));
	$keyword_type = Database::escape_string($_GET['keyword_type']);
	
	$ldap_querry=array();
	
	if ($keyword_username != "") {
		$ldap_querry[]="(uid=".$keyword_username."*)";
	} else if ($keyword_lastname!=""){
		$ldap_querry[]="(sn=".$keyword_lastname."*)";
		if ($keyword_firstname!="") {
			$ldap_querry[]="(givenName=".$keyword_firstname."*)";
		}
	}
	if ($keyword_type !="" && $keyword_type !="all") {
		$ldap_querry[]="(eduPersonPrimaryAffiliation=".$keyword_type.")";
	}
	
	if (sizeof($ldap_querry)>1){
		$str_querry.="(& ";
		foreach ($ldap_querry as $query){
			$str_querry.=" $query";
		}
		$str_querry.=" )"; 
	} else {
		$str_querry=$ldap_querry[0];
	}

	$ds = ldap_connect($ldap_host, $ldap_port);
	if ($ds && sizeof($ldap_querry)>0) {
		$r = false;
		$res = ldap_handle_bind($ds, $r);
		$sr = @ ldap_search($ds, "ou=people,$ldap_basedn", $str_querry);
		//echo "Le nombre de resultats est : ".ldap_count_entries($ds,$sr)."<p>";
		$info = ldap_get_entries($ds, $sr);
		return $info;

	} else {
		if (sizeof($ldap_querry)!=0)
			Display :: display_error_message(get_lang('LDAPConnectionError'));
		return array();
	}
}



/**
 * Get the total number of users on the platform
 * @see SortableTable#get_total_number_of_items()
 */
function get_number_of_users()
{
		
	$info = get_ldap_users();
	if (sizeof($info)>0)
		return $info['count'];
	else 
		return 0;

}

/**
 * Get the users to display on the current page.
 * @see SortableTable#get_table_data($from)
 */
function get_user_data($from, $number_of_items, $column, $direction)
{
	$users = array();
	if (isset($_GET['submit'])) {
		$info = get_ldap_users();
		if ($info['count']>0) {
			for ($key = 0; $key < $info["count"]; $key ++) {
				$user=array();
				// Get uid from dn
				$dn_array=ldap_explode_dn($info[$key]["dn"],1);
				$user[] = $dn_array[0]; // uid is first key
				$user[] = $dn_array[0]; // uid is first key
				$user[] = iconv("utf-8", "iso-8859-1", $info[$key]["sn"][0]);
				$user[] = iconv("utf-8", "iso-8859-1", $info[$key]["givenname"][0]);
				$user[] = $info[$key]["mail"][0];
				$outab[] = $info[$key]["eduPersonPrimaryAffiliation"][0]; // Ici "student"
				$users[] = $user;
			}
			
		} else
			Display :: display_error_message("Pas d'utilisateurs");	
	}
	return $users;
}

/**
 * Build the modify-column of the table
 * @param int $user_id The user id
 * @param string $url_params
 * @return string Some HTML-code with modify-buttons
 */
function modify_filter($user_id,$url_params, $row)
{

	$url_params_id="id[]=".$row[0];
	$result .= '<a href="ldap_users_list.php?action=add_user&amp;user_id='.$user_id.'&amp;id_session='.Security::remove_XSS($_GET['id_session']).'&amp;'.$url_params_id.'&amp;sec_token='.$_SESSION['sec_token'].'"  onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang("ConfirmYourChoice")))."'".')) return false;"><img src="../img/add_user.gif" border="0" style="vertical-align: middle;" title="'.get_lang('AddUsers').'" alt="'.get_lang('AddUsers').'"/></a>';
	
	return $result;
}


function addLdapUser($login){
	global $ldap_basedn, $ldap_host, $ldap_port, $ldap_rdn, $ldap_pass;
	
	$ds = ldap_connect($ldap_host, $ldap_port);
	if ($ds) {
		$str_querry="(uid=".$login.")";
		$r = false;
		$res = ldap_handle_bind($ds, $r);
		$sr = @ ldap_search($ds, "ou=people,$ldap_basedn", $str_querry);
		//echo "Le nombre de resultats est : ".ldap_count_entries($ds,$sr)."<p>";
		$info = ldap_get_entries($ds, $sr);

		for ($key = 0; $key < $info["count"]; $key ++) {
			$lastname = iconv("utf-8", api_get_setting('platform_charset'), $info[$key]["sn"][0]);
			$firstname = iconv("utf-8", api_get_setting('platform_charset'), $info[$key]["givenname"][0]);
			$email = $info[$key]["mail"][0];
			// Get uid from dn
			$dn_array=ldap_explode_dn($info[$key]["dn"],1);
			$username = $dn_array[0]; // uid is first key
			$outab[] = $info[$key]["edupersonprimaryaffiliation"][0]; // Ici "student"
			$val = ldap_get_values_len($ds, $entry, "userPassword");
			$password = $val[0];
			$structure=$info[$key]["edupersonprimaryorgunitdn"][0];
			$array_structure=explode(",", $structure);
			$array_val=explode("=", $array_structure[0]);
			$etape=$array_val[1];
			$array_val=explode("=", $array_structure[1]);
			$annee=$array_val[1];
			// Pour faciliter la gestion on ajoute le code "etape-annee"
			$official_code=$etape."-".$annee;
			$auth_source="cas";
			// Pas de date d'expiration d'etudiant (a recuperer par rapport au shadow expire LDAP)
			$expiration_date='0000-00-00 00:00:00';
			$active=1;
			if(empty($status)){$status = 5;}
			if(empty($phone)){$phone = '';}
			if(empty($picture_uri)){$picture_uri = '';}
			// Ajout de l'utilisateur
			if (UserManager::is_username_available($username))
				$user_id = UserManager::create_user($firstname,$lastname,$status,$email,$username,$password,$official_code,api_get_setting('platformLanguage'),$phone,$picture_uri,$auth_source,$expiration_date,$active);
			else{
				$user = UserManager::get_user_info($username);
				$user_id=$user['user_id'];
				UserManager::update_user($user_id, $firstname, $lastname, $username, null, null, $email, $status, $official_code, $phone, $picture_uri, $expiration_date, $active);
			}
		}

	} else {
		Display :: display_error_message(get_lang('ERREUR CONNEXION LDAP'));
	}
	return $user_id;;
}

function addUserToSession($UserList, $id_session){

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

	// Une fois les utilisateurs importer dans la base des utilisateurs, on peux les affecter a� la session
	$result=api_sql_query("SELECT course_code FROM $tbl_session_rel_course " .
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
			api_sql_query("INSERT IGNORE INTO $tbl_session_rel_course_rel_user(id_session,course_code,id_user) VALUES('$id_session','$enreg_course','$enreg_user')",__FILE__,__LINE__);
		}
		$sql = "SELECT COUNT(id_user) as nbUsers FROM $tbl_session_rel_course_rel_user " .
				"WHERE id_session='$id_session' AND course_code='$enreg_course'";
		$rs = api_sql_query($sql, __FILE__, __LINE__);
		list($nbr_users) = Database::fetch_array($rs);
		api_sql_query("UPDATE $tbl_session_rel_course  SET nbr_users=$nbr_users " .
				"WHERE id_session='$id_session' AND course_code='$enreg_course'",__FILE__,__LINE__);
	}
	foreach($UserList as $enreg_user){
			api_sql_query("INSERT IGNORE INTO $tbl_session_rel_user(id_session, id_user) " .
					"VALUES('$id_session','$enreg_user')",__FILE__,__LINE__);
	}
	// On mets a jour le nombre d'utilisateurs dans la session
	$sql = "SELECT COUNT(id_user) as nbUsers FROM $tbl_session_rel_user WHERE id_session='$id_session'";
	$rs = api_sql_query($sql, __FILE__, __LINE__);
	list($nbr_users) = Database::fetch_array($rs);
	api_sql_query("UPDATE $tbl_session SET nbr_users=$nbr_users WHERE id='$id_session'",__FILE__,__LINE__);
	
}

// Fonction pour dire si c'est un entier
function isInteger($n) {
	if (preg_match("/[^0-^9]+/",$n) > 0) {
		return false;
	} 
	return true;
}

/**
==============================================================================
		INIT SECTION
==============================================================================
*/
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
$action = $_GET["action"];
$login_as_user_id = $_GET["user_id"];

	
// Login as ...
if ($_GET['action'] == "login_as" && isset ($login_as_user_id))
{
	login_user($login_as_user_id);
}

if (($_GET['action']=="add_user") && (isInteger($_GET['id_session']))){
	header('Location: ldap_import_students_to_session.php?id_session='.$id_session);
}

$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
$tool_name = get_lang('SearchAUser'). " LDAP";
Display :: display_header($tool_name);
//api_display_tool_title($tool_name);

if (isset ($_GET['action']))
{
	$check = Security::check_token('get');
	if($check)
	{
		switch ($_GET['action'])
		{
			case 'show_message' :
				Display :: display_header($tool_name);
				Display :: display_normal_message($_GET['message']);
				break;
			case 'delete_user' :
				Display :: display_header($tool_name);
				if ($user_id != $_user['user_id'] && UserManager :: delete_user($_GET['user_id']))
				{
					Display :: display_normal_message(get_lang('UserDeleted'));
				}
				else
				{
					Display :: display_error_message(get_lang('CannotDeleteUser'));
				}
				break;
			case 'lock' :
				Display :: display_header($tool_name);
				$message=lock_unlock_user('lock',$_GET['user_id']);
				Display :: display_normal_message($message);
				break;
			case 'unlock';
				Display :: display_header($tool_name);
				$message=lock_unlock_user('unlock',$_GET['user_id']);
				Display :: display_normal_message($message);
				break;
			case 'add_user';
				$id=$_GET['id'];
				$UserList=array();
				foreach ($id as $user_id) {
					$UserList[]=addLdapUser($user_id);
				}
				if (isset($_GET['id_session']) && ($_GET['id_session']>=0)) {
					addUserToSession($UserList, $_GET['id_session']);
					header('Location: resume_session.php?id_session='.$id_session);
				} else {
					$message=get_lang('Utilisateur(s) LDAP ajoute(s)');
					Display :: display_normal_message($message);
				}
				break;
			default : 
				Display :: display_header($tool_name);
		}
		Security::clear_token();
	}
}
if (isset ($_POST['action']))
{
	$check = Security::check_token('get');
	if($check)
	{
		switch ($_POST['action'])
		{
			case 'delete' :
				$number_of_selected_users = count($_POST['id']);
				$number_of_deleted_users = 0;
				foreach ($_POST['id'] as $index => $user_id)
				{
					if($user_id != $_user['user_id'])
					{
						if(UserManager :: delete_user($user_id))
						{
							$number_of_deleted_users++;
						}
					}
				}
				if($number_of_selected_users == $number_of_deleted_users)
				{
					Display :: display_normal_message(get_lang('SelectedUsersDeleted'));
				}
				else
				{
					Display :: display_error_message(get_lang('SomeUsersNotDeleted'));
				}
				break;
			case 'add_user' :
				$number_of_selected_users = count($_POST['id']);
				$number_of_added_users = 0;
				$UserList=array();
				foreach ($_POST['id'] as $index => $user_id)
				{
					if($user_id != $_user['user_id'])
					{
						$UserList[] = addLdapUser($user_id);
					}
				}
				if (isset($_GET['id_session']) && (trim($_GET['id_session'])!=""))
					addUserToSession($UserList, $_GET['id_session']);
				if(sizeof($UserList)>0)
				{
					Display :: display_normal_message(sizeof($UserList)." ".get_lang('utilisateur(s) ajoute(s)'));
				}
				else
				{
					Display :: display_normal_message(get_lang('Aucun utilisateur ajoute'));
				}				
				break;
				
		}
		Security::clear_token();
	}
}

$form = new FormValidator('advanced_search','get');
$form->add_textfield('keyword_username',get_lang('LoginName'),false);
$form->add_textfield('keyword_lastname',get_lang('LastName'),false);
$form->add_textfield('keyword_firstname',get_lang('FirstName'),false);
if (isset($_GET['id_session'])) 
	$form->addElement('hidden','id_session',$_GET['id_session']);

$type = array();
$type["all"] = get_lang('All');
$type["employee"]  = get_lang('Staff');
$type["student"] = get_lang('Student');

$form->addElement('select','keyword_type',get_lang('Status'),$type);
// Structure a rajouer ??
$form->addElement('submit','submit',get_lang('Ok'));
//$defaults['keyword_active'] = 1;
//$defaults['keyword_inactive'] = 1;
//$form->setDefaults($defaults);
$form->display();



$parameters['keyword_username'] = $_GET['keyword_username'];
$parameters['keyword_firstname'] = $_GET['keyword_firstname'];
$parameters['keyword_lastname'] = $_GET['keyword_lastname'];
$parameters['keyword_email'] = $_GET['keyword_email'];
if (isset($_GET['id_session'])) 
	$parameters['id_session'] = $_GET['id_session'];
// Create a sortable table with user-data

$parameters['sec_token'] = Security::get_token();
$table = new SortableTable('users', 'get_number_of_users', 'get_user_data',2);
$table->set_additional_parameters($parameters);
$table->set_header(0, '', false);
$table->set_header(1, get_lang('LoginName'));
$table->set_header(2, get_lang('LastName'));
$table->set_header(3, get_lang('FirstName'));
$table->set_header(4, get_lang('Email'));
//$table->set_column_filter(5, 'email_filter');
//$table->set_column_filter(5, 'active_filter');
$table->set_column_filter(5, 'modify_filter');
$table->set_form_actions(array ('add_user' => get_lang('Ajouter les utilisateurs LDAP')));
$table->display();

/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();
?>