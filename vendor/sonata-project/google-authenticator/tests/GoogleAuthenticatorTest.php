<?php

namespace Google\Authenticator\Tests;

use Google\Authenticator\GoogleAuthenticator;

class HandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Google\Authenticator\GoogleAuthenticator
     */
    protected $helper;

    public function setUp()
    {
        $this->helper = new GoogleAuthenticator();
    }

    public function testGenerateSecret()
    {
        $secret = $this->helper->generateSecret();

        $this->assertEquals(16, strlen($secret));
    }

    public function testGetCode()
    {
        $code = $this->helper->getCode('3DHTQX4GCRKHGS55CJ', strtotime('17/03/2012 22:17'));

        $this->assertTrue($this->helper->checkCode('3DHTQX4GCRKHGS55CJ', $code));
    }

    public function testGetUrl()
    {
        $url = $this->helper->getUrl('foo', 'foobar.org', '3DHTQX4GCRKHGS55CJ');

        $expected = "https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=otpauth://totp/foo@foobar.org%3Fsecret%3D3DHTQX4GCRKHGS55CJ";

        $this->assertEquals($expected, $url);
    }
}
