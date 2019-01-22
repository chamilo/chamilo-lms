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
    'action' => isset($_GET['action']) ? $_GET['action'] : 'inbox',
    'action_details' => '',
    'current_id' => isset($_GET['id']) ? (int) $_GET['id'] : 0,
    'info' => '',
];
Event::registerLog($logInfo);

$allowSocial = api_get_setting('allow_social_tool') == 'true';
$allowMessage = api_get_setting('allow_message_tool') == 'true';

if (isset($_GET['messages_page_nr'])) {
    if ($allowSocial && $allowMessage) {
        header('Location:inbox.php');
        exit;
    }
}

$nameTools = get_lang('Messages');
$show_message = null;
if (isset($_GET['form_reply']) || isset($_GET['form_delete'])) {
    $info_reply = [];
    $info_delete = [];

    if (isset($_GET['form_reply'])) {
        //allow to insert messages
        $info_reply = explode(base64_encode('&%ff..x'), $_GET['form_reply']);
        $count_reply = count($info_reply);
        $button_sent = urldecode($info_reply[4]);
    }

    if (isset($_GET['form_delete'])) {
        //allow to delete messages
        $info_delete = explode(',', $_GET['form_delete']);
        $count_delete = (count($info_delete) - 1);
    }

    if (isset($button_sent)) {
        $title = urldecode($info_reply[0]);
        $content = str_replace("\\", "", urldecode($info_reply[1]));

        $user_reply = $info_reply[2];
        $user_email_base = str_replace(')', '(', $info_reply[5]);
        $user_email_prepare = explode('(', $user_email_base);
        if (count($user_email_prepare) == 1) {
            $user_email = trim($user_email_prepare[0]);
        } elseif (count($user_email_prepare) == 3) {
            $user_email = trim($user_email_prepare[1]);
        }
        $user_id_by_email = MessageManager::get_user_id_by_email($user_email);

        if ($info_reply[6] == 'save_form') {
            $user_id_by_email = $info_reply[2];
        }
        if (isset($user_reply) && !is_null($user_id_by_email) && strlen($info_reply[0]) > 0) {
            MessageManager::send_message($user_id_by_email, $title, $content);
            $show_message .= MessageManager::return_message($user_id_by_email, 'confirmation');
            $message_content .= MessageManager::inbox_display();
            exit;
        } elseif (is_null($user_id_by_email)) {
            $message_box = get_lang('ErrorSendingMessage');
            $show_message .= Display::return_message(api_xml_http_response_encode($message_box), 'error');
            $message_content .= MessageManager::inbox_display();
            exit;
        }
    } elseif (trim($info_delete[0]) == 'delete') {
        for ($i = 1; $i <= $count_delete; $i++) {
            MessageManager::delete_message_by_user_receiver(
                api_get_user_id(),
                $info_delete[$i]
            );
        }
        $message_box = get_lang('SelectedMessagesDeleted');
        $show_message .= Display::return_message(api_xml_http_response_encode($message_box));
        $message_content .= MessageManager::inbox_display();
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
$keyword = '';

// Comes from normal profile
if ($allowMessage) {
    $actionsLeft = '<a href="'.api_get_path(WEB_PATH).'main/messages/new_message.php">'.
        Display::return_icon('new-message.png', get_lang('ComposeMessage'), null, ICON_SIZE_MEDIUM).'</a>';
    $actionsLeft .= '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.
        Display::return_icon('inbox.png', get_lang('Inbox'), null, ICON_SIZE_MEDIUM).'</a>';
    $actionsLeft .= '<a href="'.api_get_path(WEB_PATH).'main/messages/outbox.php">'.
        Display::return_icon('outbox.png', get_lang('Outbox'), null, ICON_SIZE_MEDIUM).'</a>';

    $form = MessageManager::getSearchForm(api_get_path(WEB_PATH).'main/messages/inbox.php');
    try {
        if ($form->validate()) {
            $values = $form->getSubmitValues();
            $keyword = $values['keyword'];
        }
    } catch (HTML_QuickForm_Error $e) {
        echo  $e->getMessage();
    }

    $actionsRight = $form->returnForm();
    $actions = Display::toolbarAction('toolbar', [$actionsLeft, $actionsRight]);
}

// SOCIAL MENU
/*$social_menu_block = '';
if ($allowSocial) {
    // Block Social Menu
    $social_menu_block = SocialManager::show_social_menu('messages');
}*/

// Right content
$message_content = '';

//MAIN CONTENT

if (!isset($_GET['del_msg'])) {
    $message_content .= MessageManager::inbox_display($keyword);
} else {
    $num_msg = (int) $_POST['total'];
    for ($i = 0; $i < $num_msg; $i++) {
        if ($_POST[$i]) {
            //the user_id was necessary to delete a message??
            $show_message .= MessageManager::delete_message_by_user_receiver(
                api_get_user_id(),
                $_POST['_'.$i]
            );
        }
    }
    $message_content .= MessageManager::inbox_display();
}

$tpl = new Template($nameTools);

if ($actions) {
    $tpl->assign('actions', $actions);
}
// Block Social Avatar
// SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'messages');

$tpl->assign('content_inbox', $message_content);
$social_layout = $tpl->get_template('message/inbox.html.twig');
$content = $tpl->fetch($social_layout);
$tpl->assign('message', $show_message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
