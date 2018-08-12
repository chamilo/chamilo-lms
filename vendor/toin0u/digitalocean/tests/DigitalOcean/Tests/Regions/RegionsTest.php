<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Tests\Regions;

use DigitalOcean\Tests\TestCase;
use DigitalOcean\Regions\Regions;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class RegionsTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMEssage Impossible to process this query: https://api.digitalocean.com/droplets/?client_id=foo&api_key=bar
     */
    public function testProcessQuery()
    {
        $regions = new Regions($this->getMockCredentials(), $this->getMockAdapterReturns(null));
        $regions->getAll();
    }

    public function testGetAllUrl()
    {
        $regions = new Regions($this->getMockCredentials(), $this->getMockAdapter($this->never()));

        $method = new \ReflectionMethod(
            $regions, 'buildQuery'
        );
        $method->setAccessible(true);

        $this->assertEquals(
            'https://api.digitalocean.com/regions/?client_id=foo&api_key=bar',
            $method->invoke($regions)
        );
    }

    public function testGetAll()
    {
        $response = <<<JSON
{"status":"OK","regions":[{"id":1,"name":"New York 1"},{"id":2,"name":"Amsterdam 1"}]}
JSON
        ;

        $regions = new Regions($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $regions = $regions->getAll();

        $this->assertTrue(is_object($regions));
        $this->assertEquals('OK', $regions->status);
        $this->assertCount(2, $regions->regions);

        $region1 = $regions->regions[0];
        $this->assertSame(1, $region1->id);
        $this->assertSame('New York 1', $region1->name);

        $region2 = $regions->regions[1];
        $this->assertSame(2, $region2->id);
        $this->assertSame('Amsterdam 1', $region2->name);
    }
}
