<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

if ('true' != api_get_setting('platform_unsubscribe_allowed')) {
    api_not_allowed();
}

$tool_name = get_lang('Unsubscribe');

$message = Display::return_message(get_lang('If you want to unsubscribe completely from this campus and have all your information removed from our database, please click the button below and confirm.'), 'warning');

$form = new FormValidator('user_add');
$form->addElement(
    'button',
    'submit',
    get_lang('Unsubscribe'),
    [
        'onclick' => "javascript:if(!confirm('".addslashes(api_htmlentities(get_lang("If you want to unsubscribe completely from this campus and have all your information removed from our database, please click the button below and confirm.Confirm")))."')) return false;",
    ]
);
$content = $form->returnForm();

if ($form->validate()) {
    $user_info = api_get_user_info();
    $result = UserManager::delete_user($user_info['user_id']);
    if ($result) {
        $message = Display::return_message(
            sprintf(
                get_lang('If you want to unsubscribe completely from this campus and have all your information removed from our database, please click the button below and confirm.Success'),
                $user_info['username']
            )
        );
        $content = null;
        online_logout($user_info['user_id'], false);
        api_not_allowed(true, $message);
    }
}

$tpl = new Template($tool_name);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
