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
    
    $fecha_alta = $year_alta."-".$month_alta."-".$day_alta;
    $fecha_baja = $year_baja."-".$month_baja."-".$day_baja;
    $fecha_inicio = $year_start."-".$month_start."-".$day_start;
    $fecha_fin = $year_end."-".$month_end."-".$day_end;
    
    if (isset($new_specialty) && $new_specialty!="SI") {
        $sql = "UPDATE plugin_sepe_participants_specialty SET ORIGEN_ESPECIALIDAD='".$ORIGEN_ESPECIALIDAD."', AREA_PROFESIONAL='".$AREA_PROFESIONAL."', CODIGO_ESPECIALIDAD='".$CODIGO_ESPECIALIDAD."', FECHA_ALTA='".$fecha_alta."', FECHA_BAJA='".$fecha_baja."', ORIGEN_CENTRO='".$ORIGEN_CENTRO."', CODIGO_CENTRO='".$CODIGO_CENTRO."', FECHA_INICIO='".$fecha_inicio."', FECHA_FIN='".$fecha_fin."', RESULTADO_FINAL='".$RESULTADO_FINAL."', CALIFICACION_FINAL='".$CALIFICACION_FINAL."', PUNTUACION_FINAL='".$PUNTUACION_FINAL."' WHERE cod='".$cod_specialty."';";    
    } else {
        $sql = "INSERT INTO plugin_sepe_participants_specialty (cod_participant,ORIGEN_ESPECIALIDAD,AREA_PROFESIONAL,CODIGO_ESPECIALIDAD,FECHA_ALTA,FECHA_BAJA,ORIGEN_CENTRO,CODIGO_CENTRO,FECHA_INICIO,FECHA_FIN,RESULTADO_FINAL,CALIFICACION_FINAL,PUNTUACION_FINAL) VALUES ('".$cod_participant."','".$ORIGEN_ESPECIALIDAD."','".$AREA_PROFESIONAL."','".$CODIGO_ESPECIALIDAD."','".$fecha_alta."','".$fecha_baja."','".$ORIGEN_CENTRO."','".$CODIGO_CENTRO."','".$fecha_inicio."','".$fecha_fin."','".$RESULTADO_FINAL."','".$CALIFICACION_FINAL."','".$PUNTUACION_FINAL."');";
    }
    //echo $sql;
    //exit;
    
    $res = Database::query($sql);
    if (!$res) {
        echo Database::error();
        $_SESSION['sepe_message_error'] = "No se ha guardado los cambios";
    } else {
        $_SESSION['sepe_message_info'] = "Se ha guardado los cambios";
        if ($new_specialty=="SI") {
            $cod_specialty = Database::insert_id();
        }
        /*
        if ($RESULTADO_FINAL=="1" || $RESULTADO_FINAL=="2") {
            $sql = "INSERT INTO plugin_sepe_log_participant (cod_participant, cod_action, fecha_baja) VALUES ('".$cod_participant."','".$cod_action."','".date("Y-m-d")."');";
            $res = Database::query($sql);
        } else {
            $sql = "INSERT INTO plugin_sepe_log_mod_participant (cod_participant, cod_action) VALUES ('".$cod_participant."','".$cod_action."');";
            $res = Database::query($sql);
        }
        */
    }
    session_write_close();
    $id_course = obtener_course($cod_action);
    header("Location: editar-especialidad-participante.php?new_specialty=NO&cod_specialty=".$cod_specialty."&cod_participant=".$cod_participant."&cod_action=".$cod_action);
    
}



