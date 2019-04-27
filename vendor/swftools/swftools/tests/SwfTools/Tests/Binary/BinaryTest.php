<?php

namespace SwfTools\Tests\Binary;

use SwfTools\Tests\TestCase;

abstract class BinaryTestCase extends TestCase
{
    public function testGetVersion()
    {
        $classname = $this->getClassName();

        $object = $classname::create();
        $this->assertRegExp('/([0-9]+\.)+[0-9]+/', $object->getVersion());
    }

    abstract public function getClassName();
}
