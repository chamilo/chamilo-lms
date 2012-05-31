<?php
require_once(dirname(__FILE__).'/../../inc/global.inc.php');
//require_once (api_get_path(LIBRARY_PATH).'conditionallogin.lib.php'); moved to autologin
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
$url =  api_get_path(WEB_PATH).'main/auth/conditional_login/complete_phone_number.php';

if (! isset($_SESSION['conditional_login']['uid']))
  die("Not Authorised");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="fr" xml:lang="fr" xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
  </head>
  <body>
  <form id="data_completion" name="data_completion" method="post" action="<?php echo $url?>">
        Téléphone : <input type="text" name="phone_number" />
        <input type="submit" name="submit" value="Submit" />
    </form>
  </body>
</html>
<?php
if (isset($_POST['submit'])){
    $u = UserManager::get_user_info_by_id($_SESSION['conditional_login']['uid']);
    $u['phone'] = $_POST['phone_number'];
    $password = null; // we don't want to change the password 
    $updated = UserManager::update_user($u['user_id'], $u['firstname'], $u['lastname'], $u['username'], $password, $u['auth_source'], $u['email'], $u['status'], $u['official_code'], $u['phone'], $u['picture_uri'], $u['expiration_date'], $u['active'], $u['creator_id'], $u['hr_dept_id'], $u['extra'], $u['language'],'');
    if ($updated) {
    ConditionalLogin::login();
    }
}
?>
