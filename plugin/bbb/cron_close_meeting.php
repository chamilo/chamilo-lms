<?php

/**
 * This script initiates a video conference session, calling the BigBlueButton API.
 */
$course_plugin = 'bbb'; //needed in order to load the plugin lang variables
require_once __DIR__.'/config.php';

$plugin = BBBPlugin::create();
$meetingTable = Database::get_main_table('plugin_bbb_meeting');
$roomTable = Database::get_main_table('plugin_bbb_room');

$applyAllUrls = 'true' === $plugin->get('plugin_bbb_multiple_urls_cron_apply_to_all');

$bbb = new bbb();

if (!$bbb->pluginEnabled) {
    return;
}

$activeSessions = $bbb->getActiveSessions($applyAllUrls);

if (empty($activeSessions)) {
    return;
}

foreach ($activeSessions as $value) {
    $meetingId = $value['id'];
    $courseCode = null;
    $courseInfo = api_get_course_info_by_id($value['c_id']);
    if (!empty($courseInfo)) {
        $courseCode = $courseInfo['code'];
    }

    $meetingBBB = $bbb->getMeetingInfo(
        [
            'meetingId' => $value['remote_id'],
            'password' => $value['moderator_pw'],
        ]
    );

    if ($meetingBBB === false) {
        //checking with the remote_id didn't work, so just in case and
        // to provide backwards support, check with the id
        $params = [
            'meetingId' => $value['id'],
            'password' => $value['moderator_pw'],
        ];
        $meetingBBB = $bbb->getMeetingInfo($params);
    }

    if (empty($meetingBBB)) {
        continue;
    }

    if (!isset($meetingBBB['returncode'])) {
        continue;
    }

    $action = (string) $meetingBBB['returncode'];

    switch ($action) {
        case 'FAILED':
            $bbb->endMeeting($value['id'], $courseCode);
            break;
        case 'SUCCESS':
            Database::update(
                $roomTable,
                ['close' => BBBPlugin::ROOM_CHECK],
                ['meeting_id = ? AND close= ?' => [$meetingId, BBBPlugin::ROOM_OPEN]]
            );

            $i = 0;
            while ($i < $meetingBBB['participantCount']) {
                $participantId = $meetingBBB[$i]['userId'];
                $roomData = Database::select(
                    '*',
                    $roomTable,
                    [
                        'where' => [
                            'meeting_id = ? AND participant_id = ? AND close = ?' => [
                                $meetingId,
                                $participantId,
                                BBBPlugin::ROOM_CHECK,
                            ],
                        ],
                        'order' => 'id DESC',
                    ],
                    'first'
                );

                if (!empty($roomData)) {
                    $roomId = $roomData['id'];
                    if (!empty($roomId)) {
                        Database::update(
                            $roomTable,
                            ['out_at' => api_get_utc_datetime(), 'close' => BBBPlugin::ROOM_OPEN],
                            ['id = ? ' => $roomId]
                        );
                    }
                }
                $i++;
            }

            Database::update(
                $roomTable,
                ['out_at' => api_get_utc_datetime(), 'close' => BBBPlugin::ROOM_CLOSE],
                ['meeting_id = ? AND close= ?' => [$meetingId, BBBPlugin::ROOM_CHECK]]
            );

            break;
    }
}
