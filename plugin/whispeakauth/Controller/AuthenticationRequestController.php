<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\WhispeakAuth\Controller;

use Chamilo\PluginBundle\Entity\WhispeakAuth\LogEvent;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
 * Class AuthenticationRequestController.
 *
 * @package Chamilo\PluginBundle\WhispeakAuth\Controller
 */
class AuthenticationRequestController extends BaseRequestController
{
    /**
     * @var int
     */
    private $user2fa;

    protected function setUser()
    {
        if (!empty($this->user2fa)) {
            $this->user = api_get_user_entity($this->user2fa);
        } elseif (isset($_POST['username'])) {
            $this->user = \UserManager::getRepository()->findOneBy(['username' => $_POST['username']]);
        } else {
            $this->user = api_get_user_entity(api_get_user_id());
        }
    }

    /**
     * @return bool
     */
    protected function userIsAllowed()
    {
        $userId = api_get_user_id();
        $this->user2fa = \ChamiloSession::read(\WhispeakAuthPlugin::SESSION_2FA_USER, 0);

        if (!empty($this->user2fa) || !empty($userId)) {
            return !empty($_FILES['audio']);
        }

        return !empty($_POST['username']) && !empty($_FILES['audio']);
    }

    /**
     * @throws \Exception
     *
     * @return string
     */
    protected function doApiRequest()
    {
        $failedLogins = \ChamiloSession::read(\WhispeakAuthPlugin::SESSION_FAILED_LOGINS, 0);
        $maxAttempts = $this->plugin->getMaxAttempts();

        if ($maxAttempts && $failedLogins >= $maxAttempts) {
            return \Display::return_message($this->plugin->get_lang('MaxAttemptsReached'), 'warning');
        }

        $wsId = \WhispeakAuthPlugin::getAuthUidValue($this->user->getId());

        if (empty($wsId)) {
            return \Display::return_message($this->plugin->get_lang('SpeechAuthNotEnrolled'), 'warning');
        }

        $token = $this->createSessionToken();
        $success = $this->performAuthentication($token, $wsId->getValue());

        /** @var array $lpItemInfo */
        $lpItemInfo = \ChamiloSession::read(\WhispeakAuthPlugin::SESSION_LP_ITEM, []);
        /** @var array $quizQuestionInfo */
        $quizQuestionInfo = \ChamiloSession::read(\WhispeakAuthPlugin::SESSION_QUIZ_QUESTION, []);

        $return = '';

        $message = $this->plugin->get_lang('AuthentifySuccess');

        if (!$success) {
            if (!empty($lpItemInfo)) {
                $this->plugin->addAttemptInLearningPath(
                    LogEvent::STATUS_FAILED,
                    $this->user->getId(),
                    $lpItemInfo['lp_item'],
                    $lpItemInfo['lp']
                );
            }

            if (!empty($quizQuestionInfo)) {
                $this->plugin->addAttemptInQuiz(
                    LogEvent::STATUS_FAILED,
                    $this->user->getId(),
                    $quizQuestionInfo['question'],
                    $quizQuestionInfo['quiz']
                );
            }

            $message = $this->plugin->get_lang('AuthentifyFailed');

            \ChamiloSession::write(\WhispeakAuthPlugin::SESSION_FAILED_LOGINS, ++$failedLogins);

            if ($maxAttempts && $failedLogins >= $maxAttempts) {
                $message .= PHP_EOL
                    .'<span data-reach-attempts="true">'.$this->plugin->get_lang('MaxAttemptsReached').'</span>'
                    .PHP_EOL
                    .'<br><strong>'
                    .$this->plugin->get_lang('LoginWithUsernameAndPassword')
                    .'</strong>';

                if (!empty($user2fa)) {
                    \Display::addFlash(\Display::return_message($message, 'warning', false));
                }
            } else {
                $message .= PHP_EOL.$this->plugin->get_lang('TryAgain');

                if ('true' === api_get_setting('allow_lostpassword')) {
                    $message .= '<br>'
                        .\Display::url(
                            get_lang('LostPassword'),
                            api_get_path(WEB_CODE_PATH).'auth/lostPassword.php',
                            ['target' => $lpItemInfo ? '_top' : '_self']
                        );
                }
            }
        }

        $return .= \Display::return_message(
            $message,
            $success ? 'success' : 'warning',
            false
        );

        if (!$success && $maxAttempts && $failedLogins >= $maxAttempts) {
            \ChamiloSession::erase(\WhispeakAuthPlugin::SESSION_FAILED_LOGINS);

            if (!empty($lpItemInfo)) {
                $return .= '<script>window.location.href = "'
                    .api_get_path(WEB_PLUGIN_PATH)
                    .'whispeakauth/authentify_password.php";</script>';

                return $return;
            }

            if (!empty($quizQuestionInfo)) {
                $url = api_get_path(WEB_CODE_PATH).'exercise/exercise_submit.php?'.$quizQuestionInfo['url_params'];

                \ChamiloSession::write(\WhispeakAuthPlugin::SESSION_AUTH_PASSWORD, true);

                $return .= "<script>window.location.href = '".$url."';</script>";

                exit;
            }

            $return .= '<script>window.location.href = "'.api_get_path(WEB_PATH).'";</script>';

            return $return;
        }

        if ($success) {
            \ChamiloSession::erase(\WhispeakAuthPlugin::SESSION_SENTENCE_TEXT);
            \ChamiloSession::erase(\WhispeakAuthPlugin::SESSION_FAILED_LOGINS);

            if (!empty($lpItemInfo)) {
                \ChamiloSession::erase(\WhispeakAuthPlugin::SESSION_LP_ITEM);
                \ChamiloSession::erase(\WhispeakAuthPlugin::SESSION_2FA_USER);

                $this->plugin->addAttemptInLearningPath(
                    LogEvent::STATUS_SUCCESS,
                    $this->user->getId(),
                    $lpItemInfo['lp_item'],
                    $lpItemInfo['lp']
                );

                $return .= '<script>window.location.href = "'.$lpItemInfo['src'].'";</script>';

                return $return;
            }

            if (!empty($quizQuestionInfo)) {
                $quizQuestionInfo['passed'] = true;
                $url = api_get_path(WEB_CODE_PATH).'exercise/exercise_submit.php?'.$quizQuestionInfo['url_params'];

                \ChamiloSession::write(\WhispeakAuthPlugin::SESSION_QUIZ_QUESTION, $quizQuestionInfo);

                $this->plugin->addAttemptInQuiz(
                    LogEvent::STATUS_SUCCESS,
                    $this->user->getId(),
                    $quizQuestionInfo['question'],
                    $quizQuestionInfo['quiz']
                );

                $return .= '<script>window.location.href = "'.$url.'";</script>';

                return $return;
            }

            $loggedUser = [
                'user_id' => $this->user->getId(),
                'status' => $this->user->getStatus(),
                'uidReset' => true,
            ];

            if (empty($user2fa)) {
                \ChamiloSession::write(\WhispeakAuthPlugin::SESSION_2FA_USER, $this->user->getId());
            }

            \ChamiloSession::erase(\WhispeakAuthPlugin::SESSION_FAILED_LOGINS);
            \ChamiloSession::write('_user', $loggedUser);
            \Login::init_user($this->user->getId(), true);

            $return .= '<script>window.location.href = "'.api_get_path(WEB_PATH).'";</script>';
        }

        return $return;
    }

