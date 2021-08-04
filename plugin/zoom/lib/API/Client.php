<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Interface Client.
 * Two implementations are currently possible : OAuth and JWT.
 *
 * @see https://marketplace.zoom.us/docs/api-reference/zoom-api
 */
abstract class Client
{
    /** @var Client */
    private static $instance;

    /**
     * Returns an initialized Client.
     *
     * @return Client
     */
    public static function getInstance()
    {
        return self::$instance;
    }

    /**
     * Sends a Zoom API-compliant HTTP request and retrieves the response.
     *
     * On success, returns the body of the response
     * On error, throws an exception with an detailed error message
     *
     * @param string $httpMethod   GET, POST, PUT, DELETE ...
     * @param string $relativePath to append to https://api.zoom.us/v2/
     * @param array  $parameters   request query parameters
     * @param object $requestBody  json-encoded body of the request
     *
     * @throws Exception describing the error (message and code)
     *
     * @return string response body (not json-decoded)
     */
    abstract public function send($httpMethod, $relativePath, $parameters = [], $requestBody = null);

    /**
     * Registers an initialized Client.
     *
     * @param Client $instance
     */
    protected static function register($instance)
    {
        self::$instance = $instance;
    }
}
