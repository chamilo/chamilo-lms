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
        
        $max_page = round(who_is_online_count()/10);
        
        if (!isset($_SESSION['online_user_items']) || (isset($_SESSION['online_user_items']) && !in_array($page, $_SESSION['online_user_items']) && $page <= $max_page)) {                          
            if (isset($_GET['cidReq']) && strlen($_GET['cidReq']) > 0) {
                $user_list = who_is_online_in_this_course($page_rows, $page_rows + 10, api_get_user_id(), api_get_setting('time_limit_whosonline'), $_GET['cidReq']);
            } else {
                $page_rows = $page*10;
                $user_list = who_is_online($page_rows, $page_rows + 10);		
            }        
            if ($page == 1) {            
                $_SESSION['online_user_items'] = array();
            }           
        
            $_SESSION['online_user_items'][] = $page;         
            if (!empty($user_list)) {
                echo SocialManager::display_user_list($user_list);
            }
        }
        break;
    default:
        break;
}