    /**
     * @throws \Exception
     *
     * @return string
     */
    private function createSessionToken()
    {
        $client = new Client();
        $response = $client->get(
            "{$this->apiEndpoint}/auth",
            [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                ],
                'json' => [],
                'query' => [
                    'lang' => \WhispeakAuthPlugin::getLanguageIsoCode($this->user->getLanguage()),
                ],
            ]
        );

        $bodyContents = $response->getBody()->getContents();
        $json = json_decode($bodyContents, true);

        switch ($response->getStatusCode()) {
            case 200:
                return $json['token'];
            case 400:
            case 401:
            case 403:
                throw new \Exception($json['message']);
        }
    }

    /**
     * @param string $token
     * @param string $wsId
     *
     * @throws \Exception
     *
     * @return bool
     */
    private function performAuthentication($token, $wsId)
    {
        $client = new Client();
        $response = $client->post(
            "{$this->apiEndpoint}/auth",
            [
                'headers' => [
                    'Authorization' => "Bearer $token",
                ],
                'multipart' => [
                    [
                        'name' => 'speaker',
                        'contents' => $wsId,
                    ],
                    [
                        'name' => 'file',
                        'contents' => fopen($this->audioFilePath, 'r'),
                        'filename' => basename($this->audioFilePath),
                    ],
                ],
            ]
        );

        $bodyContents = $response->getBody()->getContents();
        $json = json_decode($bodyContents, true);

        switch ($response->getStatusCode()) {
            case 200:
                return true;
            case 419:
                throw new \Exception($this->plugin->get_lang('TryAgain'));
            default:
                throw new \Exception($json['message']);
        }
    }
}
