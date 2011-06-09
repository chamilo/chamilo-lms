<?php //$id: $
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
*/

// name of the language file that needs to be included
$language_file=array('admin','registration','userInfo');

// resetting the course id
$cidReset=true;

// including some necessary files
require_once '../inc/global.inc.php';
require_once '../inc/lib/xajax/xajax.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array('url' => 'group_list.php','name' => get_lang('GroupList'));

// Database Table Definitions
$tbl_group			= Database::get_main_table(TABLE_MAIN_GROUP);
$tbl_user			= Database::get_main_table(TABLE_MAIN_USER);
$tbl_group_rel_user	= Database::get_main_table(TABLE_MAIN_USER_REL_GROUP);

// setting the name of the tool
$tool_name = get_lang('SubscribeUsersToGroup');
$group_id = intval($_GET['id']);

$add_type = 'multiple';
if(isset($_REQUEST['add_type']) && $_REQUEST['add_type']!=''){
	$add_type = Security::remove_XSS($_REQUEST['add_type']);
}

//checking for extra field with filter on
$xajax = new xajax();
$xajax->registerFunction('search_users');
function search_users($needle,$type,$relation_type) {
	global $tbl_user,$tbl_group_rel_user,$group_id,$_configuration;
	$xajax_response = new XajaxResponse();
	$return = $return_origin = $return_destination = '';
	$without_user_id = $without_user_id = $condition_relation = '';

	if (!empty($group_id) && !empty($relation_type)) {
		$group_id = intval($group_id);
		$relation_type = intval($relation_type);
		// get user_id from relation type and group id
		$sql = "SELECT user_id FROM $tbl_group_rel_user
				WHERE group_id = '$group_id'
				AND relation_type IN (".GROUP_USER_PERMISSION_ADMIN.",".GROUP_USER_PERMISSION_READER.",".GROUP_USER_PERMISSION_PENDING_INVITATION.",".GROUP_USER_PERMISSION_MODERATOR.", ".GROUP_USER_PERMISSION_HRM.") ";
		$res = Database::query($sql);
		$user_ids = array();
		if (Database::num_rows($res) > 0) {
			while ($row = Database::fetch_row($res)) {
				$user_ids[] = $row[0];
			}
			$without_user_id = " AND user_id NOT IN(".implode(',',$user_ids).") ";
		}

		if ($relation_type==GROUP_USER_PERMISSION_PENDING_INVITATION) {
			$condition_relation = " AND groups.relation_type IN (".GROUP_USER_PERMISSION_PENDING_INVITATION.",".GROUP_USER_PERMISSION_READER.") ";
		} else {
			$condition_relation = " AND groups.relation_type = '$relation_type' ";
		}

		// data for destination user list
		$sql = "SELECT user.user_id, user.username, user.lastname, user.firstname
				FROM $tbl_group_rel_user groups
				INNER JOIN  $tbl_user user ON user.user_id = groups.user_id
				WHERE groups.group_id = '$group_id' $condition_relation ";

		$rs_destination = Database::query($sql);
		if (Database::num_rows($rs_destination) > 0) {
			$return_destination .= '<select id="destination_users" name="sessionUsersList[]" multiple="multiple" size="15" style="width:360px;">';
			while ($row = Database::fetch_array($rs_destination)) {
				$person_name = api_get_person_name($row['firstname'], $row['lastname']);
		        $return_destination .= '<option value="'.$row['user_id'].'">'.$person_name.' ('.$row['username'].')</option>';
			}
			$return_destination .= '</select>';
		} else {
			$return_destination .= '<select id="destination_users" name="sessionUsersList[]" multiple="multiple" size="15" style="width:360px;"></select>';
		}
		$xajax_response -> addAssign('ajax_destination_list','innerHTML',api_utf8_encode($return_destination));

	} else {
		$return_destination .= '<select id="destination_users" name="sessionUsersList[]" multiple="multiple" size="15" style="width:360px;"></select>';
		$xajax_response -> addAssign('ajax_destination_list','innerHTML',api_utf8_encode($return_destination));

		if ($type == 'single') {
			$return.= '';
			$xajax_response -> addAssign('ajax_list_users_single','innerHTML',api_utf8_encode($return));
		} else {
			$return_origin .= '<select id="origin_users" name="nosessionUsersList[]" multiple="multiple" size="15" style="width:360px;"></select>';
			$xajax_response -> addAssign('ajax_origin_list_multiple','innerHTML',api_utf8_encode($return_origin));
		}
	}

	if (!empty($needle) && !empty($type)) {

		// xajax send utf8 datas... datas in db can be non-utf8 datas
		$charset = api_get_setting('platform_charset');
		$needle = Database::escape_string($needle);
		$needle = api_convert_encoding($needle, $charset, 'utf-8');
		$user_anonymous=api_get_anonymous_id();
		$tbl_user_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

		$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';
		if ($type == 'single') {
			if (!empty($group_id) && !empty($relation_type)) {
				// search users where username or firstname or lastname begins likes $needle
				$sql = "SELECT user_id, username, lastname, firstname FROM $tbl_user user
						WHERE (username LIKE '$needle%' OR firstname LIKE '$needle%' OR lastname LIKE '$needle%')
						AND user_id<>'$user_anonymous' $without_user_id $order_clause LIMIT 11";
				if ($_configuration['multiple_access_urls']) {
					$access_url_id = api_get_current_access_url_id();
					if ($access_url_id != -1) {
						$sql = "SELECT user.user_id, username, lastname, firstname FROM $tbl_user user
								INNER JOIN $tbl_user_rel_access_url url_user ON (url_user.user_id=user.user_id)
								WHERE access_url_id = '$access_url_id'  AND (username LIKE '$needle%' OR firstname LIKE '$needle%' OR lastname LIKE '$needle%')
								AND user.user_id<>'$user_anonymous' $without_user_id $order_clause LIMIT 11 ";
					}
				}
				$rs_single = Database::query($sql);
	        	$i=0;
				while ($user = Database :: fetch_array($rs_single)) {
		            $i++;
		            if ($i<=10) {
		        		$person_name = api_get_person_name($user['firstname'], $user['lastname']);
						$return .= '<a href="javascript: void(0);" onclick="javascript: add_user(\''.$user['user_id'].'\',\''.$person_name.' ('.$user['username'].')'.'\')">'.$person_name.' ('.$user['username'].')</a><br />';
		            } else {
		            	$return .= '...<br />';
		            }
				}
				$xajax_response -> addAssign('ajax_list_users_single','innerHTML',api_utf8_encode($return));
			} else {
				$xajax_response ->addAlert(get_lang('YouMustChooseARelationType'));
				$xajax_response->addClear('user_to_add', 'value');
			}

		} else {
			// multiple
			if (!empty($group_id) && !empty($relation_type)) {
				$sql = "SELECT user_id, username, lastname, firstname FROM $tbl_user user
					WHERE ".(api_sort_by_first_name() ? 'firstname' : 'lastname')." LIKE '$needle%' AND user_id<>'$user_anonymous' $without_user_id $order_clause ";
				if ($_configuration['multiple_access_urls']) {
					$access_url_id = api_get_current_access_url_id();
					if ($access_url_id != -1) {
						$sql = "SELECT user.user_id, username, lastname, firstname FROM $tbl_user user
								INNER JOIN $tbl_user_rel_access_url url_user ON (url_user.user_id=user.user_id)
								WHERE access_url_id = '$access_url_id'
								AND ".(api_sort_by_first_name() ? 'firstname' : 'lastname')." LIKE '$needle%'
								AND user.user_id<>'$user_anonymous' $without_user_id $order_clause ";
					}
				}
				$rs_multiple = Database::query($sql);
				$return_origin .= '<select id="origin_users" name="nosessionUsersList[]" multiple="multiple" size="15" style="width:360px;">';
				while ($user = Database :: fetch_array($rs_multiple)) {
					$person_name = api_get_person_name($user['firstname'], $user['lastname']);
		            $return_origin .= '<option value="'.$user['user_id'].'">'.$person_name.' ('.$user['username'].')</option>';
				}
				$return_origin .= '</select>';
				$xajax_response -> addAssign('ajax_origin_list_multiple','innerHTML',api_utf8_encode($return_origin));
			}
		}
	}
	return $xajax_response;
}

