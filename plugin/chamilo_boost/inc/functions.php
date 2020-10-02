<?php

function getUserStatusStringB(){

	$returnStatus = '';

	if(!api_is_anonymous()){

		$user = api_get_user_info();

		if(isset($user['status'])){

			if($user['status']==SESSIONADMIN||$user['status']==COURSEMANAGER||$user['status']==PLATFORM_ADMIN){
				$returnStatus = 'ADMIN';
			}

		}

	}

	return $returnStatus;

}

function getUserIdBoost(){

	if(!api_is_anonymous()){

		$user = api_get_user_info();

		if(isset($user['id'])){

			return $user['id'];

		}

	}

	return -1;

}

function api_get_plugin_setting_access_urlB($plugin, $variable,$accessUrl){
	
    $variableName = $plugin.'_'.$variable.$accessUrl;

    $params = [
        'category = ? AND subkey = ? AND variable = ?' => [
            'Plugins',
            $plugin,
            $variableName,
        ],
    ];

    $table = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);

    $result = Database::select(
        'selected_value',
        $table,
        array('where' => $params),
        'one'
    );

    if ($result) {
        $result = $result['selected_value'];
        return $result;
    }

    return null;

}

function sanitize_output_tojsvar($buffer) {
	
	$search = array(
		'/\>[^\S ]+/s',     // strip whitespaces after tags, except space
		'/[^\S ]+\</s',     // strip whitespaces before tags, except space
		'/(\s)+/s'         // shorten multiple whitespace sequences
	);

	$replace = array(
		'>',
		'<',
		'\\1'
	);

	$buffer = preg_replace($search, $replace, $buffer);
	$buffer = str_replace("'", "\\'", $buffer);
	return $buffer;
	
}

function get_link_for_overlay_b($pwp,$version){
	
	$fh = '<script type="text/javascript" src="'.$pwp.'js/coreboost.js'.$version.'" ></script>';
	$fh .= '<link href="'.$pwp.'css/coreboost.css'.$version.'" rel="stylesheet" type="text/css">';
	
	$fh .= '<script type="text/javascript" src="'.$pwp.'vendor/jquery.circliful.js'.$version.'" ></script>';
	$fh .= '<link href="'.$pwp.'vendor/jquery.circliful.css'.$version.'" rel="stylesheet" type="text/css">';

	$fh .= '<script type="text/javascript" src="'.$pwp.'vendor/amplify.min.js'.$version.'" ></script>';

	$fh .= '<script type="text/javascript" src="'.$pwp.'js/charts.js'.$version.'" ></script>';
	$fh .= '<script type="text/javascript" src="'.$pwp.'js/chartist.min.js'.$version.'" ></script>';
	
	return $fh;
	
}

function minify_js_code($javascript){
	return preg_replace(array("/\s+\n/", "/\n\s+/", "/ +/"), array("\n", "\n ", " "), $javascript);
}

function minimize_css($css){
	$css = preg_replace('/\/\*((?!\*\/).)*\*\//', '', $css); // negative look ahead
	$css = preg_replace('/\s{2,}/', ' ', $css);
	$css = preg_replace('/\s*([:;{}])\s*/', '$1', $css);
	$css = preg_replace('/;}/', '}', $css);
	return $css;
}

function controlHaveSurCouche($pathControl){
		
    $b = true;

    $posCtr = strrpos($pathControl,"admin");
    if($posCtr!=false){
        $b = false;
    }
    $posCtr = strrpos($pathControl,"plugin");
    if($posCtr!=false){
        $b = false;
	}
	$posCtr = strrpos($pathControl,"user_portal.php");
    if($posCtr!=false){
        $b = false;
	}
	$posCtr = strrpos($pathControl,"lp_controller.php");
    if($posCtr!=false){
        $b = false;
	}
	$posCtr = strrpos($pathControl,"mySpace");
    if($posCtr!=false){
        $b = false;
	}
	$posCtr = strrpos($pathControl,"courses/");
    if($posCtr!=false){
        $b = false;
	}
	$posCtr = strrpos($pathControl,"main/");
    if($posCtr!=false){
        $b = false;
	}
    return $b;

}

function getLangBoost($term,$objPlugin){
	
	$t = $term;

	$rt = $objPlugin->get_lang($t);
	
	if($rt==$term){
		$rt = get_lang($term);
	}
	
	$rt = rapidTradFr($rt);
	
    return $rt;

}

function getsubSessionCourseList($progress,$refSessionid){

	$listMenuS = '';

	foreach ($progress as &$row){
		
		if($row['code']!=''){
			$posCtr = strrpos($row['code'],"SESSION-");
			if($posCtr===false){
				$Folder = $row['directory'];
				$sessionid = $row['sessionid'];
				if(!isset($sessionid)){$sessionid = 0;}
				if($sessionid==''){$sessionid = 0;}
				if($sessionid==null){$sessionid = 0;}
				if($sessionid==$refSessionid){
					$valLink = api_get_path(WEB_PATH).'courses/'.$Folder.'/index.php?id_session='.$sessionid;
					$listMenuS .= '<li class="nav-side-course" ><a href="'.$valLink.' " >'.$row['title'].'</a></li>';  
				}
			}
		}

	}

	return $listMenuS;

}

?>