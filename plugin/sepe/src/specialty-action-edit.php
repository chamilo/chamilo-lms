<?php
/* For licensing terms, see /license.txt */

/**
 *    This script displays a specialty action edit form.
 */
require_once '../config.php';

$course_plugin = 'sepe';
$plugin = SepePlugin::create();
$_cid = 0;

if (!empty($_POST)) {
    $check = Security::check_token('post');
    if ($check) {
        $specialtyOrigin = Database::escape_string(trim($_POST['specialty_origin']));
        $professionalArea = Database::escape_string(trim($_POST['professional_area']));
        $specialtyCode = Database::escape_string(trim($_POST['specialty_code']));
        $centerOrigin = Database::escape_string(trim($_POST['center_origin']));
        $centerCode = Database::escape_string(trim($_POST['center_code']));
        $dayStart = Database::escape_string(trim($_POST['day_start']));
        $monthStart = Database::escape_string(trim($_POST['month_start']));
        $yearStart = Database::escape_string(trim($_POST['year_start']));
        $dayEnd = Database::escape_string(trim($_POST['day_end']));
        $monthEnd = Database::escape_string(trim($_POST['month_end']));
        $yearEnd = Database::escape_string(trim($_POST['year_end']));
        $modality_impartition = Database::escape_string(trim($_POST['modality_impartition']));
        $classroomHours = Database::escape_string(trim($_POST['classroom_hours']));
        $distanceHours = intval($_POST['distance_hours']);
        $morningsParticipantsNumber = intval($_POST['mornings_participants_number']);
        $morningsAccessNumber = intval($_POST['mornings_access_number']);
        $morningTotalDuration = intval($_POST['morning_total_duration']);
        $afternoonParticipantsNumber = intval($_POST['afternoon_participants_number']);
        $afternoonAccessNumber = intval($_POST['afternoon_access_number']);
        $afternoonTotalDuration = intval($_POST['afternoon_total_duration']);
        $nightParticipantsNumber = intval($_POST['night_participants_number']);
        $nightAccessNumber = intval($_POST['night_access_number']);
        $nightTotalDuration = intval($_POST['night_total_duration']);
        $attendeesCount = intval($_POST['attendees_count']);
        $learningActivityCount = intval($_POST['learning_activity_count']);
        $attemptCount = intval($_POST['attempt_count']);
        $evaluationActivityCount = intval($_POST['evaluation_activity_count']);
        $actionId = intval($_POST['action_id']);
        $specialtyId = intval($_POST['specialty_id']);
        $newSpecialty = intval($_POST['new_specialty']);

        $startDate = $yearStart."-".$monthStart."-".$dayStart;
        $endDate = $yearEnd."-".$monthEnd."-".$dayEnd;

        if (isset($newSpecialty) && $newSpecialty != 1) {
            $sql = "UPDATE plugin_sepe_specialty SET 
            specialty_origin='".$specialtyOrigin."', 
            professional_area='".$professionalArea."', 
            specialty_code='".$specialtyCode."', 
            center_origin='".$centerOrigin."', 
            center_code='".$centerCode."', 
            start_date='".$startDate."', 
            end_date='".$endDate."', 
            modality_impartition='".$modalityImpartition."', 
            classroom_hours = $classroomHours, 
            distance_hours = $distanceHours, 
            mornings_participants_number = $morningsParticipantsNumber, 
            mornings_access_number = $morningsAccessNumber, 
            morning_total_duration = $morningTotalDuration, 
            afternoon_participants_number = $afternoonParticipantsNumber, 
            afternoon_access_number = $afternoonAccessNumber, 
            afternoon_total_duration = $afternoonTotalDuration, 
            night_participants_number = $nightParticipantsNumber,
            night_access_number = $nightAccessNumber,
            night_total_duration = $nightTotalDuration,
            attendees_count = $attendeesCount, 
            learning_activity_count = $learningActivityCount, 
            attempt_count = $attemptCount, 
            evaluation_activity_count = $evaluationActivityCount 
            WHERE id = $specialtyId;";
        } else {
            $sql = "INSERT INTO plugin_sepe_specialty (
                        action_id,
                        specialty_origin,
                        professional_area,
                        specialty_code,
                        center_origin,
                        center_code,
                        start_date,
                        end_date,
                        modality_impartition,
                        classroom_hours,
                        distance_hours,
                        mornings_participants_number,
                        mornings_access_number,
                        morning_total_duration,
                        afternoon_participants_number,
                        afternoon_access_number,
                        afternoon_total_duration,
                        night_participants_number,
                        night_access_number,
                        night_total_duration,
                        attendees_count,
                        learning_activity_count,
                        attempt_count,
                        evaluation_activity_count
                    ) VALUES (
                        $actionId,
                        '".$specialtyOrigin."',
                        '".$professionalArea."',
                        '".$specialtyCode."',
                        '".$centerOrigin."',
                        '".$centerCode."',
                        '".$startDate."',
                        '".$endDate."',
                        '".$modalityImpartition."',
                        $classroomHours,
                        $distanceHours,
                        $morningsParticipantsNumber,
                        $morningsAccessNumber,
                        $morningTotalDuration,
                        $afternoonParticipantsNumber,
                        $afternoonAccessNumber,
                        $afternoonTotalDuration,
                        $nightParticipantsNumber,
                        $nightAccessNumber,
                        $nightTotalDuration,
                        $attendeesCount,
                        $learningActivityCount,
                        $attemptCount,
                        $evaluationActivityCount
                    );";
        }
        $res = Database::query($sql);
        if (!$res) {
            $_SESSION['sepe_message_error'] = $plugin->get_lang('NoSaveChange');
        } else {
            if ($newSpecialty == 1) {
                $specialtyId = Database::insert_id();
                $_SESSION['sepe_message_info'] = $plugin->get_lang('SaveChange');
            }
        }
        session_write_close();
        header("Location: specialty-action-edit.php?new_specialty=0&specialty_id=".$specialtyId."&action_id=".$actionId);
    } else {
        $actionId = intval($_POST['action_id']);
        $specialtyId = intval($_POST['specialty_id']);
        $newSpecialty = intval($_POST['new_specialty']);
        Security::clear_token();
        $token = Security::get_token();
        $_SESSION['sepe_message_error'] = $plugin->get_lang('ProblemToken');
        session_write_close();
        header("Location: specialty-action-edit.php?new_specialty=".$newSpecialty."&specialty_id=".$specialtyId."&action_id=".$actionId);
    }
} else {
    $token = Security::get_token();
}

