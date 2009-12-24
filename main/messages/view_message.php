<?php
/* For licensing terms, see /chamilo_license.txt */
/**
*	@package dokeos.messages
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
==============================================================================
		HEADER
==============================================================================
*/
if ($_REQUEST['f']=='social') {
	$this_section = SECTION_SOCIAL;
	$interbreadcrumb[]= array ('url' => api_get_path(WEB_PATH).'main/social/profile.php','name' => get_lang('Social'));
	$interbreadcrumb[]= array ('url' => 'inbox.php?f=social','name' => get_lang('Inbox'));	
} else {
	$this_section = SECTION_MYPROFILE;
	$interbreadcrumb[]= array ('url' => '#','name' => get_lang('Profile'));
	$interbreadcrumb[]= array ('url' => 'inbox.php','name' => get_lang('Inbox'));
}
$interbreadcrumb[]= array ('url' => '#','name' => get_lang('View'));
Display::display_header('');

if ($_GET['f']=='social') {
	echo '<div class="actions-title">';
	echo get_lang('Messages');
	echo '</div>';
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

echo '<div id="inbox-wrapper">';
	//LEFT COLUMN
	if (api_get_setting('allow_social_tool') != 'true') { 
		echo '<div id="inbox-menu" class="actions">';
		echo '<ul>';
			echo '<li><a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.Display::return_icon('inbox.png',get_lang('Inbox')).get_lang('Inbox').'</a>'.'</li>';
			echo '<li><a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php">'.Display::return_icon('message_new.png',get_lang('ComposeMessage')).get_lang('ComposeMessage').'</a>'.'</li>';
			echo '<li><a href="'.api_get_path(WEB_PATH).'main/messages/outbox.php">'.Display::return_icon('outbox.png',get_lang('Outbox')).get_lang('Outbox').'</a>'.'</li>';
		echo '</ul>';
		echo '</div>';
	} else {
		require_once api_get_path(LIBRARY_PATH).'social.lib.php';
		SocialManager::show_social_menu('messages');		
	}

	echo '<div id="inbox">';
		//MAIN CONTENT
		
		if (empty($_GET['id'])) {
			$id_message = $_GET['id_send'];
			$source = 'outbox';
		} else {
			$id_message = $_GET['id'];
			$source = 'inbox';
		}		
		$message = MessageManager::show_message_box($id_message,$source);
		if (!empty($message)) {
			echo $message;
			
	
		} else {
			api_not_allowed();
		}

	echo '</div>';

echo '</div>';

/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>