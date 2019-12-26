<?php

/* For license terms, see /license.txt */
/**
 * Functions for the Sepe plugin.
 */
require_once '../config.php';

$plugin = SepePlugin::create();

if ('delete_center_data' == $_REQUEST['tab']) {
    $sql = "DELETE FROM $tableSepeCenter;";
    $res = Database::query($sql);
    if (!$res) {
        $sql = "DELETE FROM $tableSepeActions;";
        $res = Database::query($sql);
        $content = $plugin->get_lang('ProblemToDeleteInfoCenter');
        echo json_encode(['status' => 'false', 'content' => $content]);
    } else {
        $content = $plugin->get_lang('DeleteOk');
        echo json_encode(['status' => 'true', 'content' => $content]);
    }
}

if ('delete_action' == $_REQUEST['tab']) {
    $id = (int) ($_REQUEST['id']);
    $sql = "DELETE FROM $tableSepeActions WHERE id = $id;";
    $res = Database::query($sql);
    if (!$res) {
        $content = $plugin->get_lang('ProblemToDeleteInfoAction');
        echo json_encode(['status' => 'false', 'content' => $content]);
    } else {
        $content = $plugin->get_lang('DeleteOk');
        $_SESSION['sepe_message_info'] = $content;
        echo json_encode(['status' => 'true']);
    }
}

if ('delete_specialty' == $_REQUEST['tab']) {
    $id = (int) (substr($_REQUEST['id'], 9));
    $sql = "DELETE FROM $tableSepeSpecialty WHERE id = $id;";
    $res = Database::query($sql);
    if (!$res) {
        $content = $plugin->get_lang('ProblemToDeleteInfoSpecialty');
        echo json_encode(['status' => 'false', 'content' => $content]);
    } else {
        $content = $plugin->get_lang('DeleteOk');
        echo json_encode(['status' => 'true', 'content' => $content]);
    }
}

if ('delete_specialty_participant' == $_REQUEST['tab']) {
    $id = (int) (substr($_REQUEST['id'], 9));
    $sql = "DELETE FROM $tableSepeParticipantsSpecialty WHERE id = $id;";
    $res = Database::query($sql);
    if (!$res) {
        $content = $plugin->get_lang('ProblemToDeleteInfoSpecialty');
        echo json_encode(['status' => 'false', 'content' => $content]);
    } else {
        $content = $plugin->get_lang('DeleteOk');
        echo json_encode(['status' => 'true', 'content' => $content]);
    }
}

if ('delete_classroom' == $_REQUEST['tab']) {
    $id = (int) (substr($_REQUEST['id'], 9));
    $sql = "DELETE FROM $tableSepeSpecialtyClassroom WHERE id = $id;";
    $res = Database::query($sql);
    if (!$res) {
        $content = $plugin->get_lang('ProblemToDeleteInfoSpecialtyClassroom');
        echo json_encode(['status' => 'false', 'content' => $content]);
    } else {
        $content = $plugin->get_lang('DeleteOk');
        echo json_encode(['status' => 'true', 'content' => $content]);
    }
}

if ('checkTutorEdit' == $_REQUEST['tab']) {
    $type = Database::escape_string(trim($_REQUEST['type']));
    $number = Database::escape_string(trim($_REQUEST['number']));
    $letter = Database::escape_string(trim($_REQUEST['letter']));
    $platform_user_id = (int) ($_REQUEST['platform_user_id']);

    $sql = "SELECT platform_user_id 
            FROM $tableSepeTutors 
            WHERE document_type='".$type."' AND document_number='".$number."' AND document_letter='".$letter."';";
    $res = Database::query($sql);
    if (!$res) {
        $content = $plugin->get_lang('ProblemDataBase');
        error_log(print_r($content, 1));
        exit;
    } else {
        $aux = Database::fetch_assoc($res);
        if ($aux['platform_user_id'] == $platform_user_id || 0 == $aux['platform_user_id']) {
            echo json_encode(['status' => 'true']);
        } else {
            $content = $plugin->get_lang('ModDataTeacher');
            echo json_encode(['status' => 'false', 'content' => $content]);
        }
    }
}

