<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;
use Firebase\JWT\JWT;

final class JWTClient extends Client
{
    private const BASE_URL = 'https://api.zoom.us/v2/';

    private string $apiKey;
    private string $apiSecret;

    public function __construct(string $apiKey, string $apiSecret)
    {
        $this->apiKey = trim($apiKey);
        $this->apiSecret = trim($apiSecret);

        self::register($this);
    }

    /**
     * Build a short-lived JWT token.
     *
     * @throws Exception
     */
    public function makeToken(): string
    {
        if ('' === $this->apiKey || '' === $this->apiSecret) {
            throw new Exception('Zoom API credentials are missing.');
        }

        return JWT::encode(
            [
                'iss' => $this->apiKey,
                'exp' => time() + 55,
            ],
            $this->apiSecret,
            'HS256'
        );
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
        $url = self::BASE_URL.ltrim($relativePath, '/');
        if (!empty($parameters)) {
            $url .= '?'.http_build_query($parameters);
        }

        $curl = curl_init($url);
        if (false === $curl) {
            throw new Exception('Could not initialize Zoom API request.');
        }

        $headers = [
            'Authorization: Bearer '.$this->makeToken(),
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

            $options[CURLOPT_POSTFIELDS] = $payload;
            $headers[] = 'Content-Type: application/json';
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
            $message = $this->extractErrorMessage((string) $response);

            throw new Exception('Zoom API returned HTTP '.$statusCode.': '.$message, $statusCode);
        }

        return (string) $response;
    }

    private function extractErrorMessage(string $response): string
    {
        $decoded = json_decode($response, true);
        if (\is_array($decoded) && isset($decoded['message']) && \is_string($decoded['message'])) {
            return $decoded['message'];
        }

        if ('' !== trim($response)) {
            return trim($response);
        }

        return 'Empty response';
    }
}
