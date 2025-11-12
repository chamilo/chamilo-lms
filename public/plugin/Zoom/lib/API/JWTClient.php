<?php
declare(strict_types=1);

namespace Chamilo\PluginBundle\Zoom\API;

use Firebase\JWT\JWT;

final class JWTClient
{
    /** @var string */
    private string $apiKey;
    /** @var string */
    private string $apiSecret;

    /**
     * Do NOT sign in the constructor. Just store normalized strings.
     */
    public function __construct(string $apiKey, string $apiSecret)
    {
        // Normalize to strings; trim to avoid accidental spaces.
        $this->apiKey    = trim((string) $apiKey);
        $this->apiSecret = trim((string) $apiSecret);
    }

    /**
     * Build a short-lived JWT token. HMAC key MUST be a string.
     * Throws InvalidArgumentException when config is missing.
     */
    public function makeToken(): string
    {
        if ($this->apiKey === '' || $this->apiSecret === '') {
            throw new \InvalidArgumentException('Zoom JWT: missing API Key/Secret');
        }

        $payload = [
            'iss' => $this->apiKey,
            'exp' => time() + 55, // short-lived token
        ];

        return JWT::encode($payload, $this->apiSecret, 'HS256');
    }
}
