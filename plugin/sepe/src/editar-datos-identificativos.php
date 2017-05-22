<?php

use \ChamiloSession as Session;

require_once '../config.php';

$course_plugin = 'sepe';
$plugin = SepePlugin::create();
$_cid = 0;

if ( !empty($_POST))
{
    $origen_centro = Database::escape_string($_POST['origen_centro']);
    $codigo_centro = Database::escape_string($_POST['codigo_centro']);
    $nombre_centro = Database::escape_string($_POST['nombre_centro']);
    $url = Database::escape_string($_POST['url']);
    $url_seguimiento = Database::escape_string($_POST['url_seguimiento']);
    $telefono = Database::escape_string($_POST['telefono']);
    $email = Database::escape_string($_POST['email']);
    $cod = Database::escape_string($_POST['cod']);
    
    if (existeDatosIdentificativos()) {
        $sql = "UPDATE plugin_sepe_center 
                SET    origen_centro='".$origen_centro."', 
                    codigo_centro='".$codigo_centro."', 
                    nombre_centro='".$nombre_centro."', 
                    url='".$url."', 
                    url_seguimiento='".$url_seguimiento."', 
                    telefono='".$telefono."', 
                    email='".$email."' 
                WHERE cod='".$cod."'";    
    } else {
        $sql = "INSERT INTO plugin_sepe_center 
                (cod, origen_centro, codigo_centro, nombre_centro, url , url_seguimiento, telefono, email) 
                VALUES 
                ('1','".$origen_centro."','".$codigo_centro."','".$nombre_centro."','".$url."','".$url_seguimiento."','".$telefono."','".$email."');";
    }
    $res = Database::query($sql);
    if (!$res) {
        $_SESSION['sepe_message_error'] = "No se ha guardado los cambios";
    } else {
        $_SESSION['sepe_message_info'] = "Se ha guardado los cambios";
    }
    header("Location: datos-identificativos.php");
}


if (api_is_platform_admin()) {
     $interbreadcrumb[] = array("url" => "/plugin/sepe/src/menu_sepe_administracion.php", "name" => $plugin->get_lang('menu_sepe'));
     $interbreadcrumb[] = array("url" => "datos-identificativos.php", "name" => $plugin->get_lang('datos_centro'));
    
    
    $info = datos_identificativos();
    $templateName = $plugin->get_lang('editar_datos_centro');
    $tpl = new Template($templateName);
    
    $tpl->assign('info', $info);
    
    $listing_tpl = 'sepe/view/editar_datos_identificativos.tpl';
    $content = $tpl->fetch($listing_tpl);
    $tpl->assign('content', $content);
    $tpl->display_one_col_template();

} else {
    header("location: http://".$_SERVER['SERVER_NAME']);
}

