<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\WhispeakAuth\Request;

use Chamilo\UserBundle\Entity\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Class ApiRequest.
 *
 * @package Chamilo\PluginBundle\WhispeakAuth\Request
 */
class ApiRequest
{
    /**
     * @var \WhispeakAuthPlugin
     */
    protected $plugin;
    /**
     * @var string
     */
    protected $apiKey;

    /**
     * BaseController constructor.
     */
    public function __construct()
    {
        $this->plugin = \WhispeakAuthPlugin::create();
        $this->apiKey = $this->plugin->get(\WhispeakAuthPlugin::SETTING_TOKEN);
    }

    /**
     * Create a session token to perform an enrollment.
     *
     * @throws \Exception
     *
     * @return array
     */
    public function createEnrollmentSessionToken(User $user)
    {
        $apiKey = $this->plugin->get(\WhispeakAuthPlugin::SETTING_TOKEN);
        $langIso = api_get_language_isocode($user->getLanguage());

        return $this->sendRequest(
            'get',
            'enroll',
            $apiKey,
            $langIso
        );
    }

    /**
     * @param string $token
     * @param string $audioFilePath
     *
     * @throws \Exception
     *
     * @return array
     */
    public function createEnrollment($token, $audioFilePath, User $user)
    {
        $langIso = api_get_language_isocode($user->getLanguage());

        return $this->sendRequest(
            'post',
            'enroll',
            $token,
            $langIso,
            [
                [
                    'name' => 'file',
                    'contents' => fopen($audioFilePath, 'r'),
                    'filename' => basename($audioFilePath),
                ],
            ]
        );
    }

    /**
     * @throws \Exception
     *
     * @return array
     */
    public function createAuthenticationSessionToken(User $user = null)
    {
        $apiKey = $this->plugin->get(\WhispeakAuthPlugin::SETTING_TOKEN);

        $langIso = api_get_language_isocode($user ? $user->getLanguage() : null);

        return $this->sendRequest(
            'get',
            'auth',
            $apiKey,
            $langIso
        );
    }

    /**
     * @param string $token
     * @param string $audioFilePath
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function performAuthentication($token, User $user, $audioFilePath)
    {
        $wsid = \WhispeakAuthPlugin::getAuthUidValue($user->getId());

        if (empty($wsid)) {
            throw new \Exception($this->plugin->get_lang('SpeechAuthNotEnrolled'));
        }

        $langIso = api_get_language_isocode($user ? $user->getLanguage() : null);

        $this->sendRequest(
            'post',
            'auth',
            $token,
            $langIso,
            [
                [
                    'name' => 'speaker',
                    'contents' => $wsid->getValue(),
                ],
                [
                    'name' => 'file',
                    'contents' => fopen($audioFilePath, 'r'),
                    'filename' => basename($audioFilePath),
                ],
            ]
        );
    }

    /**
     * @param string $method
     * @param string $uri
     * @param string $authBearer
     * @param string $lang
     *
     * @throws \Exception
     *
     * @return array
     */
    private function sendRequest($method, $uri, $authBearer, $lang, array $multipart = [])
    {
        $httpClient = new Client(['base_uri' => $this->plugin->getApiUrl()]);

        try {
            $responseBody = $httpClient
                ->request(
                    $method,
                    $uri,
                    [
                        'headers' => [
                            'Authorization' => "Bearer $authBearer",
                            'Accept-Language' => $lang,
                        ],
                        'multipart' => $multipart,
                    ]
                )
                ->getBody()
                ->getContents();

            return json_decode($responseBody, true);
        } catch (RequestException $requestException) {
            if (!$requestException->hasResponse()) {
                throw new \Exception($requestException->getMessage());
            }

            $responseBody = $requestException->getResponse()->getBody()->getContents();
            $json = json_decode($responseBody, true);

            $message = '';

            if (isset($json['asserts'])) {
                foreach ($json['asserts'] as $assert) {
                    if (in_array($assert['value'], ['valid_audio', 'invalid_audio'])) {
                        $message .= $assert['message'].PHP_EOL;
                    }
                }
            } elseif (empty($json['message'])) {
                $message = $requestException->getMessage();
            } else {
                $message = is_array($json['message']) ? implode(PHP_EOL, $json['message']) : $json['message'];
            }

            throw new \Exception($message);
        } catch (Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }
}
