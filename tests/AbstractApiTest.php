<?php
namespace Chamilo\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Client;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;

abstract class AbstractApiTest extends ApiTestCase
{
    private $token;
    private $clientWithCredentials;

    //use RefreshDatabaseTrait;

    public function setUp(): void
    {
        self::bootKernel();
    }

    protected function getClientWithGuiCredentials($username, $password): Client
    {
        $params = [
            'username' => $username,
            'password' => $password,
        ];

        $client = static::createClient();
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

    /**
     * Use credentials with token.
     */
    protected function getUserToken($body = []): string
    {
        if ($this->token) {
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
                'body' => json_encode($defaultBody)
            ],
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($response->getContent());
        $this->token = $data->token;

        return $data->token;
    }
}
