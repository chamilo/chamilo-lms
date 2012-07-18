<?php
/* For licensing terms, see /license.txt */

/*
 This script is included by local.inc.php to redirect users to some url if some conditions are satisfied. 
 * Please populate the $dc_conditions array with a conditional function and an url. 
 * If the conditional function returns true the user will be redirected to URL at login.
 * This array must be filled for this module to work. 
 * This is an example asking the user to enter his phone number if it is empty. 
 * Note you can enter more than one condition in the array. They will be checked in the array order.
*/
$dc_conditions = array();

array_push($dc_conditions, array(
  'conditional_function'    => 'check_platform_legal_conditions',
  'url'                     => api_get_path(WEB_CODE_PATH).'auth/inscription.php'
));


//array_push($dc_conditions, array(
//  'conditional_function' => 'dc_check_phone_number',
//  'url' => api_get_path(WEB_PATH).'main/auth/conditional_login/complete_phone_number.php'
//));
//array_push($dc_conditions, array(
//  'conditional_function' => 'dc_check_first_login',
//  'url' => api_get_path(WEB_PATH).'main/auth/conditional_login/first_login.php'
//));
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
/*
  Please implements the functions of the $dc_conditions array. Each of these fucntion will take a user array (user_id, username, password (crypted), auth_sourcen, active, expiration_date)
 */
function dc_check_phone_number($user){
    $uInfo = UserManager::get_user_info_by_id($user['user_id']);
    if (empty($uInfo['phone'])) {
        return true;
    }
    return false;
}

function dc_check_first_login($user){
    $uInfo = UserManager::get_user_info_by_id($user['user_id']);
    return $uInfo['extra']['already_logged_in'] === 'false';
}

function check_platform_legal_conditions($user) {             
    if (api_get_setting('allow_terms_conditions') == 'true') {
        $term_and_condition_status = api_check_term_condition($user['user_id']);
        // @todo not sure why we need the login password and update_term_status
        if ($term_and_condition_status === false) {
            $_SESSION['term_and_condition'] = array('user_id'           => $user['user_id'],
                                                    //'login'             => $user['username'],
                                                    //'password'          => $user['password'],
                                                    //'update_term_status' => true,
            );
            return true;
            /*header('Location: '.api_get_path(WEB_CODE_PATH).'auth/inscription.php');
            exit;*/
        } else {
            unset($_SESSION['term_and_condition']);
            return false;
        }        
    } else {
        //No validation
        return true;
    }
}