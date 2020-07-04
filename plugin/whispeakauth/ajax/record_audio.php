<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\WhispeakAuth\LogEvent;
use Chamilo\PluginBundle\WhispeakAuth\Controller\CreateEnrollmentRequestController;
use Chamilo\UserBundle\Entity\User;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Audio\Wav;

$cidReset = true;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$action = isset($_POST['action']) ? $_POST['action'] : 'enrollment';
$isEnrollment = 'enrollment' === $action;
$isAuthentify = 'authentify' === $action;

$isAllowed = false;

if ($isEnrollment) {
    $enrollmentRequest = new CreateEnrollmentRequestController();
    $enrollmentRequest->process();
    die;

    $isAllowed = !empty($_FILES['audio']);
} elseif ($isAuthentify) {
    $userId = api_get_user_id();
    $user2fa = ChamiloSession::read(WhispeakAuthPlugin::SESSION_2FA_USER, 0);

    if (!empty($user2fa) || !empty($userId)) {
        $isAllowed = !empty($_FILES['audio']);
    } else {
        $isAllowed = !empty($_POST['username']) && !empty($_FILES['audio']);
    }
}

if (!$isAllowed) {
    WhispeakAuthPlugin::displayNotAllowedMessage();
}

$plugin = WhispeakAuthPlugin::create();

$plugin->protectTool(false);

$failedLogins = 0;
$maxAttempts = 0;

if ($isAuthentify) {
    $failedLogins = ChamiloSession::read(WhispeakAuthPlugin::SESSION_FAILED_LOGINS, 0);
    $maxAttempts = $plugin->getMaxAttempts();

    $em = Database::getManager();

    if (!empty($user2fa)) {
        $user = api_get_user_entity($user2fa);
    } elseif (!empty($userId)) {
        $user = api_get_user_entity($userId);
    } else {
        /** @var User|null $user */
        $user = UserManager::getRepository()->findOneBy(['username' => $_POST['username']]);
    }
} else {
    /** @var User $user */
    $user = api_get_user_entity(api_get_user_id());
}

if (empty($user)) {
    echo Display::return_message(get_lang('NoUser'), 'error');

    exit;
}

$path = api_upload_file('whispeakauth', $_FILES['audio'], $user->getId());

if (false === $path) {
    echo Display::return_message(get_lang('UploadError'), 'error');

    exit;
}

$newFullPath = $originFullPath = api_get_path(SYS_UPLOAD_PATH).'whispeakauth'.$path['path_to_save'];
$fileType = mime_content_type($originFullPath);

if ('wav' !== substr($fileType, -3)) {
    $directory = dirname($originFullPath);
    $newFullPath = $directory.'/audio.wav';

    try {
        $ffmpeg = FFMpeg::create();

        $audio = $ffmpeg->open($originFullPath);
        $audio->save(new Wav(), $newFullPath);
    } catch (Exception $exception) {
        echo Display::return_message($exception->getMessage(), 'error');

        exit;
    }
}

