<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = WhispeakAuthPlugin::create();

$plugin->protectTool();

$form = new FormValidator('enter_username', 'post', '#');
$form->addText('username', get_lang('Username'));

$htmlHeadXtra[] = api_get_js('rtc/RecordRTC.js');
$htmlHeadXtra[] = api_get_js_simple(api_get_path(WEB_PLUGIN_PATH).'whispeakauth/assets/js/RecordAudio.js');

$template = new Template();
$template->assign('form', $form->returnForm());
$template->assign('sample_text', $plugin->getAuthentifySampleText());

$content = $template->fetch('whispeakauth/view/authentify_recorder.html.twig');

$template->assign('header', $plugin->get_title());
$template->assign('content', $content);
$template->display_one_col_template();
