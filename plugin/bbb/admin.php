<?php
/**
 * This script initiates a video conference session, calling the BigBlueButton API
 * @package chamilo.plugin.bigbluebutton
 */

$course_plugin = 'bbb'; //needed in order to load the plugin lang variables
$cidReset = true;

require_once __DIR__ . '/../../main/inc/global.inc.php';

api_protect_admin_script();

$plugin = BBBPlugin::create();
$tool_name = $plugin->get_lang('Videoconference');
$tpl = new Template($tool_name);

$isGlobal = isset($_GET['global']) ? true : false;

$bbb = new bbb('', '', $isGlobal);
$action = isset($_GET['action']) ? $_GET['action'] : null;

$meetings = $bbb->getMeetings();

if (!empty($meetings)) {
    $meetings = array_reverse($meetings);
}

if (!$bbb->isServerRunning()) {
    Display::addFlash(
        Display::return_message(get_lang('ServerIsNotRunning'), 'error')
    );
}

$tpl->assign('meetings', $meetings);

$content = $tpl->fetch('bbb/admin.tpl');

$tpl->assign('header', get_lang('RecordList'));
$tpl->assign('content', $content);
$tpl->display_one_col_template();
