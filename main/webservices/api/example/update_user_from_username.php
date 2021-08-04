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
function updateUserFromUsername($apiKey)
{
    global $webserviceURL;
    global $webserviceUsername;
    $client = new Client([
        'base_uri' => $webserviceURL,
    ]);

    $response = $client->post('v2.php', [
        'form_params' => [
            // data for the user who makes the request
            'action' => 'update_user_from_username',
            'username' => $webserviceUsername,
            'api_key' => $apiKey,
            // data for the user to be updated
            'loginName' => 'TestUser',
            'firstname' => 'Test User',
            'lastname' => 'Chamilo',
            'status' => 5, // student
            'email' => 'testuser@example.com',
            'enabled' => 1,
            'extra' => [
                [
                    'field_name' => 'age', // The "age" user extra field needs to already be created on Chamilo
                    'field_value' => 35,
                ],
            ],
            'language' => 'english',
            'expiration_date' => '2025-12-31 23:59:59',
        ],
    ]);

    if ($response->getStatusCode() !== 200) {
        throw new Exception('Entry denied with code : '.$response->getStatusCode());
    }

    $jsonResponse = json_decode($response->getBody()->getContents());

    if ($jsonResponse->error) {
        throw new Exception('User not updated because : '.$jsonResponse->message);
    }

    return $jsonResponse->data[0];
}

$apiKey = authenticate();

//update user TestUser
if (updateUserFromUsername($apiKey)) {
    echo 'User updated successfully';
}
