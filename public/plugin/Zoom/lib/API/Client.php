<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Base Zoom API client.
 */
abstract class Client
{
    private static ?Client $instance = null;

    /**
     * Returns an initialized Client.
     *
     * @throws Exception
     */
    public static function getInstance(): Client
    {
        if (!self::$instance instanceof Client) {
            throw new Exception('Zoom API client is not configured.');
        }

        return self::$instance;
    }

    /**
     * Sends a Zoom API-compliant HTTP request and retrieves the response.
     *
     * @throws Exception
     */
    abstract public function send(
        string $httpMethod,
        string $relativePath,
        array $parameters = [],
        object|array|null $requestBody = null
    ): string;

    protected static function register(Client $instance): void
    {
        self::$instance = $instance;
    }
}
