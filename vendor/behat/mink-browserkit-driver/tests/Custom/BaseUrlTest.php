<?php

namespace Behat\Mink\Tests\Driver\Custom;

use Behat\Mink\Driver\BrowserKitDriver;
use Behat\Mink\Session;
use Behat\Mink\Tests\Driver\Util\FixturesKernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Client;

/**
 * @group functional
 */
class BaseUrlTest extends TestCase
{
    public function testBaseUrl()
    {
        $client = new Client(new FixturesKernel());
        $driver = new BrowserKitDriver($client, 'http://localhost/foo/');
        $session = new Session($driver);

        $session->visit('http://localhost/foo/index.html');
        $this->assertEquals(200, $session->getStatusCode());
        $this->assertEquals('http://localhost/foo/index.html', $session->getCurrentUrl());
    }
}
