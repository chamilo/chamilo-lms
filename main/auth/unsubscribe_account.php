<?php
/* For licensing terms, see /license.txt */
require_once '../inc/global.inc.php';

if (api_get_setting('platform_unsubscribe_allowed') != 'true') {
    api_not_allowed();
}

$tool_name = get_lang('Unsubscribe');

$message = Display::return_message(get_lang('UnsubscribeFromPlatform'), 'warning');

$form = new FormValidator('user_add');
$form->addElement('button', 'submit', get_lang('Unsubscribe'), array('onclick' => "javascript:if(!confirm('".addslashes(api_htmlentities(get_lang("UnsubscribeFromPlatformConfirm")))."')) return false;"));
$content = $form->return_form();

if ($form->validate()) {    
    $user_info = api_get_user_info();
    $result = UserManager::delete_user($user_info['user_id']);    
    if ($result) {
        $message = Display::return_message(sprintf(get_lang('UnsubscribeFromPlatformSuccess', $user_info['username'])));
        $content = null;
        online_logout($user_info['user_id'], false);
        api_not_allowed(true, $message);
    }    
}

$tpl = new Template($tool_name);
$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
