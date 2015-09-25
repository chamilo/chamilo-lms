<?php

namespace MediaAlchemyst\Tests\Transmuter;

use MediaAlchemyst\Transmuter\Flash2Image;
use MediaAlchemyst\Tests\AbstractAlchemystTester;
use MediaAlchemyst\DriversContainer;
use MediaAlchemyst\Specification\Image;
use MediaAlchemyst\Specification\Video;

class Flash2ImageTest extends AbstractAlchemystTester
{
    /**
     * @var Flash2Image
     */
    protected $object;
    protected $specs;
    protected $source;
    protected $dest;

    protected function setUp()
    {
        $this->object = new Flash2Image(new DriversContainer(), $this->getFsManager());

        $this->specs = new Image();
        $this->source = $this->getMediaVorus()->guess(__DIR__ . '/../../../files/flashfile.swf');
        $this->dest = __DIR__ . '/../../../files/output.jpg';
    }

    protected function tearDown()
    {
        if (file_exists($this->dest) && is_writable($this->dest)) {
            unlink($this->dest);
        }
    }

    /**
     * @covers MediaAlchemyst\Transmuter\Flash2Image::execute
     * @covers MediaAlchemyst\Exception\SpecNotSupportedException
     * @expectedException MediaAlchemyst\Exception\SpecNotSupportedException
     */
    public function testExecuteWrongSpecs()
    {
        $this->specs = new Video();
        $this->object->execute($this->specs, $this->source, $this->dest);
    }

    /**
     * @covers MediaAlchemyst\Transmuter\Flash2Image::execute
     */
    public function testExecute()
    {
        $this->specs->setDimensions(320, 240);
        $this->specs->setResizeMode(Image::RESIZE_MODE_INBOUND);

        $this->object->execute($this->specs, $this->source, $this->dest);

        $MediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals(320, $MediaDest->getWidth());
        $this->assertEquals(240, $MediaDest->getHeight());

        $this->specs->setResizeMode(Image::RESIZE_MODE_INBOUND_FIXEDRATIO);

        $this->object->execute($this->specs, $this->source, $this->dest);

        $MediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals(320, $MediaDest->getWidth());
        $this->assertEquals(148, $MediaDest->getHeight());
    }
}
