<?php
/* For licensing terms, see /chamilo_license.txt */

// name of the language file that needs to be included
$language_file= 'messages';
$cidReset= true;
require_once '../inc/global.inc.php';
api_block_anonymous_users();
if (api_get_setting('allow_message_tool')!='true'){
	api_not_allowed();
}
require_once api_get_path(LIBRARY_PATH).'message.lib.php';
$interbreadcrumb[]= array ('url' => 'inbox.php','name' => get_lang('Message'));
$interbreadcrumb[]= array ('url' => '#','name' => get_lang('View'));

/*
==============================================================================
		HEADER
==============================================================================
*/
if ($_GET['f']=='social') {
	$this_section = SECTION_SOCIAL;
	$interbreadcrumb[]= array ('url' => '#','name' => get_lang('Profile'));
	$interbreadcrumb[]= array ('url' => 'outbox.php','name' => get_lang('Inbox'));	
} else {
	$this_section = SECTION_MYPROFILE;
	$interbreadcrumb[]= array ('url' => '#','name' => get_lang('Profile'));
	$interbreadcrumb[]= array ('url' => 'outbox.php','name' => get_lang('Inbox'));
}
Display::display_header('');

if ($_GET['f']=='social') {
	require_once api_get_path(LIBRARY_PATH).'social.lib.php';
	SocialManager::show_social_menu();
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
		$show = isset($_GET['show']) ? '&amp;show='.Security::remove_XSS($_GET['show']) : '';
		
		//echo '<span style="float:right; padding-top:7px;">';
					 
		if (isset($_GET['type']) && $_GET['type'] == 'extended') {
			echo '<a href="profile.php?type=reduced'.$show.'">'.Display::return_icon('edit.gif', get_lang('EditNormalProfile')).'&nbsp;'.get_lang('EditNormalProfile').'</a>';
		} else {
			echo '<a href="profile.php?type=extended'.$show.'">'.Display::return_icon('edit.gif', get_lang('EditExtendProfile')).'&nbsp;'.get_lang('EditExtendProfile').'</a>';
		}
		//echo '</span>';
		
		echo '</div>';
	}
}

echo '<div id="inbox-wrapper">';
	//LEFT COLUMN
	echo '<div id="inbox-menu">';	
		echo '<ul>';
			echo '<li><a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php'.$social_parameter.'">'.Display::return_icon('inbox.png',get_lang('Inbox')).get_lang('Inbox').'</a>'.'</li>';
			echo '<li><a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php'.$social_parameter.'">'.Display::return_icon('message_new.png',get_lang('ComposeMessage')).get_lang('ComposeMessage').'</a>'.'</li>';
			echo '<li><a href="'.api_get_path(WEB_PATH).'main/messages/outbox.php'.$social_parameter.'">'.Display::return_icon('outbox.png',get_lang('Outbox')).get_lang('Outbox').'</a>'.'</li>';
		echo '</ul>';	
	echo '</div>';

	echo '<div id="inbox">';
		//MAIN CONTENT
		$message = MessageManager::show_message_box($_GET['id']);
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