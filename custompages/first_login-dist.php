<?php
/* For licensing terms, see /license.txt */
/**
 * Quick page to react to first login cases.
 *
 * @package chamilo.custompages
 */
require_once api_get_path(SYS_PATH).'main/inc/global.inc.php';
require_once __DIR__.'/language.php';

/**
 * Security checks.
 */
if (!isset($_SESSION['conditional_login']['uid'])) {
    exit("Not Authorised");
}

if (isset($_POST['password'])) {
    $u = api_get_user_info($_SESSION['conditional_login']['uid']);
    if ($_POST['password'] != $_POST['password2']) {
        header('Location: '.api_get_self().'?invalid=2');
        exit();
    }
    if (empty($_POST['password'])) { //|| !api_check_password($password)) { //Pass must be at least 5 char long with 2 digits and 3 letters
        header('Location: '.api_get_self().'?invalid=1');
        exit();
    }
    $password = $_POST['password'];
    $updated = UserManager::update_user(
        $u['user_id'],
        $u['firstname'],
        $u['lastname'],
        $u['username'],
        $password,
        $u['auth_source'],
        $u['email'],
        $u['status'],
        $u['official_code'],
        $u['phone'],
        $u['picture_uri'],
        $u['expiration_date'],
        $u['active'],
        $u['creator_id'],
        $u['hr_dept_id'],
        null,
        $u['language'],
        ''
    );

    if ($updated !== false) {
        UserManager::update_extra_field_value($u['user_id'], 'already_logged_in', 'true');
        ConditionalLogin::login();
    }
}
if ($_GET['invalid'] == 1) {
    $error_message = get_lang('CurrentPasswordEmptyOrIncorrect');
}
if ($_GET['invalid'] == 2) {
    $error_message = get_lang('PassTwo');
}
$www = api_get_path('WEB_PATH');
/**
 * HTML output.
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title>Custompage - login</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <script type="text/javascript" src="<?php echo $www; ?>web/assets/jquery/dist/jquery.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            if (top.location != location)
                top.location.href = document.location.href ;

            // Handler pour la touche retour
            $('input').keyup(function(e) {
                if (e.keyCode == 13) {
                    $('#changepassword-form').submit();
                }
            });
        });
    </script>
</head>
<body>
    <div id="backgroundimage">
        <img src="/custompages/images/page-background.png" class="backgroundimage" />
    </div>
    <div id="wrapper">
        <div id="header">
            <img src="/custompages/images/header.png" alt="Logo" />
        </div> <!-- #header -->
    <h2> <?php echo custompages_get_lang('FirstLogin'); ?> </h2>

        <div id="changepassword-form-box" class="form-box">
      <div class="info"> <?php echo custompages_get_lang('FirstLoginChangePassword'); ?> </div>
        <?php if (isset($error_message)) {
    echo '<div id="changepassword-form-error" class="form-error">'.$error_message.'</div>';
}?>
            <form id="changepassword-form" class="form" method="post">
                <div>
          <label for="password">*<?php echo custompages_get_lang('Password'); ?></label>
                    <input name="password" type="password" /><br />
          <label for="password2">*<?php echo custompages_get_lang('Password'); ?></label>
                    <input name="password2" type="password" /><br />
                </div>
            </form>
            <div id="changepassword-form-submit" class="form-submit" onclick="document.forms['changepassword-form'].submit();">
      <span><?php echo custompages_get_lang('LoginEnter'); ?></span>
            </div> <!-- #form-submit -->
        </div> <!-- #form -->
        <div id="footer">
            <img src="/custompages/images/footer.png" />
        </div> <!-- #footer -->
    </div> <!-- #wrapper -->
</body>
</html>
