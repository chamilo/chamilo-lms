<?php
/* For license terms, see /license.txt */
/**
 * Functions for the Sepe plugin
 * @package chamilo.plugin.sepe
 */
/**
 * Init
 */

require_once '../config.php';
/*
require_once 'sepe.lib.php';
//require_once api_get_path(LIBRARY_PATH) . 'mail.lib.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'course.lib.php';

$tableSepeCenter = Database::get_main_table(TABLE_SEPE_CENTER);
$tableSepeActions = Database::get_main_table(TABLE_SEPE_ACTIONS);
$tableSepeSpecialty = Database::get_main_table(TABLE_SEPE_SPECIALTY);
$tableSepeSpecialtyClassroom = Database::get_main_table(TABLE_SEPE_SPECIALTY_CLASSROOM);
$tableSepeSpecialtyTutors = Database::get_main_table(TABLE_SEPE_SPECIALTY_TUTORS);
$tableSepeTutors = Database::get_main_table(TABLE_SEPE_TUTORS);
$tableSepeParticipants = Database::get_main_table(TABLE_SEPE_PARTICIPANTS);
$tableSepeParticipantsSpecialty = Database::get_main_table(TABLE_SEPE_PARTICIPANTS_SPECIALTY);
$tableSepeParticipantsSpecialtyTutorials = Database::get_main_table(TABLE_SEPE_PARTICIPANTS_SPECIALTY_TUTORIALS);
$tableSepeCourseActions = Database::get_main_table(TABLE_SEPE_COURSE_ACTIONS);
$tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
$tableCourseRelUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tableUser = Database::get_main_table(TABLE_MAIN_USER);
*/

$plugin = SepePlugin::create();

if ($_REQUEST['tab'] == 'borra_datos_centro') {
    $sql = "DELETE FROM $tableSepeCenter;";
    $res = Database::query($sql);
    if (!$res) {
        $sql = "DELETE FROM $tableSepeActions;";
        $res = Database::query($sql);
        $content = $plugin->get_lang('ProblemToDeleteInfoCenter') . Database::error();
        echo json_encode(array("status" => "false", "content" => $content));
    } else {
        $content = $plugin->get_lang('DeleteOk');
        echo json_encode(array("status" => "true", "content" => $content));
    }
}

if ($_REQUEST['tab'] == 'borra_accion_formativa') {
    $cod = $_REQUEST['cod'];
    $sql = "DELETE FROM $tableSepeActions WHERE cod='".$cod."';";
    $res = Database::query($sql);
    if (!$res) {
        $content = $plugin->get_lang('ProblemToDeleteInfoAction') . Database::error();
        echo json_encode(array("status" => "false", "content" => $content));
    } else {
        $content = $plugin->get_lang('DeleteOk');
        $_SESSION['sepe_message_info'] = $content;
        echo json_encode(array("status" => "true"));
    }
}

if ($_REQUEST['tab'] == 'borra_especialidad_accion') {
    $cod = substr($_REQUEST['cod'],9);
    $sql = "DELETE FROM $tableSepeSpecialty WHERE cod='".$cod."';";
    $res = Database::query($sql);
    if (!$res) {
        $content = $plugin->get_lang('ProblemToDeleteInfoSpecialty') . Database::error();
        echo json_encode(array("status" => "false", "content" => $content));
    } else {
        $content = $plugin->get_lang('DeleteOk');
        echo json_encode(array("status" => "true", "content" => $content));
    }
}

if ($_REQUEST['tab'] == 'borra_especialidad_participante') {
    $cod = substr($_REQUEST['cod'],9);
    $sql = "DELETE FROM $tableSepeParticipantsSpecialty WHERE cod='".$cod."';";
    $res = Database::query($sql);
    if (!$res) {
        $content = $plugin->get_lang('ProblemToDeleteInfoSpecialty') . Database::error();
        echo json_encode(array("status" => "false", "content" => $content));
    } else {
        $content = $plugin->get_lang('DeleteOk');
        echo json_encode(array("status" => "true", "content" => $content));
    }
}

if ($_REQUEST['tab'] == 'borra_especialidad_classroom') {
    $cod = substr($_REQUEST['cod'],9);
    $sql = "DELETE FROM $tableSepeSpecialtyClassroom WHERE cod='".$cod."';";
    $res = Database::query($sql);
    if (!$res) {
        $content = $plugin->get_lang('ProblemToDeleteInfoSpecialtyClassroom') . Database::error();
        echo json_encode(array("status" => "false", "content" => $content));
    } else {
        $content = $plugin->get_lang('DeleteOk');
        echo json_encode(array("status" => "true", "content" => $content));
    }
}

