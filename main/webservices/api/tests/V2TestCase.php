<?php

/* For licensing terms, see /license.txt */

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../../vendor/autoload.php';
require_once __DIR__.'/../../../inc/global.inc.php';

/**
 * Class V2Test
 *
 * Base class for all WebService API v2 tests
 */
abstract class V2TestCase extends TestCase
{
    const WEBSERVICE_USERNAME = 'admin';
    const WEBSERVICE_PASSWORD = 'admin';
    const RELATIVE_URI = 'webservices/api/v2.php';
    /**
     * @var Client $client
     */
    private $client;
    private $apiKey;

    /**
     * Initialises the HTTP client and retrieves the API key from the server
     *
     * @throws Exception when it cannot get the API key
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new Client(
            [
                'base_uri' => api_get_path(WEB_CODE_PATH),
            ]
        );

        $response = $this->client->post(
            self::RELATIVE_URI,
            [
                'form_params' => [
                    'action' => 'authenticate',
                    'username' => self::WEBSERVICE_USERNAME,
                    'password' => self::WEBSERVICE_PASSWORD,
                ],
            ]
        );

        if (200 === $response->getStatusCode()) {
            $decodedResponse = json_decode($response->getBody()->getContents(), false, 3, JSON_THROW_ON_ERROR);

            if (is_object($decodedResponse)) {
                $this->apiKey = $decodedResponse->data->apiKey;
            } else {
                throw new Exception('The returned JSON document is not an object');
            }
        } else {
            throw new Exception($response->getReasonPhrase());
        }
    }

    /**
     * Posts an action request and assert the server returns an error message
     *
     * @param array $parameters parameters to send with the request
     *
     * @return string                   the "message" error string returned by the webservice
     */
    protected function errorMessageString($parameters = [])
    {
        $decodedResponse = $this->decodedResponse($parameters);
        $this->assertIsObject($decodedResponse);
        $this->assertTrue(property_exists($decodedResponse, 'error'));
        $error = $decodedResponse->error;
        $this->assertIsBool(true, $error);
        $this->assertTrue($error, 'error is not true: '.print_r($decodedResponse, true));
        $this->assertTrue(property_exists($decodedResponse, 'message'));
        $message = $decodedResponse->message;
        $this->assertIsString($message);

        return $message;
    }

    /**
     * Posts an action request to the web server, asserts it returns a valid JSON-encoded response and decodes it
     * supplied parameters complete or override the generated base parameters username, api_key and action
     *
     * @param array $parameters parameters to send with the request as an associative array
     *
     * @return mixed                    the decoded response (usually an object with properties data, error, message)
     */
    protected function decodedResponse($parameters = [])
    {
        $baseParams = [
            'username' => self::WEBSERVICE_USERNAME,
            'api_key' => $this->apiKey,
            'action' => $this->action(),
        ];

        $response = $this->client->post(self::RELATIVE_URI, ['form_params' => array_merge($baseParams, $parameters)]);

        $this->assertNotNull($response);
        $this->assertSame(200, $response->getStatusCode());

        $decodedResponse = json_decode($response->getBody()->getContents());

        // Help debug
        if (null === $decodedResponse) {
            var_dump($this->action(), $response->getBody()->getContents());
        }

        $this->assertNotNull($decodedResponse);

        return $decodedResponse;
    }

    /**
     * returns the name of the webservice, to be passed as the "action" with the HTTP request
     *
     * @return string    name of the webservice action to call
     */
    abstract protected function action();

    /**
     * Posts an action request and assert it returns an integer value in the "data" property
     *
     * @param array $parameters parameters to send with the request
     *
     * @return integer                  the integer value
     */
    protected function integer($parameters = [])
    {
        $value = $this->singleElementValue($parameters);
        $this->assertIsInt($value);

        return $value;
    }

    /**
     * Posts an action request and assert the server returns a single value in the "data" array of the returned object
     *
     * @param array $parameters parameters to send with the request
     *
     * @return mixed                    the unique element of the "data" array
     */
    protected function singleElementValue($parameters = [])
    {
        $data = $this->dataArray($parameters);
        $this->assertSame(1, count($data));

        return $data[0];
    }

    /**
     * Posts an action request and assert the server returns a "data" array
     *
     * @param array $parameters parameters to send with the request
     *
     * @return array                    the "data" array returned by the webservice
     */
    protected function dataArray($parameters = [])
    {
        $decodedResponse = $this->decodedResponse($parameters);
        $this->assertIsObject($decodedResponse);
        $this->assertTrue(
            property_exists($decodedResponse, 'data'),
            'response data property is missing: '.print_r($decodedResponse, true)
        );

        $data = $decodedResponse->data;
        $this->assertIsArray($data);

        return $data;
    }

    /**
     * Posts an action request and assert it returns an array with a single boolean value
     *
     * @param array $parameters parameters to send with the request
     *
     * @return boolean                  the boolean value
     */
    protected function boolean($parameters = [])
    {
        $value = $this->singleElementValue($parameters);
        $this->assertIsBool($value);

        return $value;
    }
}
