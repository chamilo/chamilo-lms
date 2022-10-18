<?php
/* For license terms, see /license.txt */

use League\OAuth2\Client\Token\AccessToken;

require __DIR__.'/../../../main/inc/global.inc.php';

if (!empty($_GET['error']) && !empty($_GET['state'])) {
    if ($_GET['state'] === ChamiloSession::read('oauth2state')) {
        api_not_allowed(
            true,
            Display::return_message(
                $_GET['error_description'] ?? $_GET['error'],
                'warning'
            )
        );
    } else {
        ChamiloSession::erase('oauth2state');
        exit('Invalid state');
    }
}

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
    /**
     * @var $accessToken AccessToken
     */
    $accessToken = $provider->getAccessToken(
        'authorization_code',
        ['code' => $_GET['code']]
    );
    ChamiloSession::write('oauth2AccessToken', $accessToken->jsonSerialize());
    $userInfo = $plugin->getUserInfo($provider, $accessToken);
    if ($userInfo['active'] != '1') {
        throw new Exception($plugin->get_lang('AccountInactive'));
    }
    if (api_is_multiple_url_enabled()) {
        $userId = $userInfo['user_id'];
        $urlIdsTheUserCanAccess = api_get_access_url_from_user($userId);
        $userCanAccessTheFirstURL = in_array(1, $urlIdsTheUserCanAccess);
        $userCanAccessTheCurrentURL = in_array(api_get_current_access_url_id(), $urlIdsTheUserCanAccess)
            || UserManager::is_admin($userId)
            && $userCanAccessTheFirstURL;

        if (!$userCanAccessTheCurrentURL) {
            throw new Exception($plugin->get_lang('UserNotAllowedOnThisPortal'));
        }
    }
} catch (Exception $exception) {
    $message = Display::return_message($exception->getMessage(), 'error', false);
    Display::addFlash($message);
    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

ConditionalLogin::check_conditions($userInfo);

$userInfo['uidReset'] = true;

$_GET['redirect_after_not_allow_page'] = 1;

$redirectAfterNotAllowPage = ChamiloSession::read('redirect_after_not_allow_page');

ChamiloSession::clear();

ChamiloSession::write('redirect_after_not_allow_page', $redirectAfterNotAllowPage);

ChamiloSession::write('_user', $userInfo);
ChamiloSession::write('_user_auth_source', 'oauth2');

Redirect::session_request_uri(true, $userInfo['user_id']);
