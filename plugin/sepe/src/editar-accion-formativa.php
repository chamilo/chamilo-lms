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
    
    if (isset($cod_action) && trim($cod_action)!='' && $cod_action!="NO") {
        $sql = "UPDATE plugin_sepe_actions SET ORIGEN_ACCION='".$ORIGEN_ACCION."', CODIGO_ACCION='".$CODIGO_ACCION."', SITUACION='".$SITUACION."', ORIGEN_ESPECIALIDAD='".$ORIGEN_ESPECIALIDAD."', AREA_PROFESIONAL='".$AREA_PROFESIONAL."', CODIGO_ESPECIALIDAD='".$CODIGO_ESPECIALIDAD."', DURACION='".$DURACION."', FECHA_INICIO='".$fecha_inicio."', FECHA_FIN='".$fecha_fin."', IND_ITINERARIO_COMPLETO='".$IND_ITINERARIO_COMPLETO."', TIPO_FINANCIACION='".$TIPO_FINANCIACION."', NUMERO_ASISTENTES='".$NUMERO_ASISTENTES."', DENOMINACION_ACCION='".$DENOMINACION_ACCION."', INFORMACION_GENERAL='".$INFORMACION_GENERAL."', HORARIOS='".$HORARIOS."', REQUISITOS='".$REQUISITOS."', CONTACTO_ACCION='".$CONTACTO_ACCION."' WHERE cod='".$cod_action."';";    
    } else {
        $sql = "INSERT INTO plugin_sepe_actions (ORIGEN_ACCION, CODIGO_ACCION, SITUACION, ORIGEN_ESPECIALIDAD,    AREA_PROFESIONAL, CODIGO_ESPECIALIDAD, DURACION, FECHA_INICIO, FECHA_FIN, IND_ITINERARIO_COMPLETO, TIPO_FINANCIACION, NUMERO_ASISTENTES, DENOMINACION_ACCION, INFORMACION_GENERAL, HORARIOS, REQUISITOS, CONTACTO_ACCION) VALUES ('".$ORIGEN_ACCION."','".$CODIGO_ACCION."','".$SITUACION."','".$ORIGEN_ESPECIALIDAD."','".$AREA_PROFESIONAL."','".$CODIGO_ESPECIALIDAD."','".$DURACION."','".$fecha_inicio."','".$fecha_fin."','".$IND_ITINERARIO_COMPLETO."','".$TIPO_FINANCIACION."','".$NUMERO_ASISTENTES."','".$DENOMINACION_ACCION."','".$INFORMACION_GENERAL."','".$HORARIOS."','".$REQUISITOS."','".$CONTACTO_ACCION."');";
    }
    $res = Database::query($sql);
    if (!$res) {
        echo Database::error();
        $_SESSION['sepe_message_error'] = "No se ha guardado los cambios";
    } else {
        $_SESSION['sepe_message_info'] = "Se ha guardado los cambios";
        if ($cod_action=="NO") {
            //Sincronizar acción formativa y curso
            $cod_action = Database::insert_id();
            $tableSepeCourse = "plugin_sepe_course_actions";
            $sql = "SELECT 1 FROM course WHERE id='".$id_course."';";
            $rs = Database::query($sql);
            if (Database::num_rows($rs) == 0) {
                $sepe_message_error .= "[editar-accion-formativa.php] - El curso al que se le asocia la accion formativa no existe";
                error_log($sepe_message_error);
            } else {
                $sql = "INSERT INTO $tableSepeCourse (id_course, cod_action) VALUES ('".$id_course."','".$cod_action."');";
                //echo $sql;    
                $rs = Database::query($sql);
                if (!$rs) {
                    $sepe_message_error .= "[editar-accion-formativa.php] - No se ha podido guardar la seleccion";
                    error_log($sepe_message_error);
                }
            }
        }
    }
    $id_course = obtener_course($cod_action);
    header("Location: accion-formativa.php?cid=".$id_course);
}

if (api_is_platform_admin()) {
    if (isset($_GET['new_action']) && $_GET['new_action']=="SI") {
        $info = array();
        $templateName = $plugin->get_lang('new_accion_formativa');
        $interbreadcrumb[] = array("url" => "/plugin/sepe/src/menu_sepe_administracion.php", "name" => $plugin->get_lang('menu_sepe'));
        $interbreadcrumb[] = array("url" => "listado-acciones-formativas.php", "name" => $plugin->get_lang('listado_acciones_formativas'));
        $tpl = new Template($templateName);
        $inicio_anio = $fin_anio = date("Y");
        $tpl->assign('info', $info);
        $tpl->assign('new_action', 'SI');
        $tpl->assign('id_course', $_GET['cid']);
    } else {
        $id_course = obtener_course($_GET['cod_action']);
        $interbreadcrumb[] = array("url" => "/plugin/sepe/src/menu_sepe_administracion.php", "name" => $plugin->get_lang('menu_sepe'));
        $interbreadcrumb[] = array("url" => "listado-acciones-formativas.php", "name" => $plugin->get_lang('listado_acciones_formativas'));
        $interbreadcrumb[] = array("url" => "accion-formativa.php?cid=".$id_course, "name" => $plugin->get_lang('accion_formativa'));
        $info = accion_formativa($_GET['cod_action']);
        $templateName = $plugin->get_lang('editar_accion_formativa');
        $tpl = new Template($templateName);
        $tpl->assign('info', $info);
        $tpl->assign('day_start', date("j",strtotime($info['FECHA_INICIO'])));
        $tpl->assign('month_start', date("n",strtotime($info['FECHA_INICIO'])));
        $tpl->assign('year_start', date("Y",strtotime($info['FECHA_INICIO'])));
        $tpl->assign('day_end', date("j",strtotime($info['FECHA_FIN'])));
        $tpl->assign('month_end', date("n",strtotime($info['FECHA_FIN'])));
        $tpl->assign('year_end', date("Y",strtotime($info['FECHA_FIN'])));
        $tpl->assign('new_action', 'NO');
        $inicio_anio = date("Y",strtotime($info['FECHA_INICIO']));
        $fin_anio = date("Y",strtotime($info['FECHA_FIN']));
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
    
    $listing_tpl = 'sepe/view/editar_accion_formativa.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();

} else {
    header("location: http://".$_SERVER['SERVER_NAME']);
}
