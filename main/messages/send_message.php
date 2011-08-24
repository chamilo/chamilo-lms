<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.messages
*/
/**
 * Code
 */
$language_file = array('registration','messages','userInfo','admin');
$cidReset=true;
require_once '../inc/global.inc.php';

if (api_is_anonymous()) {
	api_not_allowed();
}

$user_id	= intval($_POST['user_id']);
$panel_id	= intval($_POST['panel_id']);

$content_message = $_POST['txt_content']; //check this is filtered on output
$subject_message = $_POST['txt_subject']; //check this is filtered on output


$user_info = array();
$user_info = api_get_user_info($user_id);

if ($panel_id == 2 || $panel_id == 4 )  {
	if (empty($content_message)) {
?>
      <div id="display_response_id" style="height:200px;">    
		<?php echo api_xml_http_response_encode(get_lang('AddPersonalMessage'));  ?> :<br /><br />				
		<textarea id="txt_area_invite" rows="5" cols="40"></textarea><br />
		 <?php echo api_xml_http_response_encode(get_lang('YouShouldWriteAMessage'));  ?><br /><br />
		<button class="save" type="button" value="<?php echo api_xml_http_response_encode(get_lang('SocialAddToFriends')); ?>" onclick="action_database_panel('4','<?php echo $user_id;?>')" >
		<?php echo api_xml_http_response_encode(get_lang('SendInvitation')) ?></button>
	</div>
<?php
	}
} elseif ($panel_id==1) {
	if (empty($content_message) || empty($subject_message)) {
?>
    <td height="20"><?php echo api_xml_http_response_encode(get_lang('To')); ?> &nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;<?php echo api_xml_http_response_encode(api_get_person_name($user_info['firstName'], $user_info['lastName'])); ?></td>
    <td height="20"><?php echo api_xml_http_response_encode(get_lang('Subject')); ?> :<br/><input id="txt_subject_id" type="text" style="width:200px;"></td>
    <td height="20"><?php echo api_xml_http_response_encode(get_lang('Message')); ?> :<br/><textarea id="txt_area_invite" rows="3" cols="25"></textarea></td>
    <td height="20"><input type="button" value="<?php echo api_xml_http_response_encode(get_lang('NewMessage')); ?>" onclick="hide_display_message()" />&nbsp;&nbsp;&nbsp; <input type="button" value="<?php echo api_xml_http_response_encode(get_lang('SendMessage')); ?>" onclick="action_database_panel('5','<?php echo $user_id;?>')" /></td>
<?php
	}
}elseif ($panel_id==3) {
?>
<dl>
	<dd><a href="javascript:void(0)" onclick="change_panel('2','<?php echo $user_id; ?>')"><?php echo api_xml_http_response_encode(get_lang('SendInviteMessage')); ?></a></dd>
	<dd><a href="javascript:void(0)" onclick="change_panel('1','<?php echo $user_id; ?>')"><?php echo api_xml_http_response_encode(get_lang('SendMessage'));?></a></dd>
</dl>
<?php
}elseif($panel_id == 5 && empty($subject_message)) {
?>
	 <div id="display_response_id" style="height:200px;">
	 <?php echo api_xml_http_response_encode(get_lang('To')); ?> :&nbsp;&nbsp;&nbsp;&nbsp;<?php echo api_xml_http_response_encode(api_get_person_name($user_info['firstName'], $user_info['lastName'])); ?>			  		 
	 <br />
	 <br /><span style="color:red">*</span><?php echo api_xml_http_response_encode(get_lang('Subject')); ?> :<br /><input id="txt_subject_id" type="text" style="width:300px;"><br/>
	 <br /><?php echo api_xml_http_response_encode(get_lang('Message')); ?> :<br /><textarea id="txt_area_invite" rows="3" cols="40"></textarea>	 
	 <?php echo Display::display_error_message(api_xml_http_response_encode(get_lang('YouShouldWriteASubject')));  ?>	 
	 <button class="save" type="button" value="<?php echo api_xml_http_response_encode(get_lang('SendMessage')); ?>" onclick="action_database_panel('5','<?php echo $user_id;?>')">
	 <?php echo api_xml_http_response_encode(get_lang('SendMessage')) ?></button>
	 </div>
<?php
}
//here we decode to utf8 because this page is called from an ajax popup
$subject_message = api_utf8_decode($subject_message);
$content_message = api_utf8_decode($content_message);

if ($panel_id==4 && !empty($content_message)) {
	if ($subject_message=='clear') {
		$subject_message=null;
	}	
	SocialManager::send_invitation_friend_user($user_id, $subject_message, $content_message);
} elseif ($panel_id==5 && !empty($subject_message) ) {
	SocialManager::send_invitation_friend_user($user_id, $subject_message, $content_message);
}