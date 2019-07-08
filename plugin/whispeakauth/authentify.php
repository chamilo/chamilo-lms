<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = WhispeakAuthPlugin::create();

$plugin->protectTool();

$userId = ChamiloSession::read(WhispeakAuthPlugin::SESSION_2FA_USER, 0);
$is2fa = (bool) $userId;

$htmlHeadXtra[] = api_get_js('rtc/RecordRTC.js');
$htmlHeadXtra[] = api_get_js_simple(api_get_path(WEB_PLUGIN_PATH).'whispeakauth/assets/js/RecordAudio.js');

$template = new Template();

$template->assign('show_form', !$is2fa);
$template->assign('sample_text', $plugin->getAuthentifySampleText());

$content = $template->fetch('whispeakauth/view/authentify_recorder.html.twig');

$template->assign('header', $plugin->get_title());
$template->assign('content', $content);
$template->display_one_col_template();
