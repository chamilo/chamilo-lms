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

        $response = static::createClient()->request(
            'POST',
            '/api/authentication_token',
            [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode($params),
            ]
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($response->getContent());
        //$this->token = $data->access_token;

        $this->assertEquals('admin', $data->username);

        return $response;
    }

    protected function createClientWithCredentials($token = null): Client
    {
        $token = $token ?: $this->getToken();

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

        $response = static::createClient()->request(
            'POST',
            '/api/authentication_token',
            [
                /*'body' => $body ?: [
                    'username' => 'admin',
                    'password' => 'admin',
                ],*/
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode([
                    'username' => 'admin',
                    'password' => 'admin',
                ])
            ],
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($response->getContent());
        $this->token = $data->token;

        return $data->token;
    }
}
