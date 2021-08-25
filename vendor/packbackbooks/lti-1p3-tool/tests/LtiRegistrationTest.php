<?php namespace Tests;

use PHPUnit\Framework\TestCase;

use Packback\Lti1p3\LtiRegistration;

class LtiRegistrationTest extends TestCase
{
    public function setUp(): void
    {
        $this->registration = new LtiRegistration;
    }

    public function testItInstantiates()
    {
        $this->assertInstanceOf(LtiRegistration::class, $this->registration);
    }

    public function testItCreatesANewInstance()
    {
        $registration = LtiRegistration::new();

        $this->assertInstanceOf(LtiRegistration::class, $registration);
    }

    public function testItGetsIssuer()
    {
        $expected = 'expected';
        $registration = new LtiRegistration([ 'issuer' => $expected ]);

        $result = $registration->getIssuer();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsIssuer()
    {
        $expected = 'expected';

        $this->registration->setIssuer($expected);

        $this->assertEquals($expected, $this->registration->getIssuer());
    }

    public function testItGetsClientId()
    {
        $expected = 'expected';
        $registration = new LtiRegistration([ 'clientId' => $expected ]);

        $result = $registration->getClientId();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsClientId()
    {
        $expected = 'expected';

        $this->registration->setClientId($expected);

        $this->assertEquals($expected, $this->registration->getClientId());
    }

    public function testItGetsKeySetUrl()
    {
        $expected = 'expected';
        $registration = new LtiRegistration([ 'keySetUrl' => $expected ]);

        $result = $registration->getKeySetUrl();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsKeySetUrl()
    {
        $expected = 'expected';

        $this->registration->setKeySetUrl($expected);

        $this->assertEquals($expected, $this->registration->getKeySetUrl());
    }

    public function testItGetsAuthTokenUrl()
    {
        $expected = 'expected';
        $registration = new LtiRegistration([ 'authTokenUrl' => $expected ]);

        $result = $registration->getAuthTokenUrl();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsAuthTokenUrl()
    {
        $expected = 'expected';

        $this->registration->setAuthTokenUrl($expected);

        $this->assertEquals($expected, $this->registration->getAuthTokenUrl());
    }

    public function testItGetsAuthLoginUrl()
    {
        $expected = 'expected';
        $registration = new LtiRegistration([ 'authLoginUrl' => $expected ]);

        $result = $registration->getAuthLoginUrl();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsAuthLoginUrl()
    {
        $expected = 'expected';

        $this->registration->setAuthLoginUrl($expected);

        $this->assertEquals($expected, $this->registration->getAuthLoginUrl());
    }

    public function testItGetsAuthServer()
    {
        $expected = 'expected';
        $registration = new LtiRegistration([ 'authServer' => $expected ]);

        $result = $registration->getAuthServer();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsAuthServer()
    {
        $expected = 'expected';

        $this->registration->setAuthServer($expected);

        $this->assertEquals($expected, $this->registration->getAuthServer());
    }

    public function testItGetsToolPrivateKey()
    {
        $expected = 'expected';
        $registration = new LtiRegistration([ 'toolPrivateKey' => $expected ]);

        $result = $registration->getToolPrivateKey();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsToolPrivateKey()
    {
        $expected = 'expected';

        $this->registration->setToolPrivateKey($expected);

        $this->assertEquals($expected, $this->registration->getToolPrivateKey());
    }

    public function testItGetsKid()
    {
        $expected = 'expected';
        $registration = new LtiRegistration([ 'kid' => $expected ]);

        $result = $registration->getKid();

        $this->assertEquals($expected, $result);
    }

    public function testItGetsKidFromIssuerAndClientId()
    {
        $expected = '39e02c46a08382b7b352b4f1a9d38698b8fe7c8eb74ead609c804b25eeb1db52';
        $registration = new LtiRegistration([
            'issuer' => 'Issuer',
            'client_id' => 'ClientId'
        ]);

        $result = $registration->getKid();

        $this->assertEquals($expected, $result);
    }

    public function testItSetsKid()
    {
        $expected = 'expected';

        $this->registration->setKid($expected);

        $this->assertEquals($expected, $this->registration->getKid());
    }
}
