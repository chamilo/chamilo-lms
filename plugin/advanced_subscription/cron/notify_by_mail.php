<?php
/* For license terms, see /license.txt */
/**
 * This script generates four session categories.
 *
 * @package chamilo.plugin.advanced_subscription
 */

/**
 * Init.
 */
require_once __DIR__.'/../config.php';
$plugin = AdvancedSubscriptionPlugin::create();
$now = api_get_utc_datetime();
$weekAgo = api_get_utc_datetime('-1 week');
$sessionExtraField = new ExtraField('session');
$sessionExtraFieldValue = new ExtraFieldValue('session');
/**
 * Get session list.
 */
$joinTables = Database::get_main_table(TABLE_MAIN_SESSION).' s INNER JOIN '.
    Database::get_main_table(TABLE_MAIN_SESSION_USER).' su ON s.id = su.session_id INNER JOIN '.
    Database::get_main_table(TABLE_MAIN_USER_REL_USER).' uu ON su.user_id = uu.user_id INNER JOIN '.
    Database::get_main_table(TABLE_ADVANCED_SUBSCRIPTION_QUEUE).' asq ON su.session_id = asq.session_id AND su.user_id = asq.user_id';
$columns = 's.id AS session_id, uu.friend_user_id AS superior_id, uu.user_id AS student_id, asq.id AS queue_id, asq.status AS status';
$conditions = [
    'where' => [
        's.access_start_date >= ? AND uu.relation_type = ? AND asq.updated_at <= ?' => [
            $now,
            USER_RELATION_TYPE_BOSS,
            $weekAgo,
        ],
    ],
    'order' => 's.id',
];

$queueList = Database::select($columns, $joinTables, $conditions);

/**
 * Remind students.
 */
$sessionInfoList = [];
foreach ($queueList as $queueItem) {
    if (!isset($sessionInfoList[$queueItem['session_id']])) {
        $sessionInfoList[$queueItem['session_id']] = api_get_session_info($queueItem['session_id']);
    }
}

foreach ($queueList as $queueItem) {
    switch ($queueItem['status']) {
        case ADVANCED_SUBSCRIPTION_QUEUE_STATUS_START:
        case ADVANCED_SUBSCRIPTION_QUEUE_STATUS_BOSS_APPROVED:
            $data = ['sessionId' => $queueItem['session_id']];
            $data['session'] = api_get_session_info($queueItem['session_id']);
            $data['student'] = api_get_user_info($queueItem['student_id']);
            $plugin->sendMail($data, ADVANCED_SUBSCRIPTION_ACTION_REMINDER_STUDENT);
            break;
        default:
            break;
    }
}

/**
 * Remind superiors.
 */
// Get recommended number of participants
$sessionRecommendedNumber = [];
foreach ($queueList as $queueItem) {
    $row =
        $sessionExtraFieldValue->get_values_by_handler_and_field_variable(
            $queueItem['session_id'],
            'recommended_number_of_participants'
    );
    $sessionRecommendedNumber[$queueItem['session_id']] = $row['value'];
}
// Group student by superior and session
$queueBySuperior = [];
foreach ($queueList as $queueItem) {
    $queueBySuperior[$queueItem['session_id']][$queueItem['superior_id']][$queueItem['student_id']]['status'] = $queueItem['status'];
}

foreach ($queueBySuperior as $sessionId => $superiorStudents) {
    $data = [
        'sessionId' => $sessionId,
        'session' => $sessionInfoList[$sessionId],
        'students' => [],
    ];
    $dataUrl = [
        'action' => 'confirm',
        'sessionId' => $sessionId,
        'currentUserId' => 0,
        'newStatus' => ADVANCED_SUBSCRIPTION_QUEUE_STATUS_BOSS_APPROVED,
        'studentUserId' => 0,
        'is_connected' => true,
        'profile_completed' => 0,
    ];
    foreach ($superiorStudents as $superiorId => $students) {
        $data['superior'] = api_get_user_info($superiorId);
        // Check if superior has at least one student
        if (count($students) > 0) {
            foreach ($students as $studentId => $studentInfo) {
                if ($studentInfo['status'] == ADVANCED_SUBSCRIPTION_QUEUE_STATUS_START) {
                    $data['students'][$studentId] = api_get_user_info($studentId);
                    $dataUrl['studentUserId'] = $studentId;
                    $dataUrl['newStatus'] = ADVANCED_SUBSCRIPTION_QUEUE_STATUS_BOSS_APPROVED;
                    $data['students'][$studentId]['acceptUrl'] = $plugin->getQueueUrl($dataUrl);
                    $dataUrl['newStatus'] = ADVANCED_SUBSCRIPTION_QUEUE_STATUS_BOSS_DISAPPROVED;
                    $data['students'][$studentId]['rejectUrl'] = $plugin->getQueueUrl($dataUrl);
                }
            }

            if (is_array($data['students']) && count($data['students']) > 0) {
                // Check if superior have more than recommended
                if ($sessionRecommendedNumber[$sessionId] >= count($students)) {
                    // Is greater or equal than recommended
                    $plugin->sendMail($data, ADVANCED_SUBSCRIPTION_ACTION_REMINDER_SUPERIOR);
                } else {
                    // Is less than recommended
                    $plugin->sendMail($data, ADVANCED_SUBSCRIPTION_ACTION_REMINDER_SUPERIOR_MAX);
                }
            }
        }
    }
}

/**
 * Remind admins.
 */
$admins = UserManager::get_all_administrators();
$isWesternNameOrder = api_is_western_name_order();
foreach ($admins as &$admin) {
    $admin['complete_name'] = $isWesternNameOrder ?
        $admin['firstname'].', '.$admin['lastname'] : $admin['lastname'].', '.$admin['firstname']
    ;
}
unset($admin);
$queueByAdmin = [];
foreach ($queueList as $queueItem) {
    if ($queueItem['status'] == ADVANCED_SUBSCRIPTION_QUEUE_STATUS_BOSS_APPROVED) {
        $queueByAdmin[$queueItem['session_id']]['students'][$queueItem['student_id']]['user_id'] = $queueItem['student_id'];
    }
}
$data = [
    'admins' => $admins,
];
foreach ($queueByAdmin as $sessionId => $studentInfo) {
    $data['sessionId'] = $sessionId;
    $data['admin_view_url'] = api_get_path(WEB_PLUGIN_PATH).
        'advanced_subscription/src/admin_view.php?s='.$data['sessionId'];
    $data['session'] = $sessionInfoList[$sessionId];
    $data['students'] = $studentInfo['students'];
    $plugin->sendMail($data, ADVANCED_SUBSCRIPTION_ACTION_REMINDER_ADMIN);
}
