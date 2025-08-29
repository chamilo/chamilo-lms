<?php

/* For license terms, see /license.txt */

/**
 * This script initiates a video conference session, calling the BigBlueButton API.
 */
require_once __DIR__.'/../../../vendor/autoload.php';

$course_plugin = 'Bbb'; //needed in order to load the plugin lang variables

$isGlobal = isset($_GET['global']);
$isGlobalPerUser = isset($_GET['user_id']) ? (int) $_GET['user_id'] : false;

// If global setting is used then we delete the course sessions (cidReq/id_session)
if ($isGlobalPerUser || $isGlobal) {
    $cidReset = true;
}

require_once __DIR__.'/config.php';

$logInfo = [
    'tool' => 'Videoconference',
];
Event::registerLog($logInfo);

$tool_name = get_lang('Videoconference');
$tpl = new Template($tool_name);

$vmIsEnabled = false;
$host = '';
$salt = '';
$bbb = new Bbb('', '', $isGlobal, $isGlobalPerUser);

$conferenceManager = $bbb->isConferenceManager();
if ($bbb->isGlobalConference()) {
    api_block_anonymous_users();
} else {
    api_protect_course_script(true);
}

$message = null;
if ($bbb->pluginEnabled) {
    if ($bbb->isServerConfigured()) {
        if ($bbb->isServerRunning()) {
            if (isset($_GET['launch']) && 1 == $_GET['launch']) {
                if (file_exists(__DIR__.'/config.vm.php')) {
                    $config = require __DIR__.'/config.vm.php';
                    $vmIsEnabled = true;
                    $host = '';
                    $salt = '';

                    require __DIR__.'/lib/vm/AbstractVM.php';
                    require __DIR__.'/lib/vm/VMInterface.php';
                    require __DIR__.'/lib/vm/DigitalOceanVM.php';
                    require __DIR__.'/lib/VM.php';

                    $vm = new VM($config);

                    if ($vm->isEnabled()) {
                        try {
                            $vm->resizeToMaxLimit();
                        } catch (\Exception $e) {
                            echo $e->getMessage();
                            exit;
                        }
                    }
                }

                $meetingParams = [];
                $meetingParams['meeting_name'] = $bbb->getCurrentVideoConferenceName();
                $url = null;
                if ($bbb->meetingExists($meetingParams['meeting_name'])) {
                    $joinUrl = $bbb->joinMeeting($meetingParams['meeting_name']);
                    if ($joinUrl) {
                        $url = $joinUrl;
                    }
                } else {
                    if ($bbb->isConferenceManager()) {
                        if (!empty($_POST['documents']) && is_array($_POST['documents'])) {
                            $docs = [];
                            foreach ($_POST['documents'] as $raw) {
                                $json = html_entity_decode($raw);
                                $doc  = json_decode($json, true);
                                if (is_array($doc) && !empty($doc['url'])) {
                                    $docs[] = [
                                        'url'         => $doc['url'],
                                        'filename'    => $doc['filename'] ?? basename(parse_url($doc['url'], PHP_URL_PATH)),
                                        'downloadable'=> true,
                                        'removable'   => true
                                    ];
                                }
                            }
                            if (!empty($docs)) {
                                $meetingParams['documents'] = $docs;
                            }
                        }

                        $maxTotalMb = (int) api_get_course_plugin_setting('bbb', 'bbb_preupload_max_total_mb', api_get_course_info());
                        if ($maxTotalMb <= 0) { $maxTotalMb = 20; }

                        $totalBytes = 0;
                        if (!empty($_POST['documents']) && is_array($_POST['documents'])) {
                            $docs = [];
                            foreach ($_POST['documents'] as $raw) {
                                $json = html_entity_decode($raw);
                                $doc  = json_decode($json, true);
                                if (!is_array($doc) || empty($doc['url'])) { continue; }
                                $totalBytes += (int)($doc['size'] ?? 0);
                                $docs[] = [
                                    'url'         => $doc['url'],
                                    'filename'    => $doc['filename'] ?? basename(parse_url($doc['url'], PHP_URL_PATH)),
                                    'downloadable'=> true,
                                    'removable'   => true,
                                ];
                            }

                            if ($totalBytes > ($maxTotalMb * 1024 * 1024)) {
                                $message = Display::return_message(
                                    sprintf(get_lang('The total size of selected documents exceeds %d MB.'), $maxTotalMb),
                                    'error'
                                );
                                $tpl->assign('message', $message);
                                $tpl->assign('content', $message);
                                $tpl->display_one_col_template();
                                exit;
                            }

                            if (!empty($docs)) {
                                $meetingParams['documents'] = $docs;
                            }
                        }

                        $url = $bbb->createMeeting($meetingParams);
                        if (!$url) {
                            $message = Display::return_message(
                                get_lang('The selected documents exceed the upload limit of the video-conference server. Try fewer/smaller files or contact your administrator.'),
                                'error'
                            );
                        }
                    }
                }

                $meetingInfo = $bbb->findMeetingByName($meetingParams['meeting_name']);
                if (!empty($meetingInfo) && $url) {
                    $bbb->saveParticipant($meetingInfo['id'], api_get_user_id());
                    $bbb->redirectToBBB($url);
                } else {
                    Display::addFlash(
                        Display::return_message($bbb->plugin->get_lang('ThereIsNoVideoConferenceActive'))
                    );
                    $url = $bbb->getListingUrl();
                    header('Location: '.$url);
                    exit;
                }
            } else {
                $url = $bbb->getListingUrl();
                header('Location: '.$url);
                exit;
            }
        } else {
            $message = Display::return_message(get_lang('ServerIsNotRunning'), 'warning');
        }
    } else {
        $message = Display::return_message(get_lang('ServerIsNotConfigured'), 'warning');
    }
} else {
    $message = Display::return_message(get_lang('ServerIsNotConfigured'), 'warning');
}

$tpl->assign('message', $message);
$tpl->assign('content', $message);
$tpl->display_one_col_template();
