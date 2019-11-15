<?php

use ChamiloSession as Session;

require_once __DIR__.'/../../inc/global.inc.php';
require_once __DIR__.'/okta.init.php';
require_once __DIR__.'/functions.inc.php';

/**
 * This function creates all the required params
 * for the okta login page
 * @return string
 */
function oktaLoginAuthorization()
{
    Session::write('okta_state', bin2hex(random_bytes(5)));

    $query = http_build_query([
        'client_id' => $GLOBALS['okta_config']['client_id'],
        'response_type' => 'code',
        'response_mode' => 'query',
        'scope' => 'openid profile',
        'redirect_uri' => $GLOBALS['okta_config']['return_url'],
        'state' => Session::read('okta_state'),
        //'nonce' => $nonce
    ]);

    return $GLOBALS['okta_config']['okta_domain'] . '/oauth2/default/v1/authorize?' . $query;
}

/**
 * This function connect to okta and retrieves the user info
 * If user does not exist in chamilo, it creates it and logs in
 * If user already exists, it updates his info.
 */
function oktaConnect()
{
    if (empty($_GET['action'])) {
        header('Location: ' . oktaLoginAuthorization());
        exit;
    }

    if (Session::has('okta_state') && Session::read('okta_state') != $_GET['state']) {
        oktaDisplayError('Authorization server returned an invalid state parameter');
    }

    if (isset($_GET['error'])) {
        oktaDisplayError('Okta Authorization server returned an error: ' . htmlspecialchars($_GET['error']));
    }

    $metadata = oktaHttpRequest(oktaMetaUrl());

    $response = oktaHttpRequest($metadata->token_endpoint, [
        'grant_type' => 'authorization_code',
        'code' => $_GET['code'],
        'redirect_uri' => $GLOBALS['okta_config']['return_url'],
        'client_id' => $GLOBALS['okta_config']['client_id'],
        'client_secret' => $GLOBALS['okta_config']['client_secret']
    ]);

    if (!isset($response->access_token)) {
        oktaDisplayError('Error fetching access token!');
    }

    Session::erase('okta_token');
    Session::write('okta_token', $response->access_token);
    Session::erase('okta_id_token');
    Session::write('okta_id_token', $response->id_token);

    $token = oktaHttpRequest($metadata->introspection_endpoint, [
        'token' => Session::read('okta_token'),
        'client_id' => $GLOBALS['okta_config']['client_id'],
        'client_secret' => $GLOBALS['okta_config']['client_secret']
    ]);

    if ($token->active) {
        $oktaUser = oktaFindUser($token->username);
        $userData = json_decode($oktaUser, true);
        if (!isset($userData[0]['id'])) {
            oktaDisplayError('Okta User Not Found');
        }

        $u = [
            'firstname' => $userData[0]['profile']['firstName'],
            'lastname' => $userData[0]['profile']['lastName'],
            'status' => STUDENT,
            'email' => $userData[0]['profile']['email'],
            'username' => $userData[0]['profile']['email'],
            'password' => 'okta',
            'auth_source' => 'okta',
            'extra' => [],
        ];
        $chamiloUinfo = api_get_user_info_from_email($userData[0]['profile']['email']);

        $_user['uidReset'] = true;

        if ($chamiloUinfo === false) {
            $chamiloUid = external_add_user($u);
            if ($chamiloUid === false) {
                oktaDisplayError(get_lang('UserNotRegistered'));
            }

            $_user['user_id'] = $chamiloUid;
            Session::write('_user', $_user);

            header('Location: ' . api_get_path(WEB_PATH));
            exit();
        }

        // User already exists, update info and login
        $chamiloUid = $chamiloUinfo['user_id'];
        $u['user_id'] = $chamiloUid;
        external_update_user($u);
        $_user['user_id'] = $chamiloUid;
        Session::write('_user', $_user);

        header('Location: '.api_get_path(WEB_PATH));
        exit();
    }
}

/**
 * @param $input
 * @return bool|string
 */
function oktaFindUser($email)
{
    $url = oktaApiBaseUrl() . 'users?q=' . urlencode($email) . '&limit=1';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: SSWS ' . $GLOBALS['okta_config']['api_token']
    ]);

    return curl_exec($ch);
}

/**
 * Helper to make request
 * @param $url
 * @param null $params
 * @return mixed
 */
function oktaHttpRequest($url, $params = null)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($params) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }
    return json_decode(curl_exec($ch));
}

/*
 * @return string
 */
function oktaMetaUrl()
{
    return $GLOBALS['okta_config']['okta_domain'] . '/oauth2/default/.well-known/oauth-authorization-server';
}

/*
 * @return string
 */
function oktaApiBaseUrl()
{
    return $GLOBALS['okta_config']['okta_domain'] . '/api/v1/';
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

/**
 * Delete Open Session
 * @return bool|string
 */
function oktaCloseSession()
{

    $url = oktaApiBaseUrl() . 'sessions/' . Session::read('okta_id_token');
   // echo $url;exit;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    //var_dump($result);
    //exit;
}
