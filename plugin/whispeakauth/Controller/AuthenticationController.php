<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\WhispeakAuth\Controller;

use Chamilo\PluginBundle\Entity\WhispeakAuth\LogEvent;
use Chamilo\PluginBundle\WhispeakAuth\Request\ApiRequest;
use Chamilo\UserBundle\Entity\User;
use ChamiloSession;
use Display;
use Login;
use WhispeakAuthPlugin;

/**
 * Class AuthenticationController.
 *
 * @package Chamilo\PluginBundle\WhispeakAuth\Controller
 */
class AuthenticationController extends BaseController
{
    /**
     * @throws \Exception
     */
    public function index()
    {
        if (!$this->plugin->toolIsEnabled()) {
            throw new \Exception(get_lang('NotAllowed'));
        }

        /** @var array $lpQuestionInfo */
        $lpQuestionInfo = ChamiloSession::read(WhispeakAuthPlugin::SESSION_QUIZ_QUESTION, []);

        if (ChamiloSession::read(WhispeakAuthPlugin::SESSION_AUTH_PASSWORD, false)) {
            ChamiloSession::erase(WhispeakAuthPlugin::SESSION_AUTH_PASSWORD);

            if (empty($lpQuestionInfo)) {
                $message = $this->plugin->get_lang('MaxAttemptsReached')
                    .'<br><strong>'.$this->plugin->get_lang('LoginWithUsernameAndPassword').'</strong>';

                Display::addFlash(
                    Display::return_message($message, 'warning')
                );
            }

            header('Location: '.api_get_path(WEB_PLUGIN_PATH).'whispeakauth/authentify_password.php');
            exit;
        }

        /** @var array $lpItemInfo */
        $lpItemInfo = ChamiloSession::read(WhispeakAuthPlugin::SESSION_LP_ITEM, []);
        /** @var \learnpath $oLp */
        $oLp = ChamiloSession::read('oLP', null);
        /** @var \Exercise $objExercise */
        $objExercise = ChamiloSession::read('objExercise', null);

        $isAuthOnLp = !empty($lpItemInfo) && !empty($oLp);
        $isAuthOnQuiz = !empty($lpQuestionInfo) && !empty($objExercise);
        $showFullPage = !$isAuthOnLp && !$isAuthOnQuiz;

        $user = api_get_user_entity(
            ChamiloSession::read(WhispeakAuthPlugin::SESSION_2FA_USER, 0) ?: api_get_user_id()
        );

        $showForm = !$user;

        if ($user) {
            if (!WhispeakAuthPlugin::getAuthUidValue($user)) {
                $message = Display::return_message($this->plugin->get_lang('SpeechAuthNotEnrolled'), 'warning');

                if (!empty($lpQuestionInfo) && empty($lpItemInfo)) {
                    echo $message;
                } else {
                    Display::addFlash($message);
                }

                header('Location: '.api_get_path(WEB_PLUGIN_PATH).'whispeakauth/authentify_password.php');

                exit;
            }
        }

        if (!empty($lpQuestionInfo) && empty($lpItemInfo)) {
            echo api_get_js('rtc/RecordRTC.js');
            echo api_get_js_simple(api_get_path(WEB_PLUGIN_PATH).'whispeakauth/assets/js/RecordAudio.js');
        }

        $request = new ApiRequest();
        $response = $request->createAuthenticationSessionToken($user);

        if (empty($response['text'])) {
            $varNumber = mt_rand(1, 6);
            $response['text'] = $this->plugin->get_lang("AuthentifySampleText$varNumber");
        }

        ChamiloSession::write(WhispeakAuthPlugin::SESSION_SENTENCE_TEXT, $response['token']);

        if (!empty($lpQuestionInfo) && empty($lpItemInfo)) {
            $template = new \Template('', $showFullPage, $showFullPage, false, true, false);
            $template->assign('show_form', $showForm);
            $template->assign('sample_text', $response['text']);

            echo $template->fetch('whispeakauth/view/authentify_recorder.html.twig');
            exit;
        }

        $this->displayPage(
            $showFullPage,
            [
                'show_form' => $showForm,
                'sample_text' => $response['text'],
            ]
        );
    }

