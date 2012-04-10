<?php
/* 
  SSO sample
  
  This is the "server" of my institution/university authentification "code"
  
  1. Active all the SSO option in your Chamilo installation: main/admin/settings.php?category=Security
  2. Make sure this script is located in the index page of the server you fill in the  "Domain of the Single Sign On server" Chamilo setting
     For example this script must be located in example.com/index.php if you set the "Domain of the Single Sign On server" = example.com
  3. Create a user in chamilo and in your external system with login = "joe" and password = "doe"  
  4. Remember this is just a sample! Check the chamilo drupal extension for more information: 
     http://drupal.org/node/817682
  5. See the main/auth/sso.class.php library for more details
*/


//This page will be loaded when entering to the Chamilo site if the SSO option was set in step 1.

//Getting the chamilo server
$my_chamilo_server = filter_xss($_SERVER['HTTP_HOST']);

//Correct user values - we asume that this user exists in both systems
$correct_user = 'john';
$correct_pass = 'doe';

$account = array();

if (isset($_SESSION['my_server_user_session'])) {
    //validate if the user is already logged in my external system in order to redirect to chamilo
}

//Login process
        
if (isset($_POST['user']) && isset($_POST['password'])) {
    if ($_POST['user'] == $correct_user && $_POST['password'] == $correct_pass) {
    
        // You have to add here your server validations
        
        $account['name'] = 'john';
        $account['pass'] = 'doe';
        
        $master_auth_uri = $my_chamilo_server . '?q=user';

        // Creating an array cookie that will be sent to Chamilo
        $sso = array(
            'username'          => $account['name'],
            'secret'            => sha1($account['pass']),
            'master_domain'     => $my_chamilo_server,
            'master_auth_uri'   => $master_auth_uri,
            'lifetime'          => time() + 3600,
            'target'            => filter_xss($_GET['sso_target']),
        );

        $cookie = base64_encode(serialize($sso));
        $url = chamilo_sso_protocol() . $master_auth_uri;
        $params = 'sso_referer='. urlencode($url) .'&sso_cookie='. urlencode($cookie);
        $final_url = filter_xss($_GET['sso_referer']) .'?'. $params;
        
        //If your user exists redirect to chamilo and set the account in a session to check it later        
        $_SESSION['my_server_user_session'] = $account;
        
        //Redirect to chamilo
        header('Location: '.$final_url);
    } else {
        echo '<h2>Wrong parameters</h2>';
    }
}

if (isset($_POST['logout'])) {
    //echo do something to logout
}

function filter_xss($val) {
    //do some cleaning
    return $val;
}

function chamilo_sso_protocol() {
    //get the sso_protocol from chamilo using webservices
    return 'http://';
}
?>
<html>
    <form method="post">
        User <input name="user" value="<?php echo $correct_user; ?>">
        Pass <input name="password" value="<?php echo $correct_pass; ?>">
        <input type="submit" value="Login">
    </form>
</html>