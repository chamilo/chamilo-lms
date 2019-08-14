<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.messages
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
api_block_anonymous_users();

if (api_get_setting('allow_social_tool') != 'true') {
    api_not_allowed(true);
}

$this_section = SECTION_SOCIAL;
$interbreadcrumb[] = ['url' => api_get_path(WEB_PATH).'main/social/home.php', 'name' => get_lang('SocialNetwork')];
$interbreadcrumb[] = ['url' => 'promoted_messages.php', 'name' => get_lang('PromotedMessages')];

$social_right_content = '';
if (empty($_GET['id'])) {
    $messageId = $_GET['id_send'];
    $source = 'outbox';
    $show_menu = 'messages_outbox';
} else {
    $messageId = $_GET['id'];
    $source = 'inbox';
    $show_menu = 'messages_inbox';
}

$message = '';

$logInfo = [
    'tool' => 'Messages',
    'action' => $source,
    'action_details' => 'view-message',
];
Event::registerLog($logInfo);
$social_menu_block = SocialManager::show_social_menu($show_menu);
$message .= MessageManager::showMessageBox($messageId, 'promoted_messages');

if (!empty($message)) {
    $social_right_content .= $message;
} else {
    api_not_allowed(true);
}
$tpl = new Template(get_lang('View'));
// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), $show_menu);

$tpl->assign('social_menu_block', $social_menu_block);
$tpl->assign('social_right_content', $social_right_content);
$social_layout = $tpl->get_template('social/inbox.tpl');
$tpl->display($social_layout);
