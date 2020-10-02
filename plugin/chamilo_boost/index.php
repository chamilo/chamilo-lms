<?php

	/** @package chamilo.plugin.chamilo_boost */
	
	ini_set('display_errors',1);
	ini_set('display_startup_errors',1);
	error_reporting(E_ALL);
	
	require_once 'inc/functions.php';
	require_once 'inc/user-content.php';
	require_once 'inc/catalog-content.php';
	
	$version = '?v=1-11-12-18';
	$interface = 'localhost';
	
	$parsedUrl = parse_url($_SERVER['REQUEST_URI']);
	
	$userStatus = getUserStatusStringB();
	
	$userId = getUserIdBoost();
	$aid = api_get_current_access_url_id();
	$urlIdFinal = $aid;
	if($urlIdFinal==1){$urlIdFinal = '';}

	$optionTitle = api_get_plugin_setting_access_urlB('chamilo_boost','optionTitle',$aid);
	$interface = api_get_plugin_setting_access_urlB('chamilo_boost','dossierinterface',$aid);
	$urlSite = api_get_plugin_setting_access_urlB('chamilo_boost','urlinterface',$aid);
	
if($interface==''||$urlSite==''){

}else{
	
	$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_PUBLIC_PATH).'assets/jquery.easy-pie-chart/dist/jquery.easypiechart.js"></script>';
	
	$parsedUrlpath = $parsedUrl['path'];

	$haveSurCouche = controlHaveSurCouche($parsedUrlpath);

	$pwp = api_get_path(WEB_PLUGIN_PATH).'chamilo_boost/resources/';
	$pwb = api_get_path(WEB_PLUGIN_PATH).'chamilo_boost/cham-boost/';
	
	$fh = '';
	
	$pathContent = __DIR__.'/resources/templates/'.$interface.'/freeHome'.$urlIdFinal.'.html';
	$pathMenu = __DIR__.'/resources/templates/'.$interface.'/menu'.$urlIdFinal.'.html';
	
	include("inc/boost-params.php");

	//delete topmenu
	$fh .= '<style>';
    $fh .= '#header-logo{display:none}';
	$fh .= '#cm-header .container .row{display:none}';
	$fh .= '#toolbar-admin{display:none}';
	$fh .= '.hot-courses{display:none}';
	$fh .= '.items-hotcourse{display:none}';
	$fh .= '#carousel-announcement{display:none}';
	$fh .= '</style>';
	//Extra css for lateral menu
	if($BlateralMenu==1){
		$fh .= '<link href="'.$pwp.'css/menuLeftBoost.css'.$version.'"  rel="stylesheet" type="text/css">';
	}

	$fh .= '<script type="text/javascript" >';
	$fh .= "$(document).ready(function($){";
	$fh .= "_p['web_plugin'] = '".api_get_path(WEB_PLUGIN_PATH)."' ;";
	$fh .= "_p['boost_logo']= '".$BlogoTop."';";
	$fh .= "_p['boost_url']= '".$aid."';";
	$fh .= "});";
	$fh .= '</script>';
	
	$fh .= get_link_for_overlay_b($pwp,$version);
	
	$fh .= '<link href="'.$pwp.'templates/'.$interface.'/extra.css'.$version.'" ';
	$fh .= ' rel="stylesheet" type="text/css">';
	
	if(api_is_anonymous()){
		$fh .= '<div id="no-login" ></div>';
	}else{
		$pathContent = __DIR__.'/resources/templates/'.$interface.'/onLiveHome'.$urlIdFinal.'.html';
		$fh .= '<div id="id-login" style="display:none;" >'.$userId.'</div>';
		$fh .= '<div id="status-login" style="display:none;" >'.$userStatus.'</div>';
	}
	
	if($haveSurCouche){

		if($Bextracode1!=''){
			$fh .= $Bextracode1;
		}
		if($Bextracode2!=''){
			$fh .= '<style>'.$Bextracode2.'</style>';
		}
		if($BbtnSuscribe==1){
			$fh .= '<div id="btnSuscribe" style="display:none;" >'.$BlabelSuscribe.'</div>';
		}
		if($BbtnBuy==1){
			$fh .= '<div id="btnBuy" style="display:none;" href="'.$BlinkBuy.'" >'.$BlabelBuy.'</div>';
		}
		
		$fh .= '<div id="stylecourses" style="display:none;" >'.$Bstylecourses.'</div>';

		$fh .= '<script type="text/javascript" >';
		$fh .= "\n";

		$fh .= "var contentHboost = '";

		//Title
		$fh .= '<div class=boost-logo-back ></div>';
		$fh .= '<div class=boost-logo-div >';
		$fh .= '<div class=boost-title-block ';
		$fh .= sanitize_output_tojsvar(' style="background-image:url('.$Blogo.');" >'.$Btitle.'</div>');
		$fh .= '</div>';
		
		//Search
		if($BactiveSearch==1){
			$fh .= '<div class="boost-search-wrapper">';
			$fh .= '<div class="input-holder">';
			$fh .= '<input onChange="searchLoadResult()" OnKeyUp="searchLoadResult()" ';
			$fh .= ' type="text" class="search-input" placeholder="'.get_lang('Search').' ...." />';
			$fh .= '<button class="search-icon" onclick="searchToggleBoost(this, event);"><span></span></button>';
			$fh .= '</div>';
			$fh .= '<span class="search-close" onclick="searchToggleBoost(this, event);"></span>';
			$fh .= '</div>';

			$fh .= '<div class="boost-search-result">';
			$fh .= '</div>';		
		}
		
		if($BactiveSkills==1){
			$fh .= '<div id="activeSkills" style="display:none;" ></div>';	
		}

		if(file_exists($pathContent)){
			$fh .= sanitize_output_tojsvar(file_get_contents($pathContent));
		}

		$fh .= "';";
		
		$fh .= "\n";
		$fh .= "var menuHboost = '";

		if(file_exists($pathMenu)){
			$fh .= sanitize_output_tojsvar(file_get_contents($pathMenu));
		}
		$fh .= "';";
		$fh .= "\n";
		
		$fh .= "var userHboost = ";
		if(api_is_anonymous()){
			$fh .= "{}";
		}else{
			$fh .= getUserProg2Json($userId,$interface);
		}
		$fh .= ";\n";
		
		$fh .= "var catalogHboost = ";
		$fh .= getCatalogProg2Json($interface);
		$fh .= ";\n";
		
		$fh .= '</script>';

		$fh .= '<div style="display:none;" id="tradStats" >'.get_lang('Stats').'</div>';
		$fh .= '<div style="display:none;" id="tradMyInbox" >'.get_lang('MyInbox').'</div>';
	}
	
	//lateralMenu
	if($BlateralMenu==1){

		include('inc/boost-menu.php');

		$fh .= '<script type="text/javascript" >';
		$fh .= "\n";
		$fh .= "var menuLeftboost = '";
		if($mBody!=''){
			$fh .= sanitize_output_tojsvar($mBody);
		}else{
			$fh .= "<div>error-menu</div>";
		}
		$fh .= "';";
		$fh .= "$('body').append(menuLeftboost);";
		$fh .= "\n".'</script>';

		$fh .= '<style>';
		
		$fh .= ".nav-side-message-boost{color:$BColorText;background-color:$BColor1;}";
		$fh .= ".nav-side-menu-boost{color:$BColorText;background-color:$BColor1;}";
		$fh .= ".nav-side-menu-boost .brand{background-color:$BColor1;}";
		$fh .= ".nav-side-menu-boost .brand-login{background-color:$BColor2!important;border-bottom:solid 5px $BColor1!important;}";
		$fh .= ".nav-side-menu-boost li{background-color:$BColor2;border-color:$BColor1!important;border-left:3px solid $BColor2!important;color:$BColorText!important;}";
		$fh .= ".nav-side-menu-boost ul .sub-menu li, .nav-side-menu-boost li .sub-menu li{background-color:$BColor1!important;}";
		$fh .= ".nav-side-menu-boost li a{color:$BColorText!important;}";

		if($BtopnavigationColor==1){
			$fh .= ".navbar-default{background-color:$BColor2!important;}";
			$fh .= ".navbar-default{border-color:$BColor2!important;}";
			$fh .= ".navbar-default .navbar-nav > li > a{color:$BColorText!important;}";
			$fh .= ".navbar-default .navbar-nav > .active > a{background-color:$BColor1!important;}";
		}

		$fh .= '</style>';
		

	}
	//lateralMenu

	//Btopnavigationoff
	if($Btopnavigationoff==1){
		$fh .= '<script type="text/javascript" >'."\n";
		$fh .= "$('#navbar ul.nav.navbar-nav').css('display','none');";
		$fh .= "$('#navbar ul.nav.navbar-right').css('display','');";
		$fh .= "\n".'</script>';
	}

	$fh .= '<div style="display:none;" id="plugfullpath" >'.$pwp.'</div>';
	$fh .= '<div style="display:none;" id="folder-tpl" >'.$interface.'</div>';
	
	$urlSite = str_replace(".","apprpointend",$urlSite);
	
	$fh .= '<div style="display:none;" id="plugUrlSite" >'.$urlSite.'</div>';
	
	echo $fh;

}


