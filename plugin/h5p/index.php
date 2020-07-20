<?php
/* For license terms, see /license.txt */

if(file_exists(__DIR__."/chamilo_boost_h5p.php")){
	require_once(__DIR__."/chamilo_boost_h5p.php");
	$plugin_info = chamilo_boost_h5p::create()->get_info();
}

$parsedUrl = parse_url($_SERVER['REQUEST_URI']);
$parsedUrlpathCtr = $parsedUrl['path'];
$posCtr = strrpos($parsedUrlpathCtr,"lp_controller.php");
$fh = '';

if($posCtr===false){

	echo '';

}else{

	$ctrAction = isset($_GET['action']) ? (string) $_GET['action']:'';

	$version = '?v=09';
    $loginAccepted = isset($_SESSION['h5p']) ? $_SESSION['h5p_accepted'] : null;

	$pwp = api_get_path(WEB_PLUGIN_PATH);
	if($ctrAction=='edit_item'||$ctrAction=='add_item'){
		$fh .= '<script src="'.$pwp.'h5p/resources/js/h5p_extras.js'.$version.'" type="text/javascript" ></script>';
        $fh .= '<script type="text/javascript" src="'.api_get_path(WEB_PATH).'web/assets/jquery-ui/jquery-ui.min.js"></script>';
		$fh .= '<link rel="stylesheet" type="text/css" href="'.api_get_path(WEB_PATH).'web/assets/jquery-ui/themes/smoothness/jquery-ui.min.css">';
        $fh .= '<link rel="stylesheet" type="text/css" href="'.$pwp.'h5p/resources/css/window-h5p.css">';

    }



}

echo $fh;
