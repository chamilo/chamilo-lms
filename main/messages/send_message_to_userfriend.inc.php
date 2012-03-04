<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.messages
*/
/**
 * Code
 */
$language_file = array('registration','messages','userInfo','admin');
$cidReset = true;
require_once '../inc/global.inc.php';

if (api_is_anonymous()) {
	api_not_allowed();
}
if (api_get_setting('allow_message_tool') != 'true' && api_get_setting('allow_social_tool') != 'true'){
	api_not_allowed();
}
if (isset($_REQUEST['user_friend']) ) {
	$info_user_friend=array();	
 	$userfriend_id=intval($_REQUEST['user_friend']);
 	// panel=1  send message
 	// panel=2  send invitation
 	$panel=intval($_REQUEST['view_panel']);
 	$info_user_friend=api_get_user_info($userfriend_id); 	
}

?>
<div id="id_content_panel_init">
<?php
if (api_get_setting('allow_message_tool')=='true') {
    if ($panel == 1) {
        //normal message
   		$user_info=api_get_user_info($userfriend_id);
        echo '<div id="display_response_id" style="height:200px;">';
        $form = new FormValidator('add_user', null, null, null, array('class'=>'form-vertical'));
        //$username = api_xml_http_response_encode(get_lang('To')).api_xml_http_response_encode(api_get_person_name($user_info['firstName'], $user_info['lastName']));
        $form->addElement('label', get_lang('To'), api_get_person_name($user_info['firstName'], $user_info['lastName']));        
        ////$form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');
        $form->addElement('text', 'subject', get_lang('Subject'), array('id'=>'txt_subject_id','class'=>'span4'));        
        $form->addElement('textarea', 'message', get_lang('Message'), array('id'=>'txt_area_invite','class'=>'span4'));        
        $form->addElement('style_submit_button', 'send', get_lang('SendMessage'), array('onclick'=>"action_database_panel('5','".$userfriend_id."')"));
        $form->display();
        echo '</div>';
	} else {
        // friend invitation message
        ?>
        <div id="display_response_id" style="height:200px;">
			<?php echo api_xml_http_response_encode(get_lang('AddPersonalMessage'));  ?> :<br /><br />				
			<textarea id="txt_area_invite" rows="5" class="span6" ></textarea><br /><br />
			<button class="save" type="button" value="<?php echo api_xml_http_response_encode(get_lang('SocialAddToFriends')); ?>" onclick="action_database_panel('4','<?php echo $userfriend_id;?>')" >
			<?php echo api_xml_http_response_encode(get_lang('SendInvitation')) ?></button>
		</div>
<?php			
        }
    } 
?>
</div>