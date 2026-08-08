<?php
namespace Chamilo\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Chamilo\CoreBundle\Entity\User;

abstract class AbstractApiTest extends ApiTestCase
{
    private $token;
    private $clientWithCredentials;

    //use RefreshDatabaseTrait;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    protected function getClientWithGuiCredentials($username, $password): Client
    {
        $params = [
            'username' => $username,
            'password' => $password,
        ];

        // This client authenticates through a session cookie, so every unsafe
        // request it makes afterwards goes through CsrfProtectionListener. A
        // real browser proves same-origin through this header; sending it keeps
        // the helper from depending on whatever host the test client uses.
        $client = static::createClient([], ['headers' => ['sec-fetch-site' => 'same-origin']]);
        $response = $client->request(
            'POST',
            '/login_json',
            [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode($params),
            ]
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($response->getContent());

        $this->assertEquals($username, $data->username);

        return $client;
    }

    protected function createClientWithCredentials($token = null): Client
    {
        $token = $token ?: $this->getUserToken();

        return static::createClient([], ['headers' => ['authorization' => 'Bearer '.$token]]);
    }

    public function getUserTokenFromUser(User $user)
    {
        return $this->getUserToken([
                'username' => $user->getUsername(),
                'password' => $user->getUsername(),
            ], true
        );
    }

    /**
     * Use credentials with token, by default it returns the admin token.
     */
    protected function getUserToken($body = [], $cleanToken = false): string
    {
        if ($cleanToken) {
            $this->token = null;
        }

        if (null !== $this->token) {
            return $this->token;
        }

        $defaultBody = [
            'username' => 'admin',
            'password' => 'admin',
        ];

        if (!empty($body)) {
            $defaultBody = $body;
        }

        $response = static::createClient()->request(
            'POST',
            '/api/authentication_token',
            [
                /*'body' => $body ?: [
                    'username' => 'admin',
                    'password' => 'admin',
                ],*/
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode($defaultBody),
            ],
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($response->getContent());
        $this->token = $data->token;

        return $data->token;
    }
}
