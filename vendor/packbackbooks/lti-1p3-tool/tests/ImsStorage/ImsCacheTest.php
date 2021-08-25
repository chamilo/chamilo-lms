<?php namespace Tests\ImsStorage;

use PHPUnit\Framework\TestCase;

use Packback\Lti1p3\ImsStorage\ImsCache;

class ImsCacheTest extends TestCase
{

    public function testItInstantiates()
    {
        $cache = new ImsCache();

        $this->assertInstanceOf(ImsCache::class, $cache);
    }
}
