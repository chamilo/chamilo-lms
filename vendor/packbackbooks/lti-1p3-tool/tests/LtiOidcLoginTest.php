<?php namespace Tests;

use Mockery;
use PHPUnit\Framework\TestCase;

use Packback\Lti1p3\Interfaces\Cache;
use Packback\Lti1p3\Interfaces\Cookie;
use Packback\Lti1p3\Interfaces\Database;
use Packback\Lti1p3\LtiOidcLogin;
use Packback\Lti1p3\OidcException;

class LtiOidcLoginTest extends TestCase
{
    public function setUp(): void
    {
        $this->cache = Mockery::mock(Cache::class);
        $this->cookie = Mockery::mock(Cookie::class);
        $this->database = Mockery::mock(Database::class);

        $this->oidcLogin = new LtiOidcLogin(
            $this->database,
            $this->cache,
            $this->cookie
        );
    }

    public function testItInstantiates()
    {
        $this->assertInstanceOf(LtiOidcLogin::class, $this->oidcLogin);
    }

    public function testItCreatesANewInstance()
    {
        $oidcLogin = LtiOidcLogin::new(
            $this->database,
            $this->cache,
            $this->cookie
        );

        $this->assertInstanceOf(LtiOidcLogin::class, $this->oidcLogin);
    }

    public function testItValidatesARequest()
    {
        $expected = 'expected';
        $request = [
            'iss' => 'Issuer',
            'login_hint' => 'LoginHint',
            'client_id' => 'ClientId'
        ];

        $this->database->shouldReceive('findRegistrationByIssuer')
            ->once()->with($request['iss'], $request['client_id'])
            ->andReturn($expected);

        $result = $this->oidcLogin->validateOidcLogin($request);

        $this->assertEquals($expected, $result);
    }

    public function testValidatesFailsIfIssuerIsNotSet()
    {
        $request = [
            'login_hint' => 'LoginHint',
            'client_id' => 'ClientId'
        ];

        $this->expectException(OidcException::class);
        $this->expectExceptionMessage(LtiOidcLogin::ERROR_MSG_ISSUER);

        $this->oidcLogin->validateOidcLogin($request);
    }

    public function testValidatesFailsIfLoginHintIsNotSet()
    {
        $request = [
            'iss' => 'Issuer',
            'client_id' => 'ClientId'
        ];

        $this->expectException(OidcException::class);
        $this->expectExceptionMessage(LtiOidcLogin::ERROR_MSG_LOGIN_HINT);

        $this->oidcLogin->validateOidcLogin($request);
    }

    public function testValidatesFailsIfRegistrationNotFound()
    {
        $request = [
            'iss' => 'Issuer',
            'login_hint' => 'LoginHint',
        ];
        $this->database->shouldReceive('findRegistrationByIssuer')
            ->once()->andReturn(null);

        $this->expectException(OidcException::class);
        $this->expectExceptionMessage(LtiOidcLogin::ERROR_MSG_REGISTRATION);

        $this->oidcLogin->validateOidcLogin($request);
    }

    /**
     * @todo Finish testing
     */
}