if ('delete_tutor' == $_REQUEST['tab']) {
    $id = (int) (substr($_REQUEST['id'], 5));
    $sql = "DELETE FROM $tableSepeSpecialtyTutors WHERE id = $id;";
    $res = Database::query($sql);
    if (!$res) {
        $content = $plugin->get_lang('ProblemToDeleteInfoSpecialtyTutor');
        echo json_encode(['status' => 'false', 'content' => $content]);
    } else {
        $content = $plugin->get_lang('DeleteOk');
        echo json_encode(['status' => 'true', 'content' => $content]);
    }
}

if ('delete_participant' == $_REQUEST['tab']) {
    $id = (int) (substr($_REQUEST['id'], 11));
    $sql = "SELECT platform_user_id, action_id FROM $tableSepeParticipants WHERE id = $id;";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);

    $sql = "UPDATE plugin_sepe_log_participant SET fecha_baja='".date('Y-m-d H:i:s')."' WHERE platform_user_id='".$row['platform_user_id']."' AND action_id='".$row['action_id']."';";
    $res = Database::query($sql);

    $sql = "DELETE FROM $tableSepeParticipants WHERE id = $id;";
    $res = Database::query($sql);
    if (!$res) {
        $content = $plugin->get_lang('ProblemToDeleteInfoParticipant');
        echo json_encode(['status' => 'false', 'content' => $content]);
    } else {
        $content = $plugin->get_lang('DeleteOk');
        echo json_encode(['status' => 'true', 'content' => $content]);
    }
}

if ('unlink_action' == $_REQUEST['tab']) {
    $id = (int) (substr($_REQUEST['id'], 16));
    $sql = "DELETE FROM $tableSepeCourseActions WHERE id = $id;";
    $res = Database::query($sql);
    if (!$res) {
        $content = $plugin->get_lang('ProblemToDesvincularInfoAction');
        echo json_encode(['status' => 'false', 'content' => $content]);
    } else {
        $content = $plugin->get_lang('DeleteOk');
        echo json_encode(['status' => 'true', 'content' => $content]);
    }
}

if ('assign_action' == $_REQUEST['tab']) {
    $course_id = (int) (substr($_REQUEST['course_id'], 9));
    $action_id = (int) ($_REQUEST['action_id']);

    if (0 != $action_id && 0 != $course_id) {
        $sql = "SELECT * FROM $tableSepeCourseActions WHERE action_id = $action_id;";
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            $content = $plugin->get_lang('FormativeActionInUse');
            echo json_encode(['status' => 'false', 'content' => $content]);
        } else {
            $sql = "SELECT 1 FROM course WHERE id = $course_id;";
            $rs = Database::query($sql);
            if (0 == Database::num_rows($rs)) {
                $content = $plugin->get_lang('NoExistsCourse');
                echo json_encode(['status' => 'false', 'content' => $content]);
            } else {
                $sql = "INSERT INTO $tableSepeCourseActions (course_id, action_id) VALUES ($course_id, $action_id);";
                $rs = Database::query($sql);
                if (!$rs) {
                    $content = $plugin->get_lang('NoSaveData');
                    echo json_encode(['status' => 'false', 'content' => utf8_encode($content)]);
                } else {
                    echo json_encode(['status' => 'true']);
                }
            }
        }
    } else {
        $content = $plugin->get_lang('ErrorDataIncorrect');
        echo json_encode(['status' => 'false', 'content' => $content]);
    }
}

if ('key_sepe_generator' == $_REQUEST['tab']) {
    $tApi = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
    $info_user = api_get_user_info_from_username('SEPE');

    $array_list_key = [];
    $user_id = $info_user['user_id'];
    $api_service = 'dokeos';
    $num = UserManager::update_api_key($user_id, $api_service);
    $array_list_key = UserManager::get_api_keys($user_id, $api_service);

    if ('' != trim($array_list_key[$num])) {
        $content = $array_list_key[$num];
        echo json_encode(['status' => 'true', 'content' => $content]);
    } else {
        $content = $plugin->get_lang('ProblemGenerateApiKey');
        echo json_encode(['status' => 'false', 'content' => $content]);
    }
}
