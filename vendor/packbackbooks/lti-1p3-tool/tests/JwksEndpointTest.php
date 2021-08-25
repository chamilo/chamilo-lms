<?php namespace Tests;

use PHPUnit\Framework\TestCase;
use Mockery;

use Packback\Lti1p3\Interfaces\Database;
use Packback\Lti1p3\Interfaces\LtiRegistrationInterface;
use Packback\Lti1p3\JwksEndpoint;

class JwksEndpointTest extends TestCase
{

    public function testItInstantiates()
    {
        $jwks = new JwksEndpoint([]);

        $this->assertInstanceOf(JwksEndpoint::class, $jwks);
    }

    public function testCreatesANewInstance()
    {
        $jwks = JwksEndpoint::new([]);

        $this->assertInstanceOf(JwksEndpoint::class, $jwks);
    }

    public function testCreatesANewInstanceFromIssuer()
    {
        $database = Mockery::mock(Database::class);
        $registration = Mockery::mock(LtiRegistrationInterface::class);
        $database->shouldReceive('findRegistrationByIssuer')
            ->once()
            ->andReturn($registration);
        $registration->shouldReceive('getKid')
            ->once()
            ->andReturn('kid');
        $registration->shouldReceive('getToolPrivateKey')
            ->once()
            ->andReturn('private_key');

        $jwks = JwksEndpoint::fromIssuer($database, 'issuer');

        $this->assertInstanceOf(JwksEndpoint::class, $jwks);
    }

    public function testCreatesANewInstanceFromRegistration()
    {
        $registration = Mockery::mock(LtiRegistrationInterface::class);
        $registration->shouldReceive('getKid')
            ->once()
            ->andReturn('kid');
        $registration->shouldReceive('getToolPrivateKey')
            ->once()
            ->andReturn('private_key');

        $jwks = JwksEndpoint::fromRegistration($registration);

        $this->assertInstanceOf(JwksEndpoint::class, $jwks);
    }

    public function testItGetsJwksForTheProvidedKeys()
    {
        $jwks = new JwksEndpoint([
            'kid' => file_get_contents(__DIR__.'/data/private.key')
        ]);

        $result = $jwks->getPublicJwks();

        $this->assertEquals(['keys' => [[
            'kty' => 'RSA',
            'alg' => 'RS256',
            'use' => 'sig',
            'e' => 'AQAB',
            'n' => '6DzRJzrx0KThi0piO3wdNA3e7-xXly5WJo00CqlKDodtyX6wRT76E4cD57yrr_ZWuaA-6idSFPaEQXw9tCqqTIrS4STIYrlvC0CeEA7m0s2PbI2ffaxv2kofxdmOaUI8YW8NIqNyHMl6Acz1lQOOZ5xSreG5JAqtZpy7AwDdpJo7up9937AD9ZV77qlty6xRKVqOGP1-cH97zMvlQo0EUWUhRAzDlTlCXnbeSjVypET3l93WPT9gnIywt1xX0L6rIJd-4fyU6faaToGN9z4_Q6ay2xFSEJnoNBW9wI886W75vLcVLnT95YKJJwZoKEa9yoV_ZPiTBJcFv1HFPf4ibQ',
            'kid' => 'kid',
        ]]], $result);
    }
}
