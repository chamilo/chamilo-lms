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
*	@package dokeos.admin
==============================================================================
*/

// name of the language file that needs to be included
$language_file='admin';

$cidReset=true;

require_once('../inc/global.inc.php');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();

$libpath = api_get_path(LIBRARY_PATH);
require_once($libpath.'formvalidator/FormValidator.class.php');
include_once($libpath.'usermanager.lib.php');
require('../auth/ldap/authldap.php');

$annee_base=date('Y');

// setting breadcrumbs
$interbreadcrumb[]=array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array('url' => api_get_self(),"name" => "Liste des sessions");

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

$tbl_session_rel_etape 				= "session_rel_etape";

$id_session=intval($_GET['id_session']);

$formSent=0;
$errorMsg=$firstLetterUser=$firstLetterSession='';
$UserList=$SessionList=array();
$users=$sessions=array();
$noPHP_SELF=true;

$page=intval($_GET['page']);
$action=$_REQUEST['action'];
$sort=in_array($_GET['sort'],array('name','nbr_courses','date_start','date_end'))?$_GET['sort']:'name';
$idChecked = $_REQUEST['idChecked'];

$annee = intval($_GET['annee']);
$composante = $_GET['composante'];
$etape =  $_GET['etape'];

if (isset($id_session) && $id_session!="") 
{
	$tool_name = get_lang('Import LDAP : Utilisateurs/Etapes');;
	Display::display_header($tool_name);
	//api_display_tool_title($tool_name);

	if (isset($action) && ($action=="add")) {
	// ICI Selection ETAPE a partir de LDAP

		// form1 annee = 0; composante= 0 etape = 0
		if ($annee == "" && $composante == "" && $etape == "") {
			echo '<div style="align:center">';
			echo '<h3><img src=\"../img/group.gif\" alt="'.get_lang('SAISIE DE L\'ETAPE A AJOUTER A VOTRE SESSION').'" />'.get_lang('SAISIE DE L\'ETAPE A AJOUTER A VOTRE SESSION').'</h3>';
			echo '<b>'.get_lang('POUR CELA, IL FAUT SAISIR L\'ANNEE, L\'UFR ET L\'ETAPE').'</b><br />';
			echo get_lang('Suivre chacune de ces &eacute;tapes pas &agrave; pas').'<br />';
			echo '<form method="get" action="'.api_get_self().'"><br />';
			echo '<b>'.sprintf(get_lang('ANNEE D\'INSCRIPTION - <i>Ex: %s pour l\'ann&eacute;e %s/%s</i>'),date('Y'),date('Y'),date('Y')+1).' :</b>';
			echo '<input type="text" name="annee" size="4" maxlength="30" value="'.$annee_base.'"><br />';
			echo '<input type="hidden" name="id_session" value="'.$id_session.'">';
			echo '<input type="hidden" name="action" value="add">';
			echo '<input type="submit" value="'.get_lang('Valider').'">';
			echo '</form>';
			echo '</div>';
		}
		// form 2 annee != 0; composante= 0 etape = 0
		elseif ($annee <> "" && $composante == "" && $etape == "") {

			$ds = ldap_connect($ldap_host, $ldap_port) or die(get_lang('Impossible de se connecter au serveur LDAP'));

			if ($ds) {
				$r = false;
				$res = ldap_handle_bind($ds, $r);

				//$sr = @ ldap_search($ds, "o=groups,$ldap_basedn", "(&(description=etape*)(cn=*$annee))", array ('cn'));
				//$sr = @ ldap_search($ds, "ou=$annee, ou=diploma, o=paris1, $ldap_basedn", "ou=02*", array ('description'));
				//$sr = @ ldap_search($ds, "ou=structures, o=paris1, $ldap_basedn", "businessCategory=pedagogy", array ('ou','description'));
				$sr = @ ldap_search($ds, "$ldap_basedn", "", array ('ou','description'));
				// "ou=2006,ou=diploma,o=Paris1,dc=univ-paris1,dc=fr

				$info = ldap_get_entries($ds, $sr);
				for ($i = 0; $i < $info['count']; $i ++) {
					$composante[$info[$i]['ou'][0]] = iconv('utf-8',api_get_setting('platform_charset'), $info[$i]['description'][0]);
				}
				$oucompotab3=$composante;
				
				echo '<div style="align:center">';
				echo '<br />';
				echo '<h3><img src="../img/group.gif" alt="'.get_lang('SELECTIONNER VOTRE UFR').'"/>'.get_lang('SELECTIONNER VOTRE UFR').'</h3>';
				echo '<form method="get" action="'.api_get_self().'">';
				echo '<b>'.get_lang('ANNEE D\'INSCRIPTION').'</b> : ';
				echo '<input type="text" name="annee" size="4" maxlength="30" value="'.$annee.'">';
				echo '<b>'.get_lang('UFR').' : </b>';
				echo '<select name="composante" size="1">';
				while (list ($key, $val) = each($oucompotab3)) {
					echo '<option value="'.$key.'">'.$oucompotab3[$key].'</option>';
				}

				echo '</select>';

				echo '<br />';
				echo '<br />';
				echo '<input type="hidden" name="id_session" value="'.$id_session.'">';
				echo '<input type="hidden" name="action" value="add">';
				echo '<input type="submit" name="valider" value="'.get_lang('Submit').'">';
				echo '</form>';
				ldap_close($ds);

			}
			echo '<br />';
			echo '<br />';
			echo '<a href="'.api_get_self().'"?id_session='.$id_session.'&annee=&action=add&composante=&etape=">'.get_lang('RETOUR : nouvelle recherche').'</a>';
			echo '<br />';
			echo '</div>';
		}

		// form3 :annee!=0composante=0etape=0
		elseif ($annee <> "" && $composante <> "" && $etape == "") {

			echo '<div style="align:center;">';
			echo '<h3><img src="../img/group.gif" alt="'.get_lang('SearchResults').'" />'.get_lang('SearchResults').'</h3>';
			$ds = ldap_connect($ldap_host, $ldap_port);

			if ($ds) {

				$r = false;
				$res = ldap_handle_bind($ds, $r);

				//$sr = @ ldap_search($ds, "ou=$annee, ou=diploma, o=paris1, $ldap_basedn", "seeAlso=ou=$composante,ou=structures,o=Paris1,$ldap_basedn", array ('ou','description'));
				$sr = @ ldap_search($ds, "ou=$annee, ou=diploma, $ldap_basedn", "seeAlso=ou=$composante,ou=structures,$ldap_basedn", array ('ou','description'));
				//echo "Le nombre de resultats est : ".ldap_count_entries($ds,$sr)."<p>";
				echo '<br />';
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

				echo '<b>'.get_lang('ANNEE D\'INSCRIPTION').' :</b> <input type="text" name="annee" size="4" maxlength="30" value="'.$annee.'">';
				echo '<br /><br />';
				echo '<b>'.get_lang('UFR').' :</b><input type="text" name="composante" size="4" maxlength="30" value="'.$composante.'">';
				echo '<br />';
				echo '<h4>'.get_lang('SELECTIONNER VOTRE ETAPE (ANNEE PEDAGOGIQUE)').'</h4>';
				echo '<br />';

				echo '<b>'.get_lang('ETAPE').' : </b>';
				echo '<select name="etape" size="1">';
				$tempcomp = "";

				while (list ($key, $val) = each($ouetapetab)) {
					if ($ouetapetab[$key] != $tempcomp) {
						$etape = $ouetapetab2[$key];
						$tempcomp = '"'.$ouetapetab[$key].'"';

						//$tempcomp = str_replace("etape", " => ", $tempcomp);
						//$tempcomp = system('echo '.$tempcomp.' | iconv -f utf-8 -t iso-8859-1', $toto);
						$tempcomp = iconv('utf-8',api_get_setting('platform_charset'),$tempcomp);

						$annee = str_word_count($etape, 1);
						echo '<option value="'.$etape.'">'.$tempcomp;
						echo '</option>';
					}
				}
				echo '</select>';
				echo '<input type="hidden" name="displayname" value="'.$displayname.'">';

				echo '<br />';
				echo '<input type="hidden" name="id_session" value="'.$id_session.'">';
				echo '<input type="submit" name="envoi" value="'.get_lang('Submit').'">';

				echo '</form>';

				ldap_close($ds);

			} else {
				//    echo "<h4>Unable to connect to LDAP server</h4>";
			}
			echo '<br />';
			echo '<br />';
			echo '<br />';
				echo '<a href="'.api_get_self().'"?id_session='.$id_session.'&annee=&action=add&composante=&etape=">'.get_lang('RETOUR : nouvelle recherche').'</a>';
		        echo '<br />';
		        echo '</form>';
			echo '</div>';
		}
	} else {
		$id_session = intval($id_session);
		$annee = intval($annee);
		$composante = Database::escape_string($composante);
	// Lister les etapes concernant la session : table 
		// Ajout de l'etape
		if ($annee <> "" && $composante <> "" && $etape <> "") {
			$ds = ldap_connect($ldap_host, $ldap_port);
			if ($ds) {
				$r = false;
				$res = ldap_handle_bind($ds, $r);
				//$sr = @ ldap_search($ds, "ou=$annee, ou=diploma, o=paris1, $ldap_basedn", "ou=$etape", array ('ou','description'));
				$sr = @ ldap_search($ds, "$ldap_basedn", "ou=$etape", array ('ou','description'));
				//echo "Le nombre de resultats est : ".ldap_count_entries($ds,$sr)."<p>";
				$info = ldap_get_entries($ds, $sr);
				if ($info['count']>0) {
					// Ajout de l'Etape a la session
					$description=Database::escape_string(iconv('utf-8',api_get_setting('platform_charset'), $info[0]['description'][0]));
					$sql = "INSERT IGNORE INTO $tbl_session_rel_etape " .
							"(id_session,code_etape,etape_description,code_ufr,annee) " .
							"VALUES " .
							"('$id_session','".Database::escape_string($info[0]["ou"][0])."','".$description."','$composante','$annee')";
					if (api_sql_query($sql,__FILE__,__LINE__))
						Display :: display_normal_message(get_lang('ETAPE').": $annee, $composante, $description ".get_lang('A ETE AJOUTE AVEC SUCCES'));
					else
						Display :: display_error_message(get_lang('ERREUR : ETAPE EXISTANTE OU AUTRE ?'));
				} else
					Display :: display_error_message(get_lang('ETAPE NON PRESENTE DANS LDAP'));
			} else 
				Display :: display_error_message(get_lang('ERREUR CONNEXION LDAP'));
		}
		
		$sql = 'SELECT name, nbr_courses, nbr_users, nbr_classes, ' .
			   'DATE_FORMAT(date_start,"%d-%m-%Y") as date_start, ' .
			   'DATE_FORMAT(date_end,"%d-%m-%Y") as date_end, lastname, firstname, username
				FROM '.$tbl_session.'
				LEFT JOIN '.$tbl_user.'
					ON id_coach = user_id
				WHERE '.$tbl_session.'.id='.$id_session;

		$rs = api_sql_query($sql, __FILE__, __LINE__);
		$session = api_store_result($rs);
		$session = $session[0];


		if (isset($action) && ($action=="delete")) {
			$idChecked = $_GET['idChecked'];
			if(is_array($idChecked))
				$idChecked="'".implode("','",$idChecked)."'";
			$sql = "DELETE FROM $tbl_session_rel_etape " .
					"WHERE id_session='$id_session' " .
					"AND code_etape IN($idChecked)";
			api_sql_query($sql,__FILE__,__LINE__);
			if (mysql_affected_rows()>0)
				Display :: display_normal_message(get_lang('ETAPE SUPPRIMEE AVEC SUCCES'));
			else 
				Display :: display_error_message(get_lang('Erreur de suppression'));
		
		} else if (isset($action) && ($action=='deleteusers')) {
			$idChecked = $_GET['idChecked'];
			if(is_array($idChecked))
			{
				$idChecked=Database::escape_string("'".implode("','",$idChecked)."'");
			}
				
			
			$sql = 'SELECT '.$tbl_user.'.user_id, lastname, firstname, username, official_code
			FROM '.$tbl_user.'
			INNER JOIN '.$tbl_session_rel_user.'
				ON '.$tbl_user.'.user_id = '.$tbl_session_rel_user.'.id_user
				AND '.$tbl_session_rel_user.'.id_session = '.$id_session.'
			WHERE official_code IN('.$idChecked.')
			ORDER BY official_code, lastname, firstname';

			$result=api_sql_query($sql,__FILE__,__LINE__);
			$users=api_store_result($result);
			$UserList=array();
			foreach($users as $user){
				$UserList[]=$user['user_id'];
			}
			
			// On supprime toutes les relations de l'utilisateur
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
					api_sql_query("DELETE IGNORE FROM $tbl_session_rel_course_rel_user " .
							"WHERE id_user='$enreg_user'",__FILE__,__LINE__);
				}
				$sql = "SELECT COUNT(id_user) as nbUsers FROM $tbl_session_rel_course_rel_user " .
						"WHERE id_session='$id_session' AND course_code='$enreg_course'";
				$rs = api_sql_query($sql, __FILE__, __LINE__);
				list($nbr_users) = Database::fetch_array($rs);
				api_sql_query("UPDATE $tbl_session_rel_course  " .
						"SET nbr_users=$nbr_users " .
						"WHERE id_session='$id_session' " .
						"AND course_code='$enreg_course'",__FILE__,__LINE__);
			}
			foreach($UserList as $enreg_user){
					api_sql_query("DELETE IGNORE FROM $tbl_session_rel_user " .
							"WHERE id_user='$enreg_user'",__FILE__,__LINE__);
			}
			// updating number of users in the session
			$sql = "SELECT COUNT(id_user) as nbUsers FROM $tbl_session_rel_user " .
					"WHERE id_session='$id_session'";
			$rs = api_sql_query($sql, __FILE__, __LINE__);
			list($nbr_users) = Database::fetch_array($rs);
			api_sql_query("UPDATE $tbl_session SET nbr_users=$nbr_users " .
					"WHERE id='$id_session'",__FILE__,__LINE__);	
				
			foreach($UserList as $enreg_user){
				if (UserManager::can_delete_user($enreg_user))
					 UserManager::delete_user($enreg_user);
			}
			Display :: display_normal_message(get_lang('UTILISATEURS ETAPE SUPPRIME AVEC SUCCES'));
			
		}
		
		// Importing periods/steps users into the session
		if (isset($action) && ($action=="import")) {
			// id_session
			// Parse des code etape de la session
			$sql = "SELECT  id_session, code_etape, etape_description, code_ufr, annee 
				FROM $tbl_session_rel_etape
				WHERE id_session='$id_session'
				ORDER BY code_ufr, code_etape";
			$result = api_sql_query($sql);
			$ds = ldap_connect($ldap_host, $ldap_port) or die(get_lang('Impossible de se connecter au serveur LDAP'));
			// Import des utilisateurs des etapes dans la session
			if ($ds)
				$r = false;
				$res = ldap_handle_bind($ds, $r);
				$UserList=array();
				while($row = Database::fetch_array($result)){
					$annee = $row['annee'];
					$code_ufr = $row['code_ufr'];
					$etape = $row['code_etape'];
					// LDAP Querry
					// edupersonorgunitdn=ou=12CI1,ou=2006,ou=diploma,o=Paris1,dc=univ-paris1,dc=fr
					//$sr = @ ldap_search($ds, "ou=people,$ldap_basedn", "edupersonorgunitdn=ou=$etape,ou=$annee,ou=diploma,o=Paris1,$ldap_basedn");
					$sr = @ ldap_search($ds, "ou=people,$ldap_basedn", "edupersonorgunitdn=ou=$etape,ou=$annee,ou=diploma,$ldap_basedn");
					$info = ldap_get_entries($ds, $sr);

					for ($key = 0; $key < $info["count"]; $key ++) {
						$lastname = iconv('utf-8', api_get_setting('platform_charset'), $info[$key]["sn"][0]);
						$firstname = iconv('utf-8', api_get_setting('platform_charset'), $info[$key]["givenname"][0]);
						$email = $info[$key]["mail"][0];
						// Get uid from dn
						$dn_array=ldap_explode_dn($info[$key]["dn"],1);
						$username = $dn_array[0]; // uid is first key
						$outab[] = $info[$key]["edupersonprimaryaffiliation"][0]; // Ici "student"
						$val = ldap_get_values_len($ds, $entry, "userPassword");
						$password = $val[0];
						// Pour faciliter la gestion on ajoute le code "etape-annee"
						$official_code=$etape."-".$annee;
						$auth_source="cas";
						// Pas de date d'expiration d'etudiant (a recuperer par rapport au shadow expire LDAP)
						$expiration_date='0000-00-00 00:00:00';
						$active=1;
						// Ajout de l'utilisateur
						if (UserManager::is_username_available($username))
							$user_id = UserManager::create_user($firstname,$lastname,$status,$email,$username,$password,$official_code,api_get_setting('platformLanguage'),$phone,$picture_uri,$auth_source,$expiration_date,$active);
						else{
							$user = UserManager::get_user_info($username);
							$user_id=$user['user_id'];
							UserManager::update_user($user_id, $firstname, $lastname, $username, null, null, $email, $status, $official_code, $phone, $picture_uri, $expiration_date, $active);
						}
						$UserList[]=$user_id;
					}
				}
				
				// Une fois les utilisateurs importer dans la base des utilisateurs, on peux les affecter a la session
				$result=api_sql_query("SELECT course_code FROM $tbl_session_rel_course WHERE id_session='$id_session'",__FILE__,__LINE__);
				$CourseList=array();
				while($row=Database::fetch_array($result))
				{
					$CourseList[]=$row['course_code'];
				}
				foreach($CourseList as $enreg_course)
				{
					// On ajoute la relation entre l'utilisateur et le cours
					foreach($UserList as $enreg_user)
					{
						api_sql_query("INSERT IGNORE INTO $tbl_session_rel_course_rel_user(id_session,course_code,id_user) VALUES('$id_session','$enreg_course','$enreg_user')",__FILE__,__LINE__);
					}
					$sql = "SELECT COUNT(id_user) as nbUsers FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code='$enreg_course'";
					$rs = api_sql_query($sql, __FILE__, __LINE__);
					list($nbr_users) = Database::fetch_array($rs);
					api_sql_query("UPDATE $tbl_session_rel_course SET nbr_users=$nbr_users WHERE id_session='$id_session' AND course_code='$enreg_course'",__FILE__,__LINE__);
				}
				// On ajoute la relation entre l'utilisateur et la session
				foreach($UserList as $enreg_user){
					api_sql_query("INSERT IGNORE INTO $tbl_session_rel_user(id_session, id_user) VALUES('$id_session','$enreg_user')",__FILE__,__LINE__);
				}
				$sql = "SELECT COUNT(id_user) as nbUsers FROM $tbl_session_rel_user WHERE id_session='$id_session'";
				$rs = api_sql_query($sql, __FILE__, __LINE__);
				list($nbr_users) = Database::fetch_array($rs);
				api_sql_query("UPDATE $tbl_session SET nbr_users=$nbr_users WHERE id='$id_session'",__FILE__,__LINE__);
			}
		?>
		
		<!-- General properties -->
		<table class="data_table" width="100%">
		<tr>
		  <th colspan="2"><?php echo get_lang('GeneralProperties'); ?>
		  	<a href="session_edit.php?page=ldap_import_students_to_session.php&id=<?php echo $id_session; ?>">
		  	 <img src="../img/edit.gif" border="0" align="absmiddle" alt="<?php echo get_lang('Edit'); ?>">
		  	</a>
		  </th>
		</tr>
		<tr>
			<td><?php echo get_lang('SessionName');?> :</td>
			<td><?php echo $session['name'] ?></td>
		</tr>
		<tr>
			<td><?php echo get_lang('GeneralCoach'); ?> :</td>
			<td><?php echo $session['lastname'].' '.$session['firstname'].' ('.$session['username'].')' ?></td>
		</tr>
		<tr>
			<td><?php echo ('Dates'); ?> :</td>
			<td>
			<?php
				if($session['date_start']=='00-00-0000')
					echo get_lang('NoTimeLimits');
				else
					echo get_lang('From').' '.$session['date_start'].' '.get_lang('To').' '.$session['date_end'];
				 ?>
			</td>
		</tr>
		</table>
		
		<!--List des etapes -->
		<table class="data_table" width="100%">
		<tr>
		  <th colspan="4"><?php echo get_lang('Liste des etapes');?>
		  	<a href="<?php echo api_get_self(); ?>?action=add&id_session=<?php echo $id_session; ?>">
		  	  <img src="../img/group_add_big.gif" border="0" align="absmiddle" alt="<?php echo get_lang('Ajouter etape');?>">
		  	</a>
		  </th>
		</tr>
		<tr>
		  <tr>
		  <th width="20%"><?php echo get_lang('UFR');?></th>
		  <th width="20%"><?php echo get_lang('Code etape');?></th>
		  <th width="45%"><?php echo get_lang('Intitule');?></th>
		  <th width="15%"><?php echo get_lang('Actions'); ?></th>
		</tr>
		</tr>
		<?php
		
		$sql = "SELECT  id_session, code_etape, etape_description, code_ufr, annee 
				FROM $tbl_session_rel_etape
				WHERE id_session='$id_session'
				ORDER BY code_ufr, code_etape";
		$result=api_sql_query($sql,__FILE__,__LINE__);
		$etapes=api_store_result($result);
			
		if(sizeof($etapes)==0){
			echo '
				<tr>
					<td colspan="4">'.get_lang('Pas d\'etape pour cette session').'</td>
				</tr>';
		} else {
			foreach($etapes as $etape){
				echo '
				<tr>
					<td>'.$etape['code_ufr'].' ('.$etape['annee'].')</td>
					<td>'.$etape['code_etape'].'</td>
					<td>'.$etape['etape_description'].'</td>
					<td>
						<a href="'.api_get_self().'?id_session='.$id_session.'&action=delete&idChecked[]='.$etape['code_etape'].'" onclick="javascript:if(!confirm(\''.get_lang('ConfirmYourChoice').'\')) return false;">' .
							'<img src="../img/delete.gif" border="0" align="absmiddle" alt="'.get_lang('Delete').'">' .
						'</a>
						<a href="'.api_get_self().'?id_session='.$id_session.'&action=deleteusers&idChecked[]='.$etape['code_etape'].'-'.$etape['annee'].'" onclick="javascript:if(!confirm(\''.get_lang('ConfirmYourChoice').'\')) return false;">' .
							'<img src="../img/undelete.gif" border="0" align="absmiddle" title="'.get_lang('Supprimer utilisateurs etapes').'">' .
						'</a>
						</td>
				</tr>';
			}
		}
		?>
		</table>
	
		<br />
		
		<form method="get" action="<?php echo api_get_self(); ?>" onsubmit="javascript:if(!confirm('<?php echo get_lang('ConfirmYourChoice'); ?>')) return false;">
			<select name="action">
			<option value="import"><?php echo get_lang('Importer les etudiants de toutes les etapes');?></option>
			</select>
			<input type="hidden" name="id_session" value="<?php echo $id_session; ?>">
			<input type="submit" value="<?php echo get_lang('Submit'); ?>">
		</form>
		
		<br />
		<br />
		
		<!--List of users -->
		<table class="data_table" width="100%">
		<tr>
		  <th colspan="4"><?php echo get_lang('UserList')." (".$session['nbr_users'].")"; ?>
		  	<a href="add_users_to_session.php?page=resume_session.php&id_session=<?php echo $id_session; ?>"><img src="../img/edit.gif" border="0" align="absmiddle" alt="<?php echo get_lang('Edit');?>"></a>
			&nbsp;
			<a href="ldap_users_list.php?page=resume_session.php&id_session=<?php echo $id_session; ?>"><img src="../img/add_user.gif" border="0" align="absmiddle" alt="<?php echo get_lang('Ajouter ustilisateurs LDAP');?>"></a>
			</th>
		  </th>
		</tr>
		</tr>
		<?php
		if($session['nbr_users']==0){
			echo '
				<tr>
					<td colspan="2">'.get_lang('Pas d\'utilisateurs pour cette session').'</td>
				</tr>';
		}
		else {

			$sql = 'SELECT '.$tbl_user.'.user_id, lastname, firstname, username, official_code
					FROM '.$tbl_user.'
					INNER JOIN '.$tbl_session_rel_user.'
						ON '.$tbl_user.'.user_id = '.$tbl_session_rel_user.'.id_user
						AND '.$tbl_session_rel_user.'.id_session = '.$id_session.'
					ORDER BY official_code, lastname, firstname';

			$result=api_sql_query($sql,__FILE__,__LINE__);
			$users=api_store_result($result);
			foreach($users as $user){
				echo '<tr>
							<td width="10%">
								<b>'.$user['official_code'].'</b>
							</td>
							<td width="80%">
								<b>'.$user['lastname'].' '.$user['firstname'].' ('.$user['username'].')</b>
							</td>
							<td>
								<a href="../mySpace/student.php?user_id='.$user['user_id'].'">' .
									'<img src="../img/statistics.gif" border="0" style="vertical-align: middle;" title="'.get_lang('Reporting').'" alt="'.get_lang('Reporting').'"/>' .
								'</a>
							</td>
						  </tr>';
			}
		}
		echo '</table>';
	}
} 
else 
{
	$limit=20;
	$from=$page * $limit;

	$result=api_sql_query("SELECT id,name,nbr_courses,date_start,date_end FROM $tbl_session ".(empty($_POST['keyword']) ? "" : "WHERE name LIKE '%".Database::escape_string($_POST['keyword'])."%'")." ORDER BY $sort LIMIT $from,".($limit+1),__FILE__,__LINE__);

	$Sessions=api_store_result($result);

	$nbr_results=sizeof($Sessions);

	//$tool_name = "Import LDAP session";
	//$interbreadcrumb[]=array("url" => "index.php","name" => get_lang('AdministrationTools'));

	Display::display_header($tool_name);
	
	?>

	<div id="main">

	<?php

	if(isset($_GET['action'])){
		Display::display_normal_message($_GET['message']);
	}

	?>
	<form method="POST" <?php echo api_get_self(); ?>>
			<input type="text" name="keyword" value="<?php echo Security::remove_XSS($_GET['keyword']); ?>"/>
		<input type="submit" value="<?php echo get_lang('Search'); ?>"/>
		</form>

	<div align="left">

	<?php

	if(count($Sessions)==0 && isset($_POST['keyword']))
	{
		echo get_lang('NoSearchResults');
	}
	else
	{
		if($page)
		{
		?>

		<a href="<?php echo api_get_self(); ?>?page=<?php echo $page-1; ?>&sort=<?php echo $sort; ?>"><?php echo get_lang('Previous'); ?></a>

		<?php
		}
		else
		{
			echo get_lang('Previous');
		}
		?>

		|

		<?php
		if($nbr_results > $limit)
		{
		?>

		<a href="<?php echo api_get_self(); ?>?page=<?php echo $page+1; ?>&sort=<?php echo $sort; ?>"><?php echo get_lang('Next'); ?></a>

		<?php
		}
		else
		{
			echo get_lang('Next');
		}
		?>

		</div>

		<br />

		<table class="data_table" width="100%">
		<tr>
		  <th>&nbsp;</th>
		  <th><a href="<?php echo api_get_self(); ?>?sort=nbr_courses"><?php echo get_lang('NumberOfCourses');?></a></th>
		  <th><a href="<?php echo api_get_self(); ?>?sort=date_start"><?php echo get_lang('StartDate'); ?></a></th>
		  <th><a href="<?php echo api_get_self(); ?>?sort=date_end"><?php echo get_lang('EndDate'); ?></a></th>
		  <th><?php echo get_lang('Actions'); ?></th>
		</tr>

		<?php
		$i=0;

		foreach($Sessions as $key=>$enreg)
		{
			if($key == $limit)
			{
				break;
			}
			$sql = 'SELECT COUNT(course_code) FROM '.$tbl_session_rel_course.' WHERE id_session='.intval($enreg['id']);

		  	$rs = api_sql_query($sql, __FILE__, __LINE__);
		  	list($nb_courses) = Database::fetch_array($rs);

		?>

		<tr class="<?php echo $i?'row_odd':'row_even'; ?>">
		  <td><a href="resume_session.php?id_session=<?php echo $enreg['id']; ?>"><?php echo htmlentities($enreg['name']); ?></a></td>
		  <td><a href="session_course_list.php?id_session=<?php echo $enreg['id']; ?>"><?php echo $nb_courses; ?> cours</a></td>
		  <td><?php echo htmlentities($enreg['date_start']); ?></td>
		  <td><?php echo htmlentities($enreg['date_end']); ?></td>
		  <td>
			<a href="<?php echo api_get_self(); ?>?id_session=<?php echo $enreg['id']; ?>"><img src="../img/add_user_big.gif" border="0" align="absmiddle" title="Inscrire des utilisateurs � cette session"></a>
		  </td>
		</tr>

		<?php
			$i=$i ? 0 : 1;
		}

		unset($Sessions);

		?>

		</table>

		<br />

		<div align="left">

		<?php
		if($page)
		{
		?>

		<a href="<?php echo api_get_self(); ?>?page=<?php echo $page-1; ?>&sort=<?php echo $sort; ?>"><?php echo get_lang('Previous'); ?></a>

		<?php
		}
		else
		{
			echo get_lang('Previous');
		}
		?>

		|

		<?php
		if($nbr_results > $limit)
		{
		?>

		<a href="<?php echo api_get_self(); ?>?page=<?php echo $page+1; ?>&sort=<?php echo $sort; ?>"><?php echo get_lang('Next'); ?></a>

		<?php
		}
		else
		{
			echo get_lang('Next');
		}
		?>

		</div>

		<br />

<?php } ?>
	</table>

	</div>
<?php
}
Display::display_footer();
?>