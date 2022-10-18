<?php
/* For license terms, see /license.txt */
/**
 * Callback script for Azure. The URL of this file is sent to Azure as a
 * point of contact to send particular signals.
 */
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

$plugin = AzureActiveDirectory::create();

$provider = $plugin->getProvider();

if (!isset($_GET['code'])) {
    // If we don't have an authorization code then get one by redirecting
    // users to Azure (with the callback URL information)
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

    // We use the e-mail to authenticate the user, so check that at least one
    // e-mail source exists
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
    // Get the user ID (if any) from the EXTRA_FIELD_ORGANISATION_EMAIL extra
    // field
    if (!empty($organisationValue) && isset($organisationValue['item_id'])) {
        $userId = $organisationValue['item_id'];
    }

    if (empty($userId)) {
        // If the previous step didn't work, get the user ID from
        // EXTRA_FIELD_AZURE_ID
        if (!empty($azureValue) && isset($azureValue['item_id'])) {
            $userId = $azureValue['item_id'];
        }
    }

    if (empty($userId)) {
        // If we didn't find the user
        if ($plugin->get(AzureActiveDirectory::SETTING_PROVISION_USERS) === 'true') {
            // Get groups info, if any
            $groups = $provider->get('me/memberOf', $token);
            if (empty($me)) {
                throw new Exception('Groups info not found.');
            }
            // If any specific group ID has been defined for a specific role, use that
            // ID to give the user the right role
            $givenAdminGroup = $plugin->get(AzureActiveDirectory::SETTING_GROUP_ID_ADMIN);
            $givenSessionAdminGroup = $plugin->get(AzureActiveDirectory::SETTING_GROUP_ID_SESSION_ADMIN);
            $givenTeacherGroup = $plugin->get(AzureActiveDirectory::SETTING_GROUP_ID_TEACHER);
            $userRole = STUDENT;
            $isAdmin = false;
            foreach ($groups as $group) {
                if ($isAdmin) {
                    break;
                }
                if ($givenAdminGroup == $group['objectId']) {
                    $userRole = COURSEMANAGER;
                    $isAdmin = true;
                } elseif (!$isAdmin && $givenSessionAdminGroup == $group['objectId']) {
                    $userRole = SESSIONADMIN;
                } elseif (!$isAdmin && $userRole != SESSIONADMIN && $givenTeacherGroup == $group['objectId']) {
                    $userRole = COURSEMANAGER;
                }
            }

            // If the option is set to create users, create it
            $userId = UserManager::create_user(
                $me['givenName'],
                $me['surname'],
                $userRole,
                $me['mail'],
                $me['mailNickname'],
                '',
                null,
                null,
                $me['telephoneNumber'],
                null,
                'azure',
                null,
                ($me['accountEnabled'] ? 1 : 0),
                null,
                [
                    'extra_'.AzureActiveDirectory::EXTRA_FIELD_ORGANISATION_EMAIL => $me['mail'],
                    'extra_'.AzureActiveDirectory::EXTRA_FIELD_AZURE_ID => $me['mailNickname'],
                ],
                null,
                null,
                $isAdmin
            );
            if (!$userId) {
                throw new Exception(get_lang('UserNotAdded').' '.$me['mailNickname']);
            }
        } else {
            throw new Exception('User not found when checking the extra fields from '.$me['mail'].' or '.$me['mailNickname'].'.');
        }
    }

    $userInfo = api_get_user_info($userId);

    //TODO add user update management for groups

    //TODO add support if user exists in another URL but is validated in this one, add the user to access_url_rel_user

    if (empty($userInfo)) {
        throw new Exception('User '.$userId.' not found.');
    }

    if ($userInfo['active'] != '1') {
        throw new Exception(get_lang('AccountInactive'));
    }
} catch (Exception $exception) {
    $message = Display::return_message($exception->getMessage(), 'error');
    Display::addFlash($message);
    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

$userInfo['uidReset'] = true;

$_GET['redirect_after_not_allow_page'] = 1;

$redirectAfterNotAllowPage = ChamiloSession::read('redirect_after_not_allow_page');

ChamiloSession::clear();

ChamiloSession::write('redirect_after_not_allow_page', $redirectAfterNotAllowPage);

ChamiloSession::write('_user', $userInfo);
ChamiloSession::write('_user_auth_source', 'azure_active_directory');
Event::eventLogin($userInfo['user_id']);
Redirect::session_request_uri(true, $userInfo['user_id']);
