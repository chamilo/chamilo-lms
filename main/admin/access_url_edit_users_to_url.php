<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2009 Dokeos SPRL
	Copyright (c) 2009 Julio Montoya Armas <gugli100@gmail.com>

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

// resetting the course id
$cidReset=true;

// including some necessary dokeos files
require('../inc/global.inc.php');

require_once (api_get_path(LIBRARY_PATH).'urlmanager.lib.php');
require_once ('../inc/lib/xajax/xajax.inc.php');
$xajax = new xajax();
//$xajax->debugOn();
$xajax -> registerFunction ('search_users');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script();
if (!$_configuration['multiple_access_urls'])
	header('Location: index.php');


// Database Table Definitions
$tbl_user				 = Database::get_main_table(TABLE_MAIN_USER);
$tbl_access_url_rel_user = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
$tbl_access_url 		 = Database :: get_main_table(TABLE_MAIN_ACCESS_URL);

// setting breadcrumbs
$tool_name = get_lang('EditUsersToURL');
$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ('url' => 'access_urls.php', 'name' => get_lang('MultipleAccessURLs'));

$add_type = 'multiple';
if(isset($_REQUEST['add_type']) && $_REQUEST['add_type']!=''){
	$add_type = Security::remove_XSS($_REQUEST['add_type']);
}

$access_url_id=1;
if(isset($_REQUEST['access_url_id']) && $_REQUEST['access_url_id']!=''){
	$access_url_id = Security::remove_XSS($_REQUEST['access_url_id']);
}

function search_users($needle, $id)
{
	global $tbl_user, $tbl_access_url_rel_user;
	$xajax_response = new XajaxResponse();
	$return = '';

	if(!empty($needle)) {
		// xajax send utf8 datas... datas in db can be non-utf8 datas
		$charset = api_get_setting('platform_charset');
		$needle = api_convert_encoding($needle, $charset, 'utf-8');
		$needle = Database::escape_string($needle);
		// search users where username or firstname or lastname begins likes $needle
		$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';
		$sql = 'SELECT u.user_id, username, lastname, firstname FROM '.$tbl_user.' u
				WHERE (username LIKE "'.$needle.'%"
				OR firstname LIKE "'.$needle.'%"
				OR lastname LIKE "'.$needle.'%")'.
				$order_clause.
				' LIMIT 11';

		$rs = api_sql_query($sql, __FILE__, __LINE__);
        $i=0;

		while ($user = Database :: fetch_array($rs)) {
			$i++;
            if ($i<=10) {
			    $return .= '<a href="javascript: void(0);" onclick="javascript: add_user_to_url(\''.addslashes($user['user_id']).'\',\''.api_get_person_name(addslashes($user['firstname']), addslashes($user['lastname'])).' ('.addslashes($user['username']).')'.'\')">'.api_get_person_name($user['firstname'], $user['lastname']).' ('.$user['username'].')</a><br />';
            } else {
            	$return .= '...<br />';
            }
		}
	}
	$xajax_response -> addAssign('ajax_list_users','innerHTML',api_utf8_encode($return));
	return $xajax_response;
}

$xajax -> processRequests();
$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '
<script type="text/javascript">
function add_user_to_url(code, content) {

	document.getElementById("user_to_add").value = "";
	document.getElementById("ajax_list_users").innerHTML = "";

	destination = document.getElementById("destination_users");
	destination.options[destination.length] = new Option(content,code);

	destination.selectedIndex = -1;
	sortOptions(destination.options);
}

function send() {

	if (document.formulaire.access_url_id.value!=0) {
		document.formulaire.form_sent.value=0;
		document.formulaire.add_type.value=\''.$add_type.'\';
		document.formulaire.submit();
	}
}

function remove_item(origin)
{
	for(var i = 0 ; i<origin.options.length ; i++) {
		if(origin.options[i].selected) {
			origin.options[i]=null;
			i = i-1;
		}
	}
}
</script>';

$form_sent=0;
$errorMsg='';
$UserList=$SessionList=array();
$users=$sessions=array();

if($_POST['form_sent']) {
	$form_sent=$_POST['form_sent'];
	$UserList=$_POST['sessionUsersList'];
	if(!is_array($UserList)) {
		$UserList=array();
	}
	if($form_sent == 1) {
		if ($access_url_id==0) {
			header('Location: access_url_edit_users_to_url.php?action=show_message&message='.get_lang('SelectURL'));
		}
		elseif(is_array($UserList) ) {
			UrlManager::update_urls_rel_user($UserList,$access_url_id);
			header('Location: access_urls.php?action=show_message&message='.get_lang('UsersWereEdited'));
		}
	}
}

Display::display_header($tool_name);

echo '<div class="actions" style="height:22px;">';
echo '<div style="float:right;">
		<a href="'.api_get_path(WEB_CODE_PATH).'admin/access_url_add_users_to_url.php">'.Display::return_icon('view_more_stats.gif',get_lang('AddUserToURL'),'').get_lang('AddUsersToURL').'</a>
	  </div><br />';
echo '</div>';

api_display_tool_title($tool_name);

if ($_GET['action'] == 'show_message')
	Display :: display_normal_message(Security::remove_XSS(stripslashes($_GET['message'])));

$nosessionUsersList = $sessionUsersList = array();
$ajax_search = $add_type == 'unique' ? true : false;

