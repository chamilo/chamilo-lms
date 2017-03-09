<?php
/* For licensing terms, see /license.txt */

use \ChamiloSession as Session;
require_once '../config.php';

$course_plugin = 'sepe';
$plugin = SepePlugin::create();
$_cid = 0;

if ( ! empty($_POST))
{
    /*
    echo "<pre>";
    echo var_dump($_POST);
    echo "</pre>";
     */
    reset ($_POST);
    while (list ($param, $val) = each ($_POST)) {
        $valor = Database::escape_string($_POST[$param]);
        $asignacion = "\$" . $param . "='" . $valor . "';";
        //echo $asignacion;
        eval($asignacion);
    }
    
    if (isset($cod_tutor_empresa) && $cod_tutor_empresa=="nuevo_tutor_empresa") {
        $sql = "SELECT * FROM plugin_sepe_tutors_empresa 
                WHERE TIPO_DOCUMENTO='".$TE_TIPO_DOCUMENTO."' AND NUM_DOCUMENTO='".$TE_NUM_DOCUMENTO."' AND LETRA_NIF='".$TE_LETRA_NIF."';";
        $rs = Database::query($sql);
        if (Database::num_rows($rs)>0) {
            $row = Database::fetch_assoc($rs);
            $cod_tutor_empresa = $row['cod'];
            $sql = "UPDATE plugin_sepe_tutors_empresa SET empresa='SI' WHERE cod='".$cod_tutor_empresa."'";
            Database::query($sql);
        } else {
            $sql = "INSERT INTO plugin_sepe_tutors_empresa (alias,TIPO_DOCUMENTO,NUM_DOCUMENTO,LETRA_NIF,empresa) 
                    VALUES ('".$TE_alias."','".$TE_TIPO_DOCUMENTO."','".$TE_NUM_DOCUMENTO."','".$TE_LETRA_NIF."','SI');";
            $rs = Database::query($sql);
            if (!$rs) {
                echo Database::error();    
            } else {
                $cod_tutor_empresa = Database::insert_id();
            }
        }
    }
    
    if (isset($cod_tutor_formacion) && $cod_tutor_formacion=="nuevo_tutor_formacion") {
        $sql = "SELECT * FROM plugin_sepe_tutors_empresa 
                WHERE TIPO_DOCUMENTO='".$TF_TIPO_DOCUMENTO."' AND NUM_DOCUMENTO='".$TF_NUM_DOCUMENTO."' AND LETRA_NIF='".$TF_LETRA_NIF."';";
        $rs = Database::query($sql);

        if (Database::num_rows($rs)>0) {
            $row = Database::fetch_assoc($rs);
            $cod_tutor_formacion = $row['cod'];
            $sql = "UPDATE plugin_sepe_tutors_empresa SET formacion='SI' WHERE cod='".$cod_tutor_formacion."'";
            Database::query($sql);
        } else {
            $sql = "INSERT INTO plugin_sepe_tutors_empresa (alias,TIPO_DOCUMENTO,NUM_DOCUMENTO,LETRA_NIF,formacion) 
                    VALUES ('".$TF_alias."','".$TF_TIPO_DOCUMENTO."','".$TF_NUM_DOCUMENTO."','".$TF_LETRA_NIF."','SI');";
            $rs = Database::query($sql);
            if (!$rs) {
                echo Database::error();    
            } else {
                $cod_tutor_formacion = Database::insert_id();
            }
        }
    }
    
    if (isset($new_participant) && $new_participant!="SI") {
        $sql = "UPDATE plugin_sepe_participants SET cod_user_chamilo='".$cod_user_chamilo."', TIPO_DOCUMENTO='".$TIPO_DOCUMENTO."', NUM_DOCUMENTO='".$NUM_DOCUMENTO."', LETRA_NIF='".$LETRA_NIF."', INDICADOR_COMPETENCIAS_CLAVE='".$INDICADOR_COMPETENCIAS_CLAVE."', ID_CONTRATO_CFA='".$ID_CONTRATO_CFA."', CIF_EMPRESA='".$CIF_EMPRESA."', cod_tutor_empresa='".$cod_tutor_empresa."', cod_tutor_formacion='".$cod_tutor_formacion."' WHERE cod='".$cod_participant."';";    
    } else {
        $sql = "INSERT INTO plugin_sepe_participants(cod_action,cod_user_chamilo,TIPO_DOCUMENTO,NUM_DOCUMENTO,LETRA_NIF,INDICADOR_COMPETENCIAS_CLAVE,ID_CONTRATO_CFA,CIF_EMPRESA,cod_tutor_empresa,cod_tutor_formacion) 
                VALUES ('".$cod_action."','".$cod_user_chamilo."','".$TIPO_DOCUMENTO."','".$NUM_DOCUMENTO."','".$LETRA_NIF."','".$INDICADOR_COMPETENCIAS_CLAVE."','".$ID_CONTRATO_CFA."','".$CIF_EMPRESA."','".$cod_tutor_empresa."','".$cod_tutor_formacion."');";
    }
    
    $res = Database::query($sql);
    if (!$res) {
        echo Database::error();
        $_SESSION['sepe_message_error'] = "No se ha guardado los cambios";
    } else {
        $_SESSION['sepe_message_info'] = "Se ha guardado los cambios";
        if ($new_participant=="SI") {
            $cod_participant = Database::insert_id();
            $sql = "INSERT INTO plugin_sepe_log_participant (cod_user_chamilo, cod_action, fecha_alta) VALUES ('".$cod_user_chamilo."','".$cod_action."','".date("Y-m-d H:i:s")."');";
            $res = Database::query($sql);
        } else {
            $sql = "INSERT INTO plugin_sepe_log_mod_participant (cod_user_chamilo, cod_action, fecha_mod) VALUES ('".$cod_user_chamilo."','".$cod_action."','".date("Y-m-d H:i:s")."');";
            $res = Database::query($sql);    
        }
    }
    session_write_close();
    $id_course = obtener_course($cod_action);
    header("Location: editar-participante-accion.php?new_participant=NO&cod_participant=".$cod_participant."&cod_action=".$cod_action);
    
}



