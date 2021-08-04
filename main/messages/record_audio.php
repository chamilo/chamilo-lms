<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'rtc/RecordRTC.js"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'wami-recorder/recorder.js"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'wami-recorder/gui.js"></script>';
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'swfobject/swfobject.js"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'swfobject/swfobject.js"></script>';
$htmlHeadXtra[] = api_get_js('record_audio/record_audio.js');

$tpl = new Template(get_lang('ComposeMessage'), false, false, false, true);
$record = $tpl->get_template('message/record_audio.tpl');

$tpl->assign('user_id', api_get_user_id());
$tpl->assign('audio_title', api_get_unique_id());
$tpl->assign('reload_page', 0);
$tpl->assign('content', $tpl->fetch($record));
$tpl->display_no_layout_template();

exit;
