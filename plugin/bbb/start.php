<?php

/**
 * This script initiates a video conference session, calling the BigBlueButton API
 * @package chamilo.plugin.bigbluebutton
 */

require __DIR__ . '/../../vendor/autoload.php';

$course_plugin = 'bbb'; //needed in order to load the plugin lang variables
require_once dirname(__FILE__).'/config.php';

$tool_name = get_lang('Videoconference');
$tpl = new Template($tool_name);

$vmIsEnabled = false;
$host = null;
$salt = null;


$bbb = new bbb();

if ($bbb->plugin_enabled) {
    if ($bbb->is_server_running()) {

        if (isset($_GET['launch']) && $_GET['launch'] == 1) {

            if (file_exists(__DIR__ . '/config.vm.php')) {
                $config = require __DIR__ . '/config.vm.php';
                $vmIsEnabled = true;
                $host = null;
                $salt = null;

                require __DIR__ . '/lib/vm/AbstractVM.php';
                require __DIR__ . '/lib/vm/VMInterface.php';
                require __DIR__ . '/lib/vm/DigitalOceanVM.php';
                require __DIR__ . '/lib/VM.php';

                $vm = new VM($config);

                if ($vm->IsEnabled()) {
                    try {
                        $vm->resizeToMaxLimit();
                    } catch (\Exception $e) {
                        echo $e->getMessage();
                        exit;
                    }
                }
            }

            $meeting_params = array();
            $meeting_params['meeting_name'] = api_get_course_id().'-'.api_get_session_id();

            if ($bbb->meeting_exists($meeting_params['meeting_name'])) {
                $url = $bbb->join_meeting($meeting_params['meeting_name']);
                if ($url) {
                    $bbb->redirectToBBB($url);
                } else {
                    $url = $bbb->create_meeting($meeting_params);
                    $bbb->redirectToBBB($url);
                }
            } else {
                if ($bbb->is_teacher()) {
                    $url = $bbb->create_meeting($meeting_params);
                    $bbb->redirectToBBB($url);
                } else {
                    $url = 'listing.php?'.api_get_cidreq();
                    $bbb->redirectToBBB($url);
                }
            }
        } else {
            $url = 'listing.php?'.api_get_cidreq();
            header('Location: ' . $url);
            exit;
        }
    } else {
        $message = Display::return_message(get_lang('ServerIsNotRunning'), 'warning');
    }
} else {
    $message = Display::return_message(get_lang('ServerIsNotConfigured'), 'warning');
}
$tpl->assign('message', $message);
$tpl->display_one_col_template();
