<?php
/* For license terms, see /license.txt */

require __DIR__.'/../../../main/inc/global.inc.php';

$plugin = OAuth2::create();

$provider = $plugin->getProvider();

// If we don't have an authorization code then get one
if (!array_key_exists('code', $_GET)) {
    // Fetch the authorization URL from the provider; this returns the
    // urlAuthorize option and generates and applies any necessary parameters
    // (e.g. state).
    $authorizationUrl = $provider->getAuthorizationUrl();

    // Get the state generated for you and store it to the session.
    ChamiloSession::write('oauth2state', $provider->getState());

    // Redirect the user to the authorization URL.
    header('Location: '.$authorizationUrl);
    exit;
}

// Check given state against previously stored one to mitigate CSRF attack
if (!array_key_exists('state', $_GET) || ($_GET['state'] !== ChamiloSession::read('oauth2state'))) {
    ChamiloSession::erase('oauth2state');
    exit('Invalid state');
}

try {
    // Try to get an access token using the authorization code grant.
    $accessToken = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);
    ChamiloSession::write('oauth2AccessToken', $accessToken->jsonSerialize());
    $userInfo = $plugin->getUserInfo($provider, $accessToken);
    if ($userInfo['active'] != '1') {
        throw new Exception(get_lang('AccountInactive'));
    }
} catch (Exception $exception) {
    $message = Display::return_message($exception->getMessage(), 'error');
    Display::addFlash($message);
    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

$_user['user_id'] = $userInfo['user_id'];
$_user['uidReset'] = true;

ChamiloSession::write('_user', $_user);
ChamiloSession::write('_user_auth_source', 'oauth2');

Redirect::session_request_uri(true, $userInfo['user_id']);
