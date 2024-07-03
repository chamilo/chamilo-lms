<?php
/* For licensing terms, see /license.txt */

/**
 * Script to send notification to course's teachers when there are activities in a thread of a forum in his courses in the last X days
 * The number of days is defined at the begining of the script with the variable
 * It will send one sigle mail per user to notify with a line for each thread including thread's title, thread link and number of new post.
 *
 * @package chamilo.cron
 */

//This variable define the number of days in the past to look for activies in the forums
//This variable should be the same as the periodicity of the cron (if the cron is every day, then the variable should be equal to 1)
$numberOfDaysForLastActivy = 1;
require_once __DIR__.'/../inc/global.inc.php';

/**
 * Initialization.
 */
if ('cli' != php_sapi_name()) {
    exit; //do not run from browser
}

$date = new DateTime('now', new DateTimeZone('UTC'));
$date->modify("-$numberOfDaysForLastActivy day");
$startDate = $date->format('Y-m-d H:i:s');

$tablePost = Database::get_course_table(TABLE_FORUM_POST);
$tableThread = Database::get_course_table(TABLE_FORUM_THREAD);
$UpdatedThreads = [];
$sql = "SELECT * FROM $tableThread WHERE thread_date > '".$startDate."'";
$result = Database::query($sql);
while ($row = Database::fetch_array($result)) {
    $courseInfo = api_get_course_info_by_id($row['c_id']);
    $updatedThreads[$row['c_id']]['courseName'] = $courseInfo['name'];
    $sqlNbPost = "SELECT count(*) as nbPost FROM $tablePost WHERE thread_id = '".$row['iid']."' and post_date > '".$startDate."'";
    $resultNbPost = Database::query($sqlNbPost);
    $rowNbPost = Database::fetch_array($resultNbPost);
    $updatedThreads[$row['c_id']][$row['session_id']][$row['iid']] = [
            'threadTitle' => $row['thread_title'],
            'threadNbPost' => $rowNbPost['nbPost'],
            'threadLink' => api_get_path(WEB_PATH).'main/forum/viewthread.php?cidReq='.$courseInfo['code'].'&id_session='.$row['session_id'].'&gidReq=0&gradebook=0&origin=&forum='.$row['forum_id'].'&thread='.$row['iid'],
        ];
}
foreach ($updatedThreads as $courseId => $sessions) {
    foreach ($sessions as $sessionId => $threads) {
        if ($sessionId === 0) {
            $teacherList = CourseManager::getTeachersFromCourse($courseId, false);
            foreach ($teacherList as $teacher) {
                $usersToNotify[$teacher['id']][$courseId]['courseName'] = $sessions['courseName'];
                $usersToNotify[$teacher['id']][$courseId][$sessionId] = $threads;
            }
        } else {
            $courseCoachs = CourseManager::get_coachs_from_course(
                $sessionId,
                $courseId,
                false
            );
            foreach ($courseCoachs as $coach) {
                $usersToNotify[$coach['user_id']][$courseId]['courseName'] = $sessions['courseName'];
                $usersToNotify[$coach['user_id']][$courseId][$sessionId] = $threads;
            }
        }
    }
}
foreach ($usersToNotify as $userId => $notifyInfo) {
    sendMessage($userId, $notifyInfo);
}

/**
 * Send the message to notify the specific user for all its courses and threads that have been updated,
 * manage the corresponding template and send through MessageManager::send_message_simple.
 *
 * @param $toUserId
 * @param $notifyInfo
 *
 * @return bool|int
 */
function sendMessage(
    $toUserId,
    $notifyInfo
) {
    $userInfo = api_get_user_info($toUserId);
    $language = $userInfo['language'];
    $subject = getUserLang('ForumBulkNotificationMailSubject', $language);

    $bodyTemplate = new Template(
        null,
        false,
        false,
        false,
        false,
        false
    );

    $userFullName = api_get_person_name($userInfo['firstname'], $userInfo['lastname']);
    $bodyTemplate->assign('HelloX', sprintf(getUserLang('HelloX', $language), $userFullName));
    $bodyTemplate->assign('NotificationInYouForums', sprintf(getUserLang('NotificationInYouForums', $language)));
    $bodyTemplate->assign('SignatureFormula', sprintf(getUserLang('SignatureFormula', $language)));
    $bodyTemplate->assign('notifyInfo', $notifyInfo);

    $bodyLayout = $bodyTemplate->get_template(
        'mail/cron_forum_update_bulk_notification_body.tpl'
    );

    $content = $bodyTemplate->fetch($bodyLayout);

    return MessageManager::send_message_simple(
        $toUserId,
        $subject,
        $content,
        1,
    );
}

/**
 * Returns a translated (localized) string by user language.
 *
 * @param $variable
 * @param $language
 *
 * @return mixed
 */
function getUserLang($variable, $language)
{
    $languageFilesToLoad = api_get_language_files_to_load($language);
    foreach ($languageFilesToLoad as $languageFile) {
        include $languageFile;
    }

    $translate = $variable;
    if (isset($$variable)) {
        $langVariable = $$variable;
        $translate = $langVariable;
    }

    return $translate;
}

exit();
