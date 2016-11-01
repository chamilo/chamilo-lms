<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Tests;

use DigitalOcean\Credentials;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class CrendentialsTest extends TestCase
{
    protected $clientId;
    protected $apiKey;
    protected $credentials;

    protected function setUp()
    {
        $this->clientId = 'foo';
        $this->apiKey   = 'bar';

        $this->credentials = new Credentials($this->clientId, $this->apiKey);
    }

    public function testGetClientId()
    {
        $this->assertSame($this->clientId, $this->credentials->getClientId());
    }

    public function testGetApiKey()
    {
        $this->assertSame($this->apiKey, $this->credentials->getApiKey());
    }
}
