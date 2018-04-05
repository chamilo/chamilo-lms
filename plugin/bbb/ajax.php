<?php
/**
 * This script initiates a video conference session, calling the BigBlueButton API.
 *
 * @package chamilo.plugin.bigbluebutton
 */
$course_plugin = 'bbb'; //needed in order to load the plugin lang variables
$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;
$meetingId = isset($_REQUEST['meeting']) ? intval($_REQUEST['meeting']) : 0;

$bbb = new bbb('', '');

switch ($action) {
    case 'check_m4v':
        if (!api_is_platform_admin()) {
            api_not_allowed();
            exit;
        }

        if (!$meetingId) {
            exit;
        }

        if ($bbb->checkDirectMeetingVideoUrl($meetingId)) {
            $meetingInfo = Database::select(
                '*',
                'plugin_bbb_meeting',
                ['where' => ['id = ?' => intval($meetingId)]],
                'first'
            );

            $url = $meetingInfo['video_url'].'/capture.m4v';
            $link = Display::url(
                Display::return_icon('save.png', get_lang('DownloadFile')),
                $meetingInfo['video_url'].'/capture.m4v',
                ['target' => '_blank']
            );

            header('Content-Type: application/json');
            echo json_encode(['url' => $url, 'link' => $link]);
        }
        break;
}
