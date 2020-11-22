<?php

/* For licensing terms, see /license.txt */

/**
 * Redirect script.
 */
require_once api_get_path(SYS_PATH).'main/inc/global.inc.php';
require_once __DIR__.'/language.php';
$template = new Template(get_lang('SignIn'), false, false, false, false, true, true);

/**
 * Homemade micro-controller.
 */
if (isset($_GET['loginFailed'])) {
    if (isset($_GET['error'])) {
        switch ($_GET['error']) {
            case 'account_expired':
                $error_message = get_lang('AccountExpired');
                break;
            case 'account_inactive':
                $error_message = get_lang('AccountInactive');
                break;
            case 'user_password_incorrect':
                $error_message = get_lang('InvalidId');
                break;
            case 'access_url_inactive':
                $error_message = get_lang('AccountURLInactive');
                break;
            default:
                $error_message = get_lang('InvalidId');
        }
    } else {
        $error_message = get_lang('InvalidId');
    }
}

if (isset($error_message)) {
    $template->assign('error', $error_message);
}

$flash = Display::getFlashToString();
Display::cleanFlashMessages();

if (api_get_setting('allow_registration') === 'true') {
    $urlRegister = api_get_path(WEB_CODE_PATH).'auth/inscription.php?language='.api_get_interface_language();
    $template->assign('url_register', $urlRegister);
}
$urlLostPassword = api_get_path(WEB_CODE_PATH).'auth/lostPassword.php?language='.api_get_interface_language();
$template->assign('url_lost_password', $urlLostPassword);
$template->assign('mgs_flash', $flash);

$layout = $template->get_template('custompage/login.tpl');
$content = $template->fetch($layout);
$template->assign('content', $content);
$template->display_blank_template();