if($ajax_search) {
	$Users=UrlManager::get_url_rel_user_data($access_url_id);
	foreach($Users as $user) {
		$sessionUsersList[$user['user_id']] = $user ;
	}
} else {
	$Users=UrlManager::get_url_rel_user_data();
	foreach($Users as $user) {
		if($user['access_url_id'] == $access_url_id) {
			$sessionUsersList[$user['user_id']] = $user ;
		}
	}
	$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';
	$sql="SELECT u.user_id, lastname, firstname, username
	  	  	FROM $tbl_user u".
			$order_clause;
	$result=api_sql_query($sql,__FILE__,__LINE__);
	$Users=api_store_result($result);
	$user_list_leys = array_keys($sessionUsersList);
	foreach($Users as $user) {
		if (!in_array($user['user_id'],$user_list_leys))
			$nosessionUsersList[$user['user_id']] = $user ;
	}
}


if($add_type == 'multiple') {
	$link_add_type_unique = '<a href="'.api_get_self().'?add_type=unique&access_url_id='.$access_url_id.'">'.get_lang('SessionAddTypeUnique').'</a>';
	$link_add_type_multiple = get_lang('SessionAddTypeMultiple');
} else {
	$link_add_type_unique = get_lang('SessionAddTypeUnique');
	$link_add_type_multiple = '<a href="'.api_get_self().'?add_type=multiple&access_url_id='.$access_url_id.'">'.get_lang('SessionAddTypeMultiple').'</a>';
}

$url_list = UrlManager::get_url_data();

?>

<div style="text-align: left;">
	<?php echo $link_add_type_unique ?>&nbsp;|&nbsp;<?php echo $link_add_type_multiple ?>
</div>
<br /><br />
<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>" style="margin:0px;" <?php if($ajax_search){echo ' onsubmit="valide();"';}?> >
<?php echo get_lang('SelectUrl').' : '; ?>
<select name="access_url_id" onchange="javascript:send();">
<option value="0"> <?php echo get_lang('SelectUrl')?></option>
	<?php
	$url_selected='';
	foreach ($url_list as $url_obj) {
		$checked = '';
		if (!empty($access_url_id)) {
			if ($url_obj['id']==$access_url_id) {
			$checked = 'selected=true';
			$url_selected=	$url_obj[1];
			}
		}
		if ($url_obj['active']==1) {
	?>
		<option <?php echo $checked;?> value="<?php echo $url_obj[0]; ?>"> <?php echo $url_obj[1]; ?></option>
	<?php
		}
	}
	?>
</select>
<br /><br />
<input type="hidden" name="form_sent" value="1" />
<input type="hidden" name="add_type" value = "<?php echo $add_type ?>" />

<?php
if(!empty($errorMsg)) {
	Display::display_normal_message($errorMsg); //main API
}
?>

<table border="0" cellpadding="5" cellspacing="0" width="100%">

<!-- Users -->
<tr>
  <td align="center"><b><?php echo get_lang('UserListInPlatform') ?> :</b>
  </td>
  <td></td>
  <td align="center"><b><?php echo get_lang('UserListIn').' '.$url_selected; ?> :</b></td>
</tr>

<tr>
  <td align="center">
  <div id="content_source">
  	  <?php
  	  if($ajax_search) {
  	  	?>
		<input type="text" id="user_to_add" onkeyup="xajax_search_users(this.value,document.formulaire.access_url_id.options[document.formulaire.access_url_id.selectedIndex].value)" />
		<div id="ajax_list_users"></div>
		<?php
  	  } else {
  	  ?>
	  <select id="origin_users" name="nosessionUsersList[]" multiple="multiple" size="15" style="width:300px;">
		<?php
		foreach($nosessionUsersList as $enreg) {
		?>
			<option value="<?php echo $enreg['user_id']; ?>"><?php echo api_get_person_name($enreg['firstname'], $enreg['lastname']).' ('.$enreg['username'].')'; ?></option>

$xajax -> processRequests();

$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '
<script type="text/javascript">
function add_user_to_url (code, content) {

	document.getElementById("user_to_add").value = "";
	document.getElementById("ajax_list_users").innerHTML = "";

	destination = document.getElementById("destination_users");
	destination.options[destination.length] = new Option(content,code);

	destination.selectedIndex = -1;
	sortOptions(destination.options);

}
function remove_item(origin)
{
	for(var i = 0 ; i<origin.options.length ; i++) {
		if(origin.options[i].selected) {
			origin.options[i]=null;
			i = i-1;
		}
	}
}
</script>';
		<?php
		}
		unset($nosessionUsersList);
		?>

	  </select>
	<?php
  	  }
  	 ?>
  </div>
  </td>
  <td width="10%" valign="middle" align="center">
  <?php
  if($ajax_search) {
	?>
	<input type="button" onclick="remove_item(document.getElementById('destination_users'))" value="<<" />
  	<?php
  } else {
  	?>
	<input type="button" onclick="moveItem(document.getElementById('origin_users'), document.getElementById('destination_users'))" value=">>" />
	<br /><br />
	<input type="button" onclick="moveItem(document.getElementById('destination_users'), document.getElementById('origin_users'))" value="<<" />
	<?php
  }
  ?>
	<br /><br /><br /><br /><br /><br />
  </td>
  <td align="center">
  <select id="destination_users" name="sessionUsersList[]" multiple="multiple" size="15" style="width:300px;">

<?php
foreach($sessionUsersList as $enreg) {
?>
	<option value="<?php echo $enreg['user_id']; ?>"><?php echo api_get_person_name($enreg['firstname'], $enreg['lastname']).' ('.$enreg['username'].')'; ?></option>

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
			echo '<input type="button" value="'.get_lang('EditUsers').'" onclick="valide()" />';
		else
			echo '<input type="button" value="'.get_lang('EditUsers').'" onclick="valide()" />';
		?>
	</td>
</tr>




</table>

</form>
<script type="text/javascript">
<!--
function moveItem(origin , destination) {
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

function mysort(a, b) {
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
