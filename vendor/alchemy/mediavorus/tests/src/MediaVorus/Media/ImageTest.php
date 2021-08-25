<?php

namespace MediaVorus\Media;

use FFMpeg\FFProbe;
use MediaVorus\File;
use MediaVorus\MediaVorus;
use MediaVorus\Media\MediaInterface;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use PHPExiftool\Reader;
use PHPExiftool\Writer;

class ImageTest extends MediaTestCase
{
    /**
     * @var Image
     */
    protected $object;
    protected $mediavorus;
    protected $reader;
    protected $writer;
    protected $ffprobe;

    public function setUp()
    {
        parent::setUp();
        $logger = new Logger('Tests');
        $logger->pushHandler(new NullHandler());

        $this->reader = Reader::create($logger);
        $this->writer = Writer::create($logger);
        $file = __DIR__ . '/../../../files/ExifTool.jpg';

        $this->object = new Image(new File($file), $this->reader->reset()->files($file)->first(), $this->writer);

        $logger = new Logger('test');
        $logger->pushHandler(new NullHandler());
        $this->ffprobe = FFProbe::create();

        $this->mediavorus = new MediaVorus($this->reader, $this->writer, $this->ffprobe);
    }

    /**
     * @covers \MediaVorus\Media\Image::getType
     */
    public function testGetType()
    {
        $this->assertEquals(MediaInterface::TYPE_IMAGE, $this->object->getType());
    }

    /**
     * @covers \MediaVorus\Media\Image::isRawImage
     */
    public function testIsrawImage()
    {
        $this->assertFalse($this->object->isRawImage());

        $object = $this->mediavorus->guess(__DIR__ . '/../../../files/CanonRaw.cr2');
        $this->assertTrue($object->isRawImage());
    }

    /**
     * @covers \MediaVorus\Media\Image::getWidth
     * @covers \MediaVorus\Media\Image::extractFromDimensions
     */
    public function testGetWidth()
    {
        $this->assertTrue(is_int($this->object->getWidth()));
        $this->assertEquals(8, $this->object->getWidth());

        $objects = $this->mediavorus->inspectDirectory(__DIR__ . '/../../../files/');
        foreach ($objects as $object) {
            if ($object->getType() == MediaInterface::TYPE_IMAGE) {

                if (in_array($object->getFile()->getFilename(), array('KyoceraRaw.raw', 'Font.dfont', 'XMP.svg'))) {
                    $this->assertNull($object->getWidth());
                } else {
                    $this->assertTrue(is_int($object->getWidth()), $object->getFile()->getFilename() . " has int width");
                }
            }
        }
    }

    /**
     * @covers \MediaVorus\Media\Image::getHeight
     * @covers \MediaVorus\Media\Image::extractFromDimensions
     */
    public function testGetHeight()
    {
        $this->assertTrue(is_int($this->object->getHeight()));
        $this->assertEquals(8, $this->object->getHeight());

        $objects = $this->mediavorus->inspectDirectory(__DIR__ . '/../../../files/');
        foreach ($objects as $object) {
            if ($object->getType() == MediaInterface::TYPE_IMAGE) {

                if (in_array($object->getFile()->getFilename(), array('KyoceraRaw.raw', 'Font.dfont', 'XMP.svg'))) {
                    $this->assertNull($object->getHeight());
                } else {
                    $this->assertTrue(is_int($object->getHeight()), $object->getFile()->getFilename() . " has int width");
                }
            }
        }
    }

    /**
     * @covers \MediaVorus\Media\Image::getChannels
     */
    public function testGetChannels()
    {
        $this->assertTrue(is_int($this->object->getChannels()));
        $this->assertEquals(3, $this->object->getChannels());
    }

    /**
     * @covers \MediaVorus\Media\Image::getFocalLength
     */
    public function testGetFocalLength()
    {
        $this->assertTrue(is_float($this->object->getFocalLength()));
        $this->assertEquals(6.0, $this->object->getFocalLength());
    }

    /**
     * @covers \MediaVorus\Media\Image::getColorDepth
     */
    public function testGetColorDepth()
    {
        $this->assertTrue(is_int($this->object->getColorDepth()));
        $this->assertEquals(8, $this->object->getColorDepth());
    }

    /**
     * @covers \MediaVorus\Media\Image::getCameraModel
     */
    public function testGetCameraModel()
    {
        $this->assertTrue(is_string($this->object->getCameraModel()));
    }

