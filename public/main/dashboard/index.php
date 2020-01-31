<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Template (front controller in MVC pattern) used for distpaching to
 * the controllers depend on the current action.
 *
 * @author Christian Fasanando <christian1827@gmail.com>
 */
$cidReset = true;

// including files
require_once __DIR__.'/../inc/global.inc.php';
require_once 'dashboard_controller.php';
require_once 'block.class.php';

// protect script
api_block_anonymous_users();

// current section
$this_section = SECTION_DASHBOARD;
Session::erase('this_section'); //for hmtl editor repository

// get actions
$actions = ['listing', 'store_user_block', 'disable_block'];
$action = 'listing';
if (isset($_GET['action']) && in_array($_GET['action'], $actions)) {
    $action = $_GET['action'];
}

// load styles from dashboard plugins
$htmlHeadXtra[] = DashboardManager::getStyleSheet();

// course description controller object
$dashboardController = new DashboardController();

if (isset($_GET['path'])) {
    $path = $_GET['path'];
}

// distpacher actions to controller
switch ($action) {
    case 'listing':
        $dashboardController->display();
        break;
    case 'store_user_block':
        $dashboardController->store_user_block();
        break;
    case 'disable_block':
        $dashboardController->close_user_block($path);
        break;
    default:
        $dashboardController->display();
}
