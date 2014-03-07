<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.messages
*/
/**
 * Code
 */
$language_file = array('registration','messages','userInfo');
$cidReset= true;
require_once '../inc/global.inc.php';
api_block_anonymous_users();
if (api_get_setting('allow_message_tool')!='true') {
	api_not_allowed();
}

/*		HEADER  */

$interbreadcrumb[]= array ('url' => api_get_path(WEB_PATH).'main/messages/inbox.php','name' => get_lang('Messages'));

$social_right_content = null;

if (isset($_GET['f']) && $_GET['f']=='social') {
	$social_parameter = '?f=social';
} else {
	if (api_get_setting('extended_profile') == 'true') {
		$social_right_content .= '<div class="actions">';

		if (api_get_setting('allow_social_tool') == 'true' && api_get_setting('allow_message_tool') == 'true') {
			$social_right_content .= '<a href="'.api_get_path(WEB_PATH).'main/social/profile.php">'.Display::return_icon('shared_profile.png', get_lang('ViewSharedProfile')).'</a>';
		}
		if (api_get_setting('allow_message_tool') == 'true') {
			//echo '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.Display::return_icon('inbox.png').' '.get_lang('Messages').'</a>';

		    $social_right_content .= '<a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php">'.Display::return_icon('message_new.png',get_lang('ComposeMessage')).'</a>';
            $social_right_content .= '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.Display::return_icon('inbox.png',get_lang('Inbox')).'</a>';
            $social_right_content .= '<a href="'.api_get_path(WEB_PATH).'main/messages/outbox.php">'.Display::return_icon('outbox.png',get_lang('Outbox')).'</a>';
		}
		$social_right_content .= '</div>';
	}
}

if (empty($_GET['id'])) {
    $id_message = $_GET['id_send'];
    $source = 'outbox';
    $show_menu = 'messages_outbox';
} else {
    $id_message = $_GET['id'];
    $source = 'inbox';
    $show_menu = 'messages_inbox';
}

$message = MessageManager::show_message_box($id_message, $source);

if (empty($message)) {
    api_not_allowed();
}

$tpl = $app['template'];
//$tpl->setTitle(get_lang('Read'));
$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $social_right_content);
$tpl->display_one_col_template();
