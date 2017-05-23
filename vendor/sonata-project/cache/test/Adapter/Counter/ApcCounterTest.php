<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Cache\Tests\Adapter\Cache;

use Sonata\Cache\Adapter\Counter\ApcCounter;
use Sonata\Cache\Counter;

class ApcCounterTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!function_exists('apc_store')) {
            $this->markTestSkipped('APC is not installed');
        }

        if (ini_get('apc.enable_cli') == 0) {
            $this->markTestSkipped('APC is not enabled in cli, please add apc.enable_cli=On into the apc.ini file');
        }

        apc_clear_cache('user');
    }

    public function testCounterBackend()
    {
        $backend = new ApcCounter('prefix');

        $counter = $backend->set(Counter::create('mycounter', 10));

        $this->assertInstanceOf('Sonata\Cache\Counter', $counter);
        $this->assertEquals(10, $counter->getValue());
        $this->assertEquals('mycounter', $counter->getName());

        $counter = $backend->get('mycounter');
        $this->assertInstanceOf('Sonata\Cache\Counter', $counter);
        $this->assertEquals(10, $counter->getValue());
        $this->assertEquals('mycounter', $counter->getName());

        $counter = $backend->increment($counter);
        $this->assertEquals(11, $counter->getValue());

        $counter = $backend->increment($counter, 10);
        $this->assertEquals(21, $counter->getValue());

        $counter = $backend->decrement($counter);
        $this->assertEquals(20, $counter->getValue());

        $counter = $backend->decrement($counter, 30);
        $this->assertEquals(-10, $counter->getValue());
    }

    public function testNonExistantKey()
    {
        $backend = new ApcCounter('prefix');

        $counter = $backend->increment(Counter::create('mycounter', 10));

        $this->assertEquals(11, $counter->getValue());
    }
}
