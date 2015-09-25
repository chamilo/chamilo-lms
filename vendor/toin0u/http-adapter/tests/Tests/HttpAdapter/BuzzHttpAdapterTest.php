<?php

/**
 * This file is part of the HttpAdapter library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\HttpAdapter;

use HttpAdapter\BuzzHttpAdapter;

/**
 * @author William Durand <william.durand1@gmail.com>
 * @author Antoine Corcy <contact@sbin.dk>
 */
class BuzzHttpAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $buzz;

    protected function setUp()
    {
        if (!class_exists('Buzz\Browser')) {
            $this->markTestSkipped('Buzz library has to be installed.');
        }

        $this->buzz = new BuzzHttpAdapter();
    }

    public function testGetNullContent()
    {
        $this->assertNull($this->buzz->getContent(null));
    }

    public function testGetFalseContent()
    {
        $this->assertNull($this->buzz->getContent(false));
    }

    public function testGetName()
    {
        $this->assertEquals('buzz', $this->buzz->getName());
    }
}