$xajax->processRequests();
$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');

$htmlHeadXtra[] = '
<script type="text/javascript">
function add_user (code, content) {

	// document.getElementById("user_to_add").value = "";
	//document.getElementById("ajax_list_users_single").innerHTML = "";

	destination = document.getElementById("destination_users");

	for (i=0;i<destination.length;i++) {
		if(destination.options[i].text == content) {
				return false;
		}
	}

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

function validate_filter() {
		document.formulaire.add_type.value = \''.$add_type.'\';
		document.formulaire.form_sent.value=0;
		document.formulaire.submit();
}
</script>';

$form_sent=0;
$errorMsg=$firstLetterUser=$firstLetterSession='';
$UserList=$SessionList=array();
$users=$sessions=array();
$noPHP_SELF=true;

$group_info = GroupPortalManager::get_group_data($group_id);
$group_name = $group_info['name'];

Display::display_header($group_name);

if($_POST['form_sent']) {

	$form_sent			= $_POST['form_sent'];
	$firstLetterUser	= $_POST['firstLetterUser'];
	$UserList			= $_POST['sessionUsersList'];
	$group_id			= intval($_POST['id']);
	$relation_type		= intval($_POST['relation']);

	if(!is_array($UserList)) {
		$UserList=array();
	}
	if ($form_sent == 1) {
		if ($relation_type == GROUP_USER_PERMISSION_PENDING_INVITATION) {
			$relations = array(GROUP_USER_PERMISSION_PENDING_INVITATION,GROUP_USER_PERMISSION_READER);
			$users_by_group = GroupPortalManager::get_users_by_group($group_id,null,$relations);
			$user_id_relation = array_keys($users_by_group);
			$user_relation_diff = array_diff($user_id_relation,$UserList);
			foreach ($user_relation_diff as $user_id) {
				GroupPortalManager::delete_user_rel_group($user_id,$group_id);
			}
		} else {
			GroupPortalManager::delete_users($group_id, $relation_type);
		}
		$result = GroupPortalManager::add_users_to_groups($UserList, array($group_id), $relation_type);
		Display :: display_confirmation_message(get_lang('UsersEdited'));
	}
}

$nosessionUsersList = $sessionUsersList = array();
$ajax_search = $add_type == 'unique' ? true : false;

$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';

if ($ajax_search) {

	// data for destination list
	if (isset($_POST['id']) && isset($_POST['relation'])) {
		// data for destination user list
		$id = intval($_POST['id']);
		$relation_type = intval($_POST['relation']);
		$condition_relation = "";

		if ($relation_type==GROUP_USER_PERMISSION_PENDING_INVITATION) {
			$condition_relation = " AND groups.relation_type IN (".GROUP_USER_PERMISSION_PENDING_INVITATION.",".GROUP_USER_PERMISSION_READER.") ";
		} else {
			$condition_relation = " AND groups.relation_type = '$relation_type' ";
		}

		$sql = "SELECT user.user_id, user.username, user.lastname, user.firstname
				FROM $tbl_group_rel_user groups
				INNER JOIN  $tbl_user user ON user.user_id = groups.user_id
				WHERE groups.group_id = '$id' $condition_relation ";
		$rs_destination = Database::query($sql);
		if (Database::num_rows($rs_destination) > 0) {
			while ($row_destination_list = Database::fetch_array($rs_destination)) {
				$sessionUsersList[$row_destination_list['user_id']] = $row_destination_list ;
			}
		}
	}

} else {

	$many_users = false;
	$sql = "SELECT count(user_id) FROM $tbl_user user
			WHERE ".(api_sort_by_first_name() ? 'firstname' : 'lastname')." LIKE '$needle%' AND user_id<>'$user_anonymous' $without_user_id ";
	if ($_configuration['multiple_access_urls']) {
		$access_url_id = api_get_current_access_url_id();
		if ($access_url_id != -1) {
			$sql = "SELECT count(user.user_id) FROM $tbl_user user
					INNER JOIN $tbl_user_rel_access_url url_user ON (url_user.user_id=user.user_id)
					WHERE access_url_id = '$access_url_id'
					AND ".(api_sort_by_first_name() ? 'firstname' : 'lastname')." LIKE '$needle%'
					AND user.user_id<>'$user_anonymous' $without_user_id ";
		}
	}
	$rs_count  = Database::query($sql);
	$row_count = Database::fetch_row($rs_count);
	if ($row_count > 2) $many_users = true;

	// data for origin list
	if (isset($_POST['id']) && isset($_POST['firstLetterUser'])) {
		$id = intval($_POST['id']);
		$needle = Database::escape_string($_POST['firstLetterUser']);
		$needle = api_convert_encoding($needle, $charset, 'utf-8');
		$user_anonymous=api_get_anonymous_id();
		// get user_id from relation type and group id
		$sql = "SELECT user_id FROM $tbl_group_rel_user
				WHERE group_id = '$id'
				AND relation_type IN (".GROUP_USER_PERMISSION_ADMIN.",".GROUP_USER_PERMISSION_READER.",".GROUP_USER_PERMISSION_PENDING_INVITATION.",".GROUP_USER_PERMISSION_MODERATOR.", ".GROUP_USER_PERMISSION_HRM.") ";
		$res = Database::query($sql);
		$user_ids = array();
		if (Database::num_rows($res) > 0) {
			while ($row = Database::fetch_row($res)) {
				$user_ids[] = $row[0];
			}
			$without_user_id = " AND user_id NOT IN(".implode(',',$user_ids).") ";
		}

		$sql = "SELECT user_id, username, lastname, firstname FROM $tbl_user user
				WHERE ".(api_sort_by_first_name() ? 'firstname' : 'lastname')." LIKE '$needle%' AND user_id<>'$user_anonymous' $without_user_id $order_clause ";
		if ($_configuration['multiple_access_urls']) {
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1) {
				$sql = "SELECT user.user_id, username, lastname, firstname FROM $tbl_user user
						INNER JOIN $tbl_user_rel_access_url url_user ON (url_user.user_id=user.user_id)
						WHERE access_url_id = '$access_url_id'
						AND ".(api_sort_by_first_name() ? 'firstname' : 'lastname')." LIKE '$needle%'
						AND user.user_id<>'$user_anonymous' $without_user_id $order_clause ";
			}
		}
		$rs_origin_list = Database::query($sql);
		while ($row_origin_list = Database::fetch_array($rs_origin_list)) {
			$nosessionUsersList[$row_origin_list['user_id']] = $row_origin_list;
		}
	}

	// data for destination list
	if (isset($_POST['id']) && isset($_POST['relation'])) {
		// data for destination user list
		$id = intval($_POST['id']);
		$relation_type = intval($_POST['relation']);

		if ($relation_type==GROUP_USER_PERMISSION_PENDING_INVITATION) {
			$condition_relation = " AND groups.relation_type IN (".GROUP_USER_PERMISSION_PENDING_INVITATION.",".GROUP_USER_PERMISSION_READER.") ";
		} else {
			$condition_relation = " AND groups.relation_type = '$relation_type' ";
		}

		$sql = "SELECT user.user_id, user.username, user.lastname, user.firstname
				FROM $tbl_group_rel_user groups
				INNER JOIN  $tbl_user user ON user.user_id = groups.user_id
				WHERE groups.group_id = '$id' $condition_relation ";
		$rs_destination = Database::query($sql);
		if (Database::num_rows($rs_destination) > 0) {
			while ($row_destination_list = Database::fetch_array($rs_destination)) {
				$sessionUsersList[$row_destination_list['user_id']] = $row_destination_list ;
			}
		}
	}
}

