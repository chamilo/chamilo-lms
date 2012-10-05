<?php
/* For licensing terms, see /license.txt */
/**
 * @author Juan Carlos Trabado herodoto@telefonica.net
 * @package chamilo.social
 */
/**
 * Initialization
 */
$language_file = array('messages','userInfo');
$cidReset=true;
require_once '../inc/global.inc.php';

api_block_anonymous_users();

if (api_get_setting('allow_social_tool') !='true') {
    api_not_allowed();
}

require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

$this_section = SECTION_SOCIAL;
$_SESSION['this_section']=$this_section;

$interbreadcrumb[]= array ('url' =>'profile.php','name' => get_lang('SocialNetwork'));
$interbreadcrumb[]= array ('url' =>'#','name' => get_lang('MyFiles'));

$htmlHeadXtra[] = '
<script>
		
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

$social_left_content = SocialManager::show_social_menu('myfiles');
if (isset($_GET['cidReq'])){	
	$actions = '<a href="'.api_get_path(WEB_CODE_PATH).'document/document.php?cidReq='.Security::remove_XSS($_GET['cidReq']).'&amp;id_session='.Security::remove_XSS($_GET['id_session']).'&amp;gidReq='.Security::remove_XSS($_GET['gidReq']).'&amp;id='.Security::remove_XSS($_GET['parent_id']).'">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('Documents').' ('.get_lang('Course').')').'</a>';	
}
$social_right_content .=  '<div class="span9">';	
$social_right_content .= '<iframe name="fileManager" id="fileManager" src="'.api_get_path(WEB_PATH).'main/inc/lib/fckeditor/editor/plugins/ajaxfilemanager/ajaxfilemanager.php?editor=stand_alone" scrolling="no" noresize="noresize" frameborder="no" style="height:450px; width:100%; float:left"></iframe>';
$social_right_content .= '</div>';


$tpl = new Template();
$tpl->assign('social_left_content', $social_left_content);
$tpl->assign('social_left_menu', $social_left_menu);
$tpl->assign('social_right_content', $social_right_content);
$social_layout = $tpl->get_template('layout/social_layout.tpl');
$content = $tpl->fetch($social_layout);
$tpl->assign('actions', $actions);
$tpl->assign('message', $show_message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
