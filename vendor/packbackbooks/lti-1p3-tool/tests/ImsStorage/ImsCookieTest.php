<?php namespace Tests\ImsStorage;

use PHPUnit\Framework\TestCase;

use Packback\Lti1p3\ImsStorage\ImsCookie;

class ImsCookieTest extends TestCase
{

    public function testItInstantiates()
    {
        $cookie = new ImsCookie();

        $this->assertInstanceOf(ImsCookie::class, $cookie);
    }
}