if ($_REQUEST['tab'] == 'comprobar_editar_tutor') {
    //$cod = substr($_REQUEST['cod'],9);
    $tipo = $_REQUEST['tipo'];
    $num = $_REQUEST['num'];
    $letra=$_REQUEST['letra'];
    $codchamilo = $_REQUEST['codchamilo'];
    
    $sql = "SELECT cod_user_chamilo FROM $tableSepeTutors WHERE TIPO_DOCUMENTO='".$tipo."' AND NUM_DOCUMENTO='".$num."' AND LETRA_NIF='".$letra."';";
    $res = Database::query($sql);
    if (!$res) {
        $content = $plugin->get_lang('ProblemDataBase') . Database::error();
        error_log(print_r($content,1));
        exit;
    } else {
        $aux = Database::fetch_assoc($res);
        if ($aux['cod_user_chamilo']==$codchamilo || $aux['cod_user_chamilo']=='0') {
            echo json_encode(array("status" => "true"));
        } else {
            $content = $plugin->get_lang('ModDataTeacher');
            echo json_encode(array("status" => "false", "content" => $content));
        }
    }
}

if ($_REQUEST['tab'] == 'borra_especialidad_tutor') {
    $cod = substr($_REQUEST['cod'],5);
    $sql = "DELETE FROM $tableSepeSpecialtyTutors WHERE cod='".$cod."';";
    $res = Database::query($sql);
    if (!$res) {
        $content = $plugin->get_lang('ProblemToDeleteInfoSpecialtyTutor') . Database::error();
        echo json_encode(array("status" => "false", "content" => $content));
    } else {
        $content = $plugin->get_lang('DeleteOk');
        echo json_encode(array("status" => "true", "content" => $content));
    }
}

if ($_REQUEST['tab'] == 'borra_participante_accion') {
    $cod = substr($_REQUEST['cod'],11);
    $sql = "SELECT cod_user_chamilo, cod_action FROM $tableSepeParticipants WHERE cod='".$cod."';";
    $res = Database::query($sql);
    $row = Database::fetch_assoc($res);
    
    $sql = "UPDATE plugin_sepe_log_participant SET fecha_baja='".date("Y-m-d H:i:s")."' WHERE cod_user_chamilo='".$row['cod_user_chamilo']."' AND cod_action='".$row['cod_action']."';";
    $res = Database::query($sql);
    
    $sql = "DELETE FROM $tableSepeParticipants WHERE cod='".$cod."';";
    $res = Database::query($sql);
    if (!$res) {
        $content = $plugin->get_lang('ProblemToDeleteInfoParticipant') . Database::error();
        echo json_encode(array("status" => "false", "content" => $content));
    } else {
        $content = $plugin->get_lang('DeleteOk');
        echo json_encode(array("status" => "true", "content" => $content));
    }
}

if ($_REQUEST['tab'] == 'desvincular_action') {
    $cod = substr($_REQUEST['cod'],3);
    $sql = "DELETE FROM $tableSepeCourseActions WHERE cod='".$cod."';";
    $res = Database::query($sql);
    if (!$res) {
        $content = $plugin->get_lang('ProblemToDesvincularInfoAction') . Database::error();
        echo json_encode(array("status" => "false", "content" => $content));
    } else {
        $content = $plugin->get_lang('DeleteOk');
        echo json_encode(array("status" => "true", "content" => $content));
    }
}

if ($_REQUEST['tab'] == 'asignar_accion') {
    $id_course = substr($_REQUEST['cod_course'],11);
    $cod_action = $_REQUEST['cod_action'];
    
    if (trim($cod_action)!='' && trim($id_course)!='') {
        $cod_action = Database::escape_string($cod_action);
        $id_course = Database::escape_string($id_course);
        $sql = "SELECT * FROM $tableSepeCourseActions WHERE cod_action='".$cod_action."';";
        
        $rs = Database::query($sql);
        if (Database::num_rows($rs) > 0) {
            $content = "La acci&oacute;n formativa elegida est&aacute; siendo usada por otro curso";
            echo json_encode(array("status" => "false", "content" => $content));
        } else {
            $sql = "SELECT 1 FROM course WHERE id='".$id_course."';";
            $rs = Database::query($sql);
            if (Database::num_rows($rs) == 0) {
                $content = "El curso al que se le asocia la acci&oacute;n formativa no existe";
                echo json_encode(array("status" => "false", "content" => $content));
            } else {
                $sql = "INSERT INTO $tableSepeCourseActions (id_course, cod_action) VALUES ('".$id_course."','".$cod_action."');";    
                $rs = Database::query($sql);
                if (!$rs) {
                    $content = "No se ha podido guardar la selección"; 
                    echo json_encode(array("status" => "false", "content" => utf8_encode($content)));
                } else {
                    echo json_encode(array("status" => "true"));    
                }
            }
        }
    } else {
        $content = "Error al recibir los datos"; 
        echo json_encode(array("status" => "false", "content" => $content));
    }
}

if ($_REQUEST['tab'] == 'generar_api_key_sepe') {
    $tApi = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
    //$info_user = UserManager::get_user_info('SEPE');
    $info_user = api_get_user_info_from_username('SEPE');

    $array_list_key = array();
    $user_id = $info_user['user_id'];
    $api_service = 'dokeos';
    $num = UserManager::update_api_key($user_id, $api_service);
    $array_list_key = UserManager::get_api_keys($user_id, $api_service);
    
    if (trim($array_list_key[$num])!='') {
        $content = $array_list_key[$num];
        echo json_encode(array("status" => "true", "content" => $content));
    } else {
        $content = "Problema al generar una nueva api key";
        echo json_encode(array("status" => "false", "content" => $content));
    }
}
