<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_block_anonymous_users(true);

$userId = api_get_user_id();
$plugin = WhispeakAuthPlugin::create();

$plugin->protectTool();

$isEnrolledAlready = WhispeakAuthPlugin::checkUserIsEnrolled($userId);

if ($isEnrolledAlready) {
    api_not_allowed(
        true,
        Display::return_message($plugin->get_lang('SpeechAuthAlreadyEnrolled'), 'warning')
    );
}

$sampleText = 'Hola, mundo';

ChamiloSession::write(WhispeakAuthPlugin::SESSION_SENTENCE_TEXT, $sampleText);

$htmlHeadXtra[] = api_get_js('rtc/RecordRTC.js');
$htmlHeadXtra[] = api_get_js_simple(api_get_path(WEB_PLUGIN_PATH).'whispeakauth/assets/js/RecordAudio.js');

$template = new Template();
$template->assign('action', 'enrollment');
$template->assign('sample_text', $sampleText);

$content = $template->fetch('whispeakauth/view/record_audio.html.twig');

$template->assign('header', $plugin->get_title());
$template->assign('content', $content);
$template->display_one_col_template();
