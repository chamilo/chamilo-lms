<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 University of Ghent (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	@package dokeos.admin
==============================================================================
*/

$langFile='admin';

$cidReset=true;

require('../inc/global.inc.php');

api_protect_admin_script();

$id_session=intval($_GET['id_session']);

$formSent=0;
$errorMsg=$firstLetterUser=$firstLetterSession='';
$UserList=$SessionList=array();
$users=$sessions=array();

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

$noPHP_SELF=true;

$tool_name=get_lang('SubscribeUsersToSession');

$interbreadcrumb[]=array("url" => "index.php","name" => get_lang('AdministrationTools'));
$interbreadcrumb[]=array("url" => "session_list.php","name" => "Liste des sessions");

if($_POST['formSent'])
{
	$formSent=$_POST['formSent'];
	$firstLetterUser=$_POST['firstLetterUser'];
	$firstLetterSession=$_POST['firstLetterSession'];
	$UserList=$_POST['sessionUsersList'];
	$ClassList=$_POST['sessionClassesList'];
	if(!is_array($UserList))
	{
		$UserList=array();
	}

	if($formSent == 1)
	{
		$result = api_sql_query("SELECT id_user FROM $tbl_session_rel_user WHERE id_session='$id_session'");
		$existingUsers = array();
		while($row = mysql_fetch_array($result)){
			$existingUsers[] = $row['id_user'];
		}
		
		$result=api_sql_query("SELECT course_code FROM $tbl_session_rel_course WHERE id_session='$id_session'",__FILE__,__LINE__);

		$CourseList=array();

		while($row=mysql_fetch_array($result))
		{
			$CourseList[]=$row['course_code'];
		}
		
		foreach($CourseList as $enreg_course)
		{
			$nbr_users=0;
			foreach($UserList as $enreg_user)
			{
				if(!in_array($enreg_user, $existingUsers)){
					api_sql_query("INSERT IGNORE INTO $tbl_session_rel_course_rel_user(id_session,course_code,id_user) VALUES('$id_session','$enreg_course','$enreg_user')",__FILE__,__LINE__);
					
					if(mysql_affected_rows())
					{
						$nbr_users++;
					}
				}
			}
			foreach($existingUsers as $existing_user){
				if(!in_array($existing_user, $UserList)){
					$sql = "DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code='$enreg_course' AND id_user='$existing_user'";
					api_sql_query($sql);
					
					if(mysql_affected_rows())
					{
						$nbr_users--;
					}
				}
			}
			$sql = "SELECT COUNT(id_user) as nbUsers FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code='$enreg_course'";
			$rs = api_sql_query($sql, __FILE__, __LINE__);
			list($nbr_users) = mysql_fetch_array($rs);
			api_sql_query("UPDATE $tbl_session_rel_course SET nbr_users=$nbr_users WHERE id_session='$id_session' AND course_code='$enreg_course'",__FILE__,__LINE__);
			
		}
		
		api_sql_query("DELETE FROM $tbl_session_rel_user WHERE id_session = $id_session");
		$nbr_users = 0;
		foreach($UserList as $enreg_user){
			$nbr_users++;
			api_sql_query("INSERT IGNORE INTO $tbl_session_rel_user(id_session, id_user) VALUES('$id_session','$enreg_user')",__FILE__,__LINE__);
			
		}
		$nbr_users = count($UserList);
		api_sql_query("UPDATE $tbl_session SET nbr_users= $nbr_users WHERE id='$id_session' ",__FILE__,__LINE__);
		
		//if(empty($_GET['add']))
			//header('Location: '.$_GET['page'].'?id_session='.$id_session);
		//else
		header('Location: resume_session.php?id_session='.$id_session);
			
	}
}

Display::display_header($tool_name);

api_display_tool_title($tool_name);

$nosessionUsersList = $sessionUsersList = $nosessionClassesList = $sessionClassesList = array();

//classes
$sql="SELECT id, name FROM $tbl_class ORDER BY name";
$result=api_sql_query($sql,__FILE__,__LINE__);

$Classes=api_store_result($result);
foreach($Classes as $classe)
	if($classe['id_session'] == $id_session)
		$sessionClassesList[$classe['id']] = $classe ;
				
foreach($Classes as $classe)
	if(empty($sessionClassesList[$classe['user_id']]) && empty($nosessionClassesList[$classe['user_id']]))
		$nosessionClassesList[$classe['id']] = $classe ;


//users
$sql="SELECT user_id, lastname, firstname, username, id_session
		FROM $tbl_user 
		LEFT JOIN $tbl_session_rel_user
			ON $tbl_session_rel_user.id_user = $tbl_user.user_id
		ORDER BY lastname,firstname,username";

$result=api_sql_query($sql,__FILE__,__LINE__);

$Users=api_store_result($result);

foreach($Users as $user)
	if($user['id_session'] == $id_session) 
		$sessionUsersList[$user['user_id']] = $user ;
				
foreach($Users as $user)
	if(empty($sessionUsersList[$user['user_id']]) && empty($nosessionUsersList[$user['user_id']]))
		$nosessionUsersList[$user['user_id']] = $user ;
		

	


?>

<form name="formulaire" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo $_GET['page'] ?>&id_session=<?php echo $id_session; ?><?php if(!empty($_GET['add'])) echo '&add=true' ; ?>" style="margin:0px;">
<input type="hidden" name="formSent" value="1" />

<?php
if(!empty($errorMsg))
{
	Display::display_normal_message($errorMsg); //main API
}
?>

<table border="0" cellpadding="5" cellspacing="0" width="100%">


<!-- Users -->
<tr>
  <td align="center"><b><?php echo get_lang('UserListInPlatform') ?> :</b>
  </td>
  <td></td>
  <td align="center"><b><?php echo get_lang('UserListInSession') ?> :</b></td>
</tr>

<tr>
  <td align="center">
  <div id="content_source">
	  <select id="origin_users" name="nosessionUsersList[]" multiple="multiple" size="15" style="width:300px;">
	
		<?php
		foreach($nosessionUsersList as $enreg)
		{
		?>
		
			<option value="<?php echo $enreg['user_id']; ?>"><?php echo $enreg['lastname'].' '.$enreg['firstname'].' ('.$enreg['username'].')'; ?></option>
		
		<?php
		}
		
		unset($nosessionUsersList);
		?>
	
	  </select>
  </div>
  </td>
  <td width="10%" valign="middle" align="center">
	<input type="button" onclick="moveItem(document.getElementById('origin_users'), document.getElementById('destination_users'))" value=">>" />
	<br /><br />
	<input type="button" onclick="moveItem(document.getElementById('destination_users'), document.getElementById('origin_users'))" value="<<" />
	<br /><br /><br /><br /><br /><br />
  </td>
  <td align="center">
  <select id="destination_users" name="sessionUsersList[]" multiple="multiple" size="15" style="width:300px;">

<?php
foreach($sessionUsersList as $enreg)
{
?>

	<option value="<?php echo $enreg['user_id']; ?>"><?php echo $enreg['lastname'].' '.$enreg['firstname'].' ('.$enreg['username'].')'; ?></option>

<?php
}

unset($sessionUsersList);
?>

  </select></td>
</tr>

<tr>
	<td colspan="3" align="center">
		<br />
		<?php
		if(isset($_GET['add']))
			echo '<input type="button" value="'.get_lang("FinishSessionCreation").'" onclick="valide()" />';
		else	
			echo '<input type="button" value="'.get_lang('Ok').'" onclick="valide()" />';
		?>
	</td>
</tr>




</table>

</form>
<script type="text/javascript">
<!--
function moveItem(origin , destination){
	
	for(var i = 0 ; i<origin.options.length ; i++) {
		if(origin.options[i].selected) {
			destination.options[destination.length] = new Option(origin.options[i].text,origin.options[i].value);
			origin.options[i]=null;	
			i = i-1;
		}
	}
	destination.selectedIndex = -1;
	sortOptions(destination.options);
	
}

function sortOptions(options) { 

	newOptions = new Array();
	for (i = 0 ; i<options.length ; i++)
		newOptions[i] = options[i];
		
	newOptions = newOptions.sort(mysort);	
	options.length = 0;
	for(i = 0 ; i < newOptions.length ; i++)
		options[i] = newOptions[i];
	
}

function mysort(a, b){
	if(a.text.toLowerCase() > b.text.toLowerCase()){
		return 1;
	}
	if(a.text.toLowerCase() < b.text.toLowerCase()){
		return -1;
	}
	return 0;
}

function valide(){
	var options = document.getElementById('destination_users').options;
	for (i = 0 ; i<options.length ; i++)
		options[i].selected = true;
	/*
	var options = document.getElementById('destination_classes').options;
	for (i = 0 ; i<options.length ; i++)
		options[i].selected = true;
		*/
	document.forms.formulaire.submit();
}


function loadUsersInSelect(select){
	
	var xhr_object = null; 
	
	if(window.XMLHttpRequest) // Firefox 
		xhr_object = new XMLHttpRequest(); 
	else if(window.ActiveXObject) // Internet Explorer 
		xhr_object = new ActiveXObject("Microsoft.XMLHTTP"); 
	else  // XMLHttpRequest non supporté par le navigateur 
	alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest..."); 
	
	//xhr_object.open("GET", "loadUsersInSelect.ajax.php?id_session=<?php echo $id_session ?>&letter="+select.options[select.selectedIndex].text, false); 
	xhr_object.open("POST", "loadUsersInSelect.ajax.php");
	 		
	xhr_object.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	
	
	nosessionUsers = makepost(document.getElementById('origin_users'));
	sessionUsers = makepost(document.getElementById('destination_users'));
	nosessionClasses = makepost(document.getElementById('origin_classes'));
	sessionClasses = makepost(document.getElementById('destination_classes'));
	xhr_object.send("nosessionusers="+nosessionUsers+"&sessionusers="+sessionUsers+"&nosessionclasses="+nosessionClasses+"&sessionclasses="+sessionClasses);
	
	xhr_object.onreadystatechange = function() { 
		if(xhr_object.readyState == 4) {
			document.getElementById('content_source').innerHTML = result = xhr_object.responseText;		
			//alert(xhr_object.responseText);
		}
	} 
}

function makepost(select){

	var options = select.options;
	var ret = "";
	for (i = 0 ; i<options.length ; i++)
		ret = ret + options[i].value +'::'+options[i].text+";;";
	
	return ret;

}
-->

</script>
<?php
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>
