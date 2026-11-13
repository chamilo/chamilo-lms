<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

/**
 * Checks whether the current user may review the given exercise attempt.
 *
 * @param int $exeId   Exercise attempt id
 * @param int $ownerId User who took the attempt
 *
 * @return bool
 */
function isAllowedToReviewExerciseAttempt($exeId, $ownerId)
{
    $exeId = (int) $exeId;
    $ownerId = (int) $ownerId;
    $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
    $sql = "SELECT c_id, session_id
            FROM $table
            WHERE exe_id = $exeId AND exe_user_id = $ownerId";
    $result = Database::query($sql);

    if (Database::num_rows($result) === 0) {
        return false;
    }

    $attempt = Database::fetch_assoc($result);
    $courseId = (int) $attempt['c_id'];
    $sessionId = (int) $attempt['session_id'];
    $courseInfo = api_get_course_info_by_id($courseId);

    if (empty($courseInfo)) {
        return false;
    }

    if (CourseManager::is_course_teacher(api_get_user_id(), $courseInfo['code'])) {
        return true;
    }

    return !empty($sessionId) && api_is_coach($sessionId, $courseId, false);
}

/**
 * Checks whether the owner of the files teaches the given user, either as a
 * teacher of a course the user is subscribed to (directly or through a session)
 * or as a coach of a session the user is subscribed to.
 * Teachers often insert images from their personal folder into course content
 * (quiz questions, documents...), so these must stay readable by their learners.
 *
 * @param int $ownerId Owner of the my_files folder
 * @param int $userId  User requesting the file
 *
 * @return bool
 */
function isTeacherOfUser($ownerId, $userId)
{
    $ownerId = (int) $ownerId;
    $userId = (int) $userId;
    $tblCourseUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $tblSession = Database::get_main_table(TABLE_MAIN_SESSION);
    $tblSessionUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);
    $tblSessionCourseUser = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

    $sql = "SELECT 1 FROM $tblCourseUser teacher
            WHERE teacher.user_id = $ownerId
                AND teacher.status = ".COURSEMANAGER."
                AND (
                    teacher.c_id IN (SELECT c_id FROM $tblCourseUser WHERE user_id = $userId)
                    OR teacher.c_id IN (SELECT c_id FROM $tblSessionCourseUser WHERE user_id = $userId)
                )
            UNION
            SELECT 1 FROM $tblSessionCourseUser coach
            INNER JOIN $tblSessionCourseUser learner
                ON learner.session_id = coach.session_id AND learner.c_id = coach.c_id
            WHERE coach.user_id = $ownerId AND coach.status = 2 AND learner.user_id = $userId
            UNION
            SELECT 1 FROM $tblSession s
            INNER JOIN $tblSessionUser learner ON learner.session_id = s.id
            WHERE s.id_coach = $ownerId AND learner.user_id = $userId
            LIMIT 1";

    return Database::num_rows(Database::query($sql)) > 0;
}

/**
 * Checks whether the current user may download a file stored in the personal
 * folder (my_files) of another user.
 *
 * @param int    $ownerId      Owner of the my_files folder
 * @param string $relativePath Requested file, relative to the my_files folder
 *
 * @return bool
 */
function isAllowedToDownloadMyFile($ownerId, $relativePath)
{
    $ownerId = (int) $ownerId;
    $currentUserId = api_get_user_id();

    // The owner always has access to their own personal files.
    if ($currentUserId === $ownerId) {
        return true;
    }

    // Platform and session administrators manage every user account.
    if (api_is_platform_admin(true)) {
        return true;
    }

    // Files attached to an exercise answer must stay readable by the teachers
    // and coaches allowed to correct that attempt.
    if (strpos($relativePath, '..') === false
        && preg_match('#^/?upload_answer/(\d+)/\d+/#', $relativePath, $matches)
        && isAllowedToReviewExerciseAttempt($matches[1], $ownerId)
    ) {
        return true;
    }

    // Teachers and HR managers already follow the content produced by their students.
    if (UserManager::isTeacherOfStudent($currentUserId, $ownerId)
        || UserManager::userIsBossOfStudent($currentUserId, $ownerId)
    ) {
        return true;
    }

    // Learners must see the files their teachers inserted in the course content.
    if (isTeacherOfUser($ownerId, $currentUserId)) {
        return true;
    }

    return false;
}

// Restores the pre-1.11.42 behaviour: anyone, even anonymous users, can read the
// personal files of every user (see "restore_access_my_file_for_all" in configuration.php).
$allowAccessForAll = true === api_get_configuration_value('restore_access_my_file_for_all');

// Personal files are private: anonymous access is not allowed.
if (!$allowAccessForAll) {
    api_block_anonymous_users();
}

$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$file = $_GET['file'] ?? '';

if (empty($userId) || empty($file)) {
    exit;
}

$dir = UserManager::getUserPathById($userId, 'system');
if (empty($dir)) {
    exit;
}

// The "user_id" parameter is attacker controlled: never trust it to grant access.
if (!$allowAccessForAll && !isAllowedToDownloadMyFile($userId, $file)) {
    api_not_allowed(true);
}

$file = $dir.'/my_files/'.$file;

if (Security::check_abs_path($file, $dir.'my_files/')) {
    $result = DocumentManager::file_send_for_download($file);
    if ($result === false) {
        exit;
    }
}
