<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\WhispeakAuth\Controller;

use GuzzleHttp\Client;

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
        $client = new Client();
        $response = $client->get(
            "{$this->apiEndpoint}/enroll",
            [
                'headers' => [
                    'Authorization' => "Bearer {$this->apiKey}",
                ],
                'json' => [],
                'query' => [
                    'lang' => \WhispeakAuthPlugin::getLanguageIsoCode($this->user->getLanguage()),
                ],
            ]);

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
        $client = new Client();
        $response = $client->post(
            "{$this->apiEndpoint}/enroll",
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

        $bodyContents = $response->getBody()->getContents();
        $json = json_decode($bodyContents, true);

        error_log(print_r($json, true));

        switch ($response->getStatusCode()) {
            case 200:
            case 201:
                return $json['speaker'];
            default:
                throw new \Exception($json['message']);
        }
    }
}
