<?php
/* For license terms, see /license.txt */

require __DIR__.'/../../main/inc/global.inc.php';

$plugin = OAuth2::create();

$pluginEnabled = $plugin->get(OAuth2::SETTING_ENABLE);
$managementLoginEnabled = $plugin->get(OAuth2::SETTING_MANAGEMENT_LOGIN_ENABLE);

if ('true' !== $pluginEnabled || 'true' !== $managementLoginEnabled) {
    header('Location: '.api_get_path(WEB_PATH));

    exit;
}

$userId = api_get_user_id();

if (!($userId) || api_is_anonymous($userId)) {
    $managementLoginName = $plugin->get(OAuth2::SETTING_MANAGEMENT_LOGIN_NAME);

    if (empty($managementLoginName)) {
        $managementLoginName = $plugin->get_lang('ManagementLogin');
    }

    $template = new Template($managementLoginName);
    // Only display if the user isn't logged in.
    $template->assign('login_language_form', api_display_language_form(true, true));
    $template->assign('login_form', $template->displayLoginForm());

    $content = $template->fetch('oauth2/view/login.tpl');

    $template->assign('content', $content);
    $template->display_one_col_template();
}
