<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\WhispeakAuth\LogEvent;

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

/** @var array $lpItemInfo */
$lpItemInfo = ChamiloSession::read(WhispeakAuthPlugin::SESSION_LP_ITEM, []);
/** @var array $quizQuestionInfo */
$quizQuestionInfo = ChamiloSession::read(WhispeakAuthPlugin::SESSION_QUIZ_QUESTION, []);

$isValidPassword = UserManager::checkPassword($user->getPassword(), $password, $user->getSalt(), $user->getId());
$isActive = $user->isActive();
$isExpired = empty($user->getExpirationDate()) || $user->getExpirationDate() > api_get_utc_datetime(null, false, true);

$userPass = true;

if (!$isValidPassword || !$isActive || !$isExpired) {
    if (!empty($lpItemInfo)) {
        $plugin->addAttemptInLearningPath(
            LogEvent::STATUS_FAILED,
            $user->getId(),
            $lpItemInfo['lp_item'],
            $lpItemInfo['lp']
        );
    } elseif (!empty($quizQuestionInfo)) {
        $plugin->addAttemptInQuiz(
            LogEvent::STATUS_FAILED,
            $user->getId(),
            $quizQuestionInfo['question'],
            $quizQuestionInfo['quiz']
        );
    }

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
        $message .= PHP_EOL.'<span data-reach-attempts="true">'.$plugin->get_lang('MaxAttemptsReached').'</span>';
    } else {
        $message .= PHP_EOL.$plugin->get_lang('TryAgain');
    }

    echo Display::return_message($message, 'error', false);

    if (!$maxAttempts ||
        ($maxAttempts && $failedLogins >= $maxAttempts)
    ) {
        $userPass = true;
    }
} elseif ($isValidPassword) {
    ChamiloSession::erase(WhispeakAuthPlugin::SESSION_FAILED_LOGINS);
    ChamiloSession::erase(WhispeakAuthPlugin::SESSION_2FA_USER);

    if (!empty($lpItemInfo)) {
        $plugin->addAttemptInLearningPath(
            LogEvent::STATUS_SUCCESS,
            $user->getId(),
            $lpItemInfo['lp_item'],
            $lpItemInfo['lp']
        );
    } elseif (!empty($quizQuestionInfo)) {
        $plugin->addAttemptInQuiz(
            LogEvent::STATUS_SUCCESS,
            $user->getId(),
            $quizQuestionInfo['question'],
            $quizQuestionInfo['quiz']
        );
    }

    echo Display::return_message($plugin->get_lang('AuthentifySuccess'), 'success');
}

if ($userPass) {
    $url = '';

    ChamiloSession::erase(WhispeakAuthPlugin::SESSION_FAILED_LOGINS);
    ChamiloSession::erase(WhispeakAuthPlugin::SESSION_2FA_USER);

    if ($lpItemInfo) {
        ChamiloSession::erase(WhispeakAuthPlugin::SESSION_LP_ITEM);

        $url = $lpItemInfo['src'];
    } elseif ($quizQuestionInfo) {
        $quizQuestionInfo['passed'] = true;
        $url = api_get_path(WEB_CODE_PATH).'exercise/exercise_submit.php?'.$quizQuestionInfo['url_params'];

        ChamiloSession::write(WhispeakAuthPlugin::SESSION_QUIZ_QUESTION, $quizQuestionInfo);
    }

    if (!empty($url)) {
        echo '
            <script>window.location.href = "'.$url.'";</script>
        ';
    }
}
