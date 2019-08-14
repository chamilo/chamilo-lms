<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

if (api_get_setting('allow_social_tool') !== 'true') {
    api_not_allowed(true);
}

$logInfo = [
    'tool' => 'Messages',
    'action' => 'promoted_messages_list',
];
Event::registerLog($logInfo);

$nameTools = get_lang('Messages');
$show_message = null;
if (isset($_GET['form_reply']) || isset($_GET['form_delete'])) {
    $info_reply = [];
    $info_delete = [];
    if (isset($_GET['form_delete'])) {
        //allow to delete messages
        $info_delete = explode(',', $_GET['form_delete']);
        $count_delete = (count($info_delete) - 1);
    }

    if (trim($info_delete[0]) === 'delete') {
        for ($i = 1; $i <= $count_delete; $i++) {
            MessageManager::delete_message_by_user_receiver(
                api_get_user_id(),
                $info_delete[$i]
            );
        }
        $message_box = get_lang('SelectedMessagesDeleted');
        $show_message .= Display::return_message(api_xml_http_response_encode($message_box));
        $social_right_content .= MessageManager::inboxDisplay();
        exit;
    }
}

$this_section = SECTION_SOCIAL;
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'social/home.php',
    'name' => get_lang('SocialNetwork'),
];

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'social/promoted_messages.php',
    'name' => get_lang('PromotedMessages'),
];
$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('List')];
$menu = SocialManager::show_social_menu('messages');

// Right content
$social_right_content = '';
$keyword = '';
$actionsLeft = '<a href="'.api_get_path(WEB_CODE_PATH).'social/new_promoted_message.php">'.
    Display::return_icon('new-message.png', get_lang('ComposeMessage'), [], 32).'</a>';

$form = MessageManager::getSearchForm(api_get_self());
if ($form->validate()) {
    $values = $form->getSubmitValues();
    $keyword = $values['keyword'];
}
$actionsRight = $form->returnForm();
$social_right_content .= Display::toolbarAction('toolbar', [$actionsLeft, $actionsRight]);

if (!isset($_GET['del_msg'])) {
    $social_right_content .= MessageManager::getPromotedMessagesGrid($keyword);
} else {
    $num_msg = (int) $_POST['total'];
    for ($i = 0; $i < $num_msg; $i++) {
        if ($_POST[$i]) {
            // The user_id was necessary to delete a message??
            $show_message .= MessageManager::delete_message_by_user_receiver(
                api_get_user_id(),
                $_POST['_'.$i]
            );
        }
    }
    $social_right_content .= MessageManager::getPromotedMessagesGrid();
}

$tpl = new Template(null);
// Block Social Avatar
SocialManager::setSocialUserBlock($tpl, api_get_user_id(), 'messages');

$tpl->assign('social_menu_block', $menu);
$tpl->assign('social_right_content', $social_right_content);
$social_layout = $tpl->get_template('social/inbox.tpl');
$tpl->display($social_layout);