if ($add_type == 'multiple') {
	$link_add_type_unique = '<a href="'.api_get_self().'?id='.$group_id.'&add='.Security::remove_XSS($_GET['add']).'&add_type=unique">'.Display::return_icon('single.gif').get_lang('SessionAddTypeUnique').'</a>';
	$link_add_type_multiple = Display::return_icon('multiple.gif').get_lang('SessionAddTypeMultiple');
} else {
	$link_add_type_unique = Display::return_icon('single.gif').get_lang('SessionAddTypeUnique');
	$link_add_type_multiple = '<a href="'.api_get_self().'?id='.$group_id.'&add='.Security::remove_XSS($_GET['add']).'&add_type=multiple">'.Display::return_icon('multiple.gif').get_lang('SessionAddTypeMultiple').'</a>';
}
?>

<div class="actions">
	<?php echo $link_add_type_unique ?>&nbsp;|&nbsp;<?php echo $link_add_type_multiple ?>
</div>

<?php echo '<div class="row"><div class="form_header">'.$tool_name.' ('.$session_info['name'].')</div></div><br/>'; ?>

<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?id=<?php echo $group_id; ?><?php if(!empty($_GET['add'])) echo '&add=true' ; ?>" style="margin:0px;" <?php if($ajax_search){echo ' onsubmit="valide();"';}?>>

