<?php
/* For licensing terms, see /license.txt */

/**
 * Test case for v2.php
 *
 * Using Guzzle' HTTP client to call the API endpoint and make requests.
 */
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../vendor/autoload.php';
require_once __DIR__.'/../../inc/global.inc.php';


class V2Test extends TestCase
{
    const WEBSERVICE_USERNAME = 'admin';
    const WEBSERVICE_PASSWORD = 'admin';
    public $client;

    protected function setUp(): void
    {
        $this->client = new Client([
            'base_uri' => api_get_path(WEB_CODE_PATH).'webservices/api/v2.php',
        ]);
    }

    /**
     * Make a request to get the API key for admin user.
     *
     * @return string
     * @throws Exception
     *
     */
    public function testAuthenticate()
    {
        $response = $this->client->post('v2.php', [
            'form_params' => [
                'action' => 'authenticate',
                'username' => self::WEBSERVICE_USERNAME,
                'password' => self::WEBSERVICE_PASSWORD,
            ],
        ]);

        $this->assertSame(200, $response->getStatusCode(), 'Entry denied with code : ' . $response->getStatusCode());

        $jsonResponse = json_decode($response->getBody()->getContents());

        $this->assertSame(False, $jsonResponse->error, 'Authentication failed because : ' . $jsonResponse->message);
        $this->assertNotNull($jsonResponse->data);
        $this->assertIsObject($jsonResponse->data);
        $this->assertNotNull($jsonResponse->data->apiKey);
        $this->assertIsString($jsonResponse->data->apiKey);

        return $jsonResponse->data->apiKey;
    }

    /**
     * @param $apiKey
     *
     * @return int
     *
     * @depends testAuthenticate
     * @throws Exception
     *
     */
    public function testCreateUser($apiKey)
    {
        $response = $this->client->post(
            'v2.php',
            [
                'form_params' => [
                    // data for the user who makes the request
                    'action' => 'save_user',
                    'username' => self::WEBSERVICE_USERNAME,
                    'api_key' => $apiKey,
                    // data for new user
                    'firstname' => 'Test User ',
                    'lastname' => 'Chamilo',
                    'status' => 5, // student
                    'email' => 'testuser@example.com',
                    'loginname' => 'restuser',
                    'password' => 'restuser',
                    'original_user_id_name' => 'myplatform_user_id', // field to identify the user in the external system
                    'original_user_id_value' => '1234', // ID for the user in the external system
                    'extra' => [
                        [
                            'field_name' => 'age',
                            'field_value' => 29,
                        ],
                    ],
                    'language' => 'english',
                    //'phone' => '',
                    //'expiration_date' => '',
                ],
            ]
        );

        $this->assertSame(200, $response->getStatusCode(), 'Entry denied with code : ' . $response->getStatusCode());

        $jsonResponse = json_decode($response->getBody()->getContents());

        $this->assertFalse($jsonResponse->error, 'User not created because : ' . $jsonResponse->message);
        $this->assertNotNull($jsonResponse->data);
        $this->assertIsArray($jsonResponse->data);
        $this->assertArrayHasKey(0, $jsonResponse->data);
        $this->assertIsInt($jsonResponse->data[0]);
        $userId = $jsonResponse->data[0];

        UserManager::delete_user($userId);
    }

}
