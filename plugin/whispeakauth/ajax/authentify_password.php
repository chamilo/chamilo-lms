<?php
/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

api_block_anonymous_users(false);

$plugin = WhispeakAuthPlugin::create();

$plugin->protectTool(false);

$tokenIsValid = Security::check_token();

if (!$tokenIsValid) {
    WhispeakAuthPlugin::displayNotAllowedMessage();
}

$maxAttempts = $plugin->getMaxAttempts();
$failedLogins = ChamiloSession::read(WhispeakAuthPlugin::SESSION_FAILED_LOGINS, 0);

if ($maxAttempts && $failedLogins >= $maxAttempts) {
    echo Display::return_message($plugin->get_lang('MaxAttemptsReached'), 'warning');

    exit;
}

$user = api_get_user_entity(api_get_user_id());
$password = isset($_POST['password']) ? $_POST['password'] : null;

if (empty($password) || empty($user)) {
    WhispeakAuthPlugin::displayNotAllowedMessage();
}

if (!in_array($user->getAuthSource(), [PLATFORM_AUTH_SOURCE, CAS_AUTH_SOURCE])) {
    WhispeakAuthPlugin::displayNotAllowedMessage();
}

$isValidPassword = UserManager::isPasswordValid($user->getPassword(), $password, $user->getSalt());
$isActive = $user->isActive();
$isExpired = empty($user->getExpirationDate()) || $user->getExpirationDate() > api_get_utc_datetime(null, false, true);

$userPass = true;

if (!$isValidPassword || !$isActive || !$isExpired) {
    $userPass = false;

    $message = $plugin->get_lang('AuthentifyFailed');

    if (!$isActive) {
        $message .= PHP_EOL.get_lang('Account inactive');
    }

    if (!$isExpired) {
        $message .= PHP_EOL.get_lang('AccountExpired');
    }

    ChamiloSession::write(WhispeakAuthPlugin::SESSION_FAILED_LOGINS, ++$failedLogins);

    if ($maxAttempts && $failedLogins >= $maxAttempts) {
        $message .= PHP_EOL.$plugin->get_lang('MaxAttemptsReached');
    } else {
        $message .= PHP_EOL.$plugin->get_lang('TryAgain');
    }

    echo Display::return_message($message, 'error', false);

    if ($maxAttempts && $failedLogins >= $maxAttempts) {
        //$userPass = true;
    }
} elseif ($isValidPassword) {
    echo Display::return_message($plugin->get_lang('AuthentifySuccess'), 'success');
}

if ($userPass) {
    /** @var array $lpItemInfo */
    $lpItemInfo = ChamiloSession::read(WhispeakAuthPlugin::SESSION_LP_ITEM, []);

    ChamiloSession::erase(WhispeakAuthPlugin::SESSION_FAILED_LOGINS);
    ChamiloSession::erase(WhispeakAuthPlugin::SESSION_LP_ITEM);
    ChamiloSession::erase(WhispeakAuthPlugin::SESSION_2FA_USER);

    echo '<script>window.setTimeout(function () {
            window.location.href = "'.$lpItemInfo['src'].'";
        }, 1500);</script>';
}
