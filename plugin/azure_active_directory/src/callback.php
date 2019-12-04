<?php
/* For license terms, see /license.txt */

require __DIR__.'/../../../main/inc/global.inc.php';

$plugin = AzureActiveDirectory::create();

$provider = $plugin->getProvider();

if (!isset($_GET['code'])) {
    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();

    ChamiloSession::write('oauth2state', $provider->getState());

    header('Location: '.$authUrl);
    exit;
}

// Check given state against previously stored one to mitigate CSRF attack
if (empty($_GET['state']) || ($_GET['state'] !== ChamiloSession::read('oauth2state'))) {
    ChamiloSession::erase('oauth2state');

    exit;
}

// Try to get an access token (using the authorization code grant)
$token = $provider->getAccessToken('authorization_code', [
    'code' => $_GET['code'],
    'resource' => 'https://graph.windows.net',
]);

$me = null;

try {
    $me = $provider->get("me", $token);
} catch (Exception $e) {
    exit;
}

$userInfo = [];

if (!empty($me['email'])) {
    $userInfo = api_get_user_info_from_email($me['email']);
}

if (empty($userInfo) && !empty($me['email'])) {
    $extraFieldValue = new ExtraFieldValue('user');
    $itemValue = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
        AzureActiveDirectory::EXTRA_FIELD_ORGANISATION_EMAIL,
        $me['email']
    );

    $userInfo = api_get_user_info($itemValue['item_id']);
}

if (empty($userInfo) && !empty($me['mailNickname'])) {
    $extraFieldValue = new ExtraFieldValue('user');
    $itemValue = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
        AzureActiveDirectory::EXTRA_FIELD_AZURE_ID,
        $me['mailNickname']
    );

    $userInfo = api_get_user_info($itemValue['item_id']);
}

if (empty($userInfo)) {
    header('Location: '.api_get_path(WEB_PATH).'index.php?loginFailed=1&error=user_password_incorrect');

    exit;
}

$_user['user_id'] = $userInfo['user_id'];
$_user['uidReset'] = true;

ChamiloSession::write('_user', $_user);
ChamiloSession::write('_user_auth_source', 'azure_active_directory');

header('Location: '.api_get_path(WEB_PATH));
exit;
