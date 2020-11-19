<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../../../vendor/autoload.php';
/**
 * Test example to user API v2.php.
 *
 * Using Guzzle' HTTP client to call the API endpoint and make requests.
 * Change URL on the first lines of createUser() below to suit your needs.
 */

use GuzzleHttp\Client as Client;

// set your URL, username and password here to use it for all webservices in this test file.
$webserviceURL = 'https://YOURCHAMILO/main/webservices/api/';
$webserviceUsername = 'USERNAME';
$webservicePassword = 'PASSWORD';

/**
 * Make a request to get the API key for admin user.
 *
 * @throws Exception
 *
 * @return string
 */
function authenticate()
{
    global $webserviceURL;
    global $webserviceUsername;
    global $webservicePassword;
    $client = new Client([
        'base_uri' => $webserviceURL,
    ]);

    $response = $client->post('v2.php', [
        'form_params' => [
            'action' => 'authenticate',
            'username' => $webserviceUsername,
            'password' => $webservicePassword,
        ],
    ]);

    if ($response->getStatusCode() !== 200) {
        throw new Exception('Entry denied with code : '.$response->getStatusCode());
    }

    $jsonResponse = json_decode($response->getBody()->getContents());

    if ($jsonResponse->error) {
        throw new Exception('Authentication failed because : '.$jsonResponse->message);
    }

    return $jsonResponse->data->apiKey;
}

/**
 * @param $apiKey
 *
 * @throws Exception
 *
 * @return int
 */
function createSession($apiKey)
{
    global $webserviceURL;
    global $webserviceUsername;
    $client = new Client([
        'base_uri' => $webserviceURL,
    ]);

    $response = $client->post(
        'v2.php',
        [
            'form_params' => [
                // data for the user who makes the request
                'action' => 'save_session',
                'username' => $webserviceUsername,
                'api_key' => $apiKey,
                // data for new session
                'name' => 'Test Session',
                'coach_username' => 1, // user_id of CoachUsername that needs to already exist in Chamilo
                'access_start_date' => '2020-01-15 15:00:00',
                'access_end_date' => '2021-01-15 15:00:00',
                'description' => 'My complete text description of the session',
                'extra' => [
                    [
                        'extra_Price' => '200', // the "Price" session extra field needs to be already created in Chamilo
                    ],
                ],
            ],
        ]
    );

    if ($response->getStatusCode() !== 200) {
        throw new Exception('Entry denied with code : '.$response->getStatusCode());
    }

    $content = $response->getBody()->getContents();
    $jsonResponse = json_decode($content, true);

    if ($jsonResponse['error']) {
        throw new Exception('Cant save session because : '.$jsonResponse['message']);
    }

    return $jsonResponse['data']['id_session'];
}

$apiKey = authenticate();

//Creating a new session Test Session
$sessionId = createSession($apiKey);
echo 'ID of new session: '.$sessionId;
