<?php
/* For licensing terms, see /dokeos_license.txt */

$language_file = array('registration','messages','userInfo','admin');
$cidReset=true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'message.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';

if (api_is_anonymous()) {
	api_not_allowed();
}

$user_id	= intval($_POST['user_id']);
$panel_id	= intval($_POST['panel_id']);

$content_message = Security::remove_XSS($_POST['txt_content'],COURSEMANAGERLOWSECURITY); //check this is filtered on output
$subject_message = Security::remove_XSS($_POST['txt_subject']); //check this is filtered on output
$user_info = array();
$user_info = api_get_user_info($user_id);

if ($panel_id==2) {
?>
    <td height="20"><?php //echo api_xml_http_response_encode(get_lang('Info')).' :'; ?></td>
    <td height="20"><?php //echo api_xml_http_response_encode(get_lang('SocialUserInformationAttach')); ?></td>
    <td height="20"><?php echo api_xml_http_response_encode(get_lang('WriteAMessage'));  ?> :<br/><textarea id="txt_area_invite" rows="3" cols="25"></textarea></td>
    <td height="20"><input type="button" value="<?php echo api_xml_http_response_encode(get_lang('SendInviteMessage')); ?>" onclick="action_database_panel('4','<?php echo $user_id;?>')" /></td>
<?php
}
if ($panel_id==1) {
?>
    <td height="20"><?php echo api_xml_http_response_encode(get_lang('To')); ?> &nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;<?php echo api_xml_http_response_encode(api_get_person_name($user_info['firstName'], $user_info['lastName'])); ?></td>
    <td height="20"><?php echo api_xml_http_response_encode(get_lang('Subject')); ?> :<br/><input id="txt_subject_id" type="text" style="width:200px;"></td>
    <td height="20"><?php echo api_xml_http_response_encode(get_lang('Message')); ?> :<br/><textarea id="txt_area_invite" rows="3" cols="25"></textarea></td>
    <td height="20"><input type="button" value="<?php echo api_xml_http_response_encode(get_lang('NewMessage')); ?>" onclick="hide_display_message()" />&nbsp;&nbsp;&nbsp; <input type="button" value="<?php echo api_xml_http_response_encode(get_lang('SendMessage')); ?>" onclick="action_database_panel('5','<?php echo $user_id;?>')" /></td>
<?php
}
if ($panel_id==3) {
?>
<dl>
	<dd><a href="javascript:void(0)" onclick="change_panel('2','<?php echo $user_id; ?>')"><?php echo api_xml_http_response_encode(get_lang('SendInviteMessage')); ?></a></dd>
	<dd><a href="javascript:void(0)" onclick="change_panel('1','<?php echo $user_id; ?>')"><?php echo api_xml_http_response_encode(get_lang('SendMessage'));?></a></dd>
</dl>
<?php
//	<dd><a href="'.api_get_path(WEB_PATH).'main/social/index.php#remote-tab-5"> echo api_xml_http_response_encode(get_lang('SocialSeeContacts'));</a></dd>
}

if ($panel_id==4) {
	if ($subject_message=='clear') {
		$subject_message=null;
	}
	SocialManager::send_invitation_friend_user($user_id,$subject_message,$content_message);
} elseif ($panel_id==5) {
	SocialManager::send_invitation_friend_user($user_id,$subject_message,$content_message);
}
?>
