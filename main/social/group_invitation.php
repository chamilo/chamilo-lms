<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.social
 * @author Julio Montoya <gugli100@gmail.com>
 */

// name of the language file that needs to be included
$language_file=array('userInfo');

// resetting the course id
$cidReset=true;

// including some necessary dokeos files
require_once '../inc/global.inc.php';
require_once '../inc/lib/xajax/xajax.inc.php';

api_block_anonymous_users();

$xajax = new xajax();
//$xajax->debugOn();
$xajax -> registerFunction ('search_users');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// setting breadcrumbs
$this_section = SECTION_SOCIAL;

$interbreadcrumb[]= array ('url' =>'home.php','name' => get_lang('Social'));
$interbreadcrumb[]= array ('url' =>'groups.php','name' => get_lang('Groups'));

// Database Table Definitions
$tbl_group			= Database::get_main_table(TABLE_MAIN_GROUP);
$tbl_user			= Database::get_main_table(TABLE_MAIN_USER);
$tbl_group_rel_user	= Database::get_main_table(TABLE_MAIN_USER_REL_GROUP);

// setting the name of the tool
$tool_name = get_lang('SubscribeUsersToGroup');
$group_id = intval($_REQUEST['id']);

$add_type = 'multiple';
if(isset($_REQUEST['add_type']) && $_REQUEST['add_type']!=''){
	$add_type = Security::remove_XSS($_REQUEST['add_type']);
}

//checking for extra field with filter on
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';

//todo @this validation could be in a function in group_portal_manager
if (empty($group_id)) {
	api_not_allowed();
} else {
	$group_info = GroupPortalManager::get_group_data($group_id);
	if (empty($group_info)) {
		api_not_allowed();
	}
	//only admin or moderator can do that
	if (!GroupPortalManager::is_group_member($group_id)) {
		api_not_allowed();
	}
}

function search_users($needle,$type) {
	global $tbl_user,$tbl_group_rel_user,$group_id;
	$xajax_response = new XajaxResponse();
	$return = '';

	if (!empty($needle) && !empty($type)) {

		// xajax send utf8 datas... datas in db can be non-utf8 datas
		$charset = api_get_system_encoding();
		$needle = Database::escape_string($needle);
		$needle = api_convert_encoding($needle, $charset, 'utf-8');
		$user_anonymous=api_get_anonymous_id();

		$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';
		$cond_user_id = '';
		if (!empty($id_session)) {
		$group_id = Database::escape_string($group_id);
			// check id_user from session_rel_user table
			$sql = 'SELECT id_user FROM '.$tbl_group_rel_user.' WHERE group_id ="'.(int)$group_id.'"';
			$res = Database::query($sql);
			$user_ids = array();
			if (Database::num_rows($res) > 0) {
				while ($row = Database::fetch_row($res)) {
					$user_ids[] = (int)$row[0];
				}
			}
			if (count($user_ids) > 0){
				$cond_user_id = ' AND user_id NOT IN('.implode(",",$user_ids).')';
			}
		}

		if ($type == 'single') {
			// search users where username or firstname or lastname begins likes $needle
			$sql = 'SELECT user_id, username, lastname, firstname FROM '.$tbl_user.' user
					WHERE (username LIKE "'.$needle.'%"
					OR firstname LIKE "'.$needle.'%"
				OR lastname LIKE "'.$needle.'%") AND user_id<>"'.$user_anonymous.'"'.
				$order_clause.
				' LIMIT 11';
		} else {
			$sql = 'SELECT user_id, username, lastname, firstname FROM '.$tbl_user.' user
					WHERE '.(api_sort_by_first_name() ? 'firstname' : 'lastname').' LIKE "'.$needle.'%" AND user_id<>"'.$user_anonymous.'"'.$cond_user_id.
					$order_clause;
		}

		global $_configuration;
		if ($_configuration['multiple_access_urls']) {
			$tbl_user_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1){
				if ($type == 'single') {
					$sql = 'SELECT user.user_id, username, lastname, firstname FROM '.$tbl_user.' user
					INNER JOIN '.$tbl_user_rel_access_url.' url_user ON (url_user.user_id=user.user_id)
					WHERE access_url_id = '.$access_url_id.'  AND (username LIKE "'.$needle.'%"
					OR firstname LIKE "'.$needle.'%"
					OR lastname LIKE "'.$needle.'%") AND user.user_id<>"'.$user_anonymous.'"'.
					$order_clause.
					' LIMIT 11';
				} else {
					$sql = 'SELECT user.user_id, username, lastname, firstname FROM '.$tbl_user.' user
					INNER JOIN '.$tbl_user_rel_access_url.' url_user ON (url_user.user_id=user.user_id)
					WHERE access_url_id = '.$access_url_id.'
					AND '.(api_sort_by_first_name() ? 'firstname' : 'lastname').' LIKE "'.$needle.'%" AND user.user_id<>"'.$user_anonymous.'"'.$cond_user_id.
					$order_clause;
				}

			}
		}

		$rs = Database::query($sql);
        $i=0;
		if ($type=='single') {
			while ($user = Database :: fetch_array($rs)) {
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
			global $nosessionUsersList;
			$return .= '<select id="origin_users" name="nosessionUsersList[]" multiple="multiple" size="15" style="width:360px;">';
			while ($user = Database :: fetch_array($rs)) {
				$person_name = api_get_person_name($user['firstname'], $user['lastname']);
	            $return .= '<option value="'.$user['user_id'].'">'.$person_name.' ('.$user['username'].')</option>';
			}
			$return .= '</select>';
			$xajax_response -> addAssign('ajax_list_users_multiple','innerHTML',api_utf8_encode($return));
		}
	}

	return $xajax_response;
}

