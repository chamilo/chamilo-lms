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

$logInfo = [
    'tool' => 'Messages',
    'tool_id' => 0,
    'tool_id_detail' => 0,
    'action' => isset($_GET['action']) ? $_GET['action'] : 'outbox',
    'action_details' => '',
    'current_id' => isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0,
    'info' => '',
];
Event::registerLog($logInfo);

$allowSocial = api_get_setting('allow_social_tool') == 'true';
$allowMessage = api_get_setting('allow_message_tool') == 'true';
$show_message = null;

if (isset($_GET['messages_page_nr'])) {
    if ($allowSocial && $allowMessage) {
        header('Location:outbox.php?pager='.intval($_GET['messages_page_nr']));
        exit;
    }
}

if ($allowSocial) {
    $this_section = SECTION_SOCIAL;
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_PATH).'main/social/home.php',
        'name' => get_lang('SocialNetwork'),
    ];
} else {
    $this_section = SECTION_MYPROFILE;
    $interbreadcrumb[] = ['url' => api_get_path(WEB_PATH).'main/auth/profile.php', 'name' => get_lang('Profile')];
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_PATH).'main/messages/inbox.php',
    'name' => get_lang('Messages'),
];

$actions = null;
if ($allowMessage) {
    $actionsLeft = '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.
        Display::return_icon('back.png', get_lang('Back'), null, ICON_SIZE_MEDIUM).'</a>';
    $actionsLeft .= '<a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php">'.
        Display::return_icon('new-message.png', get_lang('ComposeMessage'), null, ICON_SIZE_MEDIUM).'</a>';
    $actionsLeft .= '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.
        Display::return_icon('inbox.png', get_lang('Inbox'), null, ICON_SIZE_MEDIUM).'</a>';
    $actionsLeft .= '<a href="'.api_get_path(WEB_PATH).'main/messages/outbox.php">'.
        Display::return_icon('outbox.png', get_lang('Outbox'), null, ICON_SIZE_MEDIUM).'</a>';

    $form = MessageManager::getSearchForm(api_get_path(WEB_PATH).'main/messages/outbox.php');
    if ($form->validate()) {
        $values = $form->getSubmitValues();
        $keyword = $values['keyword'];
    }
    $actionsRight = $form->returnForm();
}

$action = null;

if (isset($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
}

$keyword = '';
$message_content = null;

$actions .= Display::toolbarAction(
    'toolbar',
    [$actionsLeft, $actionsRight]
);

//MAIN CONTENT
if ($action == 'delete') {
    $delete_list_id = [];
    if (isset($_POST['out'])) {
        $delete_list_id = $_POST['out'];
    }
    if (isset($_POST['id'])) {
        $delete_list_id = $_POST['id'];
    }
    for ($i = 0; $i < count($delete_list_id); $i++) {
        $show_message .= MessageManager::delete_message_by_user_sender(
            api_get_user_id(),
            $delete_list_id[$i]
        );
    }
    $delete_list_id = [];
    $message_content .= MessageManager::outbox_display($keyword);
} elseif ($action == 'deleteone') {
    $delete_list_id = [];
    $id = Security::remove_XSS($_GET['id']);
    MessageManager::delete_message_by_user_sender(api_get_user_id(), $id);
    $delete_list_id = [];
    $message_content .= MessageManager::outbox_display($keyword);
} else {
    $message_content .= MessageManager::outbox_display($keyword);
}

$tpl = new Template(get_lang('Outbox'));
// Block Social Avatar

if ($actions) {
    $tpl->assign('actions', $actions);
}

$tpl->assign('content_inbox', $message_content);
$social_layout = $tpl->get_template('message/inbox.html.twig');
$content = $tpl->fetch($social_layout);
$tpl->assign('message', $show_message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