    /**
     * @covers \MediaVorus\Media\Image::getFlashFired
     */
    public function testGetFlashFired()
    {
        $this->assertTrue(is_bool($this->object->getFlashFired()));

        $object = $this->mediavorus->guess(__DIR__ . '/../../../files/photo01.JPG');
        $this->assertInstanceOf('\MediaVorus\Media\Image', $object);
        $this->assertFalse($object->getFlashFired());

        $object = $this->mediavorus->guess(__DIR__ . '/../../../files/CanonRaw.cr2');
        $this->assertInstanceOf('\MediaVorus\Media\Image', $object);
        $this->assertFalse($object->getFlashFired());

        $object = $this->mediavorus->guess(__DIR__ . '/../../../files/photoAutoNoFlash.jpg');
        $this->assertInstanceOf('\MediaVorus\Media\Image', $object);
        $this->assertFalse($object->getFlashFired());

        $object = $this->mediavorus->guess(__DIR__ . '/../../../files/PhotoFlash.jpg');
        $this->assertInstanceOf('\MediaVorus\Media\Image', $object);
        $this->assertTrue($object->getFlashFired());

        $object = $this->mediavorus->guess(__DIR__ . '/../../../files/videoFlashed.MOV');
        $this->assertInstanceOf('\MediaVorus\Media\Image', $object);
        $this->assertNull($object->getFlashFired());

        $object = $this->mediavorus->guess(__DIR__ . '/../../../files/XMP.xmp');
        $this->assertInstanceOf('\MediaVorus\Media\Image', $object);
        $this->assertFalse($object->getFlashFired());

        $object = $this->mediavorus->guess(__DIR__ . '/../../../files/DNG.dng');
        $this->assertInstanceOf('\MediaVorus\Media\Image', $object);
        $this->assertFalse($object->getFlashFired());

        $object = $this->mediavorus->guess(__DIR__ . '/../../../files/Panasonic.rw2');
        $this->assertInstanceOf('\MediaVorus\Media\Image', $object);
        $this->assertFalse($object->getFlashFired());
    }

    /**
     * @covers \MediaVorus\Media\Image::getAperture
     */
    public function testGetAperture()
    {
        $this->assertInternalType('float', $this->object->getAperture());
    }

    /**
     * @covers \MediaVorus\Media\Image::getShutterSpeed
     */
    public function testGetShutterSpeed()
    {
        $this->assertInternalType('float', $this->object->getShutterSpeed());
    }

    /**
     * @covers \MediaVorus\Media\Image::getOrientation
     */
    public function testGetOrientation()
    {
        $object1 = $this->mediavorus->guess(__DIR__ . '/../../../files/photo01.JPG');
        $object2 = $this->mediavorus->guess(__DIR__ . '/../../../files/photo02.JPG');
        $object3 = $this->mediavorus->guess(__DIR__ . '/../../../files/photo03.JPG');
        $object4 = $this->mediavorus->guess(__DIR__ . '/../../../files/Test.ogv');

        $this->assertEquals(Image::ORIENTATION_0, $this->object->getOrientation());
        $this->assertEquals(Image::ORIENTATION_90, $object1->getOrientation());
        $this->assertEquals(Image::ORIENTATION_180, $object2->getOrientation());
        $this->assertEquals(Image::ORIENTATION_270, $object3->getOrientation());
        $this->assertEquals(Image::ORIENTATION_0, $object4->getOrientation());
    }

    /**
     * @covers \MediaVorus\Media\Image::getCreationDate
     */
    public function testGetCreationDate()
    {
        $this->assertTrue(is_string($this->object->getCreationDate()));
    }

    /**
     * @covers \MediaVorus\Media\Image::getHyperfocalDistance
     */
    public function testGetHyperfocalDistance()
    {
        $this->assertInternalType('float', $this->object->getHyperfocalDistance());
    }

    /**
     * @covers \MediaVorus\Media\Image::getISO
     */
    public function testGetISO()
    {
        $this->assertTrue(is_int($this->object->getISO()));
        $this->assertEquals(100, $this->object->getISO());
    }

    /**
     * @covers \MediaVorus\Media\Image::getLightValue
     */
    public function testGetLightValue()
    {
        $this->assertInternalType('float', $this->object->getLightValue());
    }

    /**
     * @covers \MediaVorus\Media\Image::getColorSpace
     */
    public function testGetColorSpace()
    {
        $file = __DIR__ . '/../../../files/ExifTool.jpg';
        $media = new Image(new File($file), $this->reader->reset()->files($file)->first(), $this->writer);
        $this->assertEquals(Image::COLORSPACE_RGB, $media->getColorSpace());

        $file = __DIR__ . '/../../../files/GRAYSCALE.jpg';
        $media = new Image(new File($file), $this->reader->reset()->files($file)->first(), $this->writer);
        $this->assertEquals(Image::COLORSPACE_GRAYSCALE, $media->getColorSpace());

        $file = __DIR__ . '/../../../files/RVB.jpg';
        $media = new Image(new File($file), $this->reader->reset()->files($file)->first(), $this->writer);
        $this->assertEquals(Image::COLORSPACE_RGB, $media->getColorSpace());
    }

    public function testSerialize()
    {
        $json = $this->getSerializer()->serialize($this->object, 'json');

        $data = json_decode($json, true);

        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('height', $data);
        $this->assertArrayHasKey('width', $data);
    }
}
