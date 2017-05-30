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

use HttpAdapter\CurlHttpAdapter;

/**
 * @author William Durand <william.durand1@gmail.com>
 * @author Antoine Corcy <contact@sbin.dk>
 */
class CurlHttpAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $curl;

    protected function setUp()
    {
        $this->curl = new CurlHttpAdapter();
    }

    public function testGetNullContent()
    {
        $this->assertNull($this->curl->getContent(null));
    }

    public function testGetFalseContent()
    {
        $this->assertNull($this->curl->getContent(false));
    }

    public function testGetName()
    {
        $this->assertEquals('curl', $this->curl->getName());
    }
}