$xajax -> processRequests();

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
function remove_item(origin) {
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

Display :: display_header($tool_name, 'Groups');

if($_POST['form_sent']) {
	$form_sent			= $_POST['form_sent'];
	$firstLetterUser	= $_POST['firstLetterUser'];
	$firstLetterSession	= $_POST['firstLetterSession'];
	$user_list			= $_POST['sessionUsersList'];

	$group_id			= intval($_POST['id']);

	if(!is_array($user_list)) {
		$user_list=array();
	}
	if ($form_sent == 1) {
		//invite this users
		$result = GroupPortalManager::add_users_to_groups($user_list, array($group_id), GROUP_USER_PERMISSION_PENDING_INVITATION);
		$title = get_lang('YouAreInvitedToGroup').' '.$group_info['name'];
		$content = get_lang('YouAreInvitedToGroupContent').' '.$group_info['name'].' <br />';
		$content .= get_lang('ToSubscribeClickInTheLinkBelow').' <br />';
		$content .= '<a href="'.api_get_path(WEB_CODE_PATH).'social/invitations.php?accept='.$group_id.'">'.get_lang('Subscribe').'</a>';

		if (is_array($user_list) && count($user_list) > 0) {
			//send invitation message
			foreach($user_list as $user_id ){
				$result = MessageManager::send_message($user_id, $title, $content);
			}
		}
	}

}

$nosessionUsersList = $sessionUsersList = array();
$ajax_search = $add_type == 'unique' ? true : false;
global $_configuration;
$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';

if ($ajax_search) {
	$sql="SELECT  u.user_id, lastname, firstname, username, group_id
				FROM $tbl_user u
				LEFT JOIN $tbl_group_rel_user gu
				ON (gu.user_id = u.user_id) WHERE gu.group_id = $group_id ".
			$order_clause;

	if ($_configuration['multiple_access_urls']) {
		$tbl_user_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
		$access_url_id = api_get_current_access_url_id();
		if ($access_url_id != -1){
			$sql="SELECT u.user_id, lastname, firstname, username, id_session
			FROM $tbl_user u
			INNER JOIN $tbl_session_rel_user
				ON $tbl_session_rel_user.id_user = u.user_id
				AND $tbl_session_rel_user.id_session = ".intval($id_session)."
				INNER JOIN $tbl_user_rel_access_url url_user ON (url_user.user_id=u.user_id)
				WHERE access_url_id = $access_url_id
				$order_clause";
		}
	}
	$result=Database::query($sql);
	$Users=Database::store_result($result);
	foreach ($Users as $user) {
		$sessionUsersList[$user['user_id']] = $user ;
	}
} else {
		$friends = SocialManager::get_friends(api_get_user_id());

		$suggest_friends = false;

		if (!$friends) {
			$suggest_friends = true;
		} else {
			foreach($friends as $friend) {
				$group_friend_list = GroupPortalManager::get_groups_by_user($friend['friend_user_id'], 0);
				//var_dump($group_friend_list);
				$friend_group_id = '';
				if (isset($group_friend_list[$group_id]) && $group_friend_list[$group_id]['id'] == $group_id) {
					$friend_group_id = $group_id;
				}
				//var_dump ($group_friend_list[$group_id]['relation_type']);
				if ($group_friend_list[$group_id]['relation_type'] == '' ) {
					$Users[$friend['friend_user_id']]=array('user_id' => $friend['friend_user_id'],  'firstname' =>$friend['firstName'], 'lastname' => $friend['lastName'], 'username' =>$friend['username'],'group_id'=>$friend_group_id );
				}
			}
		}
		if (is_array($Users) && count($Users) > 0 ) {
			foreach ($Users as $user) {
				if($user['group_id'] != $group_id)
					$nosessionUsersList[$user['user_id']] = $user ;
			}
		}

		//deleting anonymous users
		$user_anonymous = api_get_anonymous_id();
		foreach($nosessionUsersList as $key_user_list =>$value_user_list) {
			if ($nosessionUsersList[$key_user_list]['user_id']==$user_anonymous) {
				unset($nosessionUsersList[$key_user_list]);
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
	/* <?php $link_add_type_unique ?>&nbsp;|&nbsp;<?php $link_add_type_multiple ?> */

	//Shows left column
	//echo GroupPortalManager::show_group_column_information($group_id, api_get_user_id());

	echo '<div id="social-content">';
		echo '<div id="social-content-left">';
			//this include the social menu div
			SocialManager::show_social_menu('invite_friends',$group_id);
		echo '</div>';

	echo '<div id="social-content-right">';	
    echo '<h1><a href="groups.php?id='.$group_id.'">'.$group_info['name'].'</a></h1>';
    
	if (count($nosessionUsersList) == 0) {
			$friends = SocialManager::get_friends(api_get_user_id());
			if ($friends == 0) {
				echo get_lang('YouNeedToHaveFriendsInYourSocialNetwork');
			} else {
				echo get_lang('YouAlreadyInviteAllYourContacts');
			}
			echo '<div>';
			echo '<a href="search.php">'.get_lang('TryAndFindSomeFriends').'</a>';
			echo '</div>';

			echo '</div>'; // end layout right
		echo '</div>'; //
		Display::display_footer();
		exit;
	}
?>

<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?id=<?php echo $group_id; ?><?php if(!empty($_GET['add'])) echo '&add=true' ; ?>" style="margin:0px;" <?php if($ajax_search){echo ' onsubmit="valide();"';}?>>

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
<input type="hidden" name="id" value="<?php echo $group_id?>" />
<input type="hidden" name="add_type"  />

<?php
if(!empty($errorMsg)) {
	Display::display_error_message($errorMsg); //main API
}
	
?>

<table border="0" cellpadding="5" cellspacing="0" width="100%">
<!-- Users -->
<tr>
  <td align="center"><b><?php echo get_lang('Friends') ?> :</b>
  </td>
  <td></td>
  <td align="center"><b><?php echo get_lang('SendInvitationTo') ?> :</b></td>
</tr>

<?php if ($add_type=='no') { ?>
<tr>
<td align="center">

<?php echo get_lang('FirstLetterUser'); ?> :
     <select name="firstLetterUser" onchange = "xajax_search_users(this.value,'multiple')" >
      <option value = "%">--</option>
      <?php
        echo Display :: get_alphabet_options();
      ?>
     </select>
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
		<input type="text" id="user_to_add" onkeyup="xajax_search_users(this.value,'single')" />
		<div id="ajax_list_users_single"></div>
		<?php
  	  } else {  	      
  	  ?>
  	  <div id="ajax_list_users_multiple">
	  <select id="origin_users" name="nosessionUsersList[]" multiple="multiple" size="15" style="width:290px;">
		<?php
		foreach($nosessionUsersList as $enreg) { 
		?>
			<option value="<?php echo $enreg['user_id']; ?>" <?php if(in_array($enreg['user_id'],$UserList)) echo 'selected="selected"'; ?>><?php echo api_get_person_name($enreg['firstname'], $enreg['lastname']).' ('.$enreg['username'].')'; ?></option>
		<?php
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
  	<button class="arrowr" type="button" onclick="moveItem(document.getElementById('origin_users'), document.getElementById('destination_users'))" ></button>
	<br /><br />
	<button class="arrowl" type="button" onclick="moveItem(document.getElementById('destination_users'), document.getElementById('origin_users'))" ></button>
	<br /><br />
  	<?php
  }  
  ?>
	<br /><br /><br /><br /><br />
  </td>
  <td align="center">
  <select id="destination_users" name="sessionUsersList[]" multiple="multiple" size="15" style="width:290px;">

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
		echo '<button class="save" type="button" value="" onclick="valide()" >'.get_lang('InviteUsersToGroup').'</button>';
		?>
	</td>
</tr>
</table>
</form>
<?php

//current group members
$members = GroupPortalManager::get_users_by_group($group_id, false, array(GROUP_USER_PERMISSION_PENDING_INVITATION));
if (is_array($members) && count($members)>0) {
	foreach ($members as &$member) {
		$image_path = UserManager::get_user_picture_path_by_id($member['user_id'], 'web', false, true);
		$picture = UserManager::get_picture_user($member['user_id'], $image_path['file'],80);
		$member['image'] = '<img src="'.$picture['file'].'"  width="50px" height="50px"  />';
	}
	echo '<span class="social-groups-text1"><strong>'.get_lang('UsersAlreadyInvited').'</strong></span>';
	Display::display_sortable_grid('invitation_profile', array(), $members, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, false, true,true));
}		
	echo '</div>'; // end layout right
echo '</div>';
?>

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

function makepost(select) {
	var options = select.options;
	var ret = "";
	for (i = 0 ; i<options.length ; i++)
		ret = ret + options[i].value +'::'+options[i].text+";;";
	return ret;

}
-->
</script>
<?php
/* 		FOOTER */
Display::display_footer();