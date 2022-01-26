<?php
/* For licensing terms, see /license.txt */

/**
 * New lp reminder.
 * @package chamilo.cron
 *
 */

define('NUMBER_OF_DAYS_TO_RESEND_NOTIFICATION', 3);
require_once __DIR__.'/../inc/global.inc.php';

/**
 * Initialization.
 */
if ('cli' != php_sapi_name()) {
    exit; //do not run from browser
}

notifyUsersForCheckingLpCompletion();


/**
 * Send the message to the intended user, manage the corresponding template and send through
 * MessageManager::send_message_simple, using this for the option of human resources managers.
 *
 * @param $toUserId
 * @param $courseId
 * @param $lpProgress
 * @param $registrationDate
 * @param $nbRemind
 * @return bool|int
 */
function sendMessage(
    $toUserId,
    $courseId,
    $lpProgress,
    $registrationDate,
    $nbRemind
) {

    $subjectTemplate = new Template(
        null,
        false,
        false,
        false,
        false,
        false
    );

    $courseInfo = api_get_course_info_by_id($courseId);
    $courseName = $courseInfo['title'];
    $subjectTemplate->assign('nbRemind', $nbRemind);
    $subjectTemplate->assign('courseName', $courseName);

    $subjectLayout = $subjectTemplate->get_template(
        'mail/lp_progress_reminder_subject.tpl'
    );

    $bodyTemplate = new Template(
        null,
        false,
        false,
        false,
        false,
        false
    );

    $userInfo = api_get_user_info($toUserId);
    $userFullName = api_get_person_name($userInfo['firstname'], $userInfo['lastname']);

    $teachersListString = '';
    $teachers = CourseManager::getTeachersFromCourse($courseId);
    if (!empty($teachers)) {
        $teachersList = [];
        foreach ($teachers as $value) {
            $teachersList[] = api_get_person_name(
                $value['firstname'],
                $value['lastname'],
                null,
                PERSON_NAME_EMAIL_ADDRESS
            );
        }
        $teachersListString = implode('<br/>', $teachersList);
    }

    $bodyTemplate->assign('courseName', $courseName);
    $bodyTemplate->assign('userFullName', $userFullName);
    $bodyTemplate->assign('username', $userInfo['username']);
    $bodyTemplate->assign('lpProgress', $lpProgress);
    $bodyTemplate->assign('registerDate', $registrationDate);
    $bodyTemplate->assign('trainers', $teachersListString);
    $bodyTemplate->assign('urlChamilo', api_get_path(WEB_PATH));
    $bodyTemplate->assign('urlLostPw', api_get_path(WEB_CODE_PATH).'auth/lostPassword.php');
    $bodyTemplate->assign('logoPortal', '');

    $bodyLayout = $bodyTemplate->get_template(
        'mail/lp_progress_reminder_body.tpl'
    );
    $tittle = $subjectTemplate->fetch($subjectLayout);
    $content = $bodyTemplate->fetch($bodyLayout);

    return MessageManager::send_message_simple(
        $toUserId,
        $tittle,
        $content,
        1,
        true
    );
}

/**
 * Number of reminder checking the frequency from NUMBER_OF_DAYS_TO_RESEND_NOTIFICATION
 *
 * @param $registrationDate
 * @param $nbDaysForLpCompletion
 * @return false|float|int
 * @throws Exception
 */
function getNbReminder($registrationDate, $nbDaysForLpCompletion):int
{
    $date1 = new DateTime($registrationDate);
    $date1->modify("+$nbDaysForLpCompletion day");

    $date2 = new DateTime('now', new DateTimeZone('UTC'));

    $interval = $date1->diff($date2);
    $diffDays = (int) $interval->format('%a');

    $nbRemind =  ceil($diffDays / NUMBER_OF_DAYS_TO_RESEND_NOTIFICATION) + 1;

    return $nbRemind;
}

/**
 * It checks if user has to be notified checking the current registration date and nbDaysForLpCompletion value
 *
 * @param $registrationDate
 * @param $nbDaysForLpCompletion
 * @return bool
 * @throws Exception
 */
function isTimeToRemindUser($registrationDate, $nbDaysForLpCompletion):bool
{

    $date1 = new DateTime($registrationDate);
    $date1->modify("+$nbDaysForLpCompletion day");
    $startDate = $date1->format('Y-m-d');

    $date2 = new DateTime('now', new DateTimeZone('UTC'));
    $now = $date2->format('Y-m-d');

    $reminder = false;
    if ($startDate < $now) {
        $interval = $date1->diff($date2);
        $diffDays = (int) $interval->format('%a');
        $reminder = (0 === $diffDays % NUMBER_OF_DAYS_TO_RESEND_NOTIFICATION);
    } else {
        $reminder = $startDate === $now;
    }

    return $reminder;
}

/**
 * Notify users for checking Learning path completion
 *
 * @return null
 * @throws Exception
 */
