<?php

/* For licensing terms, see /license.txt */

/**
 * This script is a specific script to finetune a complex migration from a
 * Moodle environment, where the usernames and session names need to come
 * from a CSV file rather than the database.
 */

exit;

require_once __DIR__.'/../../main/inc/global.inc.php';
//api_protect_admin_script();

/**
 * The script operates as follows:
 * - read the CSV file
 * - treat the 1st column (concatenation)
 * - look for that username in Chamilo
 * -- if not found, write it in log file
 * -- if found, then update user with:
 * --- extrafield "ScormStudentId" = this username
 * --- username = "Login Chamilo" column in CSV (special case: see below)
 * --- search session to which user is subscribed
 * ---- if none, write it in log file
 * ---- if one, rename session using special naming rule
 */

$file = '/tmp/export-users.csv';
$errorLogFile = '/tmp/error.log';
$successLogFile = '/tmp/success.log';

$sqlFindUser = "SELECT * FROM user WHERE username = '%s'";
$scormField = api_get_configuration_value('scorm_api_extrafield_to_use_as_student_id');
if (empty($scormField)) {
    $scormField = 'ScormStudentId';
}
$sqlFindExtraField = "SELECT id FROM extra_field WHERE display_text = '$scormField' AND field_type = 1";
$res = Database::query($sqlFindExtraField);
if (Database::num_rows($res) < 1) {
    echo 'Could not proceed: field '.$scormField.' does not exist'.PHP_EOL;
    die();
}
$row = Database::fetch_assoc($res);
$extraFieldId = $row['id'];
$sqlFindField = "SELECT id FROM extra_field_values WHERE field_id = $extraFieldId AND item_id = %s";
$sqlUpdateField = "UPDATE extra_field_values SET value = '%s', updated_at = NOW() WHERE field_id = $extraFieldId AND item_id = %s";
$sqlInsertField = "INSERT extra_field_values (field_id, item_id, value, created_at, updated_at) ".
    " VALUES ($extraFieldId, %s, '%s', NOW(), NOW())";
$sqlFindSessions = "SELECT session_id, relation_type, duration FROM session_rel_user WHERE user_id = %s";
$sqlUpdateUsername = "UPDATE user SET username = '%s' WHERE id = %s";
$sqlUpdateSession = "UPDATE session SET name = '%s', promotion_id = %s where id = %s";
$sqlPromotion = "SELECT id FROM promotion WHERE name = 'LearnEasily'";
$resPromotion = Database::query($sqlPromotion);
$promotionId = 0;
if (Database::num_rows($resPromotion) > 0) {
    $row = Database::fetch_assoc($resPromotion);
    $promotionId = $row['id'];
}

$users = Import :: csvToArray($file);
$countUsers = 0;
$notFoundCounter = 0;
foreach ($users as $user) {
    $errorLogString = '';
    $successLogString = '';
    $countUsers ++;
    $customUsername = strtolower(Database::escape_string('efc'.$user['Numero Inscrit']));
    $chamiloUsername = Database::escape_string($user['Login Chamilo']);
    $res = Database::query(sprintf($sqlFindUser, $customUsername));
    if (Database::num_rows($res) < 1) {
        // This user was not found
        $errorLogString .= $customUsername.' not found in Chamilo'.PHP_EOL;
        $notFoundCounter++;
    } else {
        // The user exists. Get user info in $row.
        $row = Database::fetch_assoc($res);
        $successLogString .= $customUsername.' found in Chamilo with ID '.$row['id'].PHP_EOL;

        // Update extra field ScormStudentId
        $resField = Database::query(sprintf($sqlFindField, $row['id']));
        if (Database::num_rows($resField) > 0) {
            $resUpdate = Database::query(sprintf($sqlUpdateField, $customUsername, $row['id']));
            $successLogString .= "Updated $scormField to value '$customUsername' for user ".$row['id'].PHP_EOL;
        } else {
            $resInsert = Database::query(sprintf($sqlInsertField, $row['id'], $customUsername));
            $successLogString .= "Inserted $scormField with value '$customUsername' for user ".$row['id'].PHP_EOL;
        }

        $sessions = [];
        $resFindSessions = Database::query(sprintf($sqlFindSessions, $row['id']));
        while ($rowSessions = Database::fetch_assoc($resFindSessions)) {
            $sessions[] = $rowSessions['session_id'];
        }
        // Verify special condition where the user with the name as in
        // "Login Chamilo" already exists.
        $resFindChamiloUsername = Database::query(sprintf($sqlFindUser, $chamiloUsername));
        if (Database::num_rows($resFindChamiloUsername) > 0) {
            // User with "Login Chamilo" exists.
            $rowUsername = Database::fetch_assoc($resFindChamiloUsername);
            // Get sessions from user efcUsername and assign them to user chamiloUsername
            foreach ($sessions as $session) {
                // subscribe the user with username =
                SessionManager::subscribeUsersToSession($session, [$rowUsername['id']], null, false, true);
                $errorLogString .= 'Special case: '.$chamiloUsername. ' already existed so it should be subscribed to the session of '.$customUsername.". Namely, session $session".PHP_EOL;
                $errorLogString .= "  Example SQL: INSERT INTO session_rel_user (session_id, user_id, relation_type, registered_at) VALUES ($session, ".$row['id'].", 0, NOW())".PHP_EOL;
            }
        } else {
            // Update username
            $resUpdateUser = Database::query(sprintf($sqlUpdateUsername, $chamiloUsername, $row['id']));
            $successLogString .= "Renamed $customUsername to $chamiloUsername".PHP_EOL;
        }

        if (count($sessions) < 1) {
            $errorLogString .= $customUsername.' has no associated session'.PHP_EOL;
        } else {
            // Rename the session
            $name = Database::escape_string($user['Login Chamilo'].' '.$user['Nom inscrit'].' '.$user['Prenom inscrit'].' : '.trim($user['Code formation']).' '.$user['Nom formation']);
            foreach ($sessions as $session) {
                // There should be only one session for this user
                // Rename the session and assign promotion ID
                if (empty($promotionId)) {
                    $promotionId = 'NULL';
                }
                $resUpdateSession = Database::query(sprintf($sqlUpdateSession, $name, $promotionId, $session));
                $successLogString .= "Renamed session $session to $name".PHP_EOL;
            }
        }
    }
    // Write to the log files progressively
    file_put_contents($errorLogFile, $errorLogString, FILE_APPEND | LOCK_EX);
    file_put_contents($successLogFile, $successLogString, FILE_APPEND | LOCK_EX);
}
echo "Not found $notFoundCounter over a total of $countUsers users".PHP_EOL;
