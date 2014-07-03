<?php

namespace JMS\SecurityExtraBundle\Tests\Security\Util;

use JMS\SecurityExtraBundle\Security\Util\String;

class StringTest extends \PHPUnit_Framework_TestCase
{
    public function testEquals()
    {
        $this->assertTrue(String::equals('password', 'password'));
        $this->assertFalse(String::equals('password', 'foo'));
    }
}