<?php
/* For licensing terms, see /license.txt */

/**
 * New lp reminder.
 *
 * @package chamilo.cron
 *
 * @author  Carlos Alvarado <carlos.alvarado@beeznest.com>
 */
require_once __DIR__.'/../inc/global.inc.php';

// 24-hour format of an hour without leading zeros (in UTC timezone) to execute and search learning paths
$timeSlots = [
    7,
    16,
    20,
];

/**
 * Initialization.
 */
if ('cli' != php_sapi_name()) {
    exit; //do not run from browser
}

$field = new ExtraField('lp');
$activeMessageNewlp = $field->get_handler_field_info_by_field_variable('notify_student_and_hrm_when_available');

if ($activeMessageNewlp == false) {
    // field doesnt exist
    exit();
}
if (!isset($activeMessageNewlp['default_value'])) {
    // field dont have default value
    exit();
}

$currentHour = (int) api_get_utc_datetime(null, false, true)->format('G');

if (!in_array($currentHour, $timeSlots)) {
    exit("Execution nor allowed in this hour ($currentHour).");
}

/**
 * Send the message to the intended user, manage the corresponding template and send through
 * MessageManager::send_message_simple, using this for the option of human resources managers.
 *
 * @return bool|int
 */
function sendMessage(array $toUser, int $fromUser, string $courseName, string $lpName, string $link)
{
    $toUserId = $toUser['user_id'];
    $subjectTemplate = new Template(
        null,
        false,
        false,
        false,
        false,
        false
    );

    $subjectLayout = $subjectTemplate->get_template(
        'mail/learning_path_reminder_subject.tpl'
    );

    $bodyTemplate = new Template(
        null,
        false,
        false,
        false,
        false,
        false
    );
    $bodyTemplate->assign('courseName', $courseName);
    $bodyTemplate->assign('lpName', $lpName);
    $bodyTemplate->assign('link', $link);

    $bodyLayout = $bodyTemplate->get_template(
        'mail/learning_path_reminder_body.tpl'
    );
    $tittle = $subjectTemplate->fetch($subjectLayout);
    $content = $bodyTemplate->fetch($bodyLayout);

    return MessageManager::send_message_simple(
        $toUserId,
        $tittle,
        $content,
        $fromUser,
        true
    );
    // $drhList = UserManager::getDrhListFromUser($receiverUserId);
}

/**
 * Obtains the data of the learning path and course searched by the id of the LP.
 */
function getLpDataByArrayId(array $lpid = []): array
{
    if (count($lpid) == 0) {
        return [];
    }
    $tblCourse = Database::get_main_table(TABLE_MAIN_COURSE);
    $lpTable = Database::get_course_table(TABLE_LP_MAIN);
    $sql = "SELECT
            tblCourse.title AS course_name,
            tblCourse.code AS code,
            tblLp.id AS lp_id,
            tblLp.c_id AS c_id,
            tblLp.name AS name
        FROM
            $lpTable AS tblLp
            INNER JOIN $tblCourse AS tblCourse ON tblLp.c_id = tblCourse.id
        WHERE
            tblLp.iid IN ( ".implode(',', $lpid)." )";
    $result = Database::query($sql);
    $return = [];
    while ($element = Database::fetch_array($result)) {
        $return[$element['lp_id']] = $element;
    }

    return $return;
}

/**
 * Returns the id of the LPs that have the notification option active through the extra
 * field 'notify_student_and_hrm_when_available'.
 */
function getLpIdWithNotify(): array
{
    $extraFieldValuesTable = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
    $extraFieldTable = Database::get_main_table(TABLE_EXTRA_FIELD);
    $sql = "SELECT
            tblExtraFieldValues.item_id as lp_id
        FROM
            $extraFieldValuesTable AS tblExtraFieldValues
        INNER JOIN $extraFieldTable AS tblExtraField ON (
            tblExtraFieldValues.field_id = tblExtraField.id AND
            tblExtraField.variable = 'notify_student_and_hrm_when_available'
            )
        where
              tblExtraFieldValues.value = 1";
    $result = Database::query($sql);
    $return = [];
    while ($element = Database::fetch_array($result)) {
        $return[] = $element['lp_id'];
    }

    return $return;
}

