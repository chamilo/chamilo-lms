<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.messages
*/
/**
 * Code
 */
// name of the language file that needs to be included
$language_file = array('registration','messages','userInfo');
$cidReset = true;
require_once '../inc/global.inc.php';

api_block_anonymous_users();
if (isset($_GET['messages_page_nr'])) {
	$social_link = '';
	if ($_REQUEST['f']=='social') {
		$social_link = '?f=social';
	}
	if (api_get_setting('allow_social_tool')=='true' &&  api_get_setting('allow_message_tool')=='true') {
		header('Location:inbox.php'.$social_link);
	}
}
if (api_get_setting('allow_message_tool')!='true'){
	api_not_allowed();	
}
//jquery thickbox already called from main/inc/header.inc.php

$htmlHeadXtra[]='<script language="javascript">

function show_icon_edit(element_html) {	
	ident="#edit_image";
	$(ident).show();
}		

function hide_icon_edit(element_html)  {
	ident="#edit_image";
	$(ident).hide();
}		
		
</script>';

/*
		MAIN CODE
*/
$nameTools = get_lang('Messages');
$request=api_is_xml_http_request();
if (isset($_GET['form_reply']) || isset($_GET['form_delete'])) {
	$info_reply=array();
	$info_delete=array();
	
	if ( isset($_GET['form_reply']) ) {
		//allow to insert messages
		$info_reply=explode(base64_encode('&%ff..x'),$_GET['form_reply']);
		$count_reply=count($info_reply);
		$button_sent=urldecode($info_reply[4]);
	}
	
	if ( isset($_GET['form_delete']) ) {
		//allow to delete messages
		$info_delete=explode(',',$_GET['form_delete']);
		$count_delete=(count($info_delete)-1);
	}
	
	if ( isset($button_sent) ) {
		$title     = api_convert_encoding(urldecode($info_reply[0]),'UTF-8',$charset);
		$content   = api_convert_encoding(str_replace("\\","",urldecode($info_reply[1])),'UTF-8',$charset);
		$title     = Security::remove_XSS($title);
		$content   = Security::remove_XSS($content,COURSEMANAGERLOWSECURITY);

		$user_reply= $info_reply[2];
		$user_email_base=str_replace(')','(',$info_reply[5]);
		$user_email_prepare=explode('(',$user_email_base);
		if (count($user_email_prepare)==1) {
			$user_email=trim($user_email_prepare[0]);
		} elseif (count($user_email_prepare)==3) {
			$user_email=trim($user_email_prepare[1]);
		}
		$user_id_by_email=MessageManager::get_user_id_by_email($user_email);

		if ($info_reply[6]=='save_form') {
			$user_id_by_email=$info_reply[2];
		}
		if ( isset($user_reply) && !is_null($user_id_by_email) && strlen($info_reply[0]) >0) {
			MessageManager::send_message($user_id_by_email, $title, $content);
			MessageManager::display_success_message($user_id_by_email);
			inbox_display();
			exit;
		} elseif (is_null($user_id_by_email)) {
			$message_box=get_lang('ErrorSendingMessage');
			Display::display_error_message(api_xml_http_response_encode($message_box),false);
			inbox_display();
			exit;
		}
	} elseif (trim($info_delete[0])=='delete' ) {
		for ($i=1;$i<=$count_delete;$i++) {
			MessageManager::delete_message_by_user_receiver(api_get_user_id(), $info_delete[$i]);
		}
		$message_box=get_lang('SelectedMessagesDeleted');
		Display::display_normal_message(api_xml_http_response_encode($message_box),false);
	   	inbox_display();
	    exit;
	}
}


$link_ref="new_message.php";
$table_message = Database::get_main_table(TABLE_MESSAGE);


//api_display_tool_title(api_xml_http_response_encode(get_lang('Inbox')));
if ($_GET['f']=='social') {
	$this_section = SECTION_SOCIAL;
	$interbreadcrumb[]= array ('url' => api_get_path(WEB_PATH).'main/social/home.php','name' => get_lang('Social'));
	$interbreadcrumb[]= array ('url' => '#','name' => get_lang('Inbox'));	
} else {
	$this_section = SECTION_MYPROFILE;
	$interbreadcrumb[]= array ('url' => api_get_path(WEB_PATH).'main/auth/profile.php','name' => get_lang('Profile'));
	$interbreadcrumb[]= array ('url' => '#','name' => get_lang('Inbox'));
}

Display::display_header('');
$social_parameter = '';

if ($_GET['f']=='social' || api_get_setting('allow_social_tool') == 'true') {
	$social_parameter = '?f=social';	
} else {
    
	//Comes from normal profile	
	echo '<div class="actions">';
	if (api_get_setting('allow_social_tool') == 'true' && api_get_setting('allow_message_tool') == 'true') {
		echo '<a href="'.api_get_path(WEB_PATH).'main/social/profile.php">'.Display::return_icon('shared_profile.png', get_lang('ViewSharedProfile')).'&nbsp;'.get_lang('ViewSharedProfile').'</a>';
	}
	
	if (api_get_setting('allow_message_tool') == 'true') {
		//echo '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.Display::return_icon('inbox.png').' '.get_lang('Messages').'</a>';		
		echo '<a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php">'.Display::return_icon('message_new.png',get_lang('ComposeMessage')).get_lang('ComposeMessage').'</a>';
		echo '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.Display::return_icon('inbox.png',get_lang('Inbox')).get_lang('Inbox').'</a>';
        echo '<a href="'.api_get_path(WEB_PATH).'main/messages/outbox.php">'.Display::return_icon('outbox.png',get_lang('Outbox')).get_lang('Outbox').'</a>';

            
	}	
	//echo '<a href="'.api_get_path(WEB_PATH).'main/auth/profile.php?type=reduced">'.Display::return_icon('edit.gif', get_lang('EditNormalProfile')).'&nbsp;'.get_lang('EditNormalProfile').'</a>';
	echo '</div>';
}

echo '<div id="social-content">';
	$id_content_right = '';
	//LEFT CONTENT			
	if (api_get_setting('allow_social_tool') != 'true') { 
		$id_content_right = 'inbox';		
	} else {		
		$id_content_right = 'social-content-right';
		echo '<div id="social-content-left">';	
			//this include the social menu div
			SocialManager::show_social_menu('messages');
		echo '</div>';		
	}

	echo '<div id="'.$id_content_right.'">';    
    if (api_get_setting('allow_social_tool') == 'true') {
    	echo '<div class="actions">';				
    		echo '<a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php?f=social">'.Display::return_icon('compose_message.png', get_lang('ComposeMessage'), array(), 32).'</a>';
            echo '<a href="'.api_get_path(WEB_PATH).'main/messages/outbox.php?f=social">'.Display::return_icon('outbox.png', get_lang('Outbox'), array(), 32).'</a>';                        
    	echo '</div>';
    		
    }	
    //MAIN CONTENT
    if (!isset($_GET['del_msg'])) {
    	inbox_display();
    } else {
    	$num_msg = intval($_POST['total']);
    	for ($i=0;$i<$num_msg;$i++) {
    		if($_POST[$i]) {
    			//the user_id was necesarry to delete a message??
    			MessageManager::delete_message_by_user_receiver(api_get_user_id(), $_POST['_'.$i]);
    		}
    	}
    	inbox_display();
    }    
	echo '</div>';	
echo '</div>';

Display::display_footer();
