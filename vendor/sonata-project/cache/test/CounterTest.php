<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Cache\Tests\Counter;

use Sonata\Cache\Counter;

class CounterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException RuntimeException
     */
    public function testInvalidValue()
    {
        Counter::create('value', 'data');
    }

    public function testClass()
    {
        $counter = Counter::create("mycounter", 42);

        $this->assertEquals('mycounter', $counter->getName());
        $this->assertEquals(42, $counter->getValue());
    }
}
