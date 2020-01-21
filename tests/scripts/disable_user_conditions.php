<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

$test = true;
$userReportList = [];
$extraFieldValue = new ExtraField('user');
$extraFieldInfo = $extraFieldValue->get_handler_field_info_by_field_variable('termactivated');
$fieldId = $extraFieldInfo['id'];

$date = new Datetime();
$now = $date->format('Y-m-d H:i:s');

$date = $date->sub(new DateInterval('P3M'));
$date3Months = $date->format('Y-m-d H:i:s');

$date = $date->sub(new DateInterval('P6M'));
$date6Months = $date->format('Y-m-d H:i:s');

// 1. Not contract not connected in 3 months
$sql = "SELECT u.id
        FROM user u
        LEFT JOIN extra_field_values ev
        ON u.id = ev.item_id AND field_id = $fieldId
        WHERE (ev.value IS NULL OR ev.value = '') AND u.active = 1 ";

/*
SELECT distinct u.id, access_date                 FROM user u                 LEFT JOIN extra_field_values ev                 ON u.id = ev.item_id AND field_id = 44                 INNER JOIN track_e_lastaccess a                 ON (a.access_user_id = u.id)                 WHERE (ev.value IS NULL OR ev.value = '') AND access_date > '2019-11-01';
*/
$result = Database::query($sql);
$students = Database::store_result($result);
foreach ($students as $student) {
    $studentId = $student['id'];
    $lastDate = Tracking::get_last_connection_date($studentId, false, true);
    $lastDate = api_get_utc_datetime($lastDate);

    if ($date3Months > $lastDate) {
        $userReportList[$studentId]['message'] = "User# $studentId to be disabled. Case 1. Last connection: $lastDate - 3 months: $date3Months ";
        if ($test === false) {
            UserManager::disable($studentId);
        }
    }
}

// 3. Certificate completed not connected 6 months.
$sql = "SELECT user_id, MAX(login_date) latest_login_date
        FROM gradebook_certificate c
        INNER JOIN track_e_login l
        ON (l.login_user_id = c.user_id)
        GROUP BY user_id  ";

$result = Database::query($sql);
$students = Database::store_result($result);
foreach ($students as $student) {
    $studentId = $student['user_id'];
    $lastDate = $student['latest_login_date'];

    if ($date6Months > $lastDate) {
        $disabledUser = api_get_user_info($studentId);
        if (empty($disabledUser)) {
            continue;
        }

        if ($disabledUser['active'] != 1) {
            continue;
        }

        $userReportList[$studentId]['message'] = "User# $studentId to be disabled. Case 3. Last connection: $lastDate - 6 months: $date6Months ";
        if ($test === false) {
            UserManager::disable($studentId);
            $subject = 'AccountDisabled: '.$disabledUser['complete_name'];
            $content = 'AccountDisabled: '.$disabledUser['complete_name'];
            MessageManager::send_message($studentId, $subject, $content);
        }
    }
}

// 2. Validated contract, not connected + 6 months
$sql = "SELECT u.id
        FROM user u
        INNER JOIN extra_field_values ev
        ON u.id = ev.item_id AND field_id = $fieldId
        WHERE ev.value = 1 AND u.active = 1  ";

$result = Database::query($sql);
$students = Database::store_result($result);
foreach ($students as $student) {
    $studentId = $student['id'];
    $lastDate = Tracking::get_last_connection_date($studentId, false, true);
    $lastDate = api_get_utc_datetime($lastDate);

    if ($date6Months > $lastDate) {
        $userReportList[$studentId]['message'] = "User# $studentId to be disabled. Case 2 . Last connection: $lastDate - 6 months: $date6Months ";
        if ($test === false) {
            $disabledUser = api_get_user_info($studentId);
            UserManager::disable($studentId);
            $subject = 'AccountDisabled: '.$disabledUser['complete_name'];
            $content = 'AccountDisabled: '.$disabledUser['complete_name'];

            MessageManager::send_message($studentId, $subject, $content);
            $studentBoss = UserManager::getFirstStudentBoss($studentId);
            if (!empty($studentBoss)) {
                MessageManager::send_message($studentBoss, $subject, $content);
            }

            UserManager::removeAllBossFromStudent($studentId);
        }
    }
}


//$newLine = PHP_EOL;

$newLine = '<br />';
if ($test) {
    echo 'No changes have been made.'.$newLine;
    echo "Now: $now".$newLine;
    echo "3 Months old: $date3Months".$newLine;
    echo "6 Months old: $date6Months".$newLine;
    echo $newLine;
}

ksort($userReportList);

foreach ($userReportList as $data) {
    echo $data['message'].$newLine;
}
