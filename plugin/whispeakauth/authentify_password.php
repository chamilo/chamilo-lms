<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = WhispeakAuthPlugin::create();

$plugin->protectTool();

$userId = ChamiloSession::read(WhispeakAuthPlugin::SESSION_2FA_USER, 0) ?: api_get_user_id();

/** @var array $lpItemInfo */
$lpItemInfo = ChamiloSession::read(WhispeakAuthPlugin::SESSION_LP_ITEM, []);
/** @var learnpath $oLp */
$oLp = ChamiloSession::read('oLP', null);

$showHeader = empty($lpItemInfo) || empty($oLp);

if (empty($userId)) {
    api_not_allowed($showHeader);
}

$form = new FormValidator(
    'form-login',
    'POST',
    api_get_path(WEB_PLUGIN_PATH).'whispeakauth/ajax/authentify_password.php',
    null,
    null,
    FormValidator::LAYOUT_BOX_NO_LABEL
);
$form->addElement(
    'password',
    'password',
    get_lang('Pass'),
    ['id' => 'password', 'icon' => 'lock fa-fw', 'placeholder' => get_lang('Pass')]
);
$form->addHidden('sec_token', '');
$form->setConstants(['sec_token' => Security::get_token()]);
$form->addButton('submitAuth', get_lang('LoginEnter'), 'check', 'primary', 'default', 'btn-block');

$template = new Template(
    !$showHeader ? '' : $plugin->get_title(),
    $showHeader,
    $showHeader,
    false,
    true,
    false
);
$template->assign('message', Display::return_message($plugin->get_lang('SpeechAuthNotEnrolled'), 'warning'));
$template->assign('form', $form->returnForm());

$content = $template->fetch('whispeakauth/view/authentify_password.html.twig');

$template->assign('header', $plugin->get_title());
$template->assign('content', $content);
$template->display_one_col_template();
