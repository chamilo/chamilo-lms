<?php
/* For license terms, see /license.txt */

require __DIR__.'/../../../main/inc/global.inc.php';
require_once __DIR__.'/../../../main/auth/external_login/functions.inc.php';

if (isset($_POST['error']) || empty($_REQUEST)) {
    header('Location: '.api_get_path(WEB_PATH).'index.php?logout=logout');
    exit;
}

list($jwtHeader, $jwtPayload, $jwtSignature) = explode('.', $_REQUEST['id_token']);

$jwtHeader = json_decode(
    base64_decode($jwtHeader)
);

$jwtPayload = json_decode(
    base64_decode($jwtPayload)
);

$u = array(
    'firstname' => $jwtPayload->given_name,
    'lastname' => $jwtPayload->family_name,
    'status' => STUDENT,
    'email' => $jwtPayload->emails[0],
    'username' => $jwtPayload->emails[0],
    'language' => 'en',
    'password' => 'azure_active_directory',
    'auth_source' => 'azure_active_directory '.$jwtPayload->idp,
    'extra' => array()
);

$userInfo = api_get_user_info_from_email($jwtPayload->emails[0]);

if ($userInfo === false) {
    // we have to create the user
    $chamilo_uid = external_add_user($u);

    if ($chamilo_uid !== false) {
        $_user['user_id'] = $chamilo_uid;
        $_user['uidReset'] = true;
        $_SESSION['_user'] = $_user;
    }
} else {
    // User already exists, update info and login
    $chamilo_uid = $userInfo['user_id'];
    $u['user_id'] = $chamilo_uid;
    external_update_user($u);

    $_user['user_id'] = $chamilo_uid;
    $_user['uidReset'] = true;
    $_SESSION['_user'] = $_user;
}

header('Location: '.api_get_path(WEB_PATH));
exit;
