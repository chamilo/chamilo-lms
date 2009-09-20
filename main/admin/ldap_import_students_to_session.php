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
// name of the language file that needs to be included
$language_file[]='admin';
$language_file[]='registration';
// resetting the course id
$cidReset=true;
require_once('../inc/global.inc.php');
// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;
require_once(api_get_path(LIBRARY_PATH).'course.lib.php');
require_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');
// Access restrictions
api_protect_admin_script();
require('../auth/ldap/authldap.php');

$annee_base=date('Y');

$tool_name = get_lang('LDAPImport');
// setting breadcrumbs
$interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('PlatformAdmin'));

$htmlHeadXtra[] = '<script language="JavaScript" type="text/javascript">
var buttoncheck = 1;
function checkAll() {
	//var boxes = document.form.elements[\'checkboxes[]\'];
	var boxes = document.getElementsByName(\'checkboxes[]\');
	if (buttoncheck == 0) {
		for (i = 0; i < boxes.length; i++) {
			boxes[i].checked = true;
		}
		buttoncheck = 1;
		return "'.get_lang('None').'";
	}
	else {
		for (i = 0; i < boxes.length; i++) {
			boxes[i].checked = false;
		}
		buttoncheck = 0;
		return " '.get_lang('All').' ";
	}
}
</script>';

$annee = $_GET['annee'];
$id_session = $_POST['id_session'];


// form1 annee = 0; composante= 0 etape = 0
//if ($annee == "" && $composante == "" && $etape == "") {
if (empty($annee) && empty($id_session))
{
		Display::display_header($tool_name);
		echo '<div style="align:center">';
		echo Display::return_icon('group.gif', get_lang('LDAPSelectFilterOnUsersOU')).' '.get_lang('LDAPSelectFilterOnUsersOU');
		echo '<form method="get" action="'.api_get_self().'"><br />';
		echo '<em>'.get_lang('LDAPOUAttributeFilter').' :</em> ';
		echo '<input  type="text" name="annee" size="4" maxlength="30" value="'.$annee_base.'"> ';
		echo '<input type="submit" value="'.get_lang('Submit').'">';
		echo '</form>';
		echo '</div>';

}
elseif(!empty($annee) && empty($id_session))
{
	Display::display_header($tool_name);
	echo '<div style="align:center">';
	echo Display::return_icon('course.gif', get_lang('SelectSessionToImportUsersTo')).' '.get_lang('SelectSessionToImportUsersTo').'<br />';
	echo '<form method="post" action="'.api_get_self().'?annee='.Security::remove_XSS($annee).'"><br />';
	echo '<select name="id_session">';

	$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
	$sql = "SELECT id,name,nbr_courses,date_start,date_end " .
		" FROM $tbl_session ".
		" ORDER BY name";
	$result = api_sql_query($sql,__FILE__,__LINE__);

	$sessions=api_store_result($result);
	$nbr_results=count($sessions);
	foreach($sessions as $row)
	{
		echo '<option value="'.$row['id'].'">'.api_htmlentities($row['name'], ENT_COMPAT, api_get_system_encoding()).' ('.$row['date_start'].' - '.$row['date_end'].')</option>';
	}
	echo '</select>';
	echo '<input type="submit" value="'.get_lang('Submit').'">';
	echo '</form>';
	echo '</div>';
}
// form4  annee != 0; composante != 0 etape != 0
//elseif ($annee <> "" && $composante <> "" && $etape <> "" && $listeok != 'yes') {
elseif (!empty($annee) && !empty($id_session) && empty($_POST['confirmed']))
{
	Display::display_header($tool_name);
	echo '<div style="align: center;">';
	echo '<br />';
	echo '<br />';
	echo '<h3>'.Display::return_icon('group.gif', get_lang('SelectStudents')).' '.get_lang('SelectStudents').'</h3>';
	//echo "Connection ...";
	$ds = ldap_connect($ldap_host, $ldap_port) or die(get_lang('LDAPConnectionError'));
	ldap_set_version($ds);

	if ($ds) {

		$r = false;
		$res = ldap_handle_bind($ds, $r);

		//$sr = @ ldap_search($ds, "ou=people,$LDAPbasedn", "(|(edupersonprimaryorgunitdn=ou=$etape,ou=$annee,ou=diploma,o=Paris1,$LDAPbasedn)(edupersonprimaryorgunitdn=ou=02PEL,ou=$annee,ou=diploma,o=Paris1,$LDAPbasedn))");
		//echo "(ou=*$annee,ou=$composante)";
		$sr = @ ldap_search($ds, $ldap_basedn, "(ou=*$annee)");

		$info = ldap_get_entries($ds, $sr);

		for ($key = 0; $key < $info["count"]; $key ++) {
			$nom_form[] = $info[$key]["sn"][0];//api_utf8_decode($info[$key]["sn"][0], api_get_setting('platform_charset'));
			$prenom_form[] = $info[$key]["givenname"][0];//api_utf8_decode($info[$key]["givenname"][0], api_get_setting('platform_charset'));
			$email_form[] = $info[$key]["mail"][0];
			// Get uid from dn
			//$dn_array=ldap_explode_dn($info[$key]["dn"],1);
			//$username_form[] = $dn_array[0]; // uid is first key
			$username_form[] = $info[$key]['uid'][0];
			$outab[] = $info[$key]["eduPersonPrimaryAffiliation"][0]; // Ici "student"
			//$val = ldap_get_values_len($ds, $entry, "userPassword");
			//$password_form[] = $val[0];
			$password_form[] = $info[$key]['userPassword'][0];
		}
		ldap_unbind($ds);

		/*-----------------------------------------------*/

		asort($nom_form);
		reset($nom_form);

		$statut=5;
		include ('ldap_form_add_users_group.php');
	} else {
		echo '<h4>'.get_lang('UnableToConnectTo').' '.$host.'</h4>';
	}
	echo '<br /><br />';
    echo '<a href="ldap_import_students.php?annee=">'.get_lang('BackToNewSearch').'</a>';
    echo '<br /><br />';
    echo '</div>';

}
elseif (!empty($annee) && !empty($id_session) && ($_POST['confirmed']=='yes'))
{
	$id=$_POST['username_form'];
	$UserList=array();
	$userid_match_login = array();
	foreach ($id as $form_index=>$user_id)
	{
		if(is_array($_POST['checkboxes']) && in_array($form_index,array_values($_POST['checkboxes'])))
		{
			$tmp = ldap_add_user($user_id);
			$UserList[]= $tmp;
			$userid_match_login[$tmp] = $user_id;
		}
	}
	if (!empty($_POST['id_session']))
	{
		$num = 0;
		$tbl_session_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
		$tbl_session	  = Database::get_main_table(TABLE_MAIN_SESSION);
		foreach($UserList as $user_id)
		{
			$sql = 'INSERT INTO '.$tbl_session_user.' SET
					id_user="'.intval($user_id).'",
					id_session = "'.intval($id_session).'"';
			$res_user = api_sql_query($sql,__FILE__,__LINE__);
			if($res_user != false)
			{
				$num++;
			}
		}
		if($num>0)
		{
			$sql = 'UPDATE '.$tbl_session.' SET nbr_users = (nbr_users + '.$num.') WHERE id = '.intval($id_session);
			$res = api_sql_query($sql,__FILE__,__LINE__);
		}
		header('Location: resume_session.php?id_session='.Security::remove_XSS($_POST['id_session']));
	}
	/*
	else
	{
		Display :: display_header($tool_name);
		if(count($userid_match_login)>0)
		{
			$message=get_lang('LDAPUsersAddedOrUpdated').':<br />';
			foreach($userid_match_login as $user_id => $login)
			{
				$message .= '- '.$login.'<br />';
			}
		}
		else
		{
			$message=get_lang('NoUserAdded');
		}
		Display :: display_normal_message($message,false);
	}
	*/
	else
	{
		Display::display_header($tool_name);
		$message=get_lang('NoUserAdded');
		Display :: display_normal_message($message,false);
	}
	echo '<br /><br />';
    echo '<a href="ldap_import_students.php?annee=&composante=&etape=">'.get_lang('BackToNewSearch').'</a>';
    echo '<br /><br />';
}
Display::display_footer();
?>
