<?php
/* For licensing terms, see /license.txt */

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
$messageId = $_GET['id'];

$message = '';
$logInfo = [
    'tool' => 'Messages',
    'action' => 'promoted_messages',
    'action_details' => 'view-message',
];
Event::registerLog($logInfo);
$social_menu_block = SocialManager::show_social_menu('inbox');
$message .= MessageManager::showMessageBox($messageId, MessageManager::MESSAGE_TYPE_PROMOTED);

if (!empty($message)) {
    $social_right_content .= $message;
} else {
    api_not_allowed(true);
}
$tpl = new Template(get_lang('View'));
// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'promoted_messages');

$tpl->assign('social_menu_block', $social_menu_block);
$tpl->assign('social_right_content', $social_right_content);
$social_layout = $tpl->get_template('social/inbox.tpl');
$tpl->display($social_layout);
