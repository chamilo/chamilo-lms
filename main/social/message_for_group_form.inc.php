<?php
/* For licensing terms, see /license.txt */
/**
 * Form for group message
 * @package chamilo.social
 */
/**
 * Initialization
 */
$language_file = array('registration','messages','userInfo','admin');
$cidReset=true;
require_once '../inc/global.inc.php';

api_block_anonymous_users();
if (api_get_setting('allow_social_tool') !='true') {
    api_not_allowed();
}

require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';
require_once api_get_path(LIBRARY_PATH).'fckeditor/fckeditor.php';

$tok = Security::get_token();

if (isset($_REQUEST['user_friend'])) {
	$info_user_friend=array();
	$info_path_friend=array();
 	$userfriend_id = intval($_REQUEST['user_friend']);
 	// panel=1  send message
 	// panel=2  send invitation
 	$panel = Security::remove_XSS($_REQUEST['view_panel']);
 	$info_user_friend = api_get_user_info($userfriend_id);
 	$info_path_friend = UserManager::get_user_picture_path_by_id($userfriend_id,'web',false,true);
}

$group_id = intval($_GET['group_id']);

$message_id = intval($_GET['message_id']);
$actions = array('add_message_group', 'edit_message_group', 'reply_message_group');

$allowed_action = (isset($_GET['action']) && in_array($_GET['action'],$actions))?Security::remove_XSS($_GET['action']):'';

$to_group = '';
$subject = '';
$message = '';
if (!empty($group_id) && $allowed_action) {
	$group_info = GroupPortalManager::get_group_data($group_id);	
	$is_member = GroupPortalManager::is_group_member($group_id);
	
    if ($group_info['visibility'] == GROUP_PERMISSION_CLOSED && !$is_member) {
        api_not_allowed(true);        
    }

	$to_group   = $group_info['name'];
	if (!empty($message_id)) {
		$message_info = MessageManager::get_message_by_id($message_id);		
		if ($allowed_action == 'reply_message_group') {				
			$subject  = get_lang('Reply').': '.api_xml_http_response_encode($message_info['title']);
			//$message  = api_xml_http_response_encode($message_info['content']);
		} else {
			$subject  = api_xml_http_response_encode($message_info['title']);
			$message  = api_xml_http_response_encode($message_info['content']);
		}	
	} 	
}

$page_item = !empty($_GET['topics_page_nr'])?intval($_GET['topics_page_nr']):1;
$param_item_page = isset($_GET['items_page_nr']) && isset($_GET['topic_id'])?('&items_'.intval($_GET['topic_id']).'_page_nr='.(!empty($_GET['topics_page_nr'])?intval($_GET['topics_page_nr']):1)):'';
$param_item_page .= '&topic_id='.intval($_GET['topic_id']);
$page_topic  = !empty($_GET['topics_page_nr'])?intval($_GET['topics_page_nr']):1;
?>

<form name="form" action="group_topics.php?id=<?php echo $group_id ?>&anchor_topic=<?php echo Security::remove_XSS($_GET['anchor_topic']) ?>&topics_page_nr=<?php echo $page_topic.$param_item_page ?>" method="POST" enctype="multipart/form-data">
<input type="hidden" name="action" value="<?php echo $allowed_action ?>" />
<input type="hidden" name="group_id" value="<?php echo $group_id ?>" />
<input type="hidden" name="parent_id" value="<?php echo $message_id ?>" />
<input type="hidden" name="message_id" value="<?php echo $message_id ?>" />
<input type="hidden" name="token" value="<?php echo $tok ?>" />
<table width="500px" border="0" height="220px">
    <tr height="180">
        <td align="left">
<div id="id_content_panel_init">
	<dl>
	<?php
		if (api_get_setting('allow_message_tool')=='true') {
            //normal message
	   		$user_info = api_get_user_info($userfriend_id);
	  		//echo api_xml_http_response_encode(get_lang('To')).":&nbsp;&nbsp;".api_xml_http_response_encode($to_group); 
	  		$height = 180;
	  		if ($allowed_action == 'add_message_group') {
	  		    $height = 140;
	  		    echo '<span style="color:red">*</span> '.api_xml_http_response_encode(get_lang('Title')).' :<br />';
	  		    echo '<input id="txt_subject_id" name="title" type="text" style="width:450px;" value="'.$subject.'"><br /><br />';		 	 
	  		}
	  		//echo api_xml_http_response_encode(get_lang('Description')).' :<br />';		   		
	   		
			$oFCKeditor = new FCKeditor('content') ;
			$oFCKeditor->ToolbarSet = 'messages';
			$oFCKeditor->Width		= '95%';
			$oFCKeditor->Height		= $height;
			$oFCKeditor->Value		= $message;
								
			$return =	$oFCKeditor->CreateHtml();	
			echo $return;
			if ($allowed_action == 'add_message_group') {
			    echo '<div><span style="color:red"> * </span>'.get_lang('FieldRequired').'</div>';    
			}
	   		?>	   				   		
	   		<br /><br />
	   		<?php echo api_xml_http_response_encode(get_lang('AttachmentFiles')); ?> :<br />
			<span id="filepaths"><div id="filepath_1"><input type="file" name="attach_1" size="20" /></div></span>
			<div id="link-more-attach">
				<a href="javascript://" onclick="return add_image_form()">
			    <?php echo get_lang('AddOneMoreFile') ?></a>
			</div>
			(<?php echo api_xml_http_response_encode(sprintf(get_lang('MaximunFileSizeX'),format_file_size(api_get_setting('message_max_upload_filesize')))) ?>)		   				   				   		
	   		<br />
	   		<br />
	   		
	   		<?php if ($allowed_action == 'add_message_group') { ?>
	   			<button class="save" onclick="if(validate_text_empty(this.form.title.value,'<?php echo get_lang('YouShouldWriteASubject')?>')){return false;}" type="submit" value="<?php echo api_xml_http_response_encode(get_lang('SendMessage')); ?>"><?php echo api_xml_http_response_encode(get_lang('SendMessage')) ?></button>
	   		<?php } else { ?>
	   			<button class="save" type="submit" value="<?php echo api_xml_http_response_encode(get_lang('SendMessage')); ?>"><?php echo api_xml_http_response_encode(get_lang('SendMessage')) ?></button>
	   		<?php } ?>	
	<?php } ?>
	</dl>
</td>
</tr>
</div>
</table>
</form>