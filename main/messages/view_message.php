<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.messages
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
api_block_anonymous_users();

if (api_get_setting('allow_message_tool') != 'true') {
    api_not_allowed(true);
}

$allowSocial = api_get_setting('allow_social_tool') === 'true';
$allowMessage = api_get_setting('allow_message_tool') === 'true';

if ($allowSocial) {
    $this_section = SECTION_SOCIAL;
    $interbreadcrumb[] = ['url' => api_get_path(WEB_PATH).'main/social/home.php', 'name' => get_lang('SocialNetwork')];
} else {
    $this_section = SECTION_MYPROFILE;
    $interbreadcrumb[] = ['url' => api_get_path(WEB_PATH).'main/auth/profile.php', 'name' => get_lang('Profile')];
}
$interbreadcrumb[] = ['url' => 'inbox.php', 'name' => get_lang('Messages')];

$actions = null;

if (api_get_setting('allow_message_tool') === 'true') {
    $actionsLeft = '<a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php">'.
        Display::return_icon('new-message.png', get_lang('ComposeMessage'), null, ICON_SIZE_MEDIUM).'</a>';
    $actionsLeft .= '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.
        Display::return_icon('inbox.png', get_lang('Inbox'), null, ICON_SIZE_MEDIUM).'</a>';
    $actionsLeft .= '<a href="'.api_get_path(WEB_PATH).'main/messages/outbox.php">'.
        Display::return_icon('outbox.png', get_lang('Outbox'), null, ICON_SIZE_MEDIUM).'</a>';
}

$actions .= Display::toolbarAction(
    'toolbar',
    [$actionsLeft]
);

if (empty($_GET['id'])) {
    $messageId = $_GET['id_send'];
    $source = 'outbox';
    $show_menu = 'messages_outbox';
} else {
    $messageId = $_GET['id'];
    $source = 'inbox';
    $show_menu = 'messages_inbox';
}

$logInfo = [
    'tool' => 'Messages',
    'tool_id' => 0,
    'tool_id_detail' => 0,
    'action' => $source,
    'action_details' => 'view-message',
    'current_id' => $messageId,
    'info' => '',
];
Event::registerLog($logInfo);

// MAIN CONTENT
$message_content = MessageManager::showMessageBox($messageId, $source);

if (empty($message_content)) {
    api_not_allowed(true);
}

$tpl = new Template(get_lang('View'));

if ($actions) {
    $tpl->assign('actions', $actions);
}
$tpl->assign('content_inbox', $message_content);
$social_layout = $tpl->get_template('message/inbox.html.twig');
$content = $tpl->fetch($social_layout);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
