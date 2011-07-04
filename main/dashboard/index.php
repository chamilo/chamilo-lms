<?php
/* For licensing terms, see /license.txt */

/**
* Template (front controller in MVC pattern) used for distpaching to the controllers depend on the current action  
* @author Christian Fasanando <christian1827@gmail.com>
* @package chamilo.dashboard
*/

// name of the language file that needs to be included
$language_file = array ('index', 'tracking', 'userInfo', 'admin', 'gradebook');
$cidReset = true;

// including files 
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'dashboard.lib.php';
require_once api_get_path(LIBRARY_PATH).'app_view.php';
require_once 'dashboard_controller.php';
require_once 'block.class.php';

// protect script
api_block_anonymous_users();

// defining constants

// current section
$this_section = SECTION_DASHBOARD;
unset($_SESSION['this_section']);//for hmtl editor repository

// get actions
$actions = array('listing', 'store_user_block', 'disable_block');
$action = 'listing';
if (isset($_GET['action']) && in_array($_GET['action'],$actions)) {
	$action = $_GET['action'];
}

// load styles from dashboard plugins
$dashboar_plugin_styles = DashboardManager::get_links_for_styles_from_dashboard_plugins();
$htmlHeadXtra[] = $dashboar_plugin_styles;

// interbreadcrumb
//$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('Dashboard'));

// course description controller object
$dashboard_controller = new DashboardController();


if (isset($_GET['path'])) {
	$path = $_GET['path'];
}

// distpacher actions to controller
switch ($action) {	
	case 'listing':	
		$dashboard_controller->display();
		break;
	case 'store_user_block':	
		$dashboard_controller->store_user_block();
		break;	
	case 'disable_block':	
		$dashboard_controller->close_user_block($path);
		break;					
	default :	
		$dashboard_controller->display();
}