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
    $me = $provider->get('me', $token);

    if (empty($me)) {
        throw new Exception('Token not found.');
    }

    if (empty($me['mail']) || empty($me['mailNickname'])) {
        throw new Exception('Mail empty');
    }

    $extraFieldValue = new ExtraFieldValue('user');
    $organisationValue = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
        AzureActiveDirectory::EXTRA_FIELD_ORGANISATION_EMAIL,
        $me['mail']
    );
    $azureValue = $extraFieldValue->get_item_id_from_field_variable_and_field_value(
        AzureActiveDirectory::EXTRA_FIELD_AZURE_ID,
        $me['mailNickname']
    );

    $userId = null;
    // Check EXTRA_FIELD_ORGANISATION_EMAIL
    if (!empty($organisationValue) && isset($organisationValue['item_id'])) {
        $userId = $organisationValue['item_id'];
    }

    if (empty($userId)) {
        // Check EXTRA_FIELD_AZURE_ID
        if (!empty($azureValue) && isset($azureValue['item_id'])) {
            $userId = $azureValue['item_id'];
        }
    }

    /*$emptyValues = empty($organisationValue['item_id']) || empty($azureValue['item_id']);
    $differentValues = !$emptyValues && $organisationValue['item_id'] != $azureValue['item_id'];

    if ($emptyValues || $differentValues) {
        throw new Exception('Empty values');
    }*/

    if (empty($userId)) {
        throw new Exception('User not found when checking the extra fields.');
    }

    $userInfo = api_get_user_info($userId);

    if (empty($userInfo)) {
        throw new Exception('User not found');
    }

    if ($userInfo['active'] != '1') {
        throw new Exception('account_inactive');
    }
} catch (Exception $exception) {
    $message = Display::return_message($plugin->get_lang('InvalidId'), 'error');

    if ($exception->getMessage() === 'account_inactive') {
        $message = Display::return_message(get_lang('AccountInactive'), 'error');
    }

    Display::addFlash($message);
    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

$_user['user_id'] = $userInfo['user_id'];
$_user['uidReset'] = true;

ChamiloSession::write('_user', $_user);
ChamiloSession::write('_user_auth_source', 'azure_active_directory');

Redirect::session_request_uri(true, $userInfo['user_id']);
