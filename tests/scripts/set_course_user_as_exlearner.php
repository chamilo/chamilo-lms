<?php
/* For license terms, see /license.txt */
/**
 * Change to ex-learner the users subscribed in courses with different project value (user_edition_extra_field_to_check)
 *
 */
exit;
if (PHP_SAPI != 'cli') {
    die('This script can only be launched from the command line');
}

require_once __DIR__.'/../../main/inc/global.inc.php';

if (false === api_get_configuration_value('user_edition_extra_field_to_check')) {
    die('You should set an extra field variable to check, by setting "user_edition_extra_field_to_check" in configuration.php');
}

$extraToCheck = api_get_configuration_value('user_edition_extra_field_to_check');
$tblUser = Database::get_main_table(TABLE_MAIN_USER);
$sql = "SELECT id FROM $tblUser
        WHERE status NOT IN(".ANONYMOUS.")";
$rs = Database::query($sql);
if (Database::num_rows($rs) > 0) {
    while ($row = Database::fetch_array($rs, 'ASSOC')) {
        $userId = $row['id'];
        $userExtra = UserManager::get_extra_user_data_by_field($userId, $extraToCheck);
        if (isset($userExtra[$extraToCheck])) {
            echo "<br>Checking user_id : $userId and its extrafield $extraToCheck with value {$userExtra[$extraToCheck]}:<br>" . PHP_EOL;
            // Get the courses with the same extra value
            $extraFieldValues = new ExtraFieldValue('course');
            $extraItems = $extraFieldValues->get_item_id_from_field_variable_and_field_value($extraToCheck, $userExtra[$extraToCheck], false, false, true);
            $coursesTocheck = [];
            if (!empty($extraItems)) {
                foreach ($extraItems as $items) {
                    $coursesTocheck[] = $items['item_id'];
                }
            }
            // To check in main course
            if (!empty($coursesTocheck)) {
                $tblCourseUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
                $sql1 = "SELECT id FROM $tblCourseUser
                                WHERE user_id = $userId AND c_id NOT IN(".implode(',', $coursesTocheck).")";
                $rs1 = Database::query($sql1);
                if (Database::num_rows($rs1) > 0) {
                    while ($row1 = Database::fetch_array($rs1, 'ASSOC')) {
                        $id = $row1['id'];
                        $upd1 = "UPDATE $tblCourseUser SET relation_type = ".COURSE_EXLEARNER."
                                    WHERE id = $id";
                        Database::query($upd1);
                    }
                }
            }
            // To check in sessions
            if (!empty($coursesTocheck)) {
                $tblSessionCourseUser = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
                $tblSessionUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);
                $sessionsToCheck = [];
                $sql2 = "SELECT id, session_id FROM $tblSessionCourseUser
                                WHERE user_id = $userId AND c_id NOT IN(".implode(',', $coursesTocheck).")";
                $rs2 = Database::query($sql2);
                if (Database::num_rows($rs2) > 0) {
                    while ($row2 = Database::fetch_array($rs2, 'ASSOC')) {
                        $id = $row2['id'];
                        $sessionId = $row2['session_id'];
                        $upd2 = "UPDATE $tblSessionCourseUser SET status = ".COURSE_EXLEARNER."
                                    WHERE id = $id";
                        Database::query($upd2);
                        $sessionsToCheck[] = $sessionId;
                    }
                }
                // It checks if user is ex-learner in all courses in the session to update the session relation type
                if (!empty($sessionsToCheck)) {
                    $sessionsToCheck = array_unique($sessionsToCheck);
                    foreach ($sessionsToCheck as $sessionId) {
                        $checkAll = Database::query("SELECT count(id) FROM $tblSessionCourseUser WHERE user_id = $userId AND session_id = $sessionId");
                        $countAll = Database::result($checkAll, 0, 0);
                        $checkExLearner = Database::query("SELECT count(id) FROM $tblSessionCourseUser WHERE status = ".COURSE_EXLEARNER." AND user_id = $userId AND session_id = $sessionId");
                        $countExLearner = Database::result($checkExLearner, 0, 0);
                        if ($countAll > 0 && $countAll == $countExLearner) {
                            $upd3 = "UPDATE $tblSessionUser SET relation_type = ".COURSE_EXLEARNER."
                                    WHERE user_id = $userId AND session_id = $sessionId";
                            Database::query($upd3);
                        }
                    }
                }
                // @todo To check users inside a class
            }
        }
    }
}
