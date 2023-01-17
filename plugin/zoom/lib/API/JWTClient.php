<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Firebase\JWT\JWT;

/**
 * Class JWTClient.
 *
 * @see https://marketplace.zoom.us/docs/guides/auth/jwt
 */
class JWTClient extends Client
{
    use Api2RequestTrait;

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
}