<?php if ($add_type=='multiple') { ?>
<select name="relation" id="relation" onchange="xajax_search_users(document.getElementById('firstLetterUser').value,'multiple',this.value)">
<?php } else { ?>
<select name="relation" id="relation" onchange="xajax_search_users(document.getElementById('user_to_add').value,'single',this.value);">
<?php } ?>
<option value=""><?php echo get_lang('ChooseRelationType')?></option>
<option value="<?php echo GROUP_USER_PERMISSION_ADMIN ?>" <?php echo ((isset($_POST['relation']) && $_POST['relation']==GROUP_USER_PERMISSION_ADMIN)?'selected=selected':'') ?> > <?php echo get_lang('Admin') ?></option>
<option value="<?php echo GROUP_USER_PERMISSION_PENDING_INVITATION ?>" <?php echo ((isset($_POST['relation']) && $_POST['relation']==GROUP_USER_PERMISSION_PENDING_INVITATION)?'selected=selected':'') ?> > <?php echo get_lang('Reader') ?></option>
<option value="<?php echo GROUP_USER_PERMISSION_MODERATOR ?>" <?php echo ((isset($_POST['relation']) && $_POST['relation']==GROUP_USER_PERMISSION_MODERATOR)?'selected=selected':'') ?> > <?php echo get_lang('Moderator') ?></option>
<option value="<?php echo GROUP_USER_PERMISSION_HRM ?>" <?php echo ((isset($_POST['relation']) && $_POST['relation']==GROUP_USER_PERMISSION_HRM)?'selected=selected':'') ?> > <?php echo get_lang('HumanResourcesManager') ?></option>
</select>

