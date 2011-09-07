<?php
// Conditional login
// Used to implement the loading of custom pages
// 2011, Noel Dieschburg <noel@cblue.be>

class ConditionalLogin {

  public static function check_conditions($user) {
		if (file_exists(api_get_path(SYS_PATH).'main/auth/conditional_login/conditional_login.php')) {
			include_once(api_get_path(SYS_PATH).'main/auth/conditional_login/conditional_login.php');
			if (isset($dc_conditions)){
				foreach ($dc_conditions as $dc_condition) {
					if ($dc_condition['conditional_function']($user)) {
						$_SESSION['conditional_login']['uid'] = $user['user_id'];
						$_SESSION['conditional_login']['can_login'] = false;
						header("Location:". $dc_condition['url']);
						exit();
					}
				}
			}
		}
  }

  public static function login(){
    $_SESSION['conditional_login']['can_login'] = true;
    header('location: '.api_get_path(WEB_PATH).api_get_setting('page_after_login').$param);
    exit();
  }
}
?>