function notifyUsersForCheckingLpCompletion()
{

    $lpItems = getLpIdWithDaysForCompletion();
    if (count($lpItems) == 0) {
        return null;
    }

    $tblCourse = Database::get_main_table(TABLE_MAIN_COURSE);

    $sql = "SELECT id FROM $tblCourse";
    $rs = Database::query($sql);
    if (Database::num_rows($rs) > 0) {
        while ($row = Database::fetch_array($rs)) {
            $courseId = $row['id'];

            // It checks users in main course
            $courseUsers = getCourseUsers($courseId);
            if (!empty($courseUsers)) {
                foreach ($courseUsers as $user) {
                    $toUserId = $user['user_id'];
                    $lpProgress = $user['progress'];
                    $nbDaysForLpCompletion = $lpItems[$user['lp_id']];
                    $registrationDate = getUserCourseRegistrationAt($courseId, $toUserId);
                    $notify = isTimeToRemindUser($registrationDate, $nbDaysForLpCompletion);
                    if ($notify) {
                        $nbRemind = getNbReminder($registrationDate, $nbDaysForLpCompletion);
                        sendMessage($toUserId, $courseId, $lpProgress, $registrationDate, $nbRemind);
                    }
                }
            }

            // It checks users in session course
            $sessionCourseUsers = getCourseUsers($courseId, true);
            if (!empty($sessionCourseUsers)) {
                foreach ($sessionCourseUsers as $user) {
                    $toUserId = $user['user_id'];
                    $lpProgress = $user['progress'];
                    $nbDaysForLpCompletion = $lpItems[$user['lp_id']];
                    $registrationDate = getUserCourseRegistrationAt($courseId, $toUserId, $user['session_id']);
                    $notify = isTimeToRemindUser($registrationDate, $nbDaysForLpCompletion);
                    if ($notify) {
                        $nbRemind = getNbReminder($registrationDate, $nbDaysForLpCompletion);
                        sendMessage($toUserId, $courseId, $lpProgress, $registrationDate, $nbRemind);
                    }
                }
            }

        }
    }

}

function getCourseUsers($courseId, $checkSession = false)
{
    $lpItems = getLpIdWithDaysForCompletion();
    if (count($lpItems) == 0) {
        return null;
    }

    $tblCourseUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $tblSessionCourseUser = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    $tblLp = Database::get_course_table(TABLE_LP_MAIN);
    $tblLpView = Database::get_course_table(TABLE_LP_VIEW);

    $lpItemsString = implode(',', array_keys($lpItems));

    if ($checkSession) {
        $sql = "SELECT
                scu.user_id,
                scu.c_id,
                lp.id as lp_id,
                lpv.progress,
                scu.session_id
            FROM
                $tblSessionCourseUser scu
                INNER JOIN $tblLp lp ON lp.c_id = scu.c_id
                LEFT JOIN $tblLpView lpv ON lpv.lp_id = lp.id AND lpv.user_id = scu.user_id
            WHERE
                scu.c_id = $courseId AND
                (lpv.progress < 100 OR lpv.progress is null) AND
                lp.id IN($lpItemsString)";
    } else {
        $sql = "SELECT
                cu.user_id,
                cu.c_id,
                lp.id as lp_id,
                lpv.progress
            FROM
                $tblCourseUser cu
                INNER JOIN $tblLp lp ON (lp.c_id = cu.c_id)
                LEFT JOIN $tblLpView lpv ON (lpv.lp_id = lp.id AND lpv.user_id = cu.user_id)
            WHERE
                cu.c_id = $courseId AND
                (lpv.progress < 100 OR lpv.progress is null) AND
                lp.id IN($lpItemsString)";
    }

    $rs = Database::query($sql);
    $users = [];
    if (Database::num_rows($rs) > 0) {
        while ($row = Database::fetch_assoc($rs)) {
            $users[] = $row;
        }
    }

    return $users;
}

/**
 * It returns the register date of a user in a course or session from track_e_default
 *
 * @param int $courseId
 * @param int $userId
 * @param int $sessionId
 * @return false|mixed|string|null
 */
function getUserCourseRegistrationAt($courseId, $userId, $sessionId = 0)
{
    $tblTrackDefault = Database::get_main_table(TABLE_STATISTIC_TRACK_E_DEFAULT);
    $sql = "SELECT
            default_date
        FROM $tblTrackDefault
        WHERE c_id = $courseId AND
              default_value_type = 'user_object' AND
              default_event_type = '".LOG_SUBSCRIBE_USER_TO_COURSE."' AND
              default_value LIKE CONCAT('%s:2:\\\\\\\\\"id\\\\\\\\\";i:', $userId, ';%') AND
              session_id = $sessionId";
    $rs = Database::query($sql);
    $registerDate = '';
    if (Database::num_rows($rs) > 0) {
        $registerDate = Database::result($rs, 0, 0);
    }

    return $registerDate;
}

/**
 * Returns the id of the LPs that have days for completion the progress through the extra
 * field 'number_of_days_for_completion'.
 */
function getLpIdWithDaysForCompletion(): array
{
    $extraFieldValuesTable = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
    $extraFieldTable = Database::get_main_table(TABLE_EXTRA_FIELD);
    $sql = "SELECT
            tblExtraFieldValues.item_id as lp_id,
            tblExtraFieldValues.value as ndays
        FROM
            $extraFieldValuesTable AS tblExtraFieldValues
        INNER JOIN $extraFieldTable AS tblExtraField ON (
            tblExtraFieldValues.field_id = tblExtraField.id AND
            tblExtraField.variable = 'number_of_days_for_completion'
            )
        where
              tblExtraFieldValues.value > 0";
    $result = Database::query($sql);
    $return = [];
    while ($element = Database::fetch_array($result)) {
        $return[$element['lp_id']] = $element['ndays'];
    }

    return $return;
}

exit();
