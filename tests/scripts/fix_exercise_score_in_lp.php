<?php
/* For licensing terms, see /license.txt */

/**
 * This script synchronize the exercise score (track_e_exercises.exe_result)
 * with the LP score result (lp_item_view.score).
 * This script works only if 1 there's one attempt
 */

exit;

require_once '../../main/inc/global.inc.php';

api_protect_admin_script();

$tableExercise = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
$tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);

$sql = "SELECT
            exe_id,
            exe_result,
            exe_user_id,
            exe_result,
            exe_exo_id,
            orig_lp_id,
            orig_lp_item_view_id,
            c_id,
            c.code,
            c.id real_id,
            session_id
        FROM $tableExercise t INNER JOIN $tableCourse c
        ON c.id = t.c_id
        WHERE orig_lp_id != '' AND orig_lp_item_view_id = 0 AND status = ''
        ORDER by session_id, c_id, exe_user_id, orig_lp_id, exe_exo_id
";

$result = Database::query($sql);
$items = Database::store_result($result, 'ASSOC');
if (!empty($items)) {
    foreach ($items as $item) {
        $exeId = $item['exe_id'];
        $lpId = $item['orig_lp_id'];
        $userId = $item['exe_user_id'];
        $courseId = $item['real_id'];
        $courseCode = $item['code'];
        $sessionId = $item['session_id'];

        $url = api_get_path(WEB_CODE_PATH)."mySpace/myStudents.php?student=$userId&details=true&course=$courseCode&origin=&id_session=$sessionId";
        echo "Check user page: ". Display::url($url, $url);
        echo '<br />';

        $lp = new learnpath($item['code'], $lpId, $userId);

        /** @var learnpathItem $lpItem */
        foreach ($lp->items as $lpItem) {
            if ($lpItem->get_type() == 'quiz' &&
                $lpItem->get_path() == $item['exe_exo_id']
            ) {
                $lpItemId = $lpItem->get_id();
                $table = Database::get_course_table(TABLE_LP_ITEM_VIEW);
                $tableView = Database::get_course_table(TABLE_LP_VIEW);
                $sql = "SELECT *, iv.id lp_item_view_id FROM $table iv INNER JOIN $tableView v
                        ON iv.c_id = v.c_id AND iv.lp_view_id = v.id
                        WHERE
                            lp_item_id = $lpItemId AND
                            iv.c_id = $courseId AND
                            status = 'completed' AND
                            user_id = $userId AND
                            lp_id = $lpId AND
                            session_id = $sessionId
                        ";
                $result = Database::query($sql);
                $attempts = Database::store_result($result, 'ASSOC');
                var_dump($sql);
                echo '<br />';
                $count = count($attempts);
                if ($count == 1) {
                    $attempt = current($attempts);
                    $score = $item['exe_result'];

                    /* The attempt has empty exe_result and the LP is good
                       there must be another attempt, do nothing. */
                    if ((empty($item['exe_result']) ||  $item['exe_result'] == 0) && !empty($attempt['score'])) {
                        var_dump('Skipped');
                        echo '<br />';
                        continue;
                    }

                    echo "Score: ".$attempt['score']. ' - '.$item['exe_result'].'<br />';

                    $itemViewId = $attempt['lp_item_view_id'];
                    $sql = "UPDATE $table SET
                                score = $score
                            WHERE
                                id = $itemViewId AND
                                lp_item_id = $lpItemId AND
                                c_id = $courseId AND
                    ";
                    //Database::query($sql);
                    var_dump($sql);
                    echo '<br />';
                    $sql = "UPDATE $tableExercise
                            SET orig_lp_item_view_id = $itemViewId
                            WHERE exe_id = $exeId";
                    //Database::query($sql);
                    var_dump($sql);
                    echo '<br />';
                } else {
                    echo 'Cannot update multiple attempts checking attempts:<br />';
                    foreach ($attempts as $attempt) {
                        if ($attempt['score'] == $item['exe_result']) {
                            /*echo "Score: ".$attempt['score']. ' - '.$item['exe_result'].'<br />';
                            $itemViewId = $attempt['id'];
                            $sql = "UPDATE $tableExercise
                                    SET orig_lp_item_view_id = $itemViewId
                                    WHERE exe_id = $exeId";
                            //Database::query($sql);
                            var_dump($sql);
                            echo '<br />';*/
                        }
                    }
                }
            }
        }
        echo '<br />';
    }
}
