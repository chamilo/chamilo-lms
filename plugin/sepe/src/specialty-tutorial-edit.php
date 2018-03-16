<?php
/* For licensing terms, see /license.txt */

/**
 *    This script displays a specialty tutorial edit form.
 */
require_once '../config.php';

$course_plugin = 'sepe';
$plugin = SepePlugin::create();
$_cid = 0;

if (!empty($_POST)) {
    $check = Security::check_token('post');
    if ($check) {
        $centerOrigin = Database::escape_string(trim($_POST['center_origin']));
        $centerCode = Database::escape_string(trim($_POST['center_code']));
        $dayStart = Database::escape_string(trim($_POST['day_start']));
        $monthStart = Database::escape_string(trim($_POST['month_start']));
        $yearStart = Database::escape_string(trim($_POST['year_start']));
        $dayEnd = Database::escape_string(trim($_POST['day_end']));
        $monthEnd = Database::escape_string(trim($_POST['month_end']));
        $yearEnd = Database::escape_string(trim($_POST['year_end']));
        $tutorialId = intval($_POST['tutorial_id']);
        $actionId = intval($_POST['action_id']);
        $specialtyId = intval($_POST['specialty_id']);
        $newTutorial = intval($_POST['new_tutorial']);
        $starDate = $yearStart."-".$monthStart."-".$dayStart;
        $endDate = $yearEnd."-".$monthEnd."-".$dayEnd;

        if (isset($newTutorial) && $newTutorial != 1) {
            $sql = "UPDATE $tableSepeParticipantsSpecialtyTutorials SET 
                        center_origin='".$centerOrigin."', 
                        center_code='".$centerCode."', 
                        start_date='".$starDate."', 
                        end_date='".$endDate."' 
                    WHERE id = $tutorialId;";
        } else {
            $sql = "INSERT INTO $tableSepeParticipantsSpecialtyTutorials (
                        participant_specialty_id, 
                        center_origin,
                        center_code,
                        start_date,
                        end_date
                    ) VALUES (
                        $specialtyId,
                        '".$centerOrigin."',
                        '".$centerCode."',
                        '".$starDate."',
                        '".$endDate."'
                    );";
        }
        $res = Database::query($sql);
        if (!$res) {
            $_SESSION['sepe_message_error'] = $plugin->get_lang('NoSaveChange');
        } else {
            $_SESSION['sepe_message_info'] = $plugin->get_lang('SaveChange');
        }

        session_write_close();
        $participantId = getParticipantId($specialtyId);
        header("Location: participant-specialty-edit.php?new_specialty=0&participant_id=".$participantId."&specialty_id=".$specialtyId."&action_id=".$actionId);
        exit;
    } else {
        $tutorialId = intval($_POST['tutorial_id']);
        $actionId = intval($_POST['action_id']);
        $specialtyId = intval($_POST['specialty_id']);
        $newTutorial = intval($_POST['new_tutorial']);
        Security::clear_token();
        $token = Security::get_token();
        $_SESSION['sepe_message_error'] = $plugin->get_lang('ProblemToken');
        session_write_close();
        header("Location: specialty-tutorial-edit.php?new_tutorial=".$newTutorial."&specialty_id=".$specialtyId."&tutorial_id=".$tutorialId."&action_id=".$actionId);
        exit;
    }
} else {
    $token = Security::get_token();
}

if (api_is_platform_admin()) {
    $courseId = getCourse(intval($_GET['action_id']));
    $participantId = getParticipantId(intval($_GET['specialty_id']));
    $interbreadcrumb[] = ["url" => "/plugin/sepe/src/sepe-administration-menu.php", "name" => $plugin->get_lang('MenuSepe')];
    $interbreadcrumb[] = ["url" => "formative-actions-list.php", "name" => $plugin->get_lang('FormativesActionsList')];
    $interbreadcrumb[] = ["url" => "formative-action.php?cid=".$courseId, "name" => $plugin->get_lang('FormativeAction')];
    $interbreadcrumb[] = ["url" => "participant-specialty-edit.php?new_specialty=0&participant_id=".$participantId."&specialty_id=".intval($_GET['specialty_id'])."&action_id=".intval($_GET['action_id']), "name" => $plugin->get_lang('SpecialtyFormativeParcipant')];
    if (isset($_GET['new_tutorial']) && intval($_GET['new_tutorial']) == 1) {
        $templateName = $plugin->get_lang('new_tutorial');
        $tpl = new Template($templateName);
        $tpl->assign('action_id', intval($_GET['action_id']));
        $tpl->assign('specialty_id', intval($_GET['specialty_id']));
        $info = [];
        $tpl->assign('info', $info);
        $tpl->assign('new_tutorial', '1');
        $startYear = $endYear = date("Y");
    } else {
        $templateName = $plugin->get_lang('edit_tutorial');
        $tpl = new Template($templateName);
        $tpl->assign('action_id', intval($_GET['action_id']));
        $tpl->assign('specialty_id', intval($_GET['specialty_id']));
        $tpl->assign('tutorial_id', intval($_GET['tutorial_id']));
        $info = getInfoSpecialtyTutorial(intval($_GET['tutorial_id']));
        $tpl->assign('info', $info);
        $tpl->assign('new_tutorial', '0');
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
    }
    $listYears = [];
    if ($startYear > $endYear) {
        $tmp = $startYear;
        $startYear = $endYear;
        $endYear = $tmp;
    }
    $startYear -= 5;
    $endYear += 5;
    $endRangeYear = (($startYear + 15) < $endYear) ? ($endYear + 1) : ($startYear + 15);
    while ($startYear <= $endRangeYear) {
        $listYears[] = $startYear;
        $startYear++;
    }
    $tpl->assign('list_year', $listYears);

    if (isset($_SESSION['sepe_message_info'])) {
        $tpl->assign('message_info', $_SESSION['sepe_message_info']);
        unset($_SESSION['sepe_message_info']);
    }
    if (isset($_SESSION['sepe_message_error'])) {
        $tpl->assign('message_error', $_SESSION['sepe_message_error']);
        unset($_SESSION['sepe_message_error']);
    }
    $tpl->assign('sec_token', $token);

    $listing_tpl = 'sepe/view/specialty-tutorial-edit.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
} else {
    header('Location:'.api_get_path(WEB_PATH));
    exit;
}
