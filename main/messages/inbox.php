<?php

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

if (api_get_setting('allow_message_tool') !== 'true') {
    api_not_allowed(true);
}

$logInfo = [
    'tool' => 'Messages',
    'action' => isset($_GET['action']) ? $_GET['action'] : 'inbox',
];
Event::registerLog($logInfo);

$allowSocial = api_get_setting('allow_social_tool') === 'true';
$allowMessage = api_get_setting('allow_message_tool') === 'true';

if ($allowSocial) {
    $this_section = SECTION_SOCIAL;
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_PATH).'main/social/home.php',
        'name' => get_lang('SocialNetwork'),
    ];
} else {
    $this_section = SECTION_MYPROFILE;
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_PATH).'main/auth/profile.php',
        'name' => get_lang('Profile'),
    ];
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_PATH).'main/messages/inbox.php',
    'name' => get_lang('Messages'),
];
$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Inbox')];

$actions = '';
// Comes from normal profile
if ($allowSocial === false && $allowMessage) {
    $actions .= '<a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php">'.
        Display::return_icon('message_new.png', get_lang('ComposeMessage')).'</a>';
    $actions .= '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.
        Display::return_icon('inbox.png', get_lang('Inbox')).'</a>';
    $actions .= '<a href="'.api_get_path(WEB_PATH).'main/messages/outbox.php">'.
        Display::return_icon('outbox.png', get_lang('Outbox')).'</a>';
}

// LEFT CONTENT
$social_menu_block = '';
if ($allowSocial) {
    // Block Social Menu
    $social_menu_block = SocialManager::show_social_menu('messages');
}

// Right content
$social_right_content = '';
$keyword = '';
$searchTags = [];
if ($allowSocial) {
    $actionsLeft = '<a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php">'.
        Display::return_icon('new-message.png', get_lang('ComposeMessage'), [], 32).'</a>';
    $actionsLeft .= '<a href="'.api_get_path(WEB_PATH).'main/messages/outbox.php">'.
        Display::return_icon('outbox.png', get_lang('Outbox'), [], 32).'</a>';

    $form = MessageManager::getSearchForm(api_get_path(WEB_PATH).'main/messages/inbox.php');
    if ($form->validate()) {
        $values = $form->getSubmitValues();
        $keyword = $values['keyword'];
        $searchTags = $values['tags'] ?? [];
    }
    $actionsRight = $form->returnForm();
    $social_right_content .= Display::toolbarAction('toolbar', [$actionsLeft, $actionsRight], [2, 10]);
}

$social_right_content .= MessageManager::inboxDisplay($keyword, $searchTags);

$tpl = new Template(null);

if ($actions) {
    $tpl->assign('actions', Display::toolbarAction('toolbar', [$actions]));
}
// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'messages');
if ($allowSocial) {
    $tpl->assign('social_menu_block', $social_menu_block);
    $tpl->assign('social_right_content', $social_right_content);
    $social_layout = $tpl->get_template('social/inbox.tpl');
    $tpl->display($social_layout);
} else {
    $content = $social_right_content;
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
}
