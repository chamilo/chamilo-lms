<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */
/**
 * Provides a short controller for friends registration
 */
 // names of the language files that needs to be included
$language_file = array('registration','messages','userInfo','admin');
require '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'image.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
$the_current_user_id	 = api_get_user_id();
$my_current_friend		 = intval($_POST['friend_id']);
$my_denied_current_friend= intval($_POST['denied_friend_id']);
$my_delete_friend        = intval($_POST['delete_friend_id']);
$friend_id_qualify       = intval($_POST['user_id_friend_q']);
$type_friend_qualify     = Security::remove_XSS($_POST['type_friend_q']); //filtered?
$is_my_friend            = Security::remove_XSS($_POST['is_my_friend']); //filtered?
if (isset($is_my_friend)) {
	$relation_type='3';//my friend
} else {
	$relation_type='1';//Contact unknown	
}

if (isset($my_current_friend)) {
	UserFriend::register_friend ($the_current_user_id,$my_current_friend,$relation_type);
	UserFriend::register_friend ($my_current_friend,$the_current_user_id,$relation_type);	
	UserFriend::invitation_accepted ($my_current_friend,$the_current_user_id);
	if (isset($is_my_friend)) {
		echo api_xml_http_response_encode(get_lang('AddedContactToList'));
	} else {
		Display::display_normal_message(api_xml_http_response_encode(get_lang('AddedContactToList')));
	}

}
if (isset($my_denied_current_friend)) {
	UserFriend::invitation_denied($my_denied_current_friend,$the_current_user_id);
	Display::display_confirmation_message(api_xml_http_response_encode(get_lang('InvitationDenied')));	
}
if (isset($my_delete_friend)) {
	UserFriend::removed_friend($my_delete_friend);
}
if(isset($friend_id_qualify) && isset($type_friend_qualify)) {
	UserFriend::qualify_friend($friend_id_qualify,$type_friend_qualify);
	echo api_xml_http_response_encode(get_lang('AttachContactsToGroupSuccesfuly'));
}
?>