if ($isAuthentify) {
    if ($maxAttempts && $failedLogins >= $maxAttempts) {
        echo Display::return_message($plugin->get_lang('MaxAttemptsReached'), 'warning');

        exit;
    }

    $wsid = WhispeakAuthPlugin::getAuthUidValue($user->getId());

    if (empty($wsid)) {
        echo Display::return_message($plugin->get_lang('SpeechAuthNotEnrolled'), 'warning');

        exit;
    }

    try {
        $text = ChamiloSession::read(WhispeakAuthPlugin::SESSION_SENTENCE_TEXT);

        $authentifyResult = WhispeakAuthRequest::authentify($plugin, $wsid->getValue(), $text, $newFullPath);
    } catch (Exception $exception) {
        echo Display::return_message($plugin->get_lang('TryAgain'), 'error');

        exit;
    }

    $success = (bool) $authentifyResult['result'];
    $qualityNote = !empty($authentifyResult['quality']) ? explode('|', $authentifyResult['quality']) : [];
    $qualityNote = array_map('ucfirst', $qualityNote);

    /** @var array $lpItemInfo */
    $lpItemInfo = ChamiloSession::read(WhispeakAuthPlugin::SESSION_LP_ITEM, []);
    /** @var array $quizQuestionInfo */
    $quizQuestionInfo = ChamiloSession::read(WhispeakAuthPlugin::SESSION_QUIZ_QUESTION, []);

    $message = $plugin->get_lang('AuthentifySuccess');

    if (!$success) {
        if (!empty($lpItemInfo)) {
            $plugin->addAttemptInLearningPath(
                LogEvent::STATUS_FAILED,
                $user->getId(),
                $lpItemInfo['lp_item'],
                $lpItemInfo['lp']
            );
        }

        if (!empty($quizQuestionInfo)) {
            $plugin->addAttemptInQuiz(
                LogEvent::STATUS_FAILED,
                $user->getId(),
                $quizQuestionInfo['question'],
                $quizQuestionInfo['quiz']
            );
        }

        $message = $plugin->get_lang('AuthentifyFailed');

        ChamiloSession::write(WhispeakAuthPlugin::SESSION_FAILED_LOGINS, ++$failedLogins);

        if ($maxAttempts && $failedLogins >= $maxAttempts) {
            $message .= PHP_EOL
                .'<span data-reach-attempts="true">'.$plugin->get_lang('MaxAttemptsReached').'</span>'
                .PHP_EOL
                .'<br><strong>'
                .$plugin->get_lang('LoginWithUsernameAndPassword')
                .'</strong>';

            if (!empty($user2fa)) {
                Display::addFlash(
                    Display::return_message($message, 'warning', false)
                );
            }
        } else {
            $message .= PHP_EOL.$plugin->get_lang('TryAgain');

            if ('true' === api_get_setting('allow_lostpassword')) {
                $message .= '<br>'
                    .Display::url(
                        get_lang('LostPassword'),
                        api_get_path(WEB_CODE_PATH).'auth/lostPassword.php',
                        ['target' => $lpItemInfo ? '_top' : '_self']
                    );
            }
        }
    }

    foreach ($qualityNote as $note) {
        $message .= '<br>'.PHP_EOL.$plugin->get_lang("AudioQuality$note");
    }

    echo Display::return_message(
        $message,
        $success ? 'success' : 'warning',
        false
    );

    if (!$success && $maxAttempts && $failedLogins >= $maxAttempts) {
        ChamiloSession::erase(WhispeakAuthPlugin::SESSION_FAILED_LOGINS);

        if (!empty($lpItemInfo)) {
            echo '<script>window.location.href = "'
                .api_get_path(WEB_PLUGIN_PATH)
                .'whispeakauth/authentify_password.php";</script>';

            exit;
        }

        if (!empty($quizQuestionInfo)) {
            $url = api_get_path(WEB_CODE_PATH).'exercise/exercise_submit.php?'.$quizQuestionInfo['url_params'];

            ChamiloSession::write(WhispeakAuthPlugin::SESSION_AUTH_PASSWORD, true);

            echo "<script>window.location.href = '".$url."';</script>";

            exit;
        }

        echo '<script>window.location.href = "'.api_get_path(WEB_PATH).'";</script>';

        exit;
    }

    if ($success) {
        ChamiloSession::erase(WhispeakAuthPlugin::SESSION_SENTENCE_TEXT);
        ChamiloSession::erase(WhispeakAuthPlugin::SESSION_FAILED_LOGINS);

        if (!empty($lpItemInfo)) {
            ChamiloSession::erase(WhispeakAuthPlugin::SESSION_LP_ITEM);
            ChamiloSession::erase(WhispeakAuthPlugin::SESSION_2FA_USER);

            $plugin->addAttemptInLearningPath(
                LogEvent::STATUS_SUCCESS,
                $user->getId(),
                $lpItemInfo['lp_item'],
                $lpItemInfo['lp']
            );

            echo '<script>window.location.href = "'.$lpItemInfo['src'].'";</script>';

            exit;
        }

        if (!empty($quizQuestionInfo)) {
            $quizQuestionInfo['passed'] = true;
            $url = api_get_path(WEB_CODE_PATH).'exercise/exercise_submit.php?'.$quizQuestionInfo['url_params'];

            ChamiloSession::write(WhispeakAuthPlugin::SESSION_QUIZ_QUESTION, $quizQuestionInfo);

            $plugin->addAttemptInQuiz(
                LogEvent::STATUS_SUCCESS,
                $user->getId(),
                $quizQuestionInfo['question'],
                $quizQuestionInfo['quiz']
            );

            echo '<script>window.location.href = "'.$url.'";</script>';

            exit;
        }

        $loggedUser = [
            'user_id' => $user->getId(),
            'status' => $user->getStatus(),
            'uidReset' => true,
        ];

        if (empty($user2fa)) {
            ChamiloSession::write(WhispeakAuthPlugin::SESSION_2FA_USER, $user->getId());
        }

        ChamiloSession::erase(WhispeakAuthPlugin::SESSION_FAILED_LOGINS);
        ChamiloSession::write('_user', $loggedUser);
        Login::init_user($user->getId(), true);

        echo '<script>window.location.href = "'.api_get_path(WEB_PATH).'";</script>';
    }
}
