<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

$test = true;
$newLine = '<br />';
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

$result = Database::query($sql);
$students = Database::store_result($result);
foreach ($students as $student) {
    $studentId = $student['id'];
    $lastDate = Tracking::get_last_connection_date($studentId, false, true);
    $lastDate = api_get_utc_datetime($lastDate);

    if ($date3Months > $lastDate) {
        $disabledUser = api_get_user_info($studentId);
        if (empty($disabledUser)) {
            continue;
        }
        $userReportList[$studentId]['message'] = "User# $studentId (".$disabledUser['username'].") to be disabled. Case 1. Last connection: $lastDate - 3 months: $date3Months ";

        $language = $disabledUser['language'];
        $subject = get_lang('AccountDisabled', null, $language).': '.$disabledUser['complete_name'];
        $content = get_lang('DisableUserCase1', null, $language);

        $userReportList[$studentId]['message'] .= $newLine.'Mail will be send to: '.$disabledUser['username'].$newLine.'Subject: '.$subject.$newLine.'Content: '.$content.$newLine;

        if (false === $test) {
            UserManager::disable($studentId);
            MessageManager::send_message($studentId, $subject, $content);
        }
    }
}

// 3. Certificate completed not connected 6 months.
$sql = 'SELECT user_id, MAX(login_date) latest_login_date
        FROM gradebook_certificate c
        INNER JOIN track_e_login l
        ON (l.login_user_id = c.user_id)
        GROUP BY user_id  ';

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

        if (1 != $disabledUser['active']) {
            continue;
        }

        $language = $disabledUser['language'];
        $subject = get_lang('AccountDisabled', null, $language).': '.$disabledUser['complete_name'];
        $content = get_lang('DisableUserCase3Student', null, $language);

        $userReportList[$studentId]['message'] = "User# $studentId (".$disabledUser['username'].") to be disabled. Case 3. Last connection: $lastDate - 6 months: $date6Months ";
        $userReportList[$studentId]['message'] .= $newLine.'Mail will be send to: '.$disabledUser['username'].$newLine.'Subject: '.$subject.$newLine.'Content: '.$content.$newLine;

        if (false === $test) {
            UserManager::disable($studentId);
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
        $disabledUser = api_get_user_info($studentId);
        if (empty($disabledUser)) {
            continue;
        }

        $userReportList[$studentId]['message'] = "User# $studentId (".$disabledUser['username'].") to be disabled. Case 2 . Last connection: $lastDate - 6 months: $date6Months ";

        $subject = get_lang('AccountDisabled', null, $disabledUser['language']).': '.$disabledUser['complete_name'];
        $content = get_lang('DisableUserCase2', null, $disabledUser['language']);

        $userReportList[$studentId]['message'] .= $newLine.'Mail will be send to: '.$disabledUser['username'].$newLine.'Subject: '.$subject.$newLine.'Content: '.$content.$newLine;

        $subjectBoss = '';
        $contentBoss = '';
        $studentBoss = UserManager::getFirstStudentBoss($studentId);
        $bossInfo = [];
        if (!empty($studentBoss)) {
            $bossInfo = api_get_user_info($studentBoss);
            if ($bossInfo) {
                $subjectBoss = get_lang('AccountDisabled', null, $bossInfo['language']).': '.$disabledUser['complete_name'];
                $contentBoss = sprintf(get_lang('DisableUserCase2StudentX', null, $bossInfo['language']), $disabledUser['complete_name']);
                $userReportList[$studentId]['message'] .= $newLine.'Mail will be send to: '.$bossInfo['username'].$newLine.'Subject: '.$subjectBoss.$newLine.'Content: '.$contentBoss.$newLine;
            }
        }

        if (false === $test) {
            UserManager::disable($studentId);
            MessageManager::send_message($studentId, $subject, $content);

            if (!empty($bossInfo) && !empty($subjectBoss)) {
                MessageManager::send_message($studentBoss, $subjectBoss, $contentBoss);
            }
            UserManager::removeAllBossFromStudent($studentId);
        }
    }
}

//$newLine = PHP_EOL;

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
