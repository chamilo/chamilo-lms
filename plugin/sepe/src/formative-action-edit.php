<?php
/* For licensing terms, see /license.txt */

/**
 *    This script displays a formative action edit form.
 */
require_once '../config.php';

$course_plugin = 'sepe';
$plugin = SepePlugin::create();
$_cid = 0;

if (!empty($_POST)) {
    $check = Security::check_token('post');
    if ($check) {
        $actionOrigin = Database::escape_string(trim($_POST['action_origin']));
        $actionCode = Database::escape_string(trim($_POST['action_code']));
        $situation = Database::escape_string(trim($_POST['situation']));
        $specialtyOrigin = Database::escape_string(trim($_POST['specialty_origin']));
        $professionalArea = Database::escape_string(trim($_POST['professional_area']));
        $specialtyCode = Database::escape_string(trim($_POST['specialty_code']));
        $duration = Database::escape_string(trim($_POST['duration']));
        $dayStart = Database::escape_string(trim($_POST['day_start']));
        $monthStart = Database::escape_string(trim($_POST['month_start']));
        $yearStart = Database::escape_string(trim($_POST['year_start']));
        $dayEnd = Database::escape_string(trim($_POST['day_end']));
        $monthEnd = Database::escape_string(trim($_POST['month_end']));
        $yearEnd = Database::escape_string(trim($_POST['year_end']));
        $fullItineraryIndicator = Database::escape_string(trim($_POST['full_itinerary_indicator']));
        $financingType = Database::escape_string(trim($_POST['financing_type']));
        $attendeesCount = intval($_POST['attendees_count']);
        $actionName = Database::escape_string(trim($_POST['action_name']));
        $globalInfo = Database::escape_string(trim($_POST['global_info']));
        $schedule = Database::escape_string(trim($_POST['schedule']));
        $requirements = Database::escape_string(trim($_POST['requirements']));
        $contactAction = Database::escape_string(trim($_POST['contact_action']));
        $actionId = intval($_POST['action_id']);
        $courseId = intval($_POST['course_id']);

        $startDate = $yearStart."-".$monthStart."-".$dayStart;
        $endDate = $yearEnd."-".$monthEnd."-".$dayEnd;

        if (!empty($actionId) && $actionId != '0') {
            $sql = "UPDATE plugin_sepe_actions SET
                        action_origin='".$actionOrigin."',
                        action_code='".$actionCode."',
                        situation='".$situation."',
                        specialty_origin='".$specialtyOrigin."',
                        professional_area='".$professionalArea."',
                        specialty_code='".$specialtyCode."',
                        duration='".$duration."',
                        start_date='".$startDate."',
                        end_date='".$endDate."',
                        full_itinerary_indicator='".$fullItineraryIndicator."',
                        financing_type='".$financingType."',
                        attendees_count='".$attendeesCount."',
                        action_name='".$actionName."',
                        global_info='".$globalInfo."',
                        schedule='".$schedule."',
                        requirements='".$requirements."',
                        contact_action='".$contactAction."'
                    WHERE id='".$actionId."';";
        } else {
            $sql = "INSERT INTO plugin_sepe_actions (
                        action_origin,
                        action_code,
                        situation,
                        specialty_origin,
                        professional_area,
                        specialty_code,
                        duration,
                        start_date,
                        end_date,
                        full_itinerary_indicator,
                        financing_type,
                        attendees_count,
                        action_name,
                        global_info,
                        schedule,
                        requirements,
                        contact_action
                    ) VALUES (
                        '".$actionOrigin."',
                        '".$actionCode."',
                        '".$situation."',
                        '".$specialtyOrigin."',
                        '".$professionalArea."',
                        '".$specialtyCode."',
                        '".$duration."',
                        '".$startDate."',
                        '".$endDate."',
                        '".$fullItineraryIndicator."',
                        '".$financingType."',
                        '".$attendeesCount."',
                        '".$actionName."',
                        '".$globalInfo."',
                        '".$schedule."',
                        '".$requirements."',
                        '".$contactAction."'
                    );";
        }
        $res = Database::query($sql);
        if (!$res) {
            $_SESSION['sepe_message_error'] = $plugin->get_lang('NoSaveChange');
        } else {
            if ($actionId == '0') {
                //Sync formative action and course
                $actionId = Database::insert_id();
                $sql = "SELECT 1 FROM course WHERE id='".$courseId."';";
                $rs = Database::query($sql);
                if (Database::num_rows($rs) == 0) {
                    $sepe_message_error .= $plugin->get_lang('NoExistsCourse');
                    error_log($sepe_message_error);
                } else {
                    $sql = "INSERT INTO $tableSepeCourseActions (course_id, action_id) VALUES ('".$courseId."','".$actionId."');";
                    $rs = Database::query($sql);
                    if (!$rs) {
                        $sepe_message_error .= $plugin->get_lang('NoSaveSeleccion');
                        error_log($sepe_message_error);
                    } else {
                        $_SESSION['sepe_message_info'] = $plugin->get_lang('SaveChange');
                    }
                }
            }
        }
        $courseId = getCourse($actionId);
        header("Location: formative-action.php?cid=".$courseId);
    } else {
        Security::clear_token();
        $token = Security::get_token();
        $_SESSION['sepe_message_error'] = $plugin->get_lang('ProblemToken');
        session_write_close();
        $actionId = intval($_POST['action_id']);
        if ($actionId == '0') {
            $courseId = intval($_POST['course_id']);
            header("Location: formative-action-edit.php?new_action=1&cid=".$courseId);
        } else {
            header("Location: formative-action-edit.php?action_id=".$actionId);
        }
    }
} else {
    $token = Security::get_token();
}

