<?php namespace Tests;

use Mockery;
use PHPUnit\Framework\TestCase;

use Packback\Lti1p3\Interfaces\Cache;
use Packback\Lti1p3\Interfaces\Cookie;
use Packback\Lti1p3\Interfaces\Database;
use Packback\Lti1p3\LtiMessageLaunch;

class LtiMessageLaunchTest extends TestCase
{
    public function setUp(): void
    {
        $this->cache = Mockery::mock(Cache::class);
        $this->cookie = Mockery::mock(Cookie::class);
        $this->database = Mockery::mock(Database::class);

        $this->messageLaunch = new LtiMessageLaunch(
            $this->database,
            $this->cache,
            $this->cookie
        );
    }

    public function testItInstantiates()
    {
        $this->assertInstanceOf(LtiMessageLaunch::class, $this->messageLaunch);
    }

    public function testItCreatesANewInstance()
    {
        $messageLaunch = LtiMessageLaunch::new(
            $this->database,
            $this->cache,
            $this->cookie
        );

        $this->assertInstanceOf(LtiMessageLaunch::class, $messageLaunch);
    }

    /**
     * @todo Finish testing
     */
}
