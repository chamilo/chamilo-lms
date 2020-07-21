<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

require_once __DIR__.'/../../inc/global.inc.php';
require_once __DIR__.'/okta.init.php';
require_once __DIR__.'/functions.inc.php';

/**
 * This function connect to okta and retrieves the user info
 * If user does not exist in chamilo, it creates it and logs in
 * If user already exists, it updates his info.
 */
function oktaConnect()
{
    $samlSso = 'saml_sso';
    $user = array();
    // If the user is logging in.
    if (isset($_REQUEST[$samlSso])) {
        $sp = $_REQUEST[$samlSso];
        $chamiloSession = $_SESSION;
        SimpleSAML_Session::getSessionFromRequest()->cleanup();
        $as = new SimpleSAML_Auth_Simple($sp);
        $as->requireAuth();
        $user = array(
            'sp' => $sp,
            'authed' => $as->isAuthenticated(),
            'idp' => $as->getAuthData('saml:sp:IdP'),
            'nameId' => $as->getAuthData('saml:sp:NameID')->value,
            'attributes' => $as->getAttributes(),
        );
        $simpleSamlSes = $_SESSION['SimpleSAMLphp_SESSION'];
        $_SESSION = $chamiloSession;
        $_SESSION['SimpleSAMLphp_SESSION'] = $simpleSamlSes;
    }

    if (!empty($user['attributes'])) {
        $u = [
            'firstname' => $user['attributes']['firstname'][0],
            'lastname' => $user['attributes']['lastname'][0],
            'email' => $user['attributes']['email'][0],
            'username' => $user['attributes']['email'][0],
            'password' => 'okta',
            'auth_source' => 'okta',
            'extra' => [],
        ];

        $chamiloUinfo = api_get_user_info_from_email($u['email']);

        $_user['uidReset'] = true;

        if ($chamiloUinfo === false) {
            $u['status'] = STUDENT;
            $chamiloUid = external_add_user($u);
            if ($chamiloUid === false) {
                oktaDisplayError(get_lang('UserNotRegistered'));
            }

            if (!empty($_configuration['multiple_access_urls'])) {
                UrlManager::add_user_to_url($chamiloUid, api_get_current_access_url_id());
            }

            $_user['user_id'] = $chamiloUid;
            Session::write('_user', $_user);

            oktaLastLoginUpdate($chamiloUid);
            oktaRegistrationUpdate($chamiloUid);
            header('Location: ' . api_get_path(WEB_PATH));
            exit();
        }

        // User already exists, update info and login
        $chamiloUid = $chamiloUinfo['user_id'];
        $u['user_id'] = $chamiloUid;
        external_update_user($u);
        $_user['user_id'] = $chamiloUid;
        Session::write('_user', $_user);

        oktaLastLoginUpdate($chamiloUid);
        header('Location: ' . api_get_path(WEB_PATH));
        exit();
    }
}

function oktaRegistrationUpdate($userId)
{
    $userId = (int) $userId;
    // Updating LastLogin Date
    $table_user = Database::get_main_table(TABLE_MAIN_USER);
    $sql = "UPDATE $table_user SET registration_date = NOW() WHERE user_id = " . $userId;
    Database::query($sql);
}

function oktaLastLoginUpdate($userId)
{
    $userId = (int) $userId;
    // Updating LastLogin Date
    $table_user = Database::get_main_table(TABLE_MAIN_USER);
    $sql = "UPDATE $table_user SET last_login = NOW() WHERE user_id = " . $userId;
    Database::query($sql);
}

/**
 * @param $message
 */
function oktaDisplayError($message)
{
    Display::addFlash(
        Display::return_message(
            $message, 'error'
        )
    );

    header('Location:' . api_get_path(WEB_PATH));
    exit;
}