if (api_is_platform_admin()) {
    if (isset($_GET['new_action']) && intval($_GET['new_action']) == 1) {
        $info = [];
        $interbreadcrumb[] = [
            "url" => "/plugin/sepe/src/sepe-administration-menu.php",
            "name" => $plugin->get_lang('MenuSepe'),
        ];
        $interbreadcrumb[] = [
            "url" => "formative-actions-list.php",
            "name" => $plugin->get_lang('FormativesActionsList'),
        ];
        $templateName = $plugin->get_lang('formativeActionNew');
        $tpl = new Template($templateName);
        $yearStart = $yearEnd = date("Y");
        $tpl->assign('info', $info);
        $tpl->assign('new_action', '1');
        $tpl->assign('course_id', intval($_GET['cid']));
    } else {
        $courseId = getCourse($_GET['action_id']);
        $interbreadcrumb[] = [
            "url" => "/plugin/sepe/src/sepe-administration-menu.php",
            "name" => $plugin->get_lang('MenuSepe'),
        ];
        $interbreadcrumb[] = [
            "url" => "formative-actions-list.php",
            "name" => $plugin->get_lang('FormativesActionsList'),
        ];
        $interbreadcrumb[] = [
            "url" => "formative-action.php?cid=".$courseId,
            "name" => $plugin->get_lang('FormativeAction'),
        ];
        $info = getActionInfo($_GET['action_id']);
        $templateName = $plugin->get_lang('formativeActionEdit');
        $tpl = new Template($templateName);
        $tpl->assign('info', $info);
        if ($info['start_date'] != "0000-00-00" && $info['start_date'] != null) {
            $tpl->assign('day_start', date("j", strtotime($info['start_date'])));
            $tpl->assign('month_start', date("n", strtotime($info['start_date'])));
            $tpl->assign('year_start', date("Y", strtotime($info['start_date'])));
            $yearStart = date("Y", strtotime($info['start_date']));
        } elseif (strpos($info['start_date'], '0000') === false) {
            $yearStart = date("Y", strtotime($info['start_date']));
        } else {
            $yearStart = date("Y");
        }
        if ($info['end_date'] != "0000-00-00" && $info['end_date'] != null) {
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

    $listing_tpl = 'sepe/view/formative-action-edit.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
} else {
    header('Location:'.api_get_path(WEB_PATH));
    exit;
}
