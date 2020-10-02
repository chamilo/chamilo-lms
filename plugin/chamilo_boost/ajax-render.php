<?php

	ini_set('display_errors',1);
	ini_set('display_startup_errors',1);
	error_reporting(E_ALL);

	require_once '../../main/inc/global.inc.php';
	require_once 'inc/functions.php';
	require_once 'boostTitle.php';

	$aid = api_get_current_access_url_id();
	$interface = api_get_plugin_setting_access_urlB('chamilo_boost','dossierinterface',$aid);
	
	$UrlWhere = " WHERE url_id = $aid ";

	$table = 'boostTitle';
	$sql = "SELECT * FROM $table $UrlWhere ORDER BY indexTitle";

	$idCardCard = -1;
	$idCatalogCard = -1;

	$i = 1;

	$resultMarqueurs = Database::query($sql);

	$countData = count($resultMarqueurs);

	$hnc = '<div class="thecardview" ></div>';
	$hoc = '<div class="thecardview" ></div>';

	while ($marqueur=Database::fetch_array($resultMarqueurs)){
		
		$idcard = $marqueur['id'];
		$title = $marqueur['title'];
		$subTitle = $marqueur['subTitle'];	
		$imagePic = $marqueur['imagePic'];
		$idContent = $marqueur['idContent'];
		$typeCard = $marqueur['typeCard'];
		$acces = $marqueur['acces'];
		
		if(strpos($typeCard, '.html')!=false){
			$idContent = $typeCard;
			$typeCard = 'content';
		}
		if($typeCard=='texthtml'){
			$idContent = "extras".$idcard.".html";
			$typeCard = 'contentextra';
		}

		$strHref = '#';
		if($typeCard=='link'){
			$strHref = $idContent;
		}

		$h = "<a href='$strHref' idcard='$idcard cardend' >";
		
		$h .= '<div class="thecard" data="'.$idContent.'" type="'.$typeCard.'" >';
		$h .= '<div class="card-img" >';
		$h .= '<div class="back-img" style="background-image: url({urlplug}'.$imagePic.');" ></div>';
		$h .= '</div>';
		
		$h .= '<div class="card-caption" >';
		$h .= '<i id="like-btn" class="fa fa-bars"></i>';
		$h .= '<h2>'.$title.'</h2>';
		$h .= '</div>';
		$h .= '<div class="card-outmore">';
		$h .= '<h5 class="autoTradTerm" >'.$subTitle.'</h5>';
		$h .= '<i id="outmore-icon" class="fa fa-angle-right"></i>';
		$h .= '</div></div>';
		
		$h .= '</a>';
		
		if($typeCard=='cards'){
			$idCardCard = $idcard;
			$h = '<div>###CODE###</div>';
		}
		
		if($typeCard=='catalog'){
			$idCatalogCard = $idcard;
			$h = '<div>###CATALOG###</div>';
		}

		//only no connection
		if($acces=='onc'){
			$hnc = $hnc.$h;
		}
		//only connection
		if($acces=='oc'){
			$hoc = $hoc.$h;
		}
		//connection and no connection
		if($acces=='both'){
			$hnc = $hnc.$h;
			$hoc = $hoc.$h;
		}
	}

	$urlId = api_get_current_access_url_id();
	if($urlId==1){
		$urlId = '';
	}

	$filename = 'resources/templates/'.$interface.'/freeHome'.$urlId.'.html';
	$fd = fopen($filename,'w');	
	fwrite($fd,$hnc);
	fclose($fd);

	$filename = 'resources/templates/'.$interface.'/onLiveHome'.$urlId.'.html';
	$fd = fopen($filename,'w');	
	fwrite($fd,$hoc);
	fclose($fd);


	$hnc = '<link href="resources/css/cardsoverview.css" rel="stylesheet" type="text/css">'.$hnc;
	$hnc = '<link href="../../web/assets/fontawesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">'.$hnc;
	$hnc = '
	<script>
	function showEditTitle(i){
		window.top.location.href = "../edit-title.php?action=edit&id=" + i; 
	}
	</script>
	<style>body{background:#D8F6CE;}</style>'.$hnc;

	$hoc = '<link href="resources/css/cardsoverview.css" rel="stylesheet" type="text/css">'.$hoc;
	$hoc = '<link href="../../web/assets/fontawesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">'.$hoc;
	$hoc = '
	<script>
	function showEditTitle(i){
		window.top.location.href = "../edit-title.php?action=edit&id=" + i; 
	}
	</script>
	<style>body{background:#D8CEF6;}</style>'.$hoc;

	$aid = api_get_current_access_url_id();
	$tpl = api_get_plugin_setting_access_urlB('chamilo_boost', 'dossierinterface',$aid);

	$urlplug = "resources/templates/".$tpl."/img/";

	//OFFLINE MODE
	$hnc = str_replace("idcard='","onclick='showEditTitle(", $hnc);
	$hnc = str_replace(" cardend",");", $hnc);
	$hnc = str_replace('<div>###CODE###</div>',getCardZone($idCardCard), $hnc);
	$hnc = str_replace('<div>###CATALOG###</div>',getCardCatalog($idCatalogCard),$hnc);
	$hnc = str_replace('{urlplug}',$urlplug, $hnc);
	$hnc = str_replace("href='#'"," style='cursor:pointer;' ", $hnc);

	$hnc = str_replace('href="resources/','href="../resources/', $hnc);
	$hnc = str_replace('href="../../web/','href="../../../web/', $hnc);
	$hnc = str_replace('url(resources/','url(../resources/', $hnc);


	$filename = 'params/speed'.$urlId.'view.html';

	$fd = fopen($filename,'w');	
	fwrite($fd,$hnc);
	fclose($fd);

	//ONLINE MODE

	$hoc = str_replace("idcard='","onclick='showEditTitle(", $hoc);
	$hoc = str_replace(" cardend",");", $hoc);
	$hoc = str_replace('{urlplug}',$urlplug, $hoc);
	$hoc = str_replace('<div>###CODE###</div>',getCardZone($idCardCard), $hoc);
	$hoc = str_replace('<div>###CATALOG###</div>',getCardCatalog($idCatalogCard),$hoc);
	$hoc = str_replace("href='#'"," style='cursor:pointer;' ", $hoc);

	$hoc = str_replace('href="resources/','href="../resources/', $hoc);
	$hoc = str_replace('href="../../web/','href="../../../web/', $hoc);
	$hoc = str_replace('url(resources/','url(../resources/', $hoc);

	$filename = 'params/speed'.$urlId.'view2.html';

	$fd = fopen($filename,'w');	
	fwrite($fd,$hoc);
	fclose($fd);

	function getCardZone($idc){

		$h = "<a onclick='showEditTitle(".$idc.");' >";
		$h .= '<div class="thecard" data="" type="" >';
		$h .= '<div class="card-img" >';
		$h .= '<div class="back-img" style="background-image: url(resources/img/cards.jpg);" ></div>';
		$h .= '</div>';
		$h .= '<div class="card-caption" >';
		$h .= '<i id="like-btn" class="fa fa-bars"></i>';
		$h .= '<h2>Course Space</h2>';
		$h .= '</div>';
		$h .= '<div class="card-outmore">';
		$h .= '<h5 class="autoTradTerm" >Course Space</h5>';
		$h .= '<i id="outmore-icon" class="fa fa-angle-right"></i>';
		$h .= '</div></div>';
		$h .= '</a>';

		return $h;

	}

	function getCardCatalog($idc){

		$h = "<a onclick='showEditTitle(".$idc.");' >";
		$h .= '<div class="thecard" data="" type="" >';
		$h .= '<div class="card-img" >';
		$h .= '<div class="back-img" style="background-image: url(resources/img/catalog.jpg);" ></div>';
		$h .= '</div>';
		$h .= '<div class="card-caption" >';
		$h .= '<i id="like-btn" class="fa fa-bars"></i>';
		$h .= '<h2>Catalog</h2>';
		$h .= '</div>';
		$h .= '<div class="card-outmore">';
		$h .= '<h5 class="autoTradTerm" >Catalog</h5>';
		$h .= '<i id="outmore-icon" class="fa fa-angle-right"></i>';
		$h .= '</div></div>';
		$h .= '</a>';

		return $h;

	}

