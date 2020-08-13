<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;
use Firebase\JWT\JWT;

/**
 * Class JWTClient.
 *
 * @see https://marketplace.zoom.us/docs/guides/auth/jwt
 */
class JWTClient extends Client
{
    public $token;

    /**
     * JWTClient constructor.
     * Requires JWT app credentials.
     *
     * @param string $apiKey    JWT API Key
     * @param string $apiSecret JWT API Secret
     */
    public function __construct($apiKey, $apiSecret)
    {
        $this->token = JWT::encode(
            [
                'iss' => $apiKey,
                'exp' => (time() + 60) * 1000, // will expire in one minute
            ],
            $apiSecret
        );
        self::register($this);
    }

    /**
     * {@inheritdoc}
     */
    public function send($httpMethod, $relativePath, $parameters = [], $requestBody = null)
    {
        $options = [
            CURLOPT_CUSTOMREQUEST => $httpMethod,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => [
                'authorization: Bearer '.$this->token,
                'content-type: application/json',
            ],
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ];
        if (!is_null($requestBody)) {
            $jsonRequestBody = json_encode($requestBody);
            if (false === $jsonRequestBody) {
                throw new Exception('Could not generate JSON request body');
            }
            $options[CURLOPT_POSTFIELDS] = $jsonRequestBody;
        }

        $url = "https://api.zoom.us/v2/$relativePath";
        if (!empty($parameters)) {
            $url .= '?'.http_build_query($parameters);
        }
        $curl = curl_init($url);
        if (false === $curl) {
            throw new Exception("curl_init returned false");
        }
        curl_setopt_array($curl, $options);
        $responseBody = curl_exec($curl);
        $responseCode = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($curlError) {
            throw new Exception("cURL Error: $curlError");
        }

        if (false === $responseBody || !is_string($responseBody)) {
            throw new Exception('cURL Error');
        }

        if (empty($responseCode)
            || $responseCode < 200
            || $responseCode >= 300
        ) {
            throw new Exception($responseBody, $responseCode);
        }

        return $responseBody;
    }
}
