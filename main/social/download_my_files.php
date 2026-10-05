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

    return false;
}

// Personal files are private: anonymous access is never allowed, whatever the
// value of the "block_my_files_access" setting is.
api_block_anonymous_users();

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
if (!isAllowedToDownloadMyFile($userId, $file)) {
    api_not_allowed(true);
}

$file = $dir.'/my_files/'.$file;

if (Security::check_abs_path($file, $dir.'my_files/')) {
    $result = DocumentManager::file_send_for_download($file);
    if ($result === false) {
        exit;
    }
}