if (api_is_platform_admin()) {
    $id_course = obtener_course($_GET['cod_action']);
    $interbreadcrumb[] = array("url" => "/plugin/sepe/src/menu_sepe_administracion.php", "name" => $plugin->get_lang('menu_sepe'));
    $interbreadcrumb[] = array("url" => "listado-acciones-formativas.php", "name" => $plugin->get_lang('listado_acciones_formativas'));
    $interbreadcrumb[] = array("url" => "accion-formativa.php?cid=".$id_course, "name" => $plugin->get_lang('accion_formativa'));
    $interbreadcrumb[] = array("url" => "editar-participante-accion.php?new_participant=NO&cod_participant=".$_GET['cod_participant']."&cod_action=".$_GET['cod_action'], "name" => $plugin->get_lang('participante_accion_formativa'));
    if (isset($_GET['new_specialty']) && $_GET['new_specialty']=="SI") {
        $templateName = $plugin->get_lang('new_specialty_participant');
        $tpl = new Template($templateName);
        $tpl->assign('cod_action', $_GET['cod_action']);
        $tpl->assign('cod_participant', $_GET['cod_participant']);
        $info = array();
        $tpl->assign('info', $info);
        $tpl->assign('new_specialty', 'SI');
        $inicio_anio = $fin_anio = date("Y");
        $alta_anio = $baja_anio = date("Y");
    } else {
        $templateName = $plugin->get_lang('edit_specialty_participant');
        $tpl = new Template($templateName);
        $tpl->assign('cod_action', $_GET['cod_action']);
        $tpl->assign('cod_specialty', $_GET['cod_specialty']);
        $tpl->assign('cod_participant', $_GET['cod_participant']);
        $info = especialidad_participante($_GET['cod_specialty']);
        //error_log(print_r($info,true));
        $tpl->assign('info', $info);
        $tpl->assign('new_specialty', 'NO');
        if ($info['FECHA_ALTA']!='0000-00-00' && $info['FECHA_ALTA']!=NULL) {
            $tpl->assign('day_alta', date("j",strtotime($info['FECHA_ALTA'])));
            $tpl->assign('month_alta', date("n",strtotime($info['FECHA_ALTA'])));
            $tpl->assign('year_alta', date("Y",strtotime($info['FECHA_ALTA'])));
            $alta_anio = date("Y",strtotime($info['FECHA_ALTA']));
        } else {
            $alta_anio = date("Y");
        }
        if ($info['FECHA_BAJA']!='0000-00-00' && $info['FECHA_BAJA']!=NULL) {
            $tpl->assign('day_baja', date("j",strtotime($info['FECHA_BAJA'])));
            $tpl->assign('month_baja', date("n",strtotime($info['FECHA_BAJA'])));
            $tpl->assign('year_baja', date("Y",strtotime($info['FECHA_BAJA'])));
            $baja_anio = date("Y",strtotime($info['FECHA_BAJA']));
        } else {
            $baja_anio = date("Y");
        }
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
            $fin_anio = date("Y",strtotime($info['FECHA_FIN']));
        } else {
            $fin_anio = date("Y");
        }
        $listSpecialtyTutorials = listSpecialtyTutorial($_GET['cod_specialty']);
        $tpl->assign('listSpecialtyTutorials', $listSpecialtyTutorials);    
    }
    
    
    $lista_anio = array();
    if ($alta_anio > $baja_anio) {
        $tmp = $alta_anio;
        $alta_anio = $baja_anio;
        $baja_anio = $tmp;    
    }
    $alta_anio -= 5;
    $baja_anio += 5;
    $fin_rango_anio = (($alta_anio + 15) < $baja_anio) ? ($baja_anio+1):($alta_anio + 15);
    while ($alta_anio <= $fin_rango_anio) {
        $lista_anio[] = $alta_anio;
        $alta_anio++;
    }
    $tpl->assign('list_year', $lista_anio);
    
    $lista_anio = array();
    if ($inicio_anio > $fin_anio) {
        $tmp = $inicio_anio;
        $inicio_anio = $fin_anio;
        $fin_anio = $tmp;    
    }
    $inicio_anio -= 5;
    $fin_anio += 5;
    $fin_rango_anio = (($inicio_anio + 15) < $fin_anio) ? ($fin_anio+1):($inicio_anio +15);
    while ($inicio_anio <= $fin_rango_anio) {
        $lista_anio[] = $inicio_anio;
        $inicio_anio++;
    }
    $tpl->assign('list_year_2', $lista_anio);
    
    if (isset($_SESSION['sepe_message_info'])) {
        $tpl->assign('message_info', $_SESSION['sepe_message_info']);    
        unset($_SESSION['sepe_message_info']);
    }
    if (isset($_SESSION['sepe_message_error'])) {
        $tpl->assign('message_error', $_SESSION['sepe_message_error']);    
        unset($_SESSION['sepe_message_error']);
    }
        
    $listing_tpl = 'sepe/view/editar_especialidad_participante.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();

} else {
    header("location: http://".$_SERVER['SERVER_NAME']);
}
