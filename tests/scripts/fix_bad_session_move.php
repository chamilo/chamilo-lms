<?php
/* For licensing terms, see /license.txt */

/**
 *  Script linked to BT#23282 where the feature to move one user from one session to another
 *  had some serious flaws:
 *  - didn't remove records from session_rel_user after the move
 *  - didn't remove records from session_rel_course_rel_user after the move
 *  - didn't move records from track_e_access to the new session
 *  - didn't move records from track_e_access_complete to the new session
 *  - didn't move records from track_e_downloads to the new session
 *  This script fixes those missing steps but also fixes instantaneous issues
 *  due to users using the old session to generate data while already having
 *  access to the new session:
 *  - removes records of the old session in session_rel_course_rel_user
 *  - removes records of the old session in session_rel_user
 *  - removes records of the old session in c_lp_item_view
 *  - removes records of the old session in c_lp_view
 *  - moves all records in track_e_access in the new session
 *  - moves all records in track_e_access_complete in the new session
 *  - moves all records in track_e_course_access in the new session
 *  - moves all records in track_e_last_access in the new session
 *  - moves all records in track_e_downloads in the new session
 *  Note: this script does not look into track_e_exercises, track_e_attempt and gradebook tables.
 */


require_once __DIR__.'/../../main/inc/global.inc.php';
exit;

$userId = 0;
$oldSessionId = 0;
$newSessionId = 0;

if (!empty($argv[1])) {
    $userId = (int) $argv[1];
} else {
    die('No user ID given on the command line. Exiting.'.PHP_EOL);
}
if ($argv[1] === '-h') {
    echo "  Usage: sudo php ".basename(__FILE__)." [User ID] [Old session ID] [New session ID]".PHP_EOL;
    die('Please make sure all parameters are defined and integer values.'.PHP_EOL);
}
if (!empty($argv[2])) {
    $oldSessionId = (int) $argv[2];
} else {
    die('No old session ID given on the command line. Exiting.'.PHP_EOL);
}
if (!empty($argv[3])) {
    $newSessionId = (int) $argv[3];
} else {
    die('No new session ID given on the command line. Exiting.'.PHP_EOL);
}

$sql = "SELECT DATABASE()";
$result = Database::query($sql);
$row = Database::fetch_row($result);
echo "Executing queries in database ".$row[0].":".PHP_EOL;
$sql = "UPDATE track_e_downloads SET down_session_id = $newSessionId WHERE down_session_id = $oldSessionId AND down_user_id = $userId";
$result = Database::query($sql);
echo $sql.PHP_EOL;

$sql = "SELECT count(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'track_e_access_complete'";
$result = Database::query($sql);
$row = Database::fetch_row($result);
if ($row[0] > 0) {
    $sql = "UPDATE track_e_access_complete SET session_id = $newSessionId WHERE session_id = $oldSessionId AND user_id = $userId";
    Database::query($sql);
    echo $sql.PHP_EOL;
} else {
    echo "Table track_e_access_complete doesn't exist in this database. Skipping update.".PHP_EOL;
}
$sql = "UPDATE track_e_access SET access_session_id = $newSessionId WHERE access_session_id = $oldSessionId AND access_user_id = $userId";
Database::query($sql);
echo $sql.PHP_EOL;
$sql = "UPDATE track_e_course_access SET session_id = $newSessionId WHERE session_id = $oldSessionId AND user_id = $userId";
Database::query($sql);
echo $sql.PHP_EOL;
$sql = "UPDATE track_e_lastaccess SET access_session_id = $newSessionId WHERE access_session_id = $oldSessionId AND access_user_id = $userId";
Database::query($sql);
echo $sql.PHP_EOL;
$sql = "DELETE FROM c_lp_item_view WHERE lp_view_id IN (SELECT iid FROM c_lp_view WHERE session_id = $oldSessionId AND user_id = $userId)";
Database::query($sql);
echo $sql.PHP_EOL;
$sql = "DELETE FROM c_lp_view WHERE session_id = $oldSessionId AND user_id = $userId";
Database::query($sql);
echo $sql.PHP_EOL;
$sql = "DELETE FROM session_rel_course_rel_user WHERE session_id = $oldSessionId AND user_id = $userId";
Database::query($sql);
echo $sql.PHP_EOL;
$sql = "DELETE FROM session_rel_user WHERE session_id = $oldSessionId and user_id = $userId";
Database::query($sql);
echo $sql.PHP_EOL;

echo "Done".PHP_EOL;