<?php
if ($add_type=='multiple') {
	if (is_array($extra_field_list)) {
		if (is_array($new_field_list) && count($new_field_list)>0 ) {
			echo '<h3>'.get_lang('FilterUsers').'</h3>';
			foreach ($new_field_list as $new_field) {
				echo $new_field['name'];
				$varname = 'field_'.$new_field['variable'];
				echo '&nbsp;<select name="'.$varname.'">';
				echo '<option value="0">--'.get_lang('Select').'--</option>';
				foreach	($new_field['data'] as $option) {
					$checked='';
					if (isset($_POST[$varname])) {
						if ($_POST[$varname]==$option[1]) {
							$checked = 'selected="true"';
						}
					}
					echo '<option value="'.$option[1].'" '.$checked.'>'.$option[1].'</option>';
				}
				echo '</select>';
				echo '&nbsp;&nbsp;';
			}
			echo '<input type="button" value="'.get_lang('Filter').'" onclick="validate_filter()" />';
			echo '<br /><br />';
		}
	}
}
?>

<input type="hidden" name="form_sent" value="1" />
<input type="hidden" name="id" value="<?php echo $group_id ?>" />
<input type="hidden" name="add_type" value="<?php echo $add_type ?>" />

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
  <td>&nbsp;</td>
  <td align="center"><b><?php echo get_lang('UserListInGroup') ?> :</b></td>
