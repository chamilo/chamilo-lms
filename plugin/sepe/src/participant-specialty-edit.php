<?php
/* For licensing terms, see /license.txt */

/**
 *    This script displays a participant specialty edit form.
 */
require_once '../config.php';

$course_plugin = 'sepe';
$plugin = SepePlugin::create();
$_cid = 0;

if (!empty($_POST)) {
    $check = Security::check_token('post');
    if ($check) {
        $newSpecialty = intval($_POST['new_specialty']);
        $specialtyOrigin = Database::escape_string(trim($_POST['specialty_origin']));
        $professionalArea = Database::escape_string(trim($_POST['professional_area']));
        $specialtyCode = Database::escape_string(trim($_POST['specialty_code']));
        $centerOrigin = Database::escape_string(trim($_POST['center_origin']));
        $centerCode = Database::escape_string(trim($_POST['center_code']));
        $finalResult = Database::escape_string(trim($_POST['final_result']));
        $finalQualification = Database::escape_string(trim($_POST['final_qualification']));
        $finalScore = Database::escape_string(trim($_POST['final_score']));
        $yearRegistration = Database::escape_string(trim($_POST['year_registration']));
        $monthRegistration = Database::escape_string(trim($_POST['month_registration']));
        $dayRegistration = Database::escape_string(trim($_POST['day_registration']));
        $yearLeaving = Database::escape_string(trim($_POST['year_leaving']));
        $monthLeaving = Database::escape_string(trim($_POST['month_leaving']));
        $dayLeaving = Database::escape_string(trim($_POST['day_leaving']));
        $dayStart = Database::escape_string(trim($_POST['day_start']));
        $monthStart = Database::escape_string(trim($_POST['month_start']));
        $yearStart = Database::escape_string(trim($_POST['year_start']));
        $dayEnd = Database::escape_string(trim($_POST['day_end']));
        $monthEnd = Database::escape_string(trim($_POST['month_end']));
        $yearEnd = Database::escape_string(trim($_POST['year_end']));
        $participantId = intval($_POST['participant_id']);
        $actionId = intval($_POST['action_id']);
        $specialtyId = intval($_POST['specialty_id']);

        $registrationDate = $yearRegistration."-".$monthRegistration."-".$dayRegistration;
        $leavingDate = $yearLeaving."-".$monthLeaving."-".$dayLeaving;
        $startDate = $yearStart."-".$monthStart."-".$dayStart;
        $endDate = $yearEnd."-".$monthEnd."-".$dayEnd;

        if (isset($newSpecialty) && $newSpecialty != 1) {
            $sql = "UPDATE $tableSepeParticipantsSpecialty SET
                        specialty_origin = '".$specialtyOrigin."',
                        professional_area = '".$professionalArea."',
                        specialty_code = '".$specialtyCode."',
                        registration_date = '".$registrationDate."',
                        leaving_date = '".$leavingDate."',
                        center_origin = '".$centerOrigin."',
                        center_code = '".$centerCode."',
                        start_date = '".$startDate."',
                        end_date = '".$endDate."',
                        final_result = '".$finalResult."',
                        final_qualification = '".$finalQualification."',
                        final_score = '".$finalScore."'
                    WHERE id = $specialtyId";
        } else {
            $sql = "INSERT INTO $tableSepeParticipantsSpecialty (
                        participant_id,
                        specialty_origin,
                        professional_area,
                        specialty_code,
                        registration_date,
                        leaving_date,
                        center_origin,
                        center_code,
                        start_date,
                        end_date,
                        final_result,
                        final_qualification,
                        final_score
                    ) VALUES (
                        $participantId,
                        '".$specialtyOrigin."',
                        '".$professionalArea."',
                        '".$specialtyCode."',
                        '".$registrationDate."',
                        '".$leavingDate."',
                        '".$centerOrigin."',
                        '".$centerCode."',
                        '".$startDate."',
                        '".$endDate."',
                        '".$finalResult."',
                        '".$finalQualification."',
                        '".$finalScore."'
                    );";
        }
        $res = Database::query($sql);
        if (!$res) {
            $_SESSION['sepe_message_error'] = $plugin->get_lang('NoSaveChange');
        } else {
            $_SESSION['sepe_message_info'] = $plugin->get_lang('SaveChange');
            if ($newSpecialty == "1") {
                $specialtyId = Database::insert_id();
            }

            $platformUserId = getUserPlatformFromParticipant($participantId);
            $insertLog = checkInsertNewLog($platformUserId, $actionId);
            if ($insertLog) {
                if ($finalResult == "1" || $finalResult == "2") {
                    $leavingDateLog = date("Y-m-d H:i:s");
                } else {
                    $leavingDateLog = '0000-00-00';
                }
                $sql = "INSERT INTO $tableSepeLogParticipant (
                            platform_user_id,
                            action_id,
                            registration_date,
                            leaving_date
                        ) VALUES (
                            '".$platformUserId."',
                            '".$actionId."',
                            '".date("Y-m-d H:i:s")."'
                            '".$leavingDateLog."'
                        );";
            } else {
                if ($finalResult == "1" || $finalResult == "2") {
                    $sql = "UPDATE $tableSepeLogParticipant
                            SET leaving_date = '".date("Y-m-d H:i:s")."'
                            WHERE platform_user_id = '".$platformUserId."' AND action_id = '".$actionId."';";
                } else {
                    $sql = "INSERT INTO $tableSepeLogChangeParticipant (
                                platform_user_id,
                                action_id,
                                change_date
                            ) VALUES (
                                '".$platformUserId."',
                                '".$actionId."',
                                '".date("Y-m-d H:i:s")."'
                            );";
                }
            }
            $res = Database::query($sql);
        }
        session_write_close();
        header("Location: participant-specialty-edit.php?new_specialty=0&specialty_id=".$specialtyId."&participant_id=".$participantId."&action_id=".$actionId);
        exit;
    } else {
        $newSpecialty = intval($_POST['new_specialty']);
        $participantId = intval($_POST['participant_id']);
        $actionId = intval($_POST['action_id']);
        $specialtyId = intval($_POST['specialty_id']);
        Security::clear_token();
        $token = Security::get_token();
        $_SESSION['sepe_message_error'] = $plugin->get_lang('ProblemToken');
        session_write_close();
        header("Location: participant-specialty-edit.php?new_specialty=".$newSpecialty."&specialty_id=".$specialtyId."&participant_id=".$participantId."&action_id=".$actionId);
        exit;
    }
} else {
    $token = Security::get_token();
}

