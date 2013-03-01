<?php
/**
 * Fix missing attendances by checking all empty attendances in the
 * c_attendance_sheet table and calling, for each miss, the corresponding
 * web service (transaction_34) to update the data.
 */
require '../../main/inc/global.inc.php';
require '../../main/inc/lib/attendance.lib.php';
require 'config.php';
require 'db_matches.php';
require 'migration.class.php';
require 'migration.mssql.class.php';
require 'migration.custom.class.php';
$sql = "SELECT count(*) FROM c_attendance_sheet WHERE presence = 4";
$res = Database::query($sql);
$count = Database::result($res);
echo "Found $count undefined attendances\n";

$s1 = "SELECT id FROM user_field WHERE field_variable = 'uidIdPersona'";
$r1 = Database::query($s1);
$uidf = Database::result($r1);

$s2 = "SELECT id FROM session_field WHERE field_variable = 'sede'";
$r2 = Database::query($s2);
$sedef = Database::result($r2);

$s3 = "SELECT id FROM session_field WHERE field_variable = 'uidIdPrograma'";
$r3 = Database::query($s3);
$sidf = Database::result($r3);

$sedes = array(
    '8F67B2B3-667E-4EBC-8605-766D2FF71B55' => 1,
    '7379A7D3-6DC5-42CA-9ED4-97367519F1D9' => 2,
    '30DE73B6-8203-4F81-96C8-3B27977BB924' => 3,
    '8BA65461-60B5-4716-BEB3-22BC7B71BC09' => 4,
    '257AD17D-91F7-4BC8-81D4-71EBD35A4E50' => 5,
);

$a = new Attendance();
$sqlsc = "SELECT s.id_session, c.id as course_id FROM session_rel_course s INNER JOIN course c ON s.course_code=c.code ORDER BY id_session, course_id";
$ressc = Database::query($sqlsc);
$sessions_list = array();
while ($rowsc = Database::fetch_assoc($ressc)) {
    $sessions_list[$rowsc['id_session']] = $rowsc['course_id'];
}
//$sessions_list = SessionManager::get_sessions_list();
$min = 145150;
$max = 145150;
// Get sessions
foreach ($sessions_list as $session_id => $course_id) {
    if ($session_id < $min) { continue; }
    $out1 = "Session ".$session_id.", course ".$course_id."\n";
    echo $out1;
    // Get branch for session
    $ss1 = "SELECT field_value FROM session_field_values WHERE field_id = $sedef AND session_id = ".$session_id;
    $rs1 = Database::query($ss1);
    $sede = Database::result($rs1);
    // Get uidIdPrograma
    $ss2 = "SELECT field_value FROM session_field_values WHERE field_id = $sidf AND session_id = ".$session_id;
    $rs2 = Database::query($ss2);
    $sid = Database::result($rs2);

    // Get users in session to build users list
    $users = SessionManager::get_users_by_session($session_id);
    $u = array();
    foreach ($users as $user) {
        $u[] = $user['user_id'];
    }
    // Get courses list to get the right course (only one in each session)
    //$courses = SessionManager::get_course_list_by_session_id($session_id);
    //if (count($courses)>0) {
    //    foreach ($courses as $course) {
    //        $course_id = $course['id'];
    //        break;
    //    }
    if (!empty($course_id)) {
        $out2 = "-- Course ".$course_id."\n";
        // Get attendances sheets from course (only one in each course)
        $att = $a->get_attendances_list($course_id,$session_id);
        if (count($att)>0) {
            foreach ($att as $at) {
                $at_id = $at['id'];
                break; //get out after first result
            }
            $out3 = "---- Attendance ".$at_id."\n";
            $a->set_course_int_id($course_id);
            $cal_list = $a->get_attendance_calendar($at_id);
            $cal_count = count($cal_list);
            foreach ($cal_list as $cal) {
                $cal_id = $cal['id'];
                $sql = "SELECT * FROM c_attendance_sheet WHERE c_id = $course_id AND attendance_calendar_id = $cal_id";
                $res_att = Database::query($sql);
                $att_count = Database::num_rows($res_att);
                if ($att_count < count($u)) {
                    $out4 = "------ Found $att_count when should have found ".count($u)." attendances for ".$cal['date_time']."\n";
                    echo $out1.$out2.$out3.$out4;
                    while ($row_att = Database::fetch_assoc($res_att)) {
                        $atts[] = $row_att['user_id'];
                    }
                    $missing = array_diff($u,$atts);
                    foreach ($missing as $u1) {
                        $sqlu = "SELECT field_value FROM user_field_values where field_id = $uidf and user_id = $u1";
                        $resu = Database::query($sqlu);
                        $uid = Database::result($resu);
                        $params = array(
                            'item_id' => $uid,
                            'orig_id' => $sid,
                            'info' => substr($cal['date_time'],0,10),
                            'branch_id' => $sedes[$sede],
                        );
                        $r8 = MigrationCustom::transaction_34($params,$matches['web_service_calls']); 
                        var_dump($r8);
                    }
                }
            }
            //$a->update_users_results($u,$at_id);
        } else {
            //var_dump($att);
        }
    }
    if ($session_id>=$max) { break; }
}
die('Finished processing '.$max."\n");