if (api_is_platform_admin()) {
    $id_course = obtener_course($_GET['cod_action']);
    $interbreadcrumb[] = array("url" => "/plugin/sepe/src/menu_sepe_administracion.php", "name" => $plugin->get_lang('menu_sepe'));
    $interbreadcrumb[] = array("url" => "listado-acciones-formativas.php", "name" => $plugin->get_lang('listado_acciones_formativas'));
    $interbreadcrumb[] = array("url" => "accion-formativa.php?cid=".$id_course, "name" => $plugin->get_lang('accion_formativa'));
    if (isset($_GET['new_participant']) && $_GET['new_participant']=="SI") {
        $templateName = $plugin->get_lang('new_participant_accion');
        $tpl = new Template($templateName);
        $tpl->assign('cod_action', $_GET['cod_action']);
        $info = array();
        $tpl->assign('info', $info);
        $tpl->assign('new_participant', 'SI');
    } else {
        $templateName = $plugin->get_lang('edit_participant_accion');
        $tpl = new Template($templateName);
        $tpl->assign('cod_action', $_GET['cod_action']);
        $info = participante_accion($_GET['cod_participant']);
        $tpl->assign('info', $info);
        $tpl->assign('new_participant', 'NO');
        $tpl->assign('cod_participant', $_GET['cod_participant']);
        
        if ($info['cod_user_chamilo'] != 0) {
            $info_usuario_chamilo = api_get_user_info($info['cod_user_chamilo']);//UserManager::get_user_info_by_id($info['cod_user_chamilo']);
            $tpl->assign('info_user_chamilo', $info_usuario_chamilo);
        }
        
        $listParticipantSpecialty = listParticipantSpecialty($_GET['cod_participant']);
        $tpl->assign('listParticipantSpecialty', $listParticipantSpecialty);
    }
    $course_code = obtener_course_code($_GET['cod_action']);
    //$cod_curso = obtener_course($_GET['cod_action']);
    $listAlumnoInfo = array();
    $listAlumno = CourseManager::get_student_list_from_course_code($course_code);
    
    foreach ($listAlumno as $value) {
        $sql = "SELECT 1 FROM plugin_sepe_participants WHERE cod_user_chamilo='".$value['user_id']."';";
        $res = Database::query($sql);
        if (Database::num_rows($res)==0) {
            $listAlumnoInfo[] = api_get_user_info($value['user_id']); //UserManager::get_user_info_by_id($value['user_id']);
        }
    }
    /*
    echo "<pre>";
    echo var_dump($listAlumnoInfo);
    echo "</pre>"; 
    exit;
    */
    $tpl->assign('listAlumno', $listAlumnoInfo);
    $listTutorE = array();
    $listTutorE = listadoTutorE();
    $tpl->assign('listTutorE', $listTutorE);
    $listTutorF = array();
    $listTutorF= listadoTutorE("formacion='SI'");
    $tpl->assign('listTutorF', $listTutorF);
    
    if (isset($_SESSION['sepe_message_info'])) {
        $tpl->assign('message_info', $_SESSION['sepe_message_info']);    
        unset($_SESSION['sepe_message_info']);
    }
    if (isset($_SESSION['sepe_message_error'])) {
        $tpl->assign('message_error', $_SESSION['sepe_message_error']);    
        unset($_SESSION['sepe_message_error']);
    }
    
    
    $listing_tpl = 'sepe/view/editar_participante_accion.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();

} else {
    header("location: http://".$_SERVER['SERVER_NAME']);
}
