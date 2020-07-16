<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom\API;

use Exception;

/**
 * Interface Client.
 * Two implementations are currently possible : OAuth and JWT.
 *
 * @see https://marketplace.zoom.us/docs/api-reference/zoom-api
 *
 * @package Chamilo\PluginBundle\Zoom\API
 */
interface Client
{
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
    public function send($httpMethod, $relativePath, $parameters = [], $requestBody = null);
}
