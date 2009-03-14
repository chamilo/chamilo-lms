<?php
/*
 * Created on 24/01/2009
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 // name of the language file that needs to be included
$language_file = array('registration','messages','userInfo','admin');
require '../inc/global.inc.php';
include_once (api_get_path(LIBRARY_PATH).'image.lib.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
$the_current_user_id	 = api_get_user_id();
$my_current_friend		 = $_POST['friend_id'];
$my_denied_current_friend= $_POST['denied_friend_id'];
$my_delete_friend        = $_POST['delete_friend_id'];
$friend_id_qualify       = $_POST['user_id_friend_q'];
$type_friend_qualify     = $_POST['type_friend_q'];
$is_my_friend            = $_POST['is_my_friend'];
if (isset($is_my_friend)) {
	$relation_type='3';//my friend
} else {
	$relation_type='1';//Contact unknow	
}


if (isset($my_current_friend)) {
	UserFriend::register_friend ($the_current_user_id,$my_current_friend,$relation_type);
	UserFriend::register_friend ($my_current_friend,$the_current_user_id,$relation_type);	
	UserFriend::invitation_accepted ($my_current_friend,$the_current_user_id);
	if (isset($is_my_friend)) {
		echo get_lang('AddedContactToList');
	} else {
		Display::display_normal_message(get_lang('AddedContactToList'));
	}

}
if (isset($my_denied_current_friend)) {
	UserFriend::invitation_denied($my_denied_current_friend,$the_current_user_id);
	Display::display_confirmation_message(get_lang('InvitationDenied'));	
}
if (isset($my_delete_friend)) {
	UserFriend::removed_friend($my_delete_friend);
}
if(isset($friend_id_qualify) && isset($type_friend_qualify)) {
	UserFriend::qualify_friend($friend_id_qualify,$type_friend_qualify);
	echo get_lang('AttachContactsToGroupSuccesfuly');
}
?>