</tr>

<?php if ($add_type=='multiple') { ?>
<tr>
<td align="center">

<?php echo get_lang('FirstLetterUser'); ?> :
	<div id="firstLetter">
	     <select name="firstLetterUser" id="firstLetterUser" onchange = "xajax_search_users(this.value,'multiple',document.getElementById('relation').value)" >
	      <option value = "%"><?php echo get_lang('All') ?></option>
	      <?php
	      	$selected_letter = isset($_POST['firstLetterUser'])?$_POST['firstLetterUser']:'';
	        echo Display :: get_alphabet_options($selected_letter);
	      ?>
	     </select>
     </div>
</td>
<td align="center">&nbsp;</td>
</tr>
<?php } ?>
<tr>
  <td align="center">
  <div id="content_source">
  	  <?php
  	  if (!($add_type=='multiple')) {
  	  	?>
		<input type="text" id="user_to_add" onkeyup="xajax_search_users(this.value,'single',document.getElementById('relation').value)" />
		<div id="ajax_list_users_single"></div>
		<?php
  	  } else {
  	  ?>
  	  <div id="ajax_origin_list_multiple">
	  <select id="origin_users" name="nosessionUsersList[]" multiple="multiple" size="15" style="width:360px;">
		<?php

		if (!empty($nosessionUsersList)) {
			foreach($nosessionUsersList as $enreg) {
			?>
				<option value="<?php echo $enreg['user_id']; ?>"  > <?php echo $enreg['firstname'].' '.$enreg['lastname'].' ('.$enreg['username'].')'; ?></option>
			<?php
			}
		}
		?>
	  </select>
	  </div>
	<?php
  	  }
  	  unset($nosessionUsersList);
  	 ?>
  </div>
  </td>
  <td width="10%" valign="middle" align="center">
  <?php
  if ($ajax_search) {
  ?>
  	<button class="arrowl" type="button" onclick="remove_item(document.getElementById('destination_users'))" ></button>
  <?php
  } else {
  ?>
  	<button class="arrowr" type="button" onclick="moveItem(document.getElementById('origin_users'), document.getElementById('destination_users'))" onclick="moveItem(document.getElementById('origin_users'), document.getElementById('destination_users'))"></button>
	<br /><br />
	<button class="arrowl" type="button" onclick="moveItem(document.getElementById('destination_users'), document.getElementById('origin_users'))" onclick="moveItem(document.getElementById('destination_users'), document.getElementById('origin_users'))"></button>
	<?php
  }
  ?>
	<br /><br /><br /><br /><br />
  </td>
  <td align="center">
  <div id="ajax_destination_list">
  <select id="destination_users" name="sessionUsersList[]" multiple="multiple" size="15" style="width:360px;">
	<?php
	if (!empty($sessionUsersList)) {
		foreach($sessionUsersList as $enreg) { ?>
			<option value="<?php echo $enreg['user_id']; ?>"><?php echo $enreg['firstname'].' '.$enreg['lastname'].' ('.$enreg['username'].')'; ?></option>
	<?php }
	} unset($sessionUsersList);?>
  </select>
  </div>
  </td>
</tr>
<tr>
	<td colspan="3" align="center">
		<br />
		<?php
		echo '<button class="save" type="button" value="" onclick="valide()" >'.get_lang('SubscribeUsersToGroup').'</button>';
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

function valide() {

	var relation_select = document.getElementById('relation');
	if (relation_select && relation_select.value=="") {
		alert("<?php echo get_lang('YouMustChooseARelationType')?>");
		return false;
	} else {
		var options = document.getElementById('destination_users').options;
		for (i = 0 ; i<options.length ; i++)
			options[i].selected = true;
		document.forms.formulaire.submit();
	}
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
