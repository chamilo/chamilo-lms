<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../../../vendor/autoload.php';
/**
 * Test example to user API v2.php.
 *
 * Using Guzzle' HTTP client to call the API endpoint and make requests.
 * Change URL on the first lines of createUser() below to suit your needs.
 */

use GuzzleHttp\Client;

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
function createUser($apiKey)
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
                'action' => 'save_user',
                'username' => $webserviceUsername,
                'api_key' => $apiKey,
                // data for new user
                'firstname' => 'Test User',
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

    if ($response->getStatusCode() !== 200) {
        throw new Exception('Entry denied with code : '.$response->getStatusCode());
    }

    $jsonResponse = json_decode($response->getBody()->getContents());

    if ($jsonResponse->error) {
        throw new Exception('User not created because : '.$jsonResponse->message);
    }

    return $jsonResponse->data[0];
}

$apiKey = authenticate();

//Creating a new user restuser
$userId = createUser($apiKey);
echo 'ID of new user: '.$userId;