function getTutorIdFromCourseRelUser($cId = 0, $lpId = 0): int
{
    $lpTable = Database::get_course_table(TABLE_LP_MAIN);
    $tblCourseRelUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $sql = "SELECT DISTINCT
            tblCourseRelUser.user_id AS user_id
        FROM
            $lpTable AS tblLp
            INNER JOIN $tblCourseRelUser AS tblCourseRelUser ON ( tblCourseRelUser.c_id = tblLp.c_id)
        WHERE
            tblCourseRelUser.user_id  IS NOT NULL AND
            tblCourseRelUser.status = 1 AND
            tblLp.id = $lpId AND
            tblLp.c_id = $cId";
    $result = Database::query($sql);
    $data = Database::fetch_assoc($result);

    return (isset($data['user_id'])) ? (int) $data['user_id'] : 0;
}

function sendToArray(&$data, &$type, &$message, $lpId = 0)
{
    foreach ($data as $user) {
        $userName = $user['userInfo']['complete_name'];
        $userId = $user['userInfo']['user_id'];
        $fromUser = $user['fromUser'];
        $courseName = $user['courseName'];
        $lpName = $user['lpName'];
        $send = sendMessage(
            $user['userInfo'],
            $fromUser,
            $courseName,
            $lpName,
            $user['link']
        );
        $message .= "\n$type - Lp Id '$lpId' User Id '$userId' Sent to '$userName' Message id '$send' Lp name '$lpName'";
    }
}

function getCurrentTimeSlot(int $currentHour, array $timeSlots = []): ?array
{
    $index = array_search($currentHour, $timeSlots);
    $yesterday = false;

    if (false === $index) {
        return null;
    }

    if (0 === $index) {
        $index = count($timeSlots) - 1;
        $yesterday = true;
    } else {
        $index--;
    }

    $startHour = $timeSlots[$index];

    if ($yesterday) {
        $startDate = api_get_utc_datetime(null, false, true)->modify("yesterday $startHour:00");
    } else {
        $startDate = api_get_utc_datetime(null, false, true)->modify("today $startHour:00");
    }
    $endDate = api_get_utc_datetime(null, false, true)->modify("today $currentHour:00");

    return [$startDate, $endDate];
}