if (api_is_platform_admin()) {
    $id_course = getCourse(intval($_GET['action_id']));
    $interbreadcrumb[] = [
        "url" => "/plugin/sepe/src/sepe-administration-menu.php",
        "name" => $plugin->get_lang('MenuSepe'),
    ];
    $interbreadcrumb[] = [
        "url" => "formative-actions-list.php",
        "name" => $plugin->get_lang('FormativesActionsList'),
    ];
    $interbreadcrumb[] = [
        "url" => "formative-action.php?cid=".$id_course,
        "name" => $plugin->get_lang('FormativeAction'),
    ];
    if (isset($_GET['new_specialty']) && intval($_GET['new_specialty']) == 1) {
        $templateName = $plugin->get_lang('NewSpecialtyAccion');
        $tpl = new Template($templateName);
        $tpl->assign('action_id', intval($_GET['action_id']));
        $info = [];
        $tpl->assign('info', $info);
        $tpl->assign('new_action', '1');
        $yearStart = $yearEnd = date("Y");
    } else {
        $templateName = $plugin->get_lang('EditSpecialtyAccion');
        $tpl = new Template($templateName);
        $tpl->assign('action_id', intval($_GET['action_id']));
        $info = getSpecialtActionInfo(intval($_GET['specialty_id']));
        $tpl->assign('info', $info);
        if ($info['start_date'] != '0000-00-00' && $info['start_date'] != null) {
            $tpl->assign('day_start', date("j", strtotime($info['start_date'])));
            $tpl->assign('month_start', date("n", strtotime($info['start_date'])));
            $tpl->assign('year_start', date("Y", strtotime($info['start_date'])));
            $yearStart = date("Y", strtotime($info['start_date']));
        } elseif (strpos($info['start_date'], '0000') === false) {
            $yearStart = date("Y", strtotime($info['start_date']));
        } else {
            $yearStart = date("Y");
        }
        if ($info['end_date'] != '0000-00-00' && $info['end_date'] != null) {
            $tpl->assign('day_end', date("j", strtotime($info['end_date'])));
            $tpl->assign('month_end', date("n", strtotime($info['end_date'])));
            $tpl->assign('year_end', date("Y", strtotime($info['end_date'])));
            $yearEnd = date("Y", strtotime($info['end_date']));
        } elseif (strpos($info['end_date'], '0000') === false) {
            $yearEnd = date("Y", strtotime($info['end_date']));
        } else {
            $yearEnd = date("Y");
        }
        $tpl->assign('new_action', '0');
        $tpl->assign('specialty_id', intval($_GET['specialty_id']));

        $listClassroom = classroomList(intval($_GET['specialty_id']));
        $tpl->assign('listClassroom', $listClassroom);
        $listTutors = tutorsList(intval($_GET['specialty_id']));
        $tpl->assign('listTutors', $listTutors);
    }

    $yearList = [];
    if ($yearStart > $yearEnd) {
        $tmp = $yearStart;
        $yearStart = $yearEnd;
        $yearEnd = $tmp;
    }
    $yearStart -= 5;
    $yearEnd += 5;
    $fin_rango_anio = (($yearStart + 15) < $yearEnd) ? ($yearEnd + 1) : ($yearStart + 15);
    while ($yearStart <= $fin_rango_anio) {
        $yearList[] = $yearStart;
        $yearStart++;
    }
    $tpl->assign('list_year', $yearList);
    if (isset($_SESSION['sepe_message_info'])) {
        $tpl->assign('message_info', $_SESSION['sepe_message_info']);
        unset($_SESSION['sepe_message_info']);
    }
    if (isset($_SESSION['sepe_message_error'])) {
        $tpl->assign('message_error', $_SESSION['sepe_message_error']);
        unset($_SESSION['sepe_message_error']);
    }
    $tpl->assign('sec_token', $token);
    $listing_tpl = 'sepe/view/specialty-action-edit.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
} else {
    header('Location:'.api_get_path(WEB_PATH));
    exit;
}
