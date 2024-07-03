<?php
/* For licensing terms, see /license.txt */

/**
 * New lp reminder.
 *
 * To add this extra field for lp option number_of_days_for_completion
 * INSERT INTO extra_field (extra_field_type, field_type, variable, display_text, default_value, field_order, visible_to_self, visible_to_others, changeable, filter, created_at) VALUES
 * (6,	1,	'number_of_days_for_completion',	'NumberOfDaysForCompletion',	'',	0,	1,	0,	1,	0,	NOW());
 *
 * @package chamilo.cron
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
 *
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
    $userInfo = api_get_user_info($toUserId);
    $language = $userInfo['language'];

    $subjectTemplate->assign('RemindXLpCourseX', sprintf(getUserLang('RemindXLpCourseX', $language), $nbRemind, $courseName));

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

    $teachersListString = '';
    $teachers = CourseManager::getTeachersFromCourse($courseId);
    if (!empty($teachers)) {
        $teachersList = [];
        foreach ($teachers as $value) {
            $teacherName = api_get_person_name($value['firstname'], $value['lastname']);
            $teachersList[] = strtoupper($teacherName).': '.$value['email'];
        }
        $teachersListString = implode('<br/>', $teachersList);
    }

    $userFullName = api_get_person_name($userInfo['firstname'], $userInfo['lastname']);
    $urlChamilo = api_get_path(WEB_CODE_PATH);
    $urlLostPw = api_get_path(WEB_CODE_PATH).'auth/lostPassword.php';
    $logoPortal = return_logo();
    $bodyTemplate->assign('HelloX', sprintf(getUserLang('HelloX', $language), $userFullName));
    $bodyTemplate->assign('YouAreRegCourseXFromDateX', sprintf(getUserLang('YouAreRegCourseXFromDateX', $language), $courseName, $registrationDate));
    $bodyTemplate->assign('ThisMessageIsAboutX', sprintf(getUserLang('ThisMessageIsAboutX', $language), $lpProgress));
    $bodyTemplate->assign('StepsToRemindX', sprintf(getUserLang('StepsToRemindX', $language), $urlChamilo, $userInfo['username'], $urlLostPw));
    $bodyTemplate->assign('LpRemindFooterX', sprintf(getUserLang('LpRemindFooterX', $language), $logoPortal, $teachersListString));

    $bodyLayout = $bodyTemplate->get_template(
        'mail/lp_progress_reminder_body.tpl'
    );

    $title = $subjectTemplate->fetch($subjectLayout);
    $content = $bodyTemplate->fetch($bodyLayout);

    return MessageManager::send_message_simple(
        $toUserId,
        $title,
        $content,
        1,
        true
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

/**
 * Number of reminder checking the frequency from NUMBER_OF_DAYS_TO_RESEND_NOTIFICATION.
 *
 * @param $registrationDate
 * @param $nbDaysForLpCompletion
 *
 * @return false|float|int
 */
function getNbReminder($registrationDate, $nbDaysForLpCompletion): int
{
    $date1 = new DateTime($registrationDate);
    $date1->modify("+$nbDaysForLpCompletion day");

    $date2 = new DateTime('now', new DateTimeZone('UTC'));

    $interval = $date1->diff($date2);
    $diffDays = (int) $interval->format('%a');

    $nbRemind = ceil($diffDays / NUMBER_OF_DAYS_TO_RESEND_NOTIFICATION) + 1;

    return $nbRemind;
}

/**
 * It checks if user has to be notified checking the current registration date and nbDaysForLpCompletion value.
 *
 * @param $registrationDate
 * @param $nbDaysForLpCompletion
 */
function isTimeToRemindUser($registrationDate, $nbDaysForLpCompletion): bool
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
 * Notify users for checking Learning path completion.
 *
 * @return null
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
                    $lpProgress = (int) $user['progress'];
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
                    $lpProgress = (int) $user['progress'];
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

/**
 * Get the users in a course also checking the session.
 *
 * @param int   $courseId
 * @param false $checkSession
 *
 * @return array|null
 */
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
 * It returns the register date of a user in a course or session from track_e_default.
 *
 * @param int $courseId
 * @param int $userId
 * @param int $sessionId
 *
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
