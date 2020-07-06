<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\WhispeakAuth\Controller;

use GuzzleHttp\Exception\RequestException;

/**
 * Class CreateEnrollmentRequestController.
 *
 * @package Chamilo\PluginBundle\WhispeakAuth\Controller
 */
class CreateEnrollmentRequestController extends BaseRequestController
{
    protected function setUser()
    {
        $this->user = api_get_user_entity(api_get_user_id());
    }

    /**
     * @return bool
     */
    protected function userIsAllowed()
    {
        return !empty($_FILES['audio']);
    }

    /**
     * @throws \Exception
     */
    protected function protect()
    {
        api_block_anonymous_users(false);

        parent::protect();
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     *
     * @return string
     */
    protected function doApiRequest()
    {
        $token = $this->createSessionToken();
        $speaker = $this->createEnrollment($token);

        $this->plugin->saveEnrollment($this->user, $speaker);

        $message = '<strong>'.$this->plugin->get_lang('EnrollmentSuccess').'</strong>'.PHP_EOL;

        return \Display::return_message($message, 'success', false);
    }

    /**
     * Create a session token to perform an enrollment.
     *
     * @throws \Exception
     *
     * @return string
     */
    private function createSessionToken()
    {
        try {
            $response = $this->httpClient->get(
                'enroll',
                [
                    'headers' => [
                        'Authorization' => "Bearer {$this->apiKey}",
                    ],
                    'json' => [],
                    'query' => [
                        'lang' => api_get_language_isocode($this->user->getLanguage()),
                    ],
                ]
            );
            $json = json_decode((string) $response->getBody(), true);

            return $json['token'];
        } catch (RequestException $requestException) {
            $this->throwRequestException(
                $requestException,
                $this->plugin->get_lang('EnrollmentFailed')
            );
        }
    }

    /**
     * Create a signature associated to a configuration.
     *
     * @param string $token
     *
     * @throws \Exception
     *
     * @return string
     */
    private function createEnrollment($token)
    {
        try {
            $response = $this->httpClient->post(
                'enroll',
                [
                    'headers' => [
                        'Authorization' => "Bearer $token",
                    ],
                    'multipart' => [
                        [
                            'name' => 'file',
                            'contents' => fopen($this->audioFilePath, 'r'),
                            'filename' => basename($this->audioFilePath),
                        ],
                    ],
                ]
            );
            $json = json_decode((string) $response->getBody(), true);

            return $json['speaker'];
        } catch (RequestException $requestException) {
            $this->throwRequestException(
                $requestException,
                $this->plugin->get_lang('EnrollmentFailed')
            );
        }
    }
}
