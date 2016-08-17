<?php
ob_start(); //i'm too lazy to check when is sent what ;)
//set session cookie to be read only via http and not by JavaScript
ini_set("session.cookie_httponly", 1);

include_once(__DIR__."/../../lib/Google/Authenticator/GoogleAuthenticator.php");
include_once 'Users.php';

?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Google Authenticator in PHP demo</title>
</head>
<body>
<?php

//set this to false, if you don't want the token prefilled
$debug = true;

$users = new Users();
//check if the user has a session, if not, show the login screen
if ($username = $users->hasSession()) {
    //load the user data from the json storage.
    $user = $users->loadUser($username);
    //if he clicked logout, destroy the session and redirect to the startscreen.
    if (isset($_GET['logout'])) {
        session_destroy();
        header("Location: ./");
    }
    // check if the user is logged in.
    if ($user->isLoggedIn()) {
        include(__DIR__."/../tmpl/loggedin.php");
        //show the QR code if whished so
        if (isset($_GET['showqr'])) {
            $secret = $user->getSecret();
            include(__DIR__."/../tmpl/show-qr.php");
        }
    }
        //if the user is in the OTP phase and submit the OTP.
    else {
        if ($user->isOTP() && isset($_POST['otp'])) {
            $g = new \Google\Authenticator\GoogleAuthenticator();
            // check if the submitted token is the right one and log in
            if ($g->checkCode($user->getSecret(), $_POST['otp'])) {
                // do log-in the user
                $user->doLogin();
                //if the user clicked the "remember the token" checkbox, set the cookie
                if (isset($_POST['remember']) && $_POST['remember']) {
                    $user->setOTPCookie();
                }
                include(__DIR__."/../tmpl/loggedin.php");
            }
                //if the OTP is wrong, destroy the session and tell the user to try again
            else {
                session_destroy();
                include(__DIR__."/../tmpl/login-error.php");
            }

        }
            // if the user is neither logged in nor in the OTP phase, show the login form
        else {
            session_destroy();
            include(__DIR__."/../tmpl/login.php");
        }
    }
    die();
}
    //if the username is set in _POST, then we assume the user filled in the login form.
else {
    if (isset($_POST['username'])) {
        // check if we can load the user (ie. the user exists in our db)
        $user = $users->loadUser($_POST['username']);
        if ($user) {
            //try to authenticate the password and start the session if it's correct.
            if ($user->auth($_POST['password'])) {
                $user->startSession();
                //check if the user has a valid OTP cookie, so we don't have to
                // ask for the current token and can directly log in
                if ($user->hasValidOTPCookie()) {
                    include(__DIR__."/../tmpl/loggedin.php");
                    $user->doLogin();
                }
                    // try to get the users' secret from the db,
                    //  if he doesn't have one, generate one, store it and show it.
                else {
                    if (!$user->getSecret()) {
                        include(__DIR__."/../tmpl/loggedin.php");

                        $secret = $user->generateSecret();
                        $users->storeData($user);
                        $user->doLogin();
                        include(__DIR__."/../tmpl/show-qr.php");
                    }
                        // if the user neither has a valid OTP cookie nor it's the first login
                        //  ask for the OTP
                    else {
                        $user->doOTP();
                        include(__DIR__."/../tmpl/ask-for-otp.php");
                    }
                }


                die();
            }
        }
        // if we're here, something went wrong, destroy the session and show a login error
        session_destroy();

        include(__DIR__."/../tmpl/login-error.php");
        die();
    }
}
// if neither a session nor tried to submit the login credentials -> login screen
include(__DIR__."/../tmpl/login.php");

?>
</body>
</html>
