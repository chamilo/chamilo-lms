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
require_once('../inc/global.inc.php');
// resetting the course id
$cidReset=true;
// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;
// Access restrictions
api_protect_admin_script();
require('../auth/ldap/authldap.php');

$annee_base=date('Y');

$tool_name = get_lang('LDAPImport');
// setting breadcrumbs
$interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('PlatformAdmin'));

$htmlHeadXtra[] = '<script language="JavaScript" type="text/javascript">	
var buttoncheck = "false";
function checkAll() {
	var boxes = document.form.elements[\'checkboxes[]\'];
	if (buttoncheck == "false") {
		for (i = 0; i < boxes.length; i++) {
			boxes[i].checked = true;
		}
		buttoncheck = "true";
		return "'.get_lang('None').'";
	}
	else {
		for (i = 0; i < boxes.length; i++) {
			boxes[i].checked = false;
		}
		buttoncheck = "false";
		return " '.get_lang('All').' ";
	}
}
</script>';

Display::display_header($tool_name);

$annee = $_GET['annee'];
$composante = $_GET['composante'];
$etape =  $_GET['etape'];

// form1 annee = 0; composante= 0 etape = 0
	if ($annee == "" && $composante == "" && $etape == "") {

		echo '<div style="align:center">';
		echo '<h3><img src="../img/group.gif" alt="'.get_lang('EnterStudentsToSubscribeToCourse').'" />'.get_lang('EnterStudentsToSubscribeToCourse').'</h3>';
		echo '<em>'.get_lang('ToDoThisYouMustEnterYearComponentAndComponentStep').'</b><br />';
		echo get_lang('FollowEachOfTheseStepsStepByStep').'<br />';
		
		echo '<form method="get" action="'.api_get_self().'"><br />';
		echo '<b>'.sprintf(get_lang('RegistrationYearExample'),date('Y'),date('Y'),date('Y')+1).' :</b> ';
		echo '<input  type="text" name="annee" size="4" maxlength="30" value="'.$annee_base.'"><br />';
		echo '<input type="submit" value="'.get_lang('Submit').'">';
		echo '</form>';
		echo '</div>';
	
}
elseif ($annee <> "" && $composante == "" && $etape == "") // form 2 annee != 0; composante= 0 etape = 0 
{

	$ds = ldap_connect($ldap_host, $ldap_port) or die(get_lang('LDAPConnectionError'));
	ldap_set_version($ds);
	
	if ($ds) {
		$r = false;
		$res = ldap_handle_bind($ds, $r);

		//$sr = @ ldap_search($ds, "o=groups,$ldap_basedn", "(&(description=etape*)(cn=*$annee))", array ('cn'));
		//$sr = @ ldap_search($ds, "ou=$annee, ou=diploma, o=paris1, $ldap_basedn", "ou=02*", array ('description'));
		//$sr = @ ldap_search($ds, "ou=structures, o=paris1, $ldap_basedn", "businessCategory=pedagogy", array ('ou','description'));
		$sr = @ ldap_search($ds, "$ldap_basedn", "", array ('ou','description'));
		// "ou=2006,ou=diploma,o=Paris1,dc=univ-paris1,dc=fr

		$info = ldap_get_entries($ds, $sr);
		$composante = array();
		for($i = 0; $i < $info['count']; $i ++)
		{
			//on suppose que le serveur LDAP est en UTF-8
			$composante[$info[$i]['ou'][0]] = iconv('utf-8', api_get_setting('platform_charset'), $info[$i]['description'][0]);
		}
		$oucompotab3=$composante;

		echo '<div style="align: center">';
		echo '<br />';
		echo '<h3><img src="../img/group.gif" alt="'.get_lang('SelectComponent').'" />'.get_lang('SelectComponent').'</h3>';
		echo '<form method="get" action="'.api_get_self().'">';
		echo '<b>'.get_lang('RegistrationYear').'</b> : ';
		echo '<input type="text" name="annee" size="4" maxlength="30" value="'.$annee.'">';
		echo '<b>'.get_lang('Component').' : </b> ';
		echo '<select name="composante" size="1">';
		while (list ($key, $val) = each($oucompotab3)) {
			echo '<option value="'.$key.'">'.$oucompotab3[$key].'</option>';
		}
		echo '</select>';
		echo '<br />';
		echo '<br />';
		echo '<input type="submit" name="valider" value="'.get_lang('Submit').'">';
		echo '</form>';
		ldap_close($ds);

	}
	echo '<br />';
	echo '<br />';
	echo '<br />';
	echo '<a href="ldap_import_students.php?annee=&composante=&etape=">'.get_lang('BackToNewSearch').'</a>';
	echo '<br />';
	echo '</div>';
}
elseif ($annee <> "" && $composante <> "" && $etape == "") // form3 :annee!=0composante=0etape=0 
{

	echo '<div style="align: center">';
	echo '<h3><img src="../img/group.gif" alt="'.get_lang('SearchResults').'" />'.get_lang('SearchResults').'</h3>';
	$ds = ldap_connect($ldap_host, $ldap_port);
	ldap_set_version($ds);

	if ($ds) {

		$r = false;
		$res = ldap_handle_bind($ds, $r);

		// $sr = @ ldap_search($ds, "ou=groups, $LDAPbasedn", "(&(cn=*$annee*)(cn=*$composante*))");

		$sr = @ ldap_search($ds, "ou=$annee, ou=diploma, $ldap_basedn", "seeAlso=ou=$composante,ou=structures,$ldap_basedn", array ('ou','description'));

		//echo "Le nombre de resultats est : ".ldap_count_entries($ds,$sr)."<p>";
		$info = ldap_get_entries($ds, $sr);

		for ($i = 0; $i <= $info['count']; $i ++) {

			$description = $info[$i]['description'];
			$ouetapetab[$i] = $description[0];
			$description2 = $info[$i]['ou'];
			$ouetapetab2[$i] = $description2[0];

		}

		asort($ouetapetab);
		reset($ouetapetab);

		echo '<form method="get" action="'.api_get_self().'">';

		echo '<b>'.get_lang('RegistrationYear').':</b><input type="text"  name="annee" size="4" maxlength="30" value="'.$annee.'">';
		echo '<br /><br />';
		echo '<b>'.get_lang('Component').' :</b><input type="text" name="composante" size="4" maxlength="30" value="'.$composante.'">';
		echo '<br />';
		echo '<h4>'.get_lang('SelectStepAcademicYear').'</h4>';
		echo '<br />';

		echo '<b>'.get_lang('Step').': </b>';
		echo '<select name="etape" size="1">';
		$tempcomp = "";

		while (list ($key, $val) = each($ouetapetab)) {
			if ($ouetapetab[$key] != $tempcomp) {
				$etape = $ouetapetab2[$key];
				$tempcomp = '"'.$ouetapetab[$key].'"';
				$tempcomp = iconv('utf-8',api_get_setting('platform_charset'),$tempcomp);

				$annee = str_word_count($etape, 1);
				echo '<option value="'.$etape.'">'.$tempcomp.'</option>';
			}
		}
		echo '</select>';
		echo '<input type="hidden" name="displayname" value="'.$displayname.'">';

		echo '<br />';
		echo '<input type="submit" name="envoi" value="'.get_lang('Submit').'">';
		echo '</form>';

		ldap_close($ds);

	} else {
		//    echo "<h4>Unable to connect to LDAP server</h4>";
	}
	echo '<br />';
	echo '<br />';
    echo '<a href="ldap_import_students.php?annee=&composante=&etape=">'.get_lang('BackToNewSearch').'</a>';
	echo '</div>';
}

// form4  annee != 0; composante != 0 etape != 0
elseif ($annee <> "" && $composante <> "" && $etape <> "" && $listeok != yes) {
	echo '<div style="align: center;">';
	echo '<br />';
	echo '<br />';
	echo '<h3><img src="../img/group.gif" alt="'.get_lang('SelectStudents').'" />'.get_lang('SelectStudents').'</h3>';
	//echo "Connection ...";
	$ds = ldap_connect($ldap_host, $ldap_port) or die(get_lang('LDAPConnectionError'));
	ldap_set_version($ds);

	if ($ds) {

		$r = false;
		$res = ldap_handle_bind($ds, $r);

		//$sr = @ ldap_search($ds, "ou=people,$LDAPbasedn", "(|(edupersonprimaryorgunitdn=ou=$etape,ou=$annee,ou=diploma,o=Paris1,$LDAPbasedn)(edupersonprimaryorgunitdn=ou=02PEL,ou=$annee,ou=diploma,o=Paris1,$LDAPbasedn))");
		$sr = @ ldap_search($ds, "ou=people,$ldap_basedn", "edupersonprimaryorgunitdn=ou=$etape,ou=$annee,ou=diploma,$ldap_basedn");

		$info = ldap_get_entries($ds, $sr);

		for ($key = 0; $key < $info["count"]; $key ++) {
			$nom_form[] = $info[$key]["sn"][0];//iconv("utf-8",api_get_setting('platform_charset'), $info[$key]["sn"][0]);
			$prenom_form[] = $info[$key]["givenname"][0];//iconv("utf-8",api_get_setting('platform_charset'), $info[$key]["givenname"][0]);
			$email_form[] = $info[$key]["mail"][0];
			// Get uid from dn
			$dn_array=ldap_explode_dn($info[$key]["dn"],1);
			$username_form[] = $dn_array[0]; // uid is first key
			$outab[] = $info[$key]["eduPersonPrimaryAffiliation"][0]; // Ici "student"
			$val = ldap_get_values_len($ds, $entry, "userPassword");
			$password_form[] = $val[0];
		}

		/*-----------------------------------------------*/

		asort($nom_form);
		reset($nom_form);

		$statut=5;	
		ldap_close($ds);
		include ('ldap_form_add_users_group.php');
		ldap_unbind($ds);
	} else {
		echo '<h4>'.get_lang('UnableToConnectTo').' '.$host.'</h4>';
	}
	echo '<br /><br />';
    echo '<a href="ldap_import_students.php?annee=&composante=&etape=">'.get_lang('BackToNewSearch').'</a>';
    echo '<br /><br />';
    echo '</div>';

}
Display::display_footer();
?>