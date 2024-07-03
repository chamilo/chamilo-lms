<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

class ServerToServerOAuthClient extends Client
{
    use Api2RequestTrait;

    /**
     * @var string
     */
    public $token;

    public function __construct(string $accountId, string $clientId, string $clientSecret)
    {
        try {
            $this->token = $this->requireAccessToken($accountId, $clientId, $clientSecret);
        } catch (Exception $e) {
            error_log('Zoom: Can\'t require access token: '.$e->getMessage());
        }

        self::register($this);
    }

    private function requireAccessToken(string $accountId, string $clientId, string $clientSecret)
    {
        $options = [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic '.base64_encode("$clientId:$clientSecret"),
                'Content-Type: application/x-www-form-urlencoded',
                'Host: zoom.us',
            ],
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'account_credentials',
                'account_id' => $accountId,
            ]),
        ];

        $url = 'https://zoom.us/oauth/token';

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

        if (empty($responseCode) || $responseCode < 200 || $responseCode >= 300) {
            throw new Exception($responseBody, $responseCode);
        }

        $jsonResponseBody = json_decode($responseBody, true);

        if (false === $jsonResponseBody) {
            throw new Exception('Could not generate JSON responso body');
        }

        return $jsonResponseBody['access_token'];
    }
}
