<?php

/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
api_block_anonymous_users();

$allowSocial = 'true' === api_get_setting('allow_social_tool');
$allowMessage = 'true' === api_get_setting('allow_message_tool');

if (!$allowMessage) {
    api_not_allowed(true);
}

$messageId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (empty($messageId)) {
    api_not_allowed(true);
}

if ($allowSocial) {
    $this_section = SECTION_SOCIAL;
    $interbreadcrumb[] = ['url' => api_get_path(WEB_PATH).'main/social/home.php', 'name' => get_lang('Social network')];
} else {
    $this_section = SECTION_MYPROFILE;
    $interbreadcrumb[] = ['url' => api_get_path(WEB_PATH).'main/auth/profile.php', 'name' => get_lang('Profile')];
}
$interbreadcrumb[] = ['url' => 'inbox.php', 'name' => get_lang('Messages')];


$actions = '';
if ($allowMessage) {
    $actions .= '<a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php">'.
        Display::return_icon('new-message.png', get_lang('Compose message')).'</a>';
    $actions .= '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.
        Display::return_icon('inbox.png', get_lang('Inbox')).'</a>';
    $actions .= '<a href="'.api_get_path(WEB_PATH).'main/messages/outbox.php">'.
        Display::return_icon('outbox.png', get_lang('Outbox')).'</a>';
}
$social_right_content = Display::toolbarAction('toolbar', [$actions]);

$type = isset($_GET['type']) ? (int) $_GET['type'] : MessageManager::MESSAGE_TYPE_INBOX;

$show_menu = 'messages_inbox';
if (MessageManager::MESSAGE_TYPE_OUTBOX === $type) {
    $show_menu = 'messages_outbox';
}

$logInfo = [
    'tool' => 'Messages',
    'tool_id' => $messageId,
    'action' => 'view-message',
    'action_details' => 'view-message',
];
Event::registerLog($logInfo);

// LEFT COLUMN
if ('true' === api_get_setting('allow_social_tool')) {
    // Block Social Menu
    $social_menu_block = SocialManager::show_social_menu($show_menu);
}

$message = MessageManager::showMessageBox($messageId, $type);
if (empty($message)) {
    api_not_allowed(true);
}

$social_right_content .= $message;

$tpl = new Template(get_lang('View'));
// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), $show_menu);

if ('true' === api_get_setting('allow_social_tool')) {
    $tpl->assign('social_menu_block', $social_menu_block);
    $tpl->assign('social_right_content', $social_right_content);
    $social_layout = $tpl->get_template('social/inbox.tpl');
    $tpl->display($social_layout);
} else {
    $content = $social_right_content;

    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
}
