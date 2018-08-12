<?php

namespace MediaAlchemyst\Tests\Transmuter;

use MediaAlchemyst\Transmuter\Video2Image;
use MediaAlchemyst\Tests\AbstractAlchemystTester;
use MediaAlchemyst\DriversContainer;
use MediaAlchemyst\Specification\Image;
use MediaAlchemyst\Specification\Video;

class Video2ImageTest extends AbstractAlchemystTester
{
    /**
     * @var Video2Image
     */
    protected $object;

    /**
     *
     * @var \MediaAlchemyst\Specification\Image
     */
    protected $specs;
    protected $source;
    protected $dest;

    protected function setUp()
    {
        $this->object = new Video2Image(new DriversContainer(), $this->getFsManager());

        $this->specs = new Image();
        $this->source = $this->getMediaVorus()->guess(__DIR__ . '/../../../files/Test.ogv');
        $this->dest = __DIR__ . '/../../../files/output_.png';
    }

    protected function tearDown()
    {
        if (file_exists($this->dest) && is_writable($this->dest)) {
            unlink($this->dest);
        }
    }

    /**
     * @covers MediaAlchemyst\Transmuter\Video2Image::execute
     */
    public function testExecute()
    {
        $this->object->execute($this->specs, $this->source, $this->dest);

        $mediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals('image/png', $mediaDest->getFile()->getMimeType());
        $this->assertTrue(abs($this->source->getWidth() - $mediaDest->getWidth()) <= 16);
        $this->assertTrue(abs($this->source->getHeight() - $mediaDest->getHeight()) <= 16);
    }

    /**
     * @covers MediaAlchemyst\Transmuter\Video2Image::execute
     */
    public function testExecuteWithOptions()
    {
        $this->specs->setDimensions(320, 240);
        $this->specs->setResizeMode(Image::RESIZE_MODE_INBOUND);

        $this->object->execute($this->specs, $this->source, $this->dest);

        $mediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals('image/png', $mediaDest->getFile()->getMimeType());
        $this->assertEquals(320, $mediaDest->getWidth());
        $this->assertEquals(240, $mediaDest->getHeight());
    }

    /**
     * @covers MediaAlchemyst\Transmuter\Video2Image::execute
     * @expectedException MediaAlchemyst\Exception\SpecNotSupportedException
     */
    public function testExecuteWrongSpecs()
    {
        $this->object->execute(new Video(), $this->source, $this->dest);
    }

    /**
     * @dataProvider getTimeAndPercents
     * @covers MediaAlchemyst\Transmuter\Video2Image::parseTimeAsRatio
     */
    public function testParseTimeAsRatio($time, $percent)
    {
        $object = new Video2ImageExtended();

        $this->assertEquals($percent, $object->testparseTimeAsRatio($time));
    }

    public function getTimeAndPercents()
    {
        return array(
          array('30%', 0.3),
          array('100%', 1),
          array('0%', 0),
          array('0.5', 0.5),
        );
    }
}

class Video2ImageExtended extends Video2Image
{
    public function __construct()
    {

    }

    public function testparseTimeAsRatio($time)
    {
        return parent::parseTimeAsRatio($time);
    }
}
