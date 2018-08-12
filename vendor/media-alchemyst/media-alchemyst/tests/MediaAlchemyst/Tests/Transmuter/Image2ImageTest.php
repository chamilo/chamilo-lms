<?php

namespace MediaAlchemyst\Tests\Transmuter;

use MediaAlchemyst\Transmuter\Image2Image;
use MediaAlchemyst\Tests\AbstractAlchemystTester;
use MediaAlchemyst\DriversContainer;
use MediaAlchemyst\Tests\Specification\UnknownSpecs;
use MediaAlchemyst\Specification\Image;

class Image2ImageTest extends AbstractAlchemystTester
{
    /**
     * @var Image2Image
     */
    protected $object;
    protected $specs;
    protected $source;
    protected $dest;

    protected function setUp()
    {
        $this->object = new Image2Image(new DriversContainer(), $this->getFsManager());

        Image2Image::$autorotate = false;

        $this->specs = new Image();
        $this->source = $this->getMediaVorus()->guess(__DIR__ . '/../../../files/photo03.JPG');
        $this->dest = __DIR__ . '/../../../files/output_auto_rotate.jpg';
    }

    public function tearDown()
    {
        if (file_exists($this->dest) && is_writable($this->dest)) {
            unlink($this->dest);
        }
    }

   /**
    * @covers MediaAlchemyst\Transmuter\Image2Image::execute
    */
   public function testExecute()
   {
       $this->object->execute($this->specs, $this->source, $this->dest);

       $MediaDest = $this->getMediaVorus()->guess($this->dest);

       $this->assertEquals($this->source->getWidth(), $MediaDest->getWidth());
       $this->assertEquals($this->source->getHeight(), $MediaDest->getHeight());
   }

   /**
    * @covers MediaAlchemyst\Transmuter\Image2Image::execute
    */
   public function testExecuteOverCR2()
   {
       $this->source = $this->getMediaVorus()->guess(__DIR__ . '/../../../files/test001.CR2');
       $this->object->execute($this->specs, $this->source, $this->dest);

       $MediaDest = $this->getMediaVorus()->guess($this->dest);
    
       $this->assertEquals(1872, $MediaDest->getHeight());
       $this->assertEquals(2808, $MediaDest->getWidth());
   }

    /**
     * @covers MediaAlchemyst\Transmuter\Image2Image::execute
     */
    public function testExecuteOverAI()
    {
        $this->source = $this->getMediaVorus()->guess(__DIR__ . '/../../../files/plane.ai');
        $this->object->execute($this->specs, $this->source, $this->dest);

        $MediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals(842, $MediaDest->getHeight());
        $this->assertEquals(595, $MediaDest->getWidth());
    }

   /**
    * @covers MediaAlchemyst\Transmuter\Image2Image::execute
    */
   public function testExecuteAutorotate()
   {
       Image2Image::$autorotate = true;

       $this->object->execute($this->specs, $this->source, $this->dest);

       $MediaDest = $this->getMediaVorus()->guess($this->dest);

       $this->assertEquals($this->source->getWidth(), $MediaDest->getHeight());
       $this->assertEquals($this->source->getHeight(), $MediaDest->getWidth());
   }

   /**
    * @covers MediaAlchemyst\Transmuter\Image2Image::execute
    * @covers MediaAlchemyst\Transmuter\Image2Image::extractEmbeddedImage
    */
   public function testExecutePreviewExtract()
   {
       Image2Image::$lookForEmbeddedPreview = true;

       $source = $this->getMediaVorus()->guess(__DIR__ . '/../../../files/ExifTool.jpg');

       $this->object->execute($this->specs, $source, $this->dest);

       $MediaDest = $this->getMediaVorus()->guess($this->dest);

       $this->assertEquals(192, $MediaDest->getHeight());
       $this->assertEquals(288, $MediaDest->getWidth());
   }

   /**
    * @covers MediaAlchemyst\Transmuter\Image2Image::execute
    */
    public function testExecuteSimpleResize()
    {
        $this->specs->setDimensions(320, 240);

        $this->object->execute($this->specs, $this->source, $this->dest);

        $MediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertTrue($MediaDest->getHeight() <= 240);
        $this->assertTrue($MediaDest->getWidth() <= 320);
    }

    /**
     * @covers MediaAlchemyst\Transmuter\Image2Image::execute
     */
    public function testExecuteOutBoundResize()
    {
        $this->specs->setDimensions(240, 260);
        $this->specs->setStrip(true);
        $this->specs->setRotationAngle(-90);
        $this->specs->setResizeMode(Image::RESIZE_MODE_OUTBOUND);

        $this->object->execute($this->specs, $this->source, $this->dest);

        $MediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals(240, $MediaDest->getHeight());
        $this->assertEquals(260, $MediaDest->getWidth());
   }

    /**
     * @covers MediaAlchemyst\Transmuter\Image2Image::execute
     */
    public function testExecuteInSetFixedRatio()
    {
        $this->specs->setDimensions(200, 200);
        $this->specs->setResizeMode(Image::RESIZE_MODE_INBOUND_FIXEDRATIO);

        $this->object->execute($this->specs, $this->source, $this->dest);

        $MediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertTrue(200 >= $MediaDest->getHeight());
        $this->assertTrue(200 >= $MediaDest->getWidth());

        $this->assertEquals(round($this->source->getWidth() / $this->source->getHeight()), round($MediaDest->getWidth() / $MediaDest->getHeight()));
    }

   /**
     * @covers MediaAlchemyst\Transmuter\Image2Image::execute
     * @covers MediaAlchemyst\Exception\SpecNotSupportedException
     * @expectedException \MediaAlchemyst\Exception\SpecNotSupportedException
     */
    public function testWrongSpecs()
    {
        $this->object->execute(new UnknownSpecs(), $this->source, $this->dest);
    }

    /**
     * @covers MediaAlchemyst\Transmuter\Image2Image::execute
     */
    public function testExecuteRawImage()
    {
        $source = $this->getMediaVorus()->guess(__DIR__ . '/../../../files/RAW_CANON_40D_RAW_V105.cr2');
        $this->object->execute($this->specs, $source, $this->dest);

        $MediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals(1936, $MediaDest->getWidth());
        $this->assertEquals(1288, $MediaDest->getHeight());
   }

   public function testWithMultiLayer()
   {
        $source = $this->getMediaVorus()->guess(__DIR__ . '/../../../files/multi-layer.psd');
        $this->object->execute($this->specs, $source, $this->dest);

        $this->assertFileExists($this->dest);
   }
}
