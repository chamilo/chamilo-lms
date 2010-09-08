<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.messages
*/

$language_file = array('registration','messages','userInfo');
$cidReset= true;
require_once '../inc/global.inc.php';
api_block_anonymous_users();
if (api_get_setting('allow_message_tool')!='true'){
	api_not_allowed();
}
require_once api_get_path(LIBRARY_PATH).'message.lib.php';


/*
		HEADER
*/
$htmlHeadXtra[] = '<script type="text/javascript" src="/main/inc/lib/javascript/jquery.js"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.css" type="text/css" media="projection, screen">';


if ($_REQUEST['f']=='social') {
	$this_section = SECTION_SOCIAL;
	$interbreadcrumb[]= array ('url' => api_get_path(WEB_PATH).'main/social/home.php','name' => get_lang('Social'));
	$interbreadcrumb[]= array ('url' => 'inbox.php?f=social','name' => get_lang('Inbox'));	
} else {
	$this_section = SECTION_MYPROFILE;
	$interbreadcrumb[]= array ('url' => '#','name' => get_lang('Profile'));
	$interbreadcrumb[]= array ('url' => 'inbox.php','name' => get_lang('Inbox'));
}
$interbreadcrumb[]= array ('url' => '#','name' => get_lang('View'));
Display::display_header('');

if ($_GET['f']=='social') {	
	$social_parameter = '?f=social';
} else {
	if (api_get_setting('extended_profile') == 'true') {
		echo '<div class="actions">';
		
		if (api_get_setting('allow_social_tool') == 'true' && api_get_setting('allow_message_tool') == 'true') {
			echo '<a href="'.api_get_path(WEB_PATH).'main/social/profile.php">'.Display::return_icon('shared_profile.png', get_lang('ViewSharedProfile')).'&nbsp;'.get_lang('ViewSharedProfile').'</a>';
		}
		if (api_get_setting('allow_message_tool') == 'true') {
			echo '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.Display::return_icon('inbox.png').' '.get_lang('Messages').'</a>';
		}	
		echo '<a href="'.api_get_path(WEB_PATH).'main/auth/profile.php?type=reduced">'.Display::return_icon('edit.gif', get_lang('EditNormalProfile')).'&nbsp;'.get_lang('EditNormalProfile').'</a>';
		
		echo '</div>';
	}
}

echo '<div id="social-content">';	
	if (empty($_GET['id'])) {
		$id_message = $_GET['id_send'];
		$source = 'outbox';
		$show_menu = 'messages_outbox';
	} else {
		$id_message = $_GET['id'];
		$source = 'inbox';
		$show_menu = 'messages_inbox';
	}
	$id_content_right = '';
	//LEFT COLUMN
	if (api_get_setting('allow_social_tool') != 'true') { 
		$id_content_right = 'inbox';
		echo '<div id="inbox-menu" class="actions">';
		echo '<ul>';
			echo '<li><a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php">'.Display::return_icon('message_new.png',get_lang('ComposeMessage')).get_lang('ComposeMessage').'</a>'.'</li>';
			echo '<li><a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.Display::return_icon('inbox.png',get_lang('Inbox')).get_lang('Inbox').'</a>'.'</li>';			
			echo '<li><a href="'.api_get_path(WEB_PATH).'main/messages/outbox.php">'.Display::return_icon('outbox.png',get_lang('Outbox')).get_lang('Outbox').'</a>'.'</li>';
		echo '</ul>';
		echo '</div>';
	} else {
		require_once api_get_path(LIBRARY_PATH).'social.lib.php';
		$id_content_right = 'social-content-right';
		echo '<div id="social-content-left">';	
			//this include the social menu div
			
			SocialManager::show_social_menu($show_menu);
		echo '</div>';				
	}

	echo '<div id="'.$id_content_right.'">';
	if (api_get_setting('allow_social_tool') == 'true'){
		echo '<div class="social-box-container2">';				
		echo '<div>'.Display::return_icon('content-post-group1.jpg',get_lang('View')).'</div>';
		echo '<div id="div_content_table" class="social-box-content2">';
	}	
		//MAIN CONTENT
		$message = MessageManager::show_message_box($id_message,$source);
		
		if (!empty($message)) {
			echo $message;
		} else {
			api_not_allowed();
		}
	if (api_get_setting('allow_social_tool') == 'true'){
		echo '</div>';
		echo '</div>';
	}
	echo '</div>';

echo '</div>';

/*
		FOOTER
*/
Display::display_footer();
?>