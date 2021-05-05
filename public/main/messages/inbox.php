<?php

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

if ('true' !== api_get_setting('allow_message_tool')) {
    api_not_allowed(true);
}

$logInfo = [
    'tool' => 'Messages',
    'action' => $_GET['action'] ?? 'inbox',
];
Event::registerLog($logInfo);

$allowSocial = 'true' === api_get_setting('allow_social_tool');
$allowMessage = 'true' === api_get_setting('allow_message_tool');

if ($allowSocial) {
    $this_section = SECTION_SOCIAL;
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_PATH).'main/social/home.php',
        'name' => get_lang('Social network'),
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
if (false === $allowSocial && $allowMessage) {
    $actions .= '<a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php">'.
        Display::return_icon('message_new.png', get_lang('Compose message')).'</a>';
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
if ($allowSocial) {
    $actionsLeft = '<a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php">'.
        Display::return_icon('new-message.png', get_lang('Compose message'), [], 32).'</a>';
    $actionsLeft .= '<a href="'.api_get_path(WEB_PATH).'main/messages/outbox.php">'.
        Display::return_icon('outbox.png', get_lang('Outbox'), [], 32).'</a>';

    $form = MessageManager::getSearchForm(api_get_path(WEB_PATH).'main/messages/inbox.php');
    if ($form->validate()) {
        $values = $form->getSubmitValues();
        $keyword = $values['keyword'];
    }
    $actionsRight = $form->returnForm();
    $social_right_content .= Display::toolbarAction('toolbar', [$actionsLeft, $actionsRight]);
}

$social_right_content .= MessageManager::inboxDisplay($keyword);

$tpl = new Template(null);

if ($actions) {
    $tpl->assign('actions', Display::toolbarAction('toolbar', [$actions]));
}
// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'messages');

/*if ($allowSocial) {
    $tpl->assign('social_menu_block', $social_menu_block);
    $tpl->assign('social_right_content', $social_right_content);
    $social_layout = $tpl->get_template('social/inbox.tpl');
    $tpl->display($social_layout);
} else {
    $tpl->assign('content', $social_right_content);
    $tpl->display_one_col_template();
}*/
    $tpl->assign('content', $social_right_content);
    $tpl->display_one_col_template();
