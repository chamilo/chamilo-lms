<?php //$id: $
/**
 * Script that displays an error message when no content could be loaded
 * @package dokeos.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Nothing very interesting
 */
$debug = 0;
if($debug>0){error_log('New lp - In lp_content.php',0);}
if(empty($lp_controller_touched)){
	if($debug>0){error_log('New lp - In lp_content.php - Redirecting to lp_controller',0);}
	header('location: lp_controller.php?action=content&lp_id='.Security::remove_XSS($_REQUEST['lp_id']).'&item_id='.Security::remove_XSS($_REQUEST['item_id']));
}
$_SESSION['oLP']->error = '';
$lp_type = $_SESSION['oLP']->get_type();
$lp_item_id = $_SESSION['oLP']->get_current_item_id();

/**
 * Get a link to the corresponding document
 */
$src = '';
if($debug>0){error_log('New lp - In lp_content.php - Looking for file url',0);}

$list = $_SESSION['oLP']->get_toc();

$dokeos_chapter = false;

foreach($list as $toc) {
	if ($toc['id']==$lp_item_id && ($toc['type']=='dokeos_chapter' || $toc['type']=='dokeos_module' || $toc['type']=='dir')) {
		$dokeos_chapter = true;
	}
}

if ($dokeos_chapter) {
	$src='blank.php';
} else {
	switch($lp_type){
		case 1:
			$_SESSION['oLP']->stop_previous_item();
			$prereq_check = $_SESSION['oLP']->prerequisites_match($lp_item_id);
			if($prereq_check === true){
				$src = $_SESSION['oLP']->get_link('http',$lp_item_id);
				$_SESSION['oLP']->start_current_item(); //starts time counter manually if asset
			}else{
				$src = 'blank.php?error=prerequisites';
			}
			break;
		case 2:
				$_SESSION['oLP']->stop_previous_item();
				$prereq_check = $_SESSION['oLP']->prerequisites_match($lp_item_id);
				if($prereq_check === true){
					$src = $_SESSION['oLP']->get_link('http',$lp_item_id);
					$_SESSION['oLP']->start_current_item(); //starts time counter manually if asset
				}else{
					$src = 'blank.php?error=prerequisites';
				}
				break;
		case 3:
			//save old if asset
			$_SESSION['oLP']->stop_previous_item(); //save status manually if asset
			$prereq_check = $_SESSION['oLP']->prerequisites_match($lp_item_id);
			if($prereq_check === true){
				$src = $_SESSION['oLP']->get_link('http',$lp_item_id);
				$_SESSION['oLP']->start_current_item(); //starts time counter manually if asset
			}else{
				$src = 'blank.php';
			}
			break;
		case 4:
			break;
	}
}
if($debug>0){error_log('New lp - In lp_content.php - File url is '.$src,0);}
$_SESSION['oLP']->set_previous_item($lp_item_id);

if (isset($_SESSION['gradebook'])){
	$gradebook=	$_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook=='view') {
	$interbreadcrumb[]= array (
			'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
			'name' => get_lang('Gradebook')
		);
}
// Define the 'doc.inc.php' as language file
$nameTools = $_SESSION['oLP']->get_name();
$interbreadcrumb[]= array ("url"=>"./lp_list.php", "name"=> get_lang('Doc'));
//update global setting to avoid displaying right menu
$save_setting = api_get_setting("show_navigation_menu");
global $_setting;
$_setting['show_navigation_menu'] = false;
if($debug>0){error_log('New LP - In lp_content.php - Loading '.$src,0);}
header("Location: ".urldecode($src));