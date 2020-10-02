<?php

/* For licensing terms, see /license.txt */

/*
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);
*/
require_once __DIR__.'/../../main/inc/global.inc.php';
require_once 'inc/langfrench.php';
require_once 'inc/functions.php';
require_once 'inc/boost-form.php';
require_once 'boostTitle.php';
require_once 'chamilo_boost.php';

api_protect_admin_script();

require_once 'update.php';

$plugin = boostTitle::create();

$aid = api_get_current_access_url_id();

$interface = api_get_plugin_setting_access_urlB('chamilo_boost','dossierinterface',$aid);
$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']) : '';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$table = 'boostTitle';

$urlId = api_get_current_access_url_id();
$UrlWhere = " url_id = $urlId ";

$sql = "SELECT * FROM $table WHERE $UrlWhere ORDER BY indexTitle";

if($action=='edit'||$action=='add'){
	$sql = "SELECT * FROM $table WHERE id = $id AND $UrlWhere";
}

$result = Database::query($sql);
$terms = Database::store_result($result,'ASSOC');

$term = null;

if(!empty($id)){
	$sql = "SELECT * FROM $table WHERE id = $id AND $UrlWhere";
	$result = Database::query($sql);
	$term = Database::fetch_array($result, 'ASSOC');
	if (empty($term)) {
		api_not_allowed(true);
	}
}

if($action=='edit'||$action=='add'){
	$form = getFormTitle($action,$id,$interface,$plugin);
}

$htmlHeadXtra[] = '<link href="resources/css/overview.css" rel="stylesheet" type="text/css">';
$htmlHeadXtra[] = '<link href="resources/css/coreboost.css" rel="stylesheet" type="text/css">';

$htmlHeadXtra[] = '<script src="resources/js/edit-title.js" type="text/javascript" ></script>';
$htmlHeadXtra[] = '<script src="resources/js/jquery.dataTables.min.js" type="text/javascript" ></script>';

$htmlHeadXtra[] = "<script type='text/javascript' >
var p_boost_url = '".$aid."';
$(document).ready(function(){
	$('.data_table').DataTable({'pagingType': 'full_numbers'});
});</script>";

switch($action){
	case 'add':
		if ($form->validate()){

			$values = $form->getSubmitValues();

			if($values['typeCard']=='cards'){
			$values['acces'] = 'oc';}
			if($values['typeCard']=='stats'){
			$values['acces'] = 'oc';}
			if($values['typeCard']=='statstable'){
			$values['acces'] = 'oc';}
			
			$params = [
				'title' => $values['title'],
				'indexTitle' => $values['indexTitle'],
				'subTitle' => $values['subTitle'],
				'imagePic' => $values['imagePic'],
				'imageUrl' => $values['imageUrl'],
				'idContent' => $values['idContent'],
				'typeCard' => $values['typeCard'],
				'leftContent' => $values['leftContent'],
				'rightContent' => $values['rightContent'],
				'acces' => $values['acces'],
				'url_id' => $aid
			];
			
			saveHtmlContents($id,$values['leftContent'],$values['rightContent'],$interface);

			$result = Database::insert($table, $params);
			if ($result) {
				Display::addFlash(Display::return_message(get_lang('Added')));
			}
			
			// Upload picture if a new one is provided
			if ($_FILES['picture']['size']){
				// $_FILES['picture']['name'];
				// $_FILES['picture']['tmp_name'];
				if(!empty($_FILES)){
					$ds = DIRECTORY_SEPARATOR;  //1 
					$tempFile = $_FILES['picture']['tmp_name']; //3                 
					$targetPath = dirname( __FILE__ ).$ds.'pictures'.$ds; //4
					$targetFile =  $targetPath. $_FILES['picture']['name']; //5
					echo $targetFile;
					move_uploaded_file($tempFile,$targetFile); //6 
				}	
			}
			header('Location: '.api_get_self());
			exit;
		}
		break;
	case 'edit':
		$form->setDefaults($term);
		if ($form->validate()){
			$values = $form->getSubmitValues();
			$params = [
				'title' => $values['title'],
				'indexTitle' => $values['indexTitle'],
				'subTitle' => $values['subTitle'],
				'imagePic' => $values['imagePic'],
				'imageUrl' => $values['imageUrl'],
				'idContent' => $values['idContent'],
				'typeCard' => $values['typeCard'],
				'leftContent' => $values['leftContent'],
				'rightContent' => $values['rightContent'],
				'acces' => $values['acces']
			];
			
			saveHtmlContents($id,$values['leftContent'],$values['rightContent'],$interface);

			Database::update($table, $params, ['id = ?' => $id]);
			Display::addFlash(Display::return_message(get_lang('Updated')));
		
			header('Location: '.api_get_self());
			exit;
		}
		break;
	case 'delete':
		if (!empty($term)) {
			Database::delete($table, ['id = ?' => $id]);
			Display::addFlash(Display::return_message(get_lang('Deleted')));
			header('Location: '.api_get_self());
			exit;
		}
		break;
}

$tpl = new Template('');

if($action!='edit'&&$action!='add'){
	$tpl->assign('terms', $terms);
	$tpl->assign('form','');
}else{
	$tpl->assign('terms','');
	$tpl->assign('form', $form->returnForm());
}

$content = $tpl->fetch('chamilo_boost/view/boost-v09.tpl');
// Assign into content
$tpl->assign('content', $content);
// Display
$tpl->display_one_col_template();

function saveHtmlContents($idRef,$leftCtn,$rightCtn,$inter){
	
	$hoc = '<a href="#0" onClick="closeLoginBox();" class="cd-close" ></a>';
	$hoc .= '<div class="thecardviewzone" >'.$leftCtn.'</div>';
	$hoc .= '<div class="thecardviewzone2" >'.$rightCtn.'</div>';
	
	$filename = 'resources/templates/'.$inter.'/contents-extra/extras'.$idRef.'.html';
	$fd = fopen($filename,'w');	
	fwrite($fd,$hoc);
	fclose($fd);
	
}

function indexOf($mystring,$search){
	$pos = strrpos($mystring,$search);
	if($pos=== false){
		return false;
	}else{
		return true;
	}
}







