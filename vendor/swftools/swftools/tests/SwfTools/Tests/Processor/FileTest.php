<?php

namespace SwfTools\Tests\Processor;

use SwfTools\Binary\DriverContainer;
use SwfTools\Processor\File;
use SwfTools\Tests\TestCase;

class FileTest extends TestCase
{
    public function testGetChangePathnameExtension()
    {
        $object = new ExtendedFile(DriverContainer::create());
        $this->assertEquals('/path/kawabunga.plus', $object->change('/path/kawabunga.png', 'plus'));
    }

}

class ExtendedFile extends File
{
    public function change($pathname, $extension)
    {
        return static::changePathnameExtension($pathname, $extension);
    }
}
