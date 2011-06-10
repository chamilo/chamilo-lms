<?php
/* For licensing terms, see /license.txt */
/**
 * @author Juan Carlos Trabado herodoto@telefonica.net
 */
$language_file = array('messages','userInfo');
$cidReset=true;
require '../inc/global.inc.php';

api_block_anonymous_users();
if (api_get_setting('allow_social_tool') !='true') {
    api_not_allowed();
}

require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

$this_section = SECTION_SOCIAL;

$interbreadcrumb[]= array ('url' =>'profile.php','name' => get_lang('Social'));
$interbreadcrumb[]= array ('url' =>'#','name' => get_lang('MyFiles'));

//jquery thickbox already called from main/inc/header.inc.php

$htmlHeadXtra[] = '
<script type="text/javascript">
		
function denied_friend (element_input) {
	name_button=$(element_input).attr("id");
	name_div_id="id_"+name_button.substring(13);
	user_id=name_div_id.split("_");
	friend_user_id=user_id[1];	
	 $.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {
		$("#id_response").html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
		type: "POST",
		url: "'.api_get_path(WEB_AJAX_PATH).'social.ajax.php?a=deny_friend",
		data: "denied_friend_id="+friend_user_id,
		success: function(datos) {
		 $("div#"+name_div_id).hide("slow");
		 $("#id_response").html(datos);
		}
	});
}
function register_friend(element_input) {
    if(confirm("'.get_lang('AddToFriends').'")) {
    	name_button=$(element_input).attr("id");
    	name_div_id="id_"+name_button.substring(13);
    	user_id=name_div_id.split("_");
    	user_friend_id=user_id[1];
    	 $.ajax({
    		contentType: "application/x-www-form-urlencoded",
    		beforeSend: function(objeto) {
    		$("div#dpending_"+user_friend_id).html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
    		type: "POST",
    		url: "'.api_get_path(WEB_AJAX_PATH).'social.ajax.php?a=add_friend",
    		data: "friend_id="+user_friend_id+"&is_my_friend="+"friend",
    		success: function(datos) {  $("div#"+name_div_id).hide("slow");
    			$("form").submit()
    		}
    	});
    }
}

</script>';


Display :: display_header($tool_name, 'Groups');

// easy links
if (is_array($_GET) && count($_GET)>0) {
	foreach($_GET as $key => $value) { 
		switch ($key) {
			case 'accept':				
				$user_role = GroupPortalManager::get_user_group_role(api_get_user_id(), $value);							
				if (in_array($user_role , array(GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER,GROUP_USER_PERMISSION_PENDING_INVITATION))) {				
					GroupPortalManager::update_user_role(api_get_user_id(), $value, GROUP_USER_PERMISSION_READER);
					$show_message = get_lang('UserIsSubscribedToThisGroup');
				} elseif (in_array($user_role , array(GROUP_USER_PERMISSION_READER, GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_MODERATOR))) {
					$show_message = get_lang('UserIsAlreadySubscribedToThisGroup');
				} else {
					$show_message = get_lang('UserIsNotSubscribedToThisGroup');
				}			
			break 2;			
			case 'deny':
				// delete invitation
				GroupPortalManager::delete_user_rel_group(api_get_user_id(), $value); 
				$show_message = get_lang('GroupInvitationWasDeny');
			break 2;
		}		
	}
}

$language_variable = get_lang('PendingInvitations');
$language_comment  = get_lang('SocialInvitesComment');

echo '<div id="social-content">';
	echo '<div id="social-content-left">';	
		//this include the social menu div
		SocialManager::show_social_menu('myfiles');
	echo '</div>';

	echo '<div id="social-content-right>';
		echo '<a href=""></a>';//TODO: hack and delete this line
		echo '<br />';								
        echo '<table><tr><td><iframe name="fileManager" id="fileManager" src="'.api_get_path(WEB_PATH).'main/inc/lib/fckeditor/editor/plugins/ajaxfilemanager/ajaxfilemanager.php?editor=stand_alone" scrolling="no" noresize="noresize" frameborder="no" style="height:450px; width:710px; float:left"></iframe></td></tr></table>';
	echo '</div>';
echo '</div>';	
Display::display_footer();