function learningPaths(int $currentHour, array $timeSlots = [])
{
    $lpItems = getLpIdWithNotify();
    if (count($lpItems) == 0) {
        return null;
    }

    [$startDate, $endDate] = getCurrentTimeSlot($currentHour, $timeSlots);

    $tutors = [];
    $lpItemsString = implode(',', $lpItems);
    $lpsData = getLpDataByArrayId($lpItems);
    $itemProcessed = [];
    $lpTable = Database::get_course_table(TABLE_LP_MAIN);
    $tblCourseRelUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $tblSessionCourseUser = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    $tblItempProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);
    /* Gets subscribed users individually in lp's by LearnpathSubscription */
    $sql = "SELECT DISTINCT
            tblItemProperty.session_id as session_id,
            tblItemProperty.to_user_id as user_id,
            tblItemProperty.insert_user_id as from_user_id,
            tblLp.id AS lp_id,
            tblItemProperty.lastedit_type
        FROM
            $tblItempProperty as tblItemProperty
            INNER JOIN $lpTable as tblLp ON
                (
                    tblLp.iid = tblItemProperty.ref AND
                    tblItemProperty.lastedit_type = 'LearnpathSubscription'
                )
        WHERE
            publicated_on < '".$endDate->format('Y-m-d H:i:s')."' AND
            publicated_on >= '".$startDate->format('Y-m-d H:i:s')."' AND
            tblItemProperty.to_user_id IS NOT NULL AND
            tblLp.id in ($lpItemsString)";
    $result = Database::query($sql);
    $groupUsers = [];

    while ($row = Database::fetch_array($result)) {
        $lpId = (int) $row['lp_id'];
        $lpData = [];
        if (isset($lpsData[$lpId])) {
            $lpData = $lpsData[$lpId];
        }
        $courseName = $lpData['course_name'] ?? null;
        $courseCode = $lpData['code'] ?? null;
        $lpName = $lpData['name'] ?? null;

        $sessionId = (int) $row['session_id'];
        $toUser = (int) $row['user_id'];
        $fromUser = (int) $row['from_user_id'];
        $userInfo = api_get_user_info($toUser);
        $href = api_get_path(WEB_CODE_PATH).
            'lp/lp_controller.php?cidReq='.htmlspecialchars($courseCode).
            "&id_session=$sessionId &action=view&lp_id=$lpId&gidReq=0&gradebook=0&origin=";
        $link = "<a href='$href'>$href</a>";
        $groupUsers[$lpId][$sessionId][$toUser] = [
            'userInfo' => $userInfo,
            'fromUser' => $fromUser,
            'courseName' => $courseName,
            'lpName' => $lpName,
            'link' => $link,
        ];
        $itemProcessed[$lpId][$sessionId]['LearnpathSubscription'][$toUser] = $groupUsers[$lpId][$sessionId][$toUser];
    }
    /* Gets subscribed users by classes in lp's by LearnpathSubscription */
    $sql = "SELECT DISTINCT
             tblItemProperty.session_id as session_id,
            tblItemProperty.to_group_id as group_id,
            tblUsergroupRelUser.user_id as user_id,
            tblItemProperty.insert_user_id as from_user_id,
            tblLp.id AS lp_id,
            tblItemProperty.lastedit_type
        FROM
            $tblItempProperty as tblItemProperty
            INNER JOIN $lpTable as tblLp ON
                (
                    tblLp.iid = tblItemProperty.ref AND
                    tblItemProperty.lastedit_type = 'LearnpathSubscription'
                )
        INNER JOIN usergroup_rel_user as tblUsergroupRelUser on (
            tblItemProperty.to_group_id = tblUsergroupRelUser.usergroup_id
            )
        WHERE
            publicated_on < '".$endDate->format('Y-m-d H:i:s')."' AND
            publicated_on >= '".$startDate->format('Y-m-d H:i:s')."' AND
            tblItemProperty.to_group_id IS NOT NULL AND
            tblLp.id in ($lpItemsString)";
    $result = Database::query($sql);
    $groupUsers = [];
    while ($row = Database::fetch_array($result)) {
        $lpId = (int) $row['lp_id'];
        $lpData = [];
        if (isset($lpsData[$lpId])) {
            $lpData = $lpsData[$lpId];
        }
        $courseName = $lpData['course_name'] ?? null;
        $courseCode = $lpData['code'] ?? null;
        $lpName = $lpData['name'] ?? null;

        $sessionId = (int) $row['session_id'];
        $toUser = (int) $row['user_id'];
        $fromUser = (int) $row['from_user_id'];
        $userInfo = api_get_user_info($toUser);
        $href = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'
            .api_get_cidreq_params($courseCode, $sessionId).'&'
            .http_build_query(
                [
                    'gradebook' => '0',
                    'origin' => '',
                    'lp_id' => $lpId,
                    'action' => 'view',
                ]
            );
        $link = "<a href='$href'>$href</a>";
        $groupUsers[$lpId][$sessionId][$toUser] = [
            'userInfo' => $userInfo,
            'fromUser' => $fromUser,
            'courseName' => $courseName,
            'lpName' => $lpName,
            'link' => $link,
        ];
        $itemProcessed[$lpId][$sessionId]['LearnpathSubscription'][$toUser] = $groupUsers[$lpId][$sessionId][$toUser];
    }
    /* Get users who are enrolled in the course */

    $sql = "SELECT DISTINCT
            tblCourseRelUser.user_id AS user_id,
            tblLp.id AS lp_id,
            tblLp.c_id AS c_id
        FROM
            $lpTable AS tblLp
            INNER JOIN $tblCourseRelUser AS tblCourseRelUser ON ( tblCourseRelUser.c_id = tblLp.c_id)
        WHERE
            publicated_on < '".$endDate->format('Y-m-d H:i:s')."' AND
            publicated_on >= '".$startDate->format('Y-m-d H:i:s')."' AND
            tblCourseRelUser.user_id  IS NOT NULL AND
            tblCourseRelUser.status = 5 AND
            tblLp.id in ($lpItemsString)";

    $result = Database::query($sql);
    while ($row = Database::fetch_array($result)) {
        $lpId = (int) $row['lp_id'];
        $sessionId = 0;
        if (isset($lpsData[$lpId])) {
            $lpData = $lpsData[$lpId];
        }
        if (!isset($tutors[$row['c_id']][$row['lp_id']])) {
            $tutors[$row['c_id']][$row['lp_id']] = getTutorIdFromCourseRelUser($row['c_id'], $row['lp_id']);
        }
        $courseName = $lpData['course_name'] ?? null;
        $courseCode = $lpData['code'] ?? null;
        $lpName = $lpData['name'] ?? null;
        $toUser = (int) $row['user_id'];
        $fromUser = $tutors[$row['c_id']][$row['lp_id']];
        $userInfo = api_get_user_info($toUser);
        $href = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'
            .api_get_cidreq_params($courseCode, $sessionId).'&'
            .http_build_query(
                [
                    'gradebook' => '0',
                    'origin' => '',
                    'lp_id' => $lpId,
                    'action' => 'view',
                ]
            );
        $link = "<a href='$href'>$href</a>";
        if (!isset($itemProcessed[$lpId][$sessionId]['LearnpathSubscription'])) {
            $groupUsers[$lpId][$sessionId][$toUser] = [
                'userInfo' => $userInfo,
                'fromUser' => $fromUser,
                'courseName' => $courseName,
                'lpName' => $lpName,
                'link' => $link,
            ];
            $itemProcessed[$lpId][$sessionId]['NoLpSubscription'][$toUser] = $groupUsers[$lpId][$sessionId][$toUser];
        }
    }
    /** Get the users who are registered in the sessions */
    $sql = "SELECT DISTINCT
            tblSessionRelCourseRelUser.user_id AS user_id,
            tblLp.id AS lp_id,
            tblSessionRelCourseRelUser.session_id AS session_id,
            tblLp.c_id AS c_id,
            tblSessionRelCourseRelUser.status AS status
        FROM
            $lpTable AS tblLp
            INNER JOIN $tblSessionCourseUser AS tblSessionRelCourseRelUser ON (
                tblSessionRelCourseRelUser.c_id = tblLp.c_id)
        WHERE
            publicated_on < '".$endDate->format('Y-m-d H:i:s')."' AND
            publicated_on >= '".$startDate->format('Y-m-d H:i:s')."' AND
            tblSessionRelCourseRelUser.user_id  IS NOT NULL AND
            tblLp.id in ($lpItemsString) AND
            tblSessionRelCourseRelUser.status = 0
        ORDER BY tblSessionRelCourseRelUser.status";
    $result = Database::query($sql);
    while ($row = Database::fetch_array($result)) {
        $lpId = (int) $row['lp_id'];
        $sessionId = 0;
        if (isset($lpsData[$lpId])) {
            $lpData = $lpsData[$lpId];
        }
        $courseName = $lpData['course_name'] ?? null;
        $courseCode = $lpData['code'] ?? null;
        $lpName = $lpData['name'] ?? null;
        $toUser = (int) $row['user_id'];
        if (!isset($tutors[$row['c_id']][$row['lp_id']])) {
            $tutors[$row['c_id']][$row['lp_id']] = getTutorIdFromCourseRelUser($row['c_id'], $row['lp_id']);
        }
        $fromUser = $tutors[$row['c_id']][$row['lp_id']];
        $userInfo = api_get_user_info($toUser);
        $href = api_get_path(WEB_CODE_PATH).'lp/lp_controller.php?'
            .api_get_cidreq_params($courseCode, $sessionId).'&'
            .http_build_query(
                [
                    'gradebook' => '0',
                    'origin' => '',
                    'lp_id' => $lpId,
                    'action' => 'view',
                ]
            );
        $link = "<a href='$href'>$href</a>";
        if (!isset($itemProcessed[$lpId][$sessionId]['LearnpathSubscription'])) {
            $groupUsers[$lpId][$sessionId][$toUser] = [
                'userInfo' => $userInfo,
                'fromUser' => $fromUser,
                'courseName' => $courseName,
                'lpName' => $lpName,
                'link' => $link,
            ];
            $itemProcessed[$lpId][$sessionId]['NoLpSubscription'][$toUser] = $groupUsers[$lpId][$sessionId][$toUser];
        }
    }

    /**
     * Send the emails to the corresponding students and their DRHs, Bearing in mind that if they exist through
     * LearnpathSubscription, it will not send anything in the other elements.
     */
    $message = '';
    foreach ($itemProcessed as $lpId => $sessions) {
        foreach ($sessions as $sessionId => $types) {
            foreach ($types as $type => $users) {
                if ('LearnpathSubscription' == $type) {
                    sendToArray($users, $type, $message, $lpId);
                } elseif (!isset($itemProcessed[$lpId][$sessionId]['LearnpathSubscription'])) {
                    sendToArray($users, $type, $message, $lpId);
                }
            }
        }
    }
    echo "$message\n\n";
}

learningPaths($currentHour, $timeSlots);

exit();
