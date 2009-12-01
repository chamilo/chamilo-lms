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
$this_section = SECTION_MYPROFILE;
Display::display_header('');


echo '<div class=actions>';
echo '<a href="inbox.php">'.Display::return_icon('folder_up.gif',get_lang('BackToInbox')).get_lang('BackToInbox').'</a>';
echo '<a href="new_message.php?re_id"'.intval($_GET['id']).'">'.Display::return_icon('message_reply.png',get_lang('ReplyToMessage')).get_lang('ReplyToMessage').'</a>';
echo '<a href="inbox.php?action=deleteone&id="'.intval($_GET['id']).'" >'.Display::return_icon('message_delete.png',get_lang('DeleteMessage')).''.get_lang('DeleteMessage').'</a>';
echo '</div><br />';


$message = MessageManager::show_message_box();
if (!empty($message)) {
	echo $message;
} else {
	api_not_allowed();
}

/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>