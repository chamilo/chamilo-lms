<?php
/* For licensing terms, see /license.txt */

use \ChamiloSession as Session;
require_once '../config.php';

$course_plugin = 'sepe';
$plugin = SepePlugin::create();
$_cid = 0;

if ( ! empty($_POST))
{
    reset ($_POST);
    while (list ($param, $val) = each ($_POST)) {
        $valor = Database::escape_string($_POST[$param]);
        $asignacion = "\$" . $param . "='" . $valor . "';";
        //echo $asignacion;
        eval($asignacion);
    }

    if ($slt_user_existente == "SI") {
        $sql = "SELECT * FROM plugin_sepe_tutors WHERE cod='".$tutor_existente."';";
        $rs = Database::query($sql);
        $tmp = Database::fetch_assoc($rs);
                    
        $sql = "INSERT INTO plugin_sepe_specialty_tutors (cod_specialty, cod_tutor,ACREDITACION_TUTOR,EXPERIENCIA_PROFESIONAL,COMPETENCIA_DOCENTE,EXPERIENCIA_MODALIDAD_TELEFORMACION,FORMACION_MODALIDAD_TELEFORMACION) 
                VALUES ('".$cod_specialty."','".$tutor_existente."','".$tmp['ACREDITACION_TUTOR']."','".$tmp['EXPERIENCIA_PROFESIONAL']."','".$tmp['COMPETENCIA_DOCENTE']."','".$tmp['EXPERIENCIA_MODALIDAD_TELEFORMACION']."','".$tmp['FORMACION_MODALIDAD_TELEFORMACION']."');";
        $res = Database::query($sql);
    } else {
        $sql = "SELECT cod FROM plugin_sepe_tutors 
                WHERE TIPO_DOCUMENTO='".$TIPO_DOCUMENTO."' AND NUM_DOCUMENTO='".$NUM_DOCUMENTO."' AND LETRA_NIF='".$LETRA_NIF."';";
        $rs = Database::query($sql);
        if (Database::num_rows($rs)>0) {
            //datos identificativos existen se actualizan
            $aux = Database::fetch_assoc($rs);
            $sql = "UPDATE plugin_sepe_tutors SET 
                    cod_user_chamilo='".$cod_user_chamilo."', 
                    ACREDITACION_TUTOR='".$ACREDITACION_TUTOR."', 
                    EXPERIENCIA_PROFESIONAL='".$EXPERIENCIA_PROFESIONAL."', 
                    COMPETENCIA_DOCENTE='".$COMPETENCIA_DOCENTE."', 
                    EXPERIENCIA_MODALIDAD_TELEFORMACION='".$EXPERIENCIA_MODALIDAD_TELEFORMACION."', 
                    FORMACION_MODALIDAD_TELEFORMACION='".$FORMACION_MODALIDAD_TELEFORMACION."' 
                    WHERE cod='".$aux['cod']."';";     
            $res = Database::query($sql);
            if (!$res) {
                echo Database::error();
                exit;
                $_SESSION['sepe_message_error'] = "No se ha guardado los cambios";
            }
            $cod_tutor = $aux['cod'];
        } else {
            //datos identificativos no existen se crea un nuevo registro
            Database::query('UPDATE plugin_sepe_tutors SET cod_user_chamilo="" WHERE cod_user_chamilo="'.$cod_user_chamilo.'"');
            $sql = "INSERT INTO plugin_sepe_tutors (cod_user_chamilo,TIPO_DOCUMENTO,NUM_DOCUMENTO,LETRA_NIF,ACREDITACION_TUTOR,EXPERIENCIA_PROFESIONAL,COMPETENCIA_DOCENTE,EXPERIENCIA_MODALIDAD_TELEFORMACION,FORMACION_MODALIDAD_TELEFORMACION) 
                VALUES 
                ('".$cod_user_chamilo."','".$TIPO_DOCUMENTO."','".$NUM_DOCUMENTO."','".$LETRA_NIF."','".$ACREDITACION_TUTOR."','".$EXPERIENCIA_PROFESIONAL."','".$COMPETENCIA_DOCENTE."','".$EXPERIENCIA_MODALIDAD_TELEFORMACION."','".$FORMACION_MODALIDAD_TELEFORMACION."');";
            $res = Database::query($sql);
            if (!$res) {
                echo Database::error();
                $_SESSION['sepe_message_error'] = "No se ha guardado los cambios";
            }
            $cod_tutor = Database::insert_id();
        }
        
        if (isset($new_tutor) && $new_tutor!="SI") {
            $sql = "UPDATE plugin_sepe_specialty_tutors SET 
                    cod_tutor='".$cod_tutor."', 
                    ACREDITACION_TUTOR='".$ACREDITACION_TUTOR."', 
                    EXPERIENCIA_PROFESIONAL='".$EXPERIENCIA_PROFESIONAL."', 
                    COMPETENCIA_DOCENTE='".$COMPETENCIA_DOCENTE."', 
                    EXPERIENCIA_MODALIDAD_TELEFORMACION='".$EXPERIENCIA_MODALIDAD_TELEFORMACION."', 
                    FORMACION_MODALIDAD_TELEFORMACION='".$FORMACION_MODALIDAD_TELEFORMACION."' 
                    WHERE cod='".$cod_s_tutor."';";    
        } else {
            $sql = "INSERT INTO plugin_sepe_specialty_tutors (cod_specialty,cod_tutor,ACREDITACION_TUTOR,EXPERIENCIA_PROFESIONAL,COMPETENCIA_DOCENTE,EXPERIENCIA_MODALIDAD_TELEFORMACION,FORMACION_MODALIDAD_TELEFORMACION) 
                    VALUES 
                    ('".$cod_specialty."','".$cod_tutor."','".$ACREDITACION_TUTOR."','".$EXPERIENCIA_PROFESIONAL."','".$COMPETENCIA_DOCENTE."','".$EXPERIENCIA_MODALIDAD_TELEFORMACION."','".$FORMACION_MODALIDAD_TELEFORMACION."');";
        
        $res = Database::query($sql);
        if (!$res) {
            echo Database::error();
            $_SESSION['sepe_message_error'] = "No se ha guardado los cambios";
        } else {
            $_SESSION['sepe_message_info'] = "Se ha guardado los cambios";
            if ($new_tutor=="SI") {
                $cod_tutor = Database::insert_id();
                //$sql = "INSERT INTO plugin_sepe_specialty_tutors (cod_specialty, cod_tutor) VALUES ('".$cod_specialty."','".$cod_tutor."');";
                //$res = Database::query($sql);
            }
        }
    }
    session_write_close();
    $id_course = obtener_course($cod_action);
    header("Location: editar-especialidad-accion.php?new_specialty=NO&cod_specialty=".$cod_specialty."&cod_action=".$cod_action);
}

if (api_is_platform_admin()) {
    $id_course = obtener_course($_GET['cod_action']);
    $interbreadcrumb[] = array("url" => "/plugin/sepe/src/menu_sepe_administracion.php", "name" => $plugin->get_lang('menu_sepe'));
    $interbreadcrumb[] = array("url" => "listado-acciones-formativas.php", "name" => $plugin->get_lang('listado_acciones_formativas'));
    $interbreadcrumb[] = array("url" => "accion-formativa.php?cid=".$id_course, "name" => $plugin->get_lang('accion_formativa'));
    $interbreadcrumb[] = array("url" => "editar-especialidad-accion.php?new_specialty=NO&cod_specialty=".$_GET['cod_specialty']."&cod_action=".$_GET['cod_action'], "name" => $plugin->get_lang('especialidad_accion_formativa'));
    if (isset($_GET['new_tutor']) && $_GET['new_tutor']=="SI") {
        $templateName = $plugin->get_lang('new_specialty_tutor');
        $tpl = new Template($templateName);
        $tpl->assign('cod_action', $_GET['cod_action']);
        $tpl->assign('cod_specialty', $_GET['cod_specialty']);
        $info = array();
        $tpl->assign('info', $info);
        $tpl->assign('new_tutor', 'SI');
        $inicio_anio = date("Y");
        $cod_profesor_chamilo = '';
    } else {
        $templateName = $plugin->get_lang('edit_specialty_tutor');
        $tpl = new Template($templateName);
        $tpl->assign('cod_action', $_GET['cod_action']);
        $tpl->assign('cod_specialty', $_GET['cod_specialty']);
        $tpl->assign('cod_tutor', $_GET['cod_tutor']);
        $info = especialidad_tutor($_GET['cod_tutor']);
        $tpl->assign('info', $info);
        $tpl->assign('new_tutor', 'NO');
        $cod_profesor_chamilo = $info['cod_user_chamilo'];
    }
    $listTutores = listado_tutores_specialty($_GET['cod_specialty']);
    $tpl->assign('listTutorsExistentes', $listTutores);
    
    $course_code = obtener_course_code($_GET['cod_action']);
    $listProfesor = CourseManager::get_teacher_list_from_course_code($course_code);
    $listProfesor = limpiarAsignadosProfesores($listProfesor,$_GET['cod_specialty'],$cod_profesor_chamilo);
    $tpl->assign('listProfesor', $listProfesor);
    if (isset($_SESSION['sepe_message_info'])) {
        $tpl->assign('message_info', $_SESSION['sepe_message_info']);    
        unset($_SESSION['sepe_message_info']);
    }
    if (isset($_SESSION['sepe_message_error'])) {
        $tpl->assign('message_error', $_SESSION['sepe_message_error']);    
        unset($_SESSION['sepe_message_error']);
    }
        
    $listing_tpl = 'sepe/view/editar_especialidad_tutor.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();
} else {
    header("location: http://".$_SERVER['SERVER_NAME']);
}
