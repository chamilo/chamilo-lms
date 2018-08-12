<?php

namespace SwfTools\Tests\Processor;

use SwfTools\Binary\DriverContainer;
use SwfTools\Processor\FlashFile;
use SwfTools\Tests\TestCase;

class FlashFileTest extends TestCase
{
    /**
     * @var FlashFile
     */
    protected $object;
    protected $destination;

    protected function setUp()
    {
        $this->destination = __DIR__ . '/../../../files/tmp.jpg';
        $this->object = new FlashFile(DriverContainer::create());
    }

    protected function tearDown()
    {
        if (file_exists($this->destination)) {
            unlink($this->destination);
        }
    }

    public function testRender()
    {
        $this->destination = $this->object->render(__DIR__ . '/../../../files/flashfile.swf', $this->destination);
        $this->assertTrue(file_exists($this->destination));

        unlink($this->destination);
    }

    /**
     * @expectedException SwfTools\Exception\RuntimeException
     * @expectedExceptionMessage Unable to load swfrender
     */
    public function testRenderNoBinary()
    {
        $object = new FlashFile(DriverContainer::create(array('swfrender.binaries' => '/path/to/nowhere')));
        $object->render(__DIR__ . '/../../../files/flashfile.swf', '/target');
    }

    /**
     * @expectedException SwfTools\Exception\RuntimeException
     * @expectedExceptionMessage Unable to load swfextract
     */
    public function testExtractEmbeddedNoBinary()
    {
        $object = new FlashFile(DriverContainer::create(array('swfextract.binaries' => '/path/to/nowhere')));
        $object->extractEmbedded(1, __DIR__ . '/../../../files/flashfile.swf', '/target');
    }

    /**
     * @expectedException SwfTools\Exception\RuntimeException
     * @expectedExceptionMessage Unable to load swfextract
     */
    public function testListEmbeddedNoBinary()
    {
        $object = new FlashFile(DriverContainer::create(array('swfextract.binaries' => '/path/to/nowhere')));
        $object->listEmbeddedObjects(__DIR__ . '/../../../files/flashfile.swf');
    }

    /**
     * @expectedException SwfTools\Exception\RuntimeException
     * @expectedExceptionMessage Unable to load swfextract
     */
    public function testExtractFirstImageNoBinary()
    {
        $object = new FlashFile(DriverContainer::create(array('swfextract.binaries' => '/path/to/nowhere')));
        $object->extractFirstImage(__DIR__ . '/../../../files/flashfile.swf', '/target');
    }

    /**
     * @expectedException \SwfTools\Exception\InvalidArgumentException
     */
    public function testRenderWrongDestination()
    {
        $this->object->render(__DIR__ . '/../../../files/flashfile.swf', '');
    }

    public function testListEmbeddedObjects()
    {
        $this->object->listEmbeddedObjects(__DIR__ . '/../../../files/flashfile.swf');
    }

    public function testExtractEmbedded()
    {
        $this->object->extractEmbedded(1, __DIR__ . '/../../../files/flashfile.swf', $this->destination);
        $this->assertTrue(file_exists($this->destination));

        unlink($this->destination);
    }

    /**
     * @expectedException  \SwfTools\Exception\RuntimeException
     */
    public function testNoFirstImage()
    {
        $object = $this->getMockBuilder('SwfTools\Processor\FlashFile')
            ->setMethods(array('listEmbeddedObjects'))
            ->disableOriginalConstructor()
            ->getMock();

        $object->expects($this->any())
            ->method('listEmbeddedObjects')
            ->will($this->returnValue(array()));

        $object->extractFirstImage(__DIR__ . '/../../../files/flashfile.swf', $this->destination);
    }

    /**
     * @expectedException  \SwfTools\Exception\RuntimeException
     */
    public function testEmbeddedFailed()
    {
        $object = $this->getMockBuilder('SwfTools\Processor\FlashFile')
            ->setMethods(array('listEmbeddedObjects'))
            ->disableOriginalConstructor()
            ->getMock();

        $object->expects($this->any())
            ->method('listEmbeddedObjects')
            ->will($this->returnValue(null));

        $object->extractFirstImage(__DIR__ . '/../../../files/flashfile.swf', $this->destination);
    }

    public function testExtractFirstImage()
    {
        $this->object->extractFirstImage(__DIR__ . '/../../../files/flashfile.swf', $this->destination);
        $this->assertTrue(file_exists($this->destination));

        unlink($this->destination);
    }

    /**
     * @expectedException \SwfTools\Exception\InvalidArgumentException
     */
    public function testExtractFirstImageFailWithoutDestination()
    {
        $this->object->extractFirstImage(__DIR__ . '/../../../files/flashfile.swf', '');
    }

    /**
     * @expectedException  \SwfTools\Exception\RuntimeException
     */
    public function testExtractEmbeddedWrongId()
    {
        $this->object->extractEmbedded(-4, __DIR__ . '/../../../files/flashfile.swf', $this->destination);
    }

    /**
     * @expectedException  \SwfTools\Exception\RuntimeException
     */
    public function testExtractEmbeddedWrongOutput()
    {
        $this->object->extractEmbedded(1, __DIR__ . '/../../../files/flashfile.swf', '/dsmsdf/dslgfsdm');
    }
}
