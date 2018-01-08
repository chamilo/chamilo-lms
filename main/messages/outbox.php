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

$allowSocial = api_get_setting('allow_social_tool') == 'true';
$allowMessage = api_get_setting('allow_message_tool') == 'true';

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
    'name' => get_lang('Messages')
];

$actions = '';
if ($allowMessage) {
    $actions .= '<a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php">'.
        Display::return_icon('message_new.png', get_lang('ComposeMessage')).'</a>';
    $actions .= '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.
        Display::return_icon('inbox.png', get_lang('Inbox')).'</a>';
    $actions .= '<a href="'.api_get_path(WEB_PATH).'main/messages/outbox.php">'.
        Display::return_icon('outbox.png', get_lang('Outbox')).'</a>';
}

$info_delete_outbox = [];
$info_delete_outbox = isset($_GET['form_delete_outbox']) ? explode(',', $_GET['form_delete_outbox']) : '';
$count_delete_outbox = count($info_delete_outbox) - 1;

if (isset($info_delete_outbox[0]) && trim($info_delete_outbox[0]) == 'delete') {
    for ($i = 1; $i <= $count_delete_outbox; $i++) {
        MessageManager::delete_message_by_user_sender(api_get_user_id(), $info_delete_outbox[$i]);
    }
    $message_box = get_lang('SelectedMessagesDeleted').
        '&nbsp
        <br><a href="../social/index.php?#remote-tab-3">'.
        get_lang('BackToOutbox').
        '</a>';
    Display::addFlash(
        Display::return_message(
            api_xml_http_response_encode($message_box),
            'normal',
            false
        )
    );
    exit;
}

$action = null;
if (isset($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
}

$keyword = '';
$social_right_content = '';
if ($allowSocial) {
    // Block Social Menu
    $social_menu_block = SocialManager::show_social_menu('messages');
    $actionsLeft = '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.
        Display::return_icon('back.png', get_lang('Back'), [], 32).'</a>';

    $form = MessageManager::getSearchForm(api_get_path(WEB_PATH).'main/messages/outbox.php');
    if ($form->validate()) {
        $values = $form->getSubmitValues();
        $keyword = $values['keyword'];
    }
    $actionsRight = $form->returnForm();
    $social_right_content .= Display::toolbarAction(
        'toolbar',
        [$actionsLeft, $actionsRight]
    );
}
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
        MessageManager::delete_message_by_user_sender(
            api_get_user_id(),
            $delete_list_id[$i]
        );
    }
    $delete_list_id = [];
    $social_right_content .= MessageManager::outbox_display($keyword);
} elseif ($action == 'deleteone') {
    $delete_list_id = [];
    $id = Security::remove_XSS($_GET['id']);
    MessageManager::delete_message_by_user_sender(api_get_user_id(), $id);
    $delete_list_id = [];
    $social_right_content .= MessageManager::outbox_display($keyword);
} else {
    $social_right_content .= MessageManager::outbox_display($keyword);
}

$tpl = new Template(get_lang('Outbox'));
// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'messages');

if ($allowSocial) {
    $tpl->assign('social_menu_block', $social_menu_block);
    $tpl->assign('social_right_content', $social_right_content);
    $social_layout = $tpl->get_template('social/inbox.tpl');
    $tpl->display($social_layout);
} else {
    $content = $social_right_content;
    if ($actions) {
        $tpl->assign(
            'actions',
            Display::toolbarAction('toolbar', [$actions])
        );
    }
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
}
