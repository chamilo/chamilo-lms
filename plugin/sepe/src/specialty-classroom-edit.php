<?php
/* For licensing terms, see /license.txt */

/**
 *    This script displays a specialty classroom edit form.
 */
require_once '../config.php';

$course_plugin = 'sepe';
$plugin = SepePlugin::create();
$_cid = 0;

if (!empty($_POST)) {
    $check = Security::check_token('post');
    if ($check) {
        $sltCentersExists = intval($_POST['slt_centers_exists']);
        $specialtyId = intval($_POST['specialty_id']);
        $existsCenterId = intval($_POST['exists_center_id']);
        $centerOrigin = Database::escape_string(trim($_POST['center_origin']));
        $centerCode = Database::escape_string(trim($_POST['center_code']));
        $newClassroom = intval($_POST['new_classroom']);
        $actionId = intval($_POST['action_id']);
        $classroomId = intval($_POST['classroom_id']);

        if ($sltCentersExists == 1) {
            $sql = "INSERT INTO $tableSepeSpecialtyClassroom (specialty_id, center_id) 
                    VALUES ($specialtyId, $existsCenterId);";
            $res = Database::query($sql);
            if (!$res) {
                $_SESSION['sepe_message_error'] = $plugin->get_lang('NoSaveChange');
            } else {
                if ($newClassroom == 1) {
                    $classroomId = Database::insert_id();
                }
                $_SESSION['sepe_message_info'] = $plugin->get_lang('SaveChange');
            }
        } else {
            //Checker exists centers
            $sql = "SELECT * FROM $tableCenters 
                    WHERE center_origin='".$centerOrigin."' AND center_code='".$centerCode."'";
            $rs_tmp = Database::query($sql);
            if (Database::num_rows($rs_tmp) > 0) {
                $aux = Database::fetch_assoc($rs_tmp);
                $centerId = $aux['id'];
            } else {
                $params = [
                    'center_origin' => $centerOrigin,
                    'center_code' => $centerCode,
                ];
                $centerId = Database::insert($tableCenters, $params);
            }

            if (isset($newClassroom) && $newClassroom != 1) {
                $sql = "UPDATE $tableSepeSpecialtyClassroom SET center_id = $centerId WHERE id = $classroomId;";
            } else {
                $sql = "INSERT INTO $tableSepeSpecialtyClassroom (specialty_id, center_id) VALUES ($specialtyId, $centerId);";
            }
            $res = Database::query($sql);
            if (!$res) {
                $_SESSION['sepe_message_error'] = $plugin->get_lang('NoSaveChange');
            } else {
                if ($newClassroom == 1) {
                    $classroomId = Database::insert_id();
                }
                $_SESSION['sepe_message_info'] = $plugin->get_lang('SaveChange');
            }
        }
        session_write_close();
        header("Location: specialty-action-edit.php?new_specialty=0&specialty_id=".$specialtyId."&action_id=".$actionId);
        exit;
    } else {
        $newClassroom = intval($_POST['new_classroom']);
        $actionId = intval($_POST['action_id']);
        $classroomId = intval($_POST['classroom_id']);
        $specialtyId = intval($_POST['specialty_id']);
        Security::clear_token();
        $_SESSION['sepe_message_error'] = $plugin->get_lang('ProblemToken');
        $token = Security::get_token();
        session_write_close();
        header("Location:specialty-classroom-edit.php?new_classroom=".$newClassroom."&specialty_id=".$specialtyId."&classroom_id=".$classroomId."&action_id=".$actionId);
        exit;
    }
} else {
    $token = Security::get_token();
}

if (api_is_platform_admin()) {
    $courseId = getCourse($_GET['action_id']);
    $interbreadcrumb[] = ["url" => "/plugin/sepe/src/sepe-administration-menu.php", "name" => $plugin->get_lang('MenuSepe')];
    $interbreadcrumb[] = ["url" => "formative-actions-list.php", "name" => $plugin->get_lang('FormativesActionsList')];
    $interbreadcrumb[] = ["url" => "formative-action.php?cid=".$courseId, "name" => $plugin->get_lang('FormativeAction')];
    $interbreadcrumb[] = ["url" => "specialty-action-edit.php?new_specialty=0&specialty_id=".intval($_GET['specialty_id'])."&action_id=".intval($_GET['action_id']), "name" => $plugin->get_lang('SpecialtyFormativeAction')];
    if (isset($_GET['new_classroom']) && intval($_GET['new_classroom']) == 1) {
        $templateName = $plugin->get_lang('NewSpecialtyClassroom');
        $tpl = new Template($templateName);
        $tpl->assign('action_id', intval($_GET['action_id']));
        $tpl->assign('specialty_id', intval($_GET['specialty_id']));
        $info = [];
        $tpl->assign('info', $info);
        $tpl->assign('new_classroom', '1');
    } else {
        $templateName = $plugin->get_lang('EditSpecialtyClassroom');
        $tpl = new Template($templateName);
        $tpl->assign('action_id', intval($_GET['action_id']));
        $tpl->assign('specialty_id', intval($_GET['specialty_id']));
        $tpl->assign('classroom_id', intval($_GET['classroom_id']));
        $info = getInfoSpecialtyClassroom($_GET['classroom_id']);
        $tpl->assign('info', $info);
        $tpl->assign('new_classroom', '0');
    }
    $centerList = getCentersList();
    $tpl->assign('listExistsCenters', $centerList);

    if (isset($_SESSION['sepe_message_info'])) {
        $tpl->assign('message_info', $_SESSION['sepe_message_info']);
        unset($_SESSION['sepe_message_info']);
    }
    if (isset($_SESSION['sepe_message_error'])) {
        $tpl->assign('message_error', $_SESSION['sepe_message_error']);
        unset($_SESSION['sepe_message_error']);
    }
    $tpl->assign('sec_token', $token);

    $listing_tpl = 'sepe/view/specialty-classroom-edit.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
} else {
    header('Location:'.api_get_path(WEB_PATH));
    exit;
}
