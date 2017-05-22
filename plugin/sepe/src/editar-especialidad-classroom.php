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
    
    if ($slt_centro_existente == "SI") {
        $sql = "INSERT INTO plugin_sepe_specialty_classroom (cod_specialty, cod_centro) 
                VALUES ('".$cod_specialty."','".$centro_existente."');";
        $res = Database::query($sql);
        if (!$res) {
            echo Database::error();
            $_SESSION['sepe_message_error'] = "No se ha guardado los cambios";
        } else {
            $_SESSION['sepe_message_info'] = "Se ha guardado los cambios";
            if ($new_classroom=="SI") {
                $cod_classroom = Database::insert_id();
            }
        }
    } else {
    
        //Comprobamos si existen en los centros existentes
        $sql = "SELECT * FROM plugin_sepe_centros 
                WHERE ORIGEN_CENTRO='".$ORIGEN_CENTRO."' AND CODIGO_CENTRO='".$CODIGO_CENTRO."'";
        $rs_tmp = Database::query($sql);
        if (Database::num_rows($rs_tmp)>0) {
            $aux = Database::fetch_assoc($rs_tmp);
            $cod_centro = $aux['cod'];    
        } else {
            $params = array(
                'ORIGEN_CENTRO' => $ORIGEN_CENTRO,
                'CODIGO_CENTRO' => $CODIGO_CENTRO,
            );
            $cod_centro = Database::insert('plugin_sepe_centros', $params);
        }

        if (isset($new_classroom) && $new_classroom!="SI") {
            $sql = "UPDATE plugin_sepe_specialty_classroom SET cod_centro='".$cod_centro."' WHERE cod='".$cod_classroom."';";    
        } else {
            $sql = "INSERT INTO plugin_sepe_specialty_classroom (cod_specialty, cod_centro) VALUES ('".$cod_specialty."','".$cod_centro."');";
        }
        //echo $sql;
        //exit;
        
        $res = Database::query($sql);
        if (!$res) {
            echo Database::error();
            $_SESSION['sepe_message_error'] = "No se ha guardado los cambios";
        } else {
            $_SESSION['sepe_message_info'] = "Se ha guardado los cambios";
            if ($new_classroom=="SI") {
                $cod_classroom = Database::insert_id();
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
    if (isset($_GET['new_classroom']) && $_GET['new_classroom']=="SI") {
        $templateName = $plugin->get_lang('new_specialty_classroom');
        $tpl = new Template($templateName);
        $tpl->assign('cod_action', $_GET['cod_action']);
        $tpl->assign('cod_specialty', $_GET['cod_specialty']);
        $info = array();
        $tpl->assign('info', $info);
        $tpl->assign('new_classroom', 'SI');
    } else {
        $templateName = $plugin->get_lang('edit_specialty_classroom');
        $tpl = new Template($templateName);
        $tpl->assign('cod_action', $_GET['cod_action']);
        $tpl->assign('cod_specialty', $_GET['cod_specialty']);
        $tpl->assign('cod_classroom', $_GET['cod_classroom']);
        $info = especialidad_classroom($_GET['cod_classroom']);
        $tpl->assign('info', $info);
        $tpl->assign('new_classroom', 'NO');
            
    }
    $listCentros = listado_centros();

    $tpl->assign('listCentrosExistentes', $listCentros);
    
    if (isset($_SESSION['sepe_message_info'])) {
        $tpl->assign('message_info', $_SESSION['sepe_message_info']);    
        unset($_SESSION['sepe_message_info']);
    }
    if (isset($_SESSION['sepe_message_error'])) {
        $tpl->assign('message_error', $_SESSION['sepe_message_error']);    
        unset($_SESSION['sepe_message_error']);
    }
        
    $listing_tpl = 'sepe/view/editar_especialidad_classroom.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();

} else {
    header("location: http://".$_SERVER['SERVER_NAME']);
}