    /**
     * @throws \Exception
     */
    public function ajax()
    {
        $userId = api_get_user_id();
        $user2fa = ChamiloSession::read(WhispeakAuthPlugin::SESSION_2FA_USER, 0);

        $result = [];

        if (!empty($user2fa) || !empty($userId)) {
            $isAllowed = !empty($_FILES['audio']);
        } else {
            $isAllowed = !empty($_POST['username']) && !empty($_FILES['audio']);
        }

        if (!$isAllowed || !$this->plugin->toolIsEnabled()) {
            throw new \Exception(get_lang('NotAllowed'));
        }

        if (!empty($user2fa)) {
            $user = api_get_user_entity($user2fa);
        } elseif (!empty($userId)) {
            $user = api_get_user_entity($userId);
        } else {
            /** @var User|null $user */
            $user = \UserManager::getRepository()->findOneBy(['username' => $_POST['username']]);
        }

        if (!$user) {
            throw new \Exception(get_lang('NotFound'));
        }

        $audioFilePath = $this->uploadAudioFile($user);

        $failedLogins = ChamiloSession::read(WhispeakAuthPlugin::SESSION_FAILED_LOGINS, 0);
        $maxAttempts = $this->plugin->getMaxAttempts();

        if ($maxAttempts && $failedLogins >= $maxAttempts) {
            throw new \Exception($this->plugin->get_lang('MaxAttemptsReached'));
        }

        $token = \ChamiloSession::read(\WhispeakAuthPlugin::SESSION_SENTENCE_TEXT);

        \ChamiloSession::erase(\WhispeakAuthPlugin::SESSION_SENTENCE_TEXT);

        /** @var array $lpItemInfo */
        $lpItemInfo = ChamiloSession::read(WhispeakAuthPlugin::SESSION_LP_ITEM, []);
        /** @var array $quizQuestionInfo */
        $quizQuestionInfo = ChamiloSession::read(WhispeakAuthPlugin::SESSION_QUIZ_QUESTION, []);

        $success = true;

        $request = new ApiRequest();

        try {
            $request->performAuthentication($token, $user, $audioFilePath);

            $message = $this->plugin->get_lang('AuthentifySuccess');
        } catch (\Exception $exception) {
            $message = $this->plugin->get_lang('AuthentifyFailed')
                .PHP_EOL
                .$exception->getMessage();

            $success = false;
        }

        if (!$success) {
            if (!empty($lpItemInfo)) {
                $this->plugin->addAttemptInLearningPath(
                    LogEvent::STATUS_FAILED,
                    $user->getId(),
                    $lpItemInfo['lp_item'],
                    $lpItemInfo['lp']
                );
            }

            if (!empty($quizQuestionInfo)) {
                $this->plugin->addAttemptInQuiz(
                    LogEvent::STATUS_FAILED,
                    $user->getId(),
                    $quizQuestionInfo['question'],
                    $quizQuestionInfo['quiz']
                );
            }

            if (empty($lpItemInfo) && empty($quizQuestionInfo)) {
                $this->plugin->addAuthenticationAttempt(LogEvent::STATUS_FAILED, $user->getId());
            }

            $authTokenRequest = new ApiRequest();
            $authTokenResponse = $authTokenRequest->createAuthenticationSessionToken($user);

            if (empty($authTokenResponse['text'])) {
                $varNumber = mt_rand(1, 6);
                $authTokenResponse['text'] = $this->plugin->get_lang("AuthentifySampleText$varNumber");
            }

            $result['text'] = $authTokenResponse['text'];

            ChamiloSession::write(WhispeakAuthPlugin::SESSION_SENTENCE_TEXT, $authTokenResponse['token']);

            ChamiloSession::write(WhispeakAuthPlugin::SESSION_FAILED_LOGINS, ++$failedLogins);

            if ($maxAttempts && $failedLogins >= $maxAttempts) {
                $message .= PHP_EOL
                    .'<span data-reach-attempts="true">'.$this->plugin->get_lang('MaxAttemptsReached').'</span>'
                    .PHP_EOL.PHP_EOL
                    .'<strong>'
                    .$this->plugin->get_lang('LoginWithUsernameAndPassword')
                    .'</strong>';

                if (!empty($user2fa)) {
                    Display::addFlash(
                        Display::return_message($message, 'warning', false)
                    );
                }
            } else {
                $message .= PHP_EOL.$this->plugin->get_lang('TryAgain');

                if ('true' === api_get_setting('allow_lostpassword')) {
                    $message .= PHP_EOL
                        .Display::url(
                            get_lang('LostPassword'),
                            api_get_path(WEB_CODE_PATH).'auth/lostPassword.php',
                            ['target' => $lpItemInfo ? '_top' : '_self']
                        );
                }
            }
        }

        $result['resultHtml'] = Display::return_message(
            nl2br($message),
            $success ? 'success' : 'warning',
            false
        );

        if (!$success && $maxAttempts && $failedLogins >= $maxAttempts) {
            ChamiloSession::erase(WhispeakAuthPlugin::SESSION_FAILED_LOGINS);

            if (!empty($lpItemInfo)) {
                $result['resultHtml'] .= '<script>window.location.href = "'
                    .api_get_path(WEB_PLUGIN_PATH)
                    .'whispeakauth/authentify_password.php";</script>';

                return $result;
            }

            if (!empty($quizQuestionInfo)) {
                $url = api_get_path(WEB_CODE_PATH).'exercise/exercise_submit.php?'.$quizQuestionInfo['url_params'];

                ChamiloSession::write(WhispeakAuthPlugin::SESSION_AUTH_PASSWORD, true);

                $result['resultHtml'] .= "<script>window.location.href = '".$url."';</script>";

                return $result;
            }

            $result['resultHtml'] .= '<script>window.location.href = "'.api_get_path(WEB_PATH).'";</script>';

            return $result;
        }

        if ($success) {
            ChamiloSession::erase(WhispeakAuthPlugin::SESSION_SENTENCE_TEXT);
            ChamiloSession::erase(WhispeakAuthPlugin::SESSION_FAILED_LOGINS);

            if (!empty($lpItemInfo)) {
                ChamiloSession::erase(WhispeakAuthPlugin::SESSION_LP_ITEM);
                ChamiloSession::erase(WhispeakAuthPlugin::SESSION_2FA_USER);

                $this->plugin->addAttemptInLearningPath(
                    LogEvent::STATUS_SUCCESS,
                    $user->getId(),
                    $lpItemInfo['lp_item'],
                    $lpItemInfo['lp']
                );

                $result['resultHtml'] .= '<script>window.location.href = "'.$lpItemInfo['src'].'";</script>';

                return $result;
            }

            if (!empty($quizQuestionInfo)) {
                $quizQuestionInfo['passed'] = true;
                $url = api_get_path(WEB_CODE_PATH).'exercise/exercise_submit.php?'.$quizQuestionInfo['url_params'];

                ChamiloSession::write(WhispeakAuthPlugin::SESSION_QUIZ_QUESTION, $quizQuestionInfo);

                $this->plugin->addAttemptInQuiz(
                    LogEvent::STATUS_SUCCESS,
                    $user->getId(),
                    $quizQuestionInfo['question'],
                    $quizQuestionInfo['quiz']
                );

                $result['resultHtml'] .= '<script>window.location.href = "'.$url.'";</script>';

                return $result;
            }

            if (empty($lpItemInfo) && empty($quizQuestionInfo)) {
                $this->plugin->addAuthenticationAttempt(LogEvent::STATUS_SUCCESS, $user->getId());
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

            $result['resultHtml'] .= '<script>window.location.href = "'.api_get_path(WEB_PATH).'";</script>';

            return $result;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function displayPage($isFullPage, array $variables)
    {
        global $htmlHeadXtra;

        $htmlHeadXtra[] = api_get_js('rtc/RecordRTC.js');
        $htmlHeadXtra[] = api_get_js_simple(api_get_path(WEB_PLUGIN_PATH).'whispeakauth/assets/js/RecordAudio.js');

        $pageTitle = $this->plugin->get_title();

        $template = new \Template($pageTitle, $isFullPage, $isFullPage, !$isFullPage);

        foreach ($variables as $key => $value) {
            $template->assign($key, $value);
        }

        $pageContent = $template->fetch('whispeakauth/view/authentify_recorder.html.twig');

        $template->assign('header', $pageTitle);
        $template->assign('content', $pageContent);
        $template->display_one_col_template();
    }
}
