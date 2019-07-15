<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = WhispeakAuthPlugin::create();

$plugin->protectTool();

$userId = ChamiloSession::read(WhispeakAuthPlugin::SESSION_2FA_USER, 0) ?: api_get_user_id();
$showForm = !$userId;

/** @var array $lpItemInfo */
$lpItemInfo = ChamiloSession::read(WhispeakAuthPlugin::SESSION_LP_ITEM, []);
/** @var learnpath $oLp */
$oLp = ChamiloSession::read('oLP', null);

$showHeader = empty($lpItemInfo) || empty($oLp);

if ($userId) {
    $wsid = WhispeakAuthPlugin::getAuthUidValue($userId);

    if (empty($wsid)) {
        header('Location: '.api_get_path(WEB_PLUGIN_PATH).'whispeakauth/authentify_password.php');

        exit;
    }
}

$htmlHeadXtra[] = api_get_js('rtc/RecordRTC.js');
$htmlHeadXtra[] = api_get_js_simple(api_get_path(WEB_PLUGIN_PATH).'whispeakauth/assets/js/RecordAudio.js');

$template = new Template(
    !$showHeader ? '' : $plugin->get_title(),
    $showHeader,
    $showHeader,
    false,
    true,
    false
);
$template->assign('show_form', $showForm);
$template->assign('sample_text', $plugin->getAuthentifySampleText());

$content = $template->fetch('whispeakauth/view/authentify_recorder.html.twig');

$template->assign('header', $plugin->get_title());
$template->assign('content', $content);
$template->display_one_col_template();
