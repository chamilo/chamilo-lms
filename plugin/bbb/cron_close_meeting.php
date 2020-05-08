<?php
/**
 * This script initiates a video conference session, calling the BigBlueButton API.
 * @package chamilo.plugin.bigbluebutton
 */

$course_plugin = 'bbb'; //needed in order to load the plugin lang variables
require_once __DIR__.'/config.php';

$plugin = BBBPlugin::create();
$meetingTable = Database::get_main_table('plugin_bbb_meeting');
$roomTable = Database::get_main_table('plugin_bbb_room');

$bbb = new bbb();
if ($bbb->pluginEnabled) {
    $activeSessions = $bbb->getActiveSessions();

    if (!empty($activeSessions)) {
        foreach ($activeSessions as $value) {
            $meetingId = $value['id'];

            $courseInfo = api_get_course_info_by_id($value['c_id']);
            $courseCode = $courseInfo['code'];

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

            if (!empty($meetingBBB)) {
                if (isset($meetingBBB['returncode'])) {
                    $action = (string) $meetingBBB['returncode'];
                    switch ($action) {
                        case 'FAILED':
                            $bbb->endMeeting($value['id'], $courseCode);
                            break;
                        case 'SUCCESS':
                            $i = 0;
                            while ($i < $meetingBBB['participantCount']) {
                                $participantId = $meetingBBB[$i]['userId'];
                                $roomData = Database::select(
                                    '*',
                                    $roomTable,
                                    [
                                        'where' => [
                                            'meeting_id = ? AND participant_id = ?' => [$meetingId, $participantId],
                                        ],
                                        'order' => 'id DESC',
                                    ],
                                    'first'
                                );
                                if (!empty($roomData)) {
                                    $roomId = $roomData['id'];
                                    Database::update(
                                        $roomTable,
                                        ['out_at' => api_get_utc_datetime()],
                                        ['id = ? ' => $roomId]
                                    );
                                }
                                $i++;
                            }
                            break;
                    }
                }
            }
        }
    }
}
