<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once '../global.inc.php';

$action = $_GET['a'];

switch($action) {
    case 'load_online_user':
        $page = intval($_REQUEST['online_page_nr']);
        if (isset($_GET['cidReq']) && strlen($_GET['cidReq']) > 0) {
            $user_list = who_is_online_in_this_course(api_get_user_id(), api_get_setting('time_limit_whosonline'), $_GET['cidReq']);
        } else {
            $user_list = who_is_online(api_get_setting('time_limit_whosonline'));		
        }
        if ($page == 2) {
            $_SESSION['online_user_items'] = array();
        }
        $max_page =  round(count($user_list)/10);
        if (!in_array($page, $_SESSION['online_user_items']) && $page <= $max_page) {              
            $_SESSION['online_user_items'][] = $page;
            echo SocialManager::display_user_list($user_list);
        } else {
            echo 'end';            
        }
        break;
    default:
        break;
}