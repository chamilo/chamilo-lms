<?php //$id: $
/* For licensing terms, see /dokeos_license.txt */
$language_file = array('registration','messages','userInfo','admin');
$cidReset=true;
require_once '../inc/global.inc.php';
require_once '../messages/message.class.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'message.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
if (api_is_anonymous()) {
	api_not_allowed();
}

if (api_get_setting('allow_message_tool')<>'true' && api_get_setting('allow_social_tool')<>'true'){
	api_not_allowed();
}

if ( isset($_REQUEST['user_friend']) ) {
	$info_user_friend=array();
	$info_path_friend=array();
 	$userfriend_id=Security::remove_XSS($_REQUEST['user_friend']);
 	// panel=1  send message
 	// panel=2  send invitation
 	$panel=Security::remove_XSS($_REQUEST['view_panel']);
 	$info_user_friend=api_get_user_info($userfriend_id);
 	$info_path_friend=UserManager::get_user_picture_path_by_id($userfriend_id,'web',false,true);
}

?>
<table width="600" border="0" height="220">
    <tr height="180">
        <td>
        <div class="message-content-body-left">
			<img class="message-image-info" src="<?php echo $info_path_friend['dir'].$info_path_friend['file']; ?>"/>
			<?php
			if ($panel != 1) {
				echo '<br /><center>'.api_xml_http_response_encode(api_get_person_name($info_user_friend['firstName'], $info_user_friend['lastName'])).'</center>';
			}
			?>
		</div>

<div class="message-content-body-right">
<div id="id_content_panel_init">
			<dl>
<?php
		if (api_get_setting('allow_message_tool')=='true') {
			if ($panel == 1) {
                //normal message
		   		 $user_info=api_get_user_info($userfriend_id);
		  		 echo api_xml_http_response_encode(get_lang('To')); ?> :&nbsp;&nbsp;&nbsp;&nbsp;<?php echo api_xml_http_response_encode(api_get_person_name($user_info['firstName'], $user_info['lastName'])); ?>
		  		 <br />
		 		 <br /><?php echo api_xml_http_response_encode(get_lang('Subject')); ?> :<br /><input id="txt_subject_id" type="text" style="width:300px;"><br/>
		   		 <br /><?php echo api_xml_http_response_encode(get_lang('Message')); ?> :<br /><textarea id="txt_area_invite" rows="4" cols="41"></textarea>
		   		 <br /><br />
		   		 <button class="save" type="button" value="<?php echo api_xml_http_response_encode(get_lang('SendMessage')); ?>" onclick="action_database_panel('5','<?php echo $userfriend_id;?>')"><?php echo api_xml_http_response_encode(get_lang('SendMessage')) ?></button>
<?php
			} else {
                // friend invitation message
				echo api_xml_http_response_encode(get_lang('AddPersonalMessage'));  ?> :<br /><br />
				<textarea id="txt_area_invite" rows="5" cols="41"></textarea><br /><br />
 				<button class="save" type="button" value="<?php echo api_xml_http_response_encode(get_lang('SocialAddToFriends')); ?>" onclick="action_database_panel('4','<?php echo $userfriend_id;?>')" ><?php echo api_xml_http_response_encode(get_lang('SocialAddToFriends')) ?></button>
<?php
				}
			}
?>
			</dl>
</div>
        </td>
    </tr>
        </div>
    <tr>
        <td>
			<div><div id="display_response_id" style="position:relative"></div></div>
		</td>
	</tr>
</table>
