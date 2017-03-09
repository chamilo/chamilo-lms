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
    $fecha_inicio = $year_start."-".$month_start."-".$day_start;
    $fecha_fin = $year_end."-".$month_end."-".$day_end;

    if (isset($new_tutorial) && $new_tutorial!="SI") {
        $sql = "UPDATE plugin_sepe_participants_specialty_tutorials SET ORIGEN_CENTRO='".$ORIGEN_CENTRO."', CODIGO_CENTRO='".$CODIGO_CENTRO."', FECHA_INICIO='".$fecha_inicio."', FECHA_FIN='".$fecha_fin."' WHERE cod='".$cod_tutorial."';";    
    } else {
        $sql = "INSERT INTO plugin_sepe_participants_specialty_tutorials (cod_participant_specialty, ORIGEN_CENTRO,CODIGO_CENTRO,FECHA_INICIO,FECHA_FIN) VALUES ('".$cod_specialty."','".$ORIGEN_CENTRO."','".$CODIGO_CENTRO."','".$fecha_inicio."','".$fecha_fin."');";
    }
    
    $res = Database::query($sql);
    if (!$res) {
        echo Database::error();
        $_SESSION['sepe_message_error'] = "No se ha guardado los cambios";
    } else {
        $_SESSION['sepe_message_info'] = "Se ha guardado los cambios";
        if ($new_tutorial=="SI") {
            $cod_tutorial = Database::insert_id();
        }
    }
    
    session_write_close();
    $id_course = obtener_course($cod_action);
    $cod_participant = obtener_participant($cod_specialty);
    header("Location: editar-especialidad-participante.php?new_specialty=NO&cod_participant=".$cod_participant."&cod_specialty=".$cod_specialty."&cod_action=".$cod_action);
    
}



if (api_is_platform_admin()) {
    $id_course = obtener_course($_GET['cod_action']);
    $cod_participant = obtener_participant($_GET['cod_specialty']);
    $interbreadcrumb[] = array("url" => "/plugin/sepe/src/menu_sepe_administracion.php", "name" => $plugin->get_lang('menu_sepe'));
    $interbreadcrumb[] = array("url" => "listado-acciones-formativas.php", "name" => $plugin->get_lang('listado_acciones_formativas'));
    $interbreadcrumb[] = array("url" => "accion-formativa.php?cid=".$id_course, "name" => $plugin->get_lang('accion_formativa'));
    $interbreadcrumb[] = array("url" => "editar-especialidad-participante.php?new_specialty=NO&cod_participant=".$cod_participant."&cod_specialty=".$_GET['cod_specialty']."&cod_action=".$_GET['cod_action'], "name" => $plugin->get_lang('participante_especialidad_formativa'));
    if (isset($_GET['new_tutorial']) && $_GET['new_tutorial']=="SI") {
        $templateName = $plugin->get_lang('new_tutorial');
        $tpl = new Template($templateName);
        $tpl->assign('cod_action', $_GET['cod_action']);
        $tpl->assign('cod_specialty', $_GET['cod_specialty']);
        $info = array();
        $tpl->assign('info', $info);
        $tpl->assign('new_tutorial', 'SI');
        $inicio_anio = date("Y");
    } else {
        $templateName = $plugin->get_lang('edit_tutorial');
        $tpl = new Template($templateName);
        $tpl->assign('cod_action', $_GET['cod_action']);
        $tpl->assign('cod_specialty', $_GET['cod_specialty']);
        $tpl->assign('cod_tutorial', $_GET['cod_tutorial']);
        $info = especialidad_tutorial($_GET['cod_tutorial']);
        $tpl->assign('info', $info);
        $tpl->assign('new_tutorial', 'NO');
        if ($info['FECHA_INICIO']!='0000-00-00' && $info['FECHA_INICIO']!=NULL) {
            $tpl->assign('day_start', date("j",strtotime($info['FECHA_INICIO'])));
            $tpl->assign('month_start', date("n",strtotime($info['FECHA_INICIO'])));
            $tpl->assign('year_start', date("Y",strtotime($info['FECHA_INICIO'])));
            $inicio_anio = date("Y",strtotime($info['FECHA_INICIO']));
        } else {
            $inicio_anio = date("Y");
        }
        if ($info['FECHA_FIN']!='0000-00-00' && $info['FECHA_FIN']!=NULL) {
            $tpl->assign('day_end', date("j",strtotime($info['FECHA_FIN'])));
            $tpl->assign('month_end', date("n",strtotime($info['FECHA_FIN'])));
            $tpl->assign('year_end', date("Y",strtotime($info['FECHA_FIN'])));
        }            
    }
    $lista_anio = array();
    $fin_anio = $inicio_anio + 10;
    while ($inicio_anio < $fin_anio) {
        $lista_anio[] = $inicio_anio;
        $inicio_anio++;
    }
    $tpl->assign('list_year', $lista_anio);
    
    if (isset($_SESSION['sepe_message_info'])) {
        $tpl->assign('message_info', $_SESSION['sepe_message_info']);    
        unset($_SESSION['sepe_message_info']);
    }
    if (isset($_SESSION['sepe_message_error'])) {
        $tpl->assign('message_error', $_SESSION['sepe_message_error']);    
        unset($_SESSION['sepe_message_error']);
    }
        
    $listing_tpl = 'sepe/view/editar_especialidad_tutorials.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();

} else {
    header("location: http://".$_SERVER['SERVER_NAME']);
}
