<?php
/* For licensing terms, see /license.txt */
/* 
 * Conditional login
 * Used to implement the loading of custom pages
 * 2011, Noel Dieschburg <noel@cblue.be>
 */

class ConditionalLogin {
    
    /**
     * Check conditions based in the $login_conditions see conditional_login.php file
     * @param type $user
     */
    public static function check_conditions($user) {
        if (file_exists(api_get_path(SYS_PATH).'main/auth/conditional_login/conditional_login.php')) {
            include_once api_get_path(SYS_PATH).'main/auth/conditional_login/conditional_login.php';
            if (isset($login_conditions)) {
                foreach ($login_conditions as $condition) {
                    //If condition fails we redirect to the URL defined by the condition
                    if (isset($condition['conditional_function']) && $condition['conditional_function']($user) == false) {
                        $_SESSION['conditional_login']['uid']       = $user['user_id'];
                        $_SESSION['conditional_login']['can_login'] = false;
                        header("Location:". $condition['url']);
                        exit();
                    }
                }
            }
        }
    }

    public static function login() {
        $_SESSION['conditional_login']['can_login'] = true;
        LoginRedirection::redirect();
    }
}
