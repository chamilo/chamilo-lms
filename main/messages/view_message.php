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


if (api_get_setting('allow_message_tool') === 'true') {

    $actionLeft = Display::url(
        Display::return_icon(
            'new-message.png',
            get_lang('ComposeMessage')
        ),
        api_get_path(WEB_PATH).'main/messages/new_message.php'
    );

    $actionLeft.=Display::url(
        Display::return_icon(
            'inbox.png',
            get_lang('Inbox')
        ),
        api_get_path(WEB_PATH).'main/messages/inbox.php'
    );

    $actionLeft.=Display::url(
        Display::return_icon(
            'outbox.png',
            get_lang('Outbox')
        ),
        api_get_path(WEB_PATH).'main/messages/outbox.php'
    );

    $toolbar = Display::toolbarAction('inbox',[ 0 => $actionLeft ]);

}


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
    'tool_id' => 0,
    'tool_id_detail' => 0,
    'action' => $source,
    'action_details' => 'view-message',
];
Event::registerLog($logInfo);

// MAIN CONTENT
$message = MessageManager::showMessageBox($messageId, $source);
$messageContent = '';


if (!empty($message)) {
    $messageContent = $message;
} else {
    api_not_allowed(true);
}

$tpl = new Template(get_lang('View'));
// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), $show_menu);

if (api_get_setting('allow_social_tool') === 'true') {
    $tpl->assign('actions',$toolbar);
    $tpl->assign('message', $messageContent);
    $social_layout = $tpl->get_template('message/view_message.html.twig');
    $tpl->display($social_layout);
} else {
    $content = $social_right_content;
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
}
