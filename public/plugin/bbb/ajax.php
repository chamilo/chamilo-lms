<?php
/**
 * This script initiates a video conference session, calling the BigBlueButton API.
 */

use Chamilo\CoreBundle\Component\Utils\ActionIcon;

$course_plugin = 'bbb'; //needed in order to load the plugin lang variables
$cidReset = true;

require_once __DIR__.'/../../main/inc/global.inc.php';

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;
$meetingId = isset($_REQUEST['meeting']) ? (int) ($_REQUEST['meeting']) : 0;

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
                ['where' => ['id = ?' => (int) $meetingId]],
                'first'
            );

            $url = $meetingInfo['video_url'].'/capture.m4v';
            $link = Display::url(
                Display::getMdiIcon(ActionIcon::SAVE_FORM, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Download file')),
                $meetingInfo['video_url'].'/capture.m4v',
                ['target' => '_blank']
            );

            header('Content-Type: application/json');
            echo json_encode(['url' => $url, 'link' => $link]);
        }
        break;
}
