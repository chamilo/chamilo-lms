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
    
    if (isset($new_specialty) && $new_specialty!="SI") {
        $sql = "UPDATE plugin_sepe_specialty SET 
        ORIGEN_ESPECIALIDAD='".$ORIGEN_ESPECIALIDAD."', 
        AREA_PROFESIONAL='".$AREA_PROFESIONAL."', 
        CODIGO_ESPECIALIDAD='".$CODIGO_ESPECIALIDAD."', 
        ORIGEN_CENTRO='".$ORIGEN_CENTRO."', 
        CODIGO_CENTRO='".$CODIGO_CENTRO."', 
        FECHA_INICIO='".$fecha_inicio."', 
        FECHA_FIN='".$fecha_fin."', 
        MODALIDAD_IMPARTICION='".$MODALIDAD_IMPARTICION."', 
        HORAS_PRESENCIAL='".$HORAS_PRESENCIAL."', 
        HORAS_TELEFORMACION='".$HORAS_TELEFORMACION."', 
        HM_NUM_PARTICIPANTES='".$HM_NUM_PARTICIPANTES."', 
        HM_NUMERO_ACCESOS='".$HM_NUMERO_ACCESOS."', 
        HM_DURACION_TOTAL='".$HM_DURACION_TOTAL."', 
        HT_NUM_PARTICIPANTES='".$HT_NUM_PARTICIPANTES."', 
        HT_NUMERO_ACCESOS='".$HT_NUMERO_ACCESOS."', 
        HT_DURACION_TOTAL='".$HT_DURACION_TOTAL."', 
        HN_NUM_PARTICIPANTES='".$HN_NUM_PARTICIPANTES."',
        HN_NUMERO_ACCESOS='".$HN_NUMERO_ACCESOS."',
        HN_DURACION_TOTAL='".$HN_DURACION_TOTAL."',
        NUM_PARTICIPANTES='".$NUM_PARTICIPANTES."', 
        NUMERO_ACTIVIDADES_APRENDIZAJE='".$NUMERO_ACTIVIDADES_APRENDIZAJE."', 
        NUMERO_INTENTOS='".$NUMERO_INTENTOS."', 
        NUMERO_ACTIVIDADES_EVALUACION='".$NUMERO_ACTIVIDADES_EVALUACION."' 
        WHERE cod='".$cod_specialty."';";    
    } else {
        $sql = "INSERT INTO plugin_sepe_specialty (cod_action,ORIGEN_ESPECIALIDAD,AREA_PROFESIONAL,CODIGO_ESPECIALIDAD,ORIGEN_CENTRO,CODIGO_CENTRO,FECHA_INICIO,FECHA_FIN,MODALIDAD_IMPARTICION,HORAS_PRESENCIAL,HORAS_TELEFORMACION,HM_NUM_PARTICIPANTES,HM_NUMERO_ACCESOS,HM_DURACION_TOTAL,HT_NUM_PARTICIPANTES,HT_NUMERO_ACCESOS,HT_DURACION_TOTAL,HN_NUM_PARTICIPANTES,HN_NUMERO_ACCESOS,HN_DURACION_TOTAL,NUM_PARTICIPANTES,NUMERO_ACTIVIDADES_APRENDIZAJE,NUMERO_INTENTOS,NUMERO_ACTIVIDADES_EVALUACION) VALUES ('".$cod_action."','".$ORIGEN_ESPECIALIDAD."','".$AREA_PROFESIONAL."','".$CODIGO_ESPECIALIDAD."','".$ORIGEN_CENTRO."','".$CODIGO_CENTRO."','".$fecha_inicio."','".$fecha_fin."','".$MODALIDAD_IMPARTICION."','".$HORAS_PRESENCIAL."','".$HORAS_TELEFORMACION."','".$HM_NUM_PARTICIPANTES."','".$HM_NUMERO_ACCESOS."','".$HM_DURACION_TOTAL."','".$HT_NUM_PARTICIPANTES."','".$HT_NUMERO_ACCESOS."','".$HT_DURACION_TOTAL."','".$HN_NUM_PARTICIPANTES."','".$HN_NUMERO_ACCESOS."','".$HN_DURACION_TOTAL."','".$NUM_PARTICIPANTES."','".$NUMERO_ACTIVIDADES_APRENDIZAJE."','".$NUMERO_INTENTOS."','".$NUMERO_ACTIVIDADES_EVALUACION."');";
    }
    
    $res = Database::query($sql);
    if (!$res) {
        echo Database::error();
        $_SESSION['sepe_message_error'] = "No se ha guardado los cambios";
    } else {
        $_SESSION['sepe_message_info'] = "Se ha guardado los cambios";
        if ($new_specialty=="SI") {
            $cod_specialty = Database::insert_id();
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
    if (isset($_GET['new_specialty']) && $_GET['new_specialty']=="SI") {
        $templateName = $plugin->get_lang('new_specialty_accion');
        $tpl = new Template($templateName);
        $tpl->assign('cod_action', $_GET['cod_action']);
        $info = array();
        $tpl->assign('info', $info);
        $tpl->assign('new_action', 'SI');
        $inicio_anio = $fin_anio = date("Y");
    } else {
        $templateName = $plugin->get_lang('edit_specialty_accion');
        $tpl = new Template($templateName);
        $tpl->assign('cod_action', $_GET['cod_action']);
        $info = especialidad_accion($_GET['cod_specialty']);
        $tpl->assign('info', $info);
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
        $tpl->assign('new_action', 'NO');
        $tpl->assign('cod_specialty', $_GET['cod_specialty']);
        
        $listClassroom = listClassroom($_GET['cod_specialty']);
        $tpl->assign('listClassroom', $listClassroom);
        $listTutors = listTutors($_GET['cod_specialty']);
        $tpl->assign('listTutors', $listTutors);        
    }
    
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
    $tpl->assign('list_year', $lista_anio);
    
    if (isset($_SESSION['sepe_message_info'])) {
        $tpl->assign('message_info', $_SESSION['sepe_message_info']);    
        unset($_SESSION['sepe_message_info']);
    }
    if (isset($_SESSION['sepe_message_error'])) {
        $tpl->assign('message_error', $_SESSION['sepe_message_error']);    
        unset($_SESSION['sepe_message_error']);
    }
    
    
    $listing_tpl = 'sepe/view/editar_especialidad_accion.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();

} else {
    header("location: http://".$_SERVER['SERVER_NAME']);
}