if (api_is_platform_admin()) {
    $actionId = (int) $_GET['action_id'];
    $courseId = getCourse($actionId);
    $participantId = (int) $_GET['participant_id'];
    $interbreadcrumb[] = [
        "url" => "/plugin/sepe/src/sepe-administration-menu.php",
        "name" => $plugin->get_lang('MenuSepe'),
    ];
    $interbreadcrumb[] = ["url" => "formative-actions-list.php", "name" => $plugin->get_lang('FormativesActionsList')];
    $interbreadcrumb[] = [
        "url" => "formative-action.php?cid=".$courseId,
        "name" => $plugin->get_lang('FormativeAction'),
    ];
    $interbreadcrumb[] = [
        "url" => "participant-action-edit.php?new_participant=0&participant_id=".$participantId."&action_id=".$actionId,
        "name" => $plugin->get_lang('FormativeActionParticipant'),
    ];
    if (isset($_GET['new_specialty']) && intval($_GET['new_specialty']) == 1) {
        $templateName = $plugin->get_lang('NewSpecialtyParticipant');
        $tpl = new Template($templateName);
        $tpl->assign('action_id', $actionId);
        $tpl->assign('participant_id', $participantId);
        $info = [];
        $tpl->assign('info', $info);
        $tpl->assign('new_specialty', '1');
        $startYear = $endYear = date("Y");
        $registrationYear = $leaveYear = date("Y");
    } else {
        $templateName = $plugin->get_lang('EditSpecialtyParticipant');
        $tpl = new Template($templateName);
        $tpl->assign('action_id', $actionId);
        $tpl->assign('specialty_id', intval($_GET['specialty_id']));
        $tpl->assign('participant_id', $participantId);
        $info = getInfoSpecialtyParticipant($_GET['specialty_id']);
        $tpl->assign('info', $info);
        $tpl->assign('new_specialty', '0');
        if ($info['registration_date'] != '0000-00-00' && $info['registration_date'] != null) {
            $tpl->assign('day_registration', date("j", strtotime($info['registration_date'])));
            $tpl->assign('month_registration', date("n", strtotime($info['registration_date'])));
            $tpl->assign('year_registration', date("Y", strtotime($info['registration_date'])));
            $registrationYear = date("Y", strtotime($info['registration_date']));
        } elseif (strpos($info['end_date'], '0000') === false) {
            $registrationYear = date("Y", strtotime($info['registration_date']));
        } else {
            $registrationYear = date("Y");
        }
        if ($info['leaving_date'] != '0000-00-00' && $info['leaving_date'] != null) {
            $tpl->assign('day_leaving', date("j", strtotime($info['leaving_date'])));
            $tpl->assign('month_leaving', date("n", strtotime($info['leaving_date'])));
            $tpl->assign('year_leaving', date("Y", strtotime($info['leaving_date'])));
            $leaveYear = date("Y", strtotime($info['leaving_date']));
        } elseif (strpos($info['end_date'], '0000') === false) {
            $leaveYear = date("Y", strtotime($info['leaving_date']));
        } else {
            $leaveYear = date("Y");
        }
        if ($info['start_date'] != '0000-00-00' && $info['start_date'] != null) {
            $tpl->assign('day_start', date("j", strtotime($info['start_date'])));
            $tpl->assign('month_start', date("n", strtotime($info['start_date'])));
            $tpl->assign('year_start', date("Y", strtotime($info['start_date'])));
            $startYear = date("Y", strtotime($info['start_date']));
        } elseif (strpos($info['end_date'], '0000') === false) {
            $startYear = date("Y", strtotime($info['start_date']));
        } else {
            $startYear = date("Y");
        }
        if ($info['end_date'] != '0000-00-00' && $info['end_date'] != null) {
            $tpl->assign('day_end', date("j", strtotime($info['end_date'])));
            $tpl->assign('month_end', date("n", strtotime($info['end_date'])));
            $tpl->assign('year_end', date("Y", strtotime($info['end_date'])));
            $endYear = date("Y", strtotime($info['end_date']));
        } elseif (strpos($info['end_date'], '0000') === false) {
            $endYear = date("Y", strtotime($info['end_date']));
        } else {
            $endYear = date("Y");
        }
        $listSpecialtyTutorials = getListSpecialtyTutorial($_GET['specialty_id']);
        $tpl->assign('listSpecialtyTutorials', $listSpecialtyTutorials);
    }

    $listYear = [];
    if ($registrationYear > $leaveYear) {
        $tmp = $registrationYear;
        $registrationYear = $leaveYear;
        $leaveYear = $tmp;
    }
    $registrationYear -= 5;
    $leaveYear += 5;
    $endRangeYear = (($registrationYear + 15) < $leaveYear) ? ($leaveYear + 1) : ($registrationYear + 15);
    while ($registrationYear <= $endRangeYear) {
        $listYear[] = $registrationYear;
        $registrationYear++;
    }
    $tpl->assign('list_year', $listYear);

    $listYear = [];
    if ($startYear > $endYear) {
        $tmp = $startYear;
        $startYear = $endYear;
        $endYear = $tmp;
    }
    $startYear -= 5;
    $endYear += 5;
    $endRangeYear = (($startYear + 15) < $endYear) ? ($endYear + 1) : ($startYear + 15);
    while ($startYear <= $endRangeYear) {
        $listYear[] = $startYear;
        $startYear++;
    }
    $tpl->assign('list_year_2', $listYear);

    if (isset($_SESSION['sepe_message_info'])) {
        $tpl->assign('message_info', $_SESSION['sepe_message_info']);
        unset($_SESSION['sepe_message_info']);
    }
    if (isset($_SESSION['sepe_message_error'])) {
        $tpl->assign('message_error', $_SESSION['sepe_message_error']);
        unset($_SESSION['sepe_message_error']);
    }
    $tpl->assign('sec_token', $token);
    $listing_tpl = 'sepe/view/participant-specialty-edit.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
} else {
    header('Location:'.api_get_path(WEB_PATH));
    exit;
}
