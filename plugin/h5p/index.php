<?php

/* For license terms, see /license.txt */

$parsedUrl = parse_url($_SERVER['REQUEST_URI']);
$parsedUrlpathCtr = $parsedUrl['path'];
$posCtr = strrpos($parsedUrlpathCtr, "lp_controller.php");
$fh = '';

if ($posCtr === false) {
    echo '';
} else {
    $ctrAction = isset($_GET['action']) ? (string) $_GET['action'] : '';

    $version = '?v=09';
    $loginAccepted = isset($_SESSION['h5p']) ? $_SESSION['h5p_accepted'] : null;

    $pluginPath = api_get_path(WEB_PLUGIN_PATH);
    $webPath = api_get_path(WEB_PATH);
    if ($ctrAction == 'edit_item' || $ctrAction == 'add_item') {
        $fh .= '<script src="'.$pluginPath.'h5p/resources/js/h5p_extras.js'.$version.'" type="text/javascript" ></script>';
        $fh .= '<script>if (!jQuery.ui) {'."$('body').append('<script src=\"".$webPath."web/assets/jquery-ui/jquery-ui.min.js\"></script>');}</script>";
        $fh .= '<link rel="stylesheet" type="text/css" href="'.$webPath.'web/assets/jquery-ui/themes/smoothness/jquery-ui.min.css">';
        $fh .= '<link rel="stylesheet" type="text/css" href="'.$pluginPath.'h5p/resources/css/window-h5p.css">';
    }
}

echo $fh;
