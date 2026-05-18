<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

final class OAuthClient extends Client
{
    private const API_BASE_URL = 'https://api.zoom.us/v2/';
    private const TOKEN_URL = 'https://zoom.us/oauth/token';

    private string $accountId;
    private string $clientId;
    private string $clientSecret;
    private ?string $accessToken = null;
    private int $expiresAt = 0;

    public function __construct(string $accountId, string $clientId, string $clientSecret)
    {
        $this->accountId = trim($accountId);
        $this->clientId = trim($clientId);
        $this->clientSecret = trim($clientSecret);

        self::register($this);
    }

    /**
     * @throws Exception
     */
    public function getAccessToken(): string
    {
        if ('' !== (string) $this->accessToken && time() < $this->expiresAt - 60) {
            return (string) $this->accessToken;
        }

        if ('' === $this->accountId || '' === $this->clientId || '' === $this->clientSecret) {
            throw new Exception('Zoom Server-to-Server OAuth credentials are missing.');
        }

        $url = self::TOKEN_URL.'?'.http_build_query([
            'grant_type' => 'account_credentials',
            'account_id' => $this->accountId,
        ]);

        $curl = curl_init($url);
        if (false === $curl) {
            throw new Exception('Could not initialize Zoom OAuth token request.');
        }

        curl_setopt_array(
            $curl,
            [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Basic '.base64_encode($this->clientId.':'.$this->clientSecret),
                    'Accept: application/json',
                ],
                CURLOPT_TIMEOUT => 30,
            ]
        );

        $response = curl_exec($curl);
        if (false === $response) {
            $error = curl_error($curl);
            curl_close($curl);

            throw new Exception('Zoom OAuth token request failed: '.$error);
        }

        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $data = json_decode((string) $response, true);
        if (200 !== $statusCode) {
            throw new Exception('Zoom OAuth token request returned HTTP '.$statusCode.': '.$this->extractErrorMessage((string) $response));
        }

        if (!\is_array($data) || empty($data['access_token']) || !\is_string($data['access_token'])) {
            throw new Exception('Zoom OAuth token response did not include an access token.');
        }

        $expiresIn = isset($data['expires_in']) ? (int) $data['expires_in'] : 3600;
        $this->accessToken = $data['access_token'];
        $this->expiresAt = time() + max(60, $expiresIn);

        return $this->accessToken;
    }

    /**
     * @throws Exception
     */
    public function send(
        string $httpMethod,
        string $relativePath,
        array $parameters = [],
        object|array|null $requestBody = null
    ): string {
        $url = self::API_BASE_URL.ltrim($relativePath, '/');
        if (!empty($parameters)) {
            $url .= '?'.http_build_query($parameters);
        }

        $curl = curl_init($url);
        if (false === $curl) {
            throw new Exception('Could not initialize Zoom API request.');
        }

        $headers = [
            'Authorization: Bearer '.$this->getAccessToken(),
            'Accept: application/json',
        ];

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($httpMethod),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
        ];

        if (null !== $requestBody) {
            $payload = json_encode($requestBody);
            if (false === $payload) {
                throw new Exception('Could not encode Zoom API request body.');
            }

            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_POSTFIELDS] = $payload;
            $options[CURLOPT_HTTPHEADER] = $headers;
        }

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        if (false === $response) {
            $error = curl_error($curl);
            curl_close($curl);

            throw new Exception('Zoom API request failed: '.$error);
        }

        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new Exception('Zoom API returned HTTP '.$statusCode.': '.$this->extractErrorMessage((string) $response), $statusCode);
        }

        return (string) $response;
    }

    private function extractErrorMessage(string $response): string
    {
        $decoded = json_decode($response, true);
        if (\is_array($decoded)) {
            if (isset($decoded['message']) && \is_string($decoded['message'])) {
                return $decoded['message'];
            }

            if (isset($decoded['reason']) && \is_string($decoded['reason'])) {
                return $decoded['reason'];
            }

            if (isset($decoded['error']) && \is_string($decoded['error'])) {
                return $decoded['error'];
            }
        }

        if ('' !== trim($response)) {
            return trim($response);
        }

        return 'Empty response';
    }
}
