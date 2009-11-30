<?php
/* For licensing terms, see /dokeos_license.txt */
$language_file = array('registration','messages','userInfo','admin');
$cidReset=true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'message.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

if (api_is_anonymous()) {
	api_not_allowed();
}

if (api_get_setting('allow_message_tool') != 'true' && api_get_setting('allow_social_tool') != 'true') {
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

$group_id = intval($_GET['group_id']);
$message_id = intval($_GET['message_id']);
$to_group = '';
$title_group = '';
if (!empty($group_id)) {
	$group_info = GroupPortalManager::get_group_data($group_id);
	$to_group   = $group_info['name'];
	if (!empty($message_id)) {
		$message_info = MessageManager::get_message_by_id($message_id);		
		$title_group  = get_lang('Re:').api_html_entity_decode($message_info['title'],ENT_QUOTES,$charset);	
	} 	
}

?>

<form name="form" action="groups.php?id=<?php echo $group_id ?>" method="POST" enctype="multipart/form-data">
<input type="hidden" name="action" value="send_message_group" />
<input type="hidden" name="group_id" value="<?php echo $group_id ?>" />
<input type="hidden" name="parent_id" value="<?php echo $message_id ?>" />
<table width="600" border="0" height="220">
    <tr height="180">
        <td align="center">
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
            //normal message
	   		 $user_info=api_get_user_info($userfriend_id);
	  		 echo api_xml_http_response_encode(get_lang('To')); ?> :&nbsp;&nbsp;&nbsp;&nbsp;<?php echo api_xml_http_response_encode($to_group); ?>
	  		 <br />
	 		 <br /><?php echo api_xml_http_response_encode(get_lang('Subject')); ?> :<br /><input id="txt_subject_id" name="title" type="text" style="width:300px;" value="<?php echo $title_group ?>"><br/>
	   		 <br /><?php echo api_xml_http_response_encode(get_lang('Message')); ?> :<br /><textarea id="txt_area_invite" name="content" rows="3" cols="41"></textarea><br/>
	   		 <br /><?php echo api_xml_http_response_encode(get_lang('AttachmentFiles')); ?> :<br />

			<span id="filepaths"><div id="filepath_1"><input type="file" name="attach_1" size="20" /></div></span>
			<div id="link-more-attach"><a href="javascript://" onclick="return add_image_form()"><?php echo get_lang('AddOneMoreFile') ?></a>&nbsp;(<?php echo get_lang('MaximunFileSizeXMB') ?>)</div>
	   		<!--button class="save" type="button" value="<?php echo api_xml_http_response_encode(get_lang('SendMessage')); ?>" onclick="return ajaxFileUpload()"><?php echo api_xml_http_response_encode(get_lang('SendMessage')) ?></button-->
	   		<button class="save" type="submit" value="<?php echo api_xml_http_response_encode(get_lang('SendMessage')); ?>"><?php echo api_xml_http_response_encode(get_lang('SendMessage')) ?></button>
<?php } ?>
	</dl>
</div>
</td>
</tr>
</div>
<tr>
	<td>
		<div id="display_response_id" style="position:relative"></div>
	</td>
</tr>
</table>
</form>