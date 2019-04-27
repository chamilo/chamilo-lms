<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Tests\Sizes;

use DigitalOcean\Tests\TestCase;
use DigitalOcean\Sizes\Sizes;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class SizesTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMEssage Impossible to process this query: https://api.digitalocean.com/droplets/?client_id=foo&api_key=bar
     */
    public function testProcessQuery()
    {
        $sizes = new Sizes($this->getMockCredentials(), $this->getMockAdapterReturns(null));
        $sizes->getAll();
    }

    public function testGetAllUrl()
    {
        $sizes = new Sizes($this->getMockCredentials(), $this->getMockAdapter($this->never()));

        $method = new \ReflectionMethod(
            $sizes, 'buildQuery'
        );
        $method->setAccessible(true);

        $this->assertEquals(
            'https://api.digitalocean.com/sizes/?client_id=foo&api_key=bar',
            $method->invoke($sizes)
        );
    }

    public function testGetAll()
    {
        $response = <<<JSON
{"status":"OK","sizes":[{"id":33,"name":"512MB"},{"id":34,"name":"1GB"},{"id":35,"name":"2GB"},{"id":36,"name":"4GB"},{"id":37,"name":"8GB"},{"id":38,"name":"16GB"}]}
JSON
        ;

        $sizes = new Sizes($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $sizes = $sizes->getAll();

        $this->assertTrue(is_object($sizes));
        $this->assertEquals('OK', $sizes->status);
        $this->assertCount(6, $sizes->sizes);

        $size1 = $sizes->sizes[0];
        $this->assertSame(33, $size1->id);
        $this->assertSame('512MB', $size1->name);

        $size2 = $sizes->sizes[1];
        $this->assertSame(34, $size2->id);
        $this->assertSame('1GB', $size2->name);

        $size6 = $sizes->sizes[5];
        $this->assertSame(38, $size6->id);
        $this->assertSame('16GB', $size6->name);
    }
}
