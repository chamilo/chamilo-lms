<?php
/* For license terms, see /license.txt */
/**
 * Callback script for Azure. The URL of this file is sent to Azure as a
 * point of contact to send particular signals.
 */

use Chamilo\UserBundle\Entity\User;

$GLOBALS['noredirection'] = empty($_GET) || isset($_GET['code']) || isset($_GET['state']) || isset($_GET['session_state']) || isset($_GET['error']);

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

if ('true' !== $plugin->get(AzureActiveDirectory::SETTING_ENABLE)) {
    api_not_allowed(true);
}

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
try {
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code'],
    ]);
} catch (Exception $exception) {
    if ($exception->getMessage() == 'interaction_required') {
        $message = Display::return_message($plugin->get_lang('additional_interaction_required'), 'error', false);
    } else {
        $message = Display::return_message($exception->getMessage(), 'error');
    }
    Display::addFlash($message);
    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

$me = null;

try {
    $userFields = [
        'givenName',
        'surname',
        'mail',
        'userPrincipalName',
        'businessPhones',
        'mobilePhone',
        'accountEnabled',
        'mailNickname',
        'id',
    ];

    $querySelect = implode(',', $userFields);

    $me = $provider->get(
        sprintf('me?$select=%s', $querySelect),
        $token
    );

    if (empty($me)) {
        throw new Exception('Token not found.');
    }

    // We use the e-mail to authenticate the user, so check that at least one
    // e-mail source exists
    if (empty($me['mail'])) {
        throw new Exception('The mail field is empty in Azure AD and is needed to set the organisation email for this user.');
    }
    if (empty($me['mailNickname'])) {
        throw new Exception('The mailNickname field is empty in Azure AD and is needed to set the unique username for this user.');
    }
    if (empty($me['id'])) {
        throw new Exception('The id field is empty in Azure AD and is needed to set the unique Azure ID for this user.');
    }

    $userId = $plugin->registerUser($me);

    if ($roleGroups = $plugin->getGroupUidByRole()) {
        $roleActions = $plugin->getUpdateActionByRole();
        /** @var User $user */
        $user = UserManager::getManager()->find($userId);

        $azureGroups = $provider->get('me/memberOf', $token);

        foreach ($roleGroups as $userRole => $groupUid) {
            foreach ($azureGroups as $azureGroup) {
                $azureGroupUid = $azureGroup['id'];
                if ($azureGroupUid === $groupUid) {
                    $roleActions[$userRole]($user);

                    break 2;
                }
            }
        }

        Database::getManager()->flush();
    }

    $userInfo = api_get_user_info($userId);

    /* @TODO add support if user exists in another URL but is validated in this one, add the user to access_url_rel_user */

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

$GLOBALS['noredirection'] = false;

Redirect::session_request_uri(true, $userInfo['user_id']);
