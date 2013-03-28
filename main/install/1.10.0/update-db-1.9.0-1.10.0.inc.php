<?php
/* For licensing terms, see /license.txt */

$update = function($_configuration, $mainConnection, $dryRun, $output, $app) {

    $mainConnection->beginTransaction();

    $dbNameForm = $_configuration['main_database'];

    $session_table = "$dbNameForm.session";
    $session_rel_course_table = "$dbNameForm.session_rel_course";
    $session_rel_course_rel_user_table = "$dbNameForm.session_rel_course_rel_user";
    $course_table = "$dbNameForm.course";

    //Fixes new changes in sessions
    $sql = "SELECT id, date_start, date_end, nb_days_access_before_beginning, nb_days_access_after_end FROM $session_table ";
    $result = iDatabase::query($sql);
    while ($session = Database::fetch_array($result)) {
        $session_id = $session['id'];

        //Fixing date_start
        if (isset($session['date_start']) && !empty($session['date_start']) && $session['date_start'] != '0000-00-00') {
            $datetime = $session['date_start'].' 00:00:00';
            $update_sql = "UPDATE $session_table SET display_start_date = '$datetime' , access_start_date = '$datetime' WHERE id = $session_id";
            $mainConnection->executeQuery($update_sql);

            //Fixing nb_days_access_before_beginning
            if (!empty($session['nb_days_access_before_beginning'])) {
                $datetime = api_strtotime($datetime, 'UTC') - (86400 * $session['nb_days_access_before_beginning']);
                $datetime = api_get_utc_datetime($datetime);
                $update_sql = "UPDATE $session_table SET coach_access_start_date = '$datetime' WHERE id = $session_id";
                $mainConnection->executeQuery($update_sql);
            }
        }

        //Fixing end_date
        if (isset($session['date_end']) && !empty($session['date_end']) && $session['date_end'] != '0000-00-00') {
            $datetime = $session['date_end'].' 00:00:00';
            $update_sql = "UPDATE $session_table SET display_end_date = '$datetime', access_end_date = '$datetime' WHERE id = $session_id";
            $mainConnection->executeQuery($update_sql);

            //Fixing nb_days_access_before_beginning
            if (!empty($session['nb_days_access_after_end'])) {
                $datetime = api_strtotime($datetime, 'UTC') + (86400 * $session['nb_days_access_after_end']);
                $datetime = api_get_utc_datetime($datetime);
                $update_sql = "UPDATE $session_table SET coach_access_end_date = '$datetime' WHERE id = $session_id";
                $mainConnection->executeQuery($update_sql);
            }
        }
    }

    //Fixes new changes session_rel_course
    $sql = "SELECT id_session, sc.course_code, c.id FROM $course_table c INNER JOIN $session_rel_course_table sc ON sc.course_code = c.code";
    $result = iDatabase::query($sql);
    while ($row = Database::fetch_array($result)) {
        $sql = "UPDATE $session_rel_course_table SET course_id = {$row['id']}
                WHERE course_code = '{$row['course_code']}' AND id_session = {$row['id_session']} ";
        $mainConnection->executeQuery($sql);
    }

    //Fixes new changes in session_rel_course_rel_user
    $sql = "SELECT id_session, sc.course_code, c.id FROM $course_table c INNER JOIN $session_rel_course_rel_user_table sc ON sc.course_code = c.code";
    $result = iDatabase::query($sql);
    while ($row = Database::fetch_array($result)) {
        $sql = "UPDATE $session_rel_course_rel_user_table SET course_id = {$row['id']}
                WHERE course_code = '{$row['course_code']}' AND id_session = {$row['id_session']} ";
        $mainConnection->executeQuery($sql);
    }

    //Updating c_quiz_order
    $teq = "$dbNameForm.c_quiz";
    $seq = "SELECT c_id, session_id, id FROM $teq ORDER BY c_id, session_id, id";
    $req = iDatabase::query($seq);
    $to = "$dbNameForm.c_quiz_order";
    $do = "DELETE FROM $to";
    $mainConnection->executeQuery($do);

    $cid = 0;
    $temp_session_id = 0;
    $order = 1;
    while ($row = Database::fetch_assoc($req)) {
        if ($row['c_id'] != $cid) {
            $cid = $row['c_id'];
            $temp_session_id = $row['session_id'];
            $order = 1;
        } elseif ($row['session_id'] != $temp_session_id) {
            $temp_session_id = $row['session_id'];
            $order = 1;
        }
        //echo $row['c_id'].'-'.$row['session_id'].'-'.$row['id']."\n";
        $ins = "INSERT INTO $to (c_id, session_id, exercise_id, exercise_order)".
               " VALUES ($cid, $temp_session_id, {$row['id']}, $order)";
        $mainConnection->executeQuery($ins);
        $order++;
    }




    if (!$dryRun) {
        $mainConnection->commit();
    }
};