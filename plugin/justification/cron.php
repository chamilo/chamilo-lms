<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = Justification::create();
$courseId = api_get_setting('justification_default_course_id', 'justification');

echo 'Justification CRON - '.api_get_local_time().PHP_EOL;

if (empty($courseId)) {
    echo 'No course was set';
    exit;
}

$courseInfo = api_get_course_info_by_id($courseId);
if (empty($courseInfo)) {
    echo "Course #$courseId doesn't exist";
    exit;
}

$fieldList = $plugin->getList();
$totalFields = count($fieldList);

if (empty($fieldList)) {
    echo 'No fields to check. Please add them in the justification plugin';
    exit;
}

$userList = UserManager::get_user_list();
$count = count($userList);

echo "#$count users found".PHP_EOL;
$currentDate = api_get_utc_datetime();

foreach ($userList as $user) {
    $userId = $user['id'];

    echo "Checking user id #$userId".PHP_EOL;

    $userJustificationList = $plugin->getUserJustificationList($userId);
    $userJustificationDocumentList = array_column($userJustificationList, 'date_validity', 'justification_document_id');

    if (count($userJustificationList) < $totalFields) {
        unsubscribeUser($userId, $courseInfo);
        continue;
    }

    if (count($userJustificationList) >= $totalFields) {
        $successList = [];
        foreach ($fieldList as $field) {
            if (isset($userJustificationDocumentList[$field['id']])) {
                $dateValidity = $userJustificationDocumentList[$field['id']];
                if ($dateValidity > $currentDate) {
                    $successList[] = true;
                }
            }
        }
        $countSuccess = count($successList);
        if ($countSuccess === $totalFields) {
            subscribeUser($userId, $courseInfo);
            continue;
        } else {
            echo "User #$userId only got $countSuccess justification(s) out of $totalFields.".PHP_EOL;
        }
    }

    unsubscribeUser($userId, $courseInfo);
}

function unsubscribeUser($userId, $courseInfo)
{
    $courseId = $courseInfo['real_id'];
    CourseManager::unsubscribe_user($userId, $courseInfo['code']);
    echo "Unsubscribe user id #$userId to course #$courseId".PHP_EOL;
}

function subscribeUser($userId, $courseInfo)
{
    $courseId = $courseInfo['real_id'];
    $isUserSubscribed = CourseManager::is_user_subscribed_in_course($userId, $courseInfo['code']);
    if ($isUserSubscribed === false) {
        CourseManager::subscribeUser($userId, $courseInfo['code'], STUDENT);
        echo "Subscribe user id #$userId to course #$courseId".PHP_EOL;
    } else {
        echo "Nothing to do user id #$userId is already subscribed to #$courseId".PHP_EOL;
    }
}
