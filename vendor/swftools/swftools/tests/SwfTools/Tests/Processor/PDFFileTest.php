<?php

namespace SwfTools\Tests\Processor;

use SwfTools\Binary\DriverContainer;
use SwfTools\Processor\PDFFile;
use SwfTools\Tests\TestCase;

class PDFFileTest extends TestCase
{
    /**
     * @var PDFFile
     */
    protected $object;
    protected $destination;

    protected function setUp()
    {
        $this->destination = __DIR__ . '/../../../files/tmp.swf';
        $this->object = new PDFFile(DriverContainer::create());
    }

    protected function tearDown()
    {
        if (file_exists($this->destination)) {
            unlink($this->destination);
        }
    }

    public function testToSwf()
    {
        $this->object->toSwf(__DIR__ . '/../../../files/PDF.pdf', $this->destination);
        $this->assertTrue(file_exists($this->destination));

        unlink($this->destination);
    }

    /**
     * @expectedException SwfTools\Exception\RuntimeException
     * @expectedExceptionMessage Unable to load pdf2swf
     */
    public function testToSwfNoBinary()
    {
        $this->object = new PDFFile(DriverContainer::create(array('pdf2swf.binaries' => '/path/to/nowhere')));
        $this->object->toSwf(__DIR__ . '/../../../files/PDF.pdf', $this->destination);
    }

    /**
     * @expectedException \SwfTools\Exception\InvalidArgumentException
     */
    public function testToSwfFailed()
    {
        $this->object->toSwf(__DIR__ . '/../../../files/PDF.pdf', '');
    }
}
