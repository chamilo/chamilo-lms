<?php

namespace MediaVorus;

use PHPExiftool\Reader;
use PHPExiftool\Writer;
use FFMpeg\FFProbe;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use MediaVorus\Media\MediaInterface;

class MediaVorusTest extends TestCase
{
    /**
     * @var MediaVorus
     */
    protected $object;

    /**
     * @covers MediaVorus\MediaVorus::__construct
     */
    public function setUp()
    {
        parent::setUp();
        $logger = new Logger('test');
        $logger->pushHandler(new NullHandler());

        $this->object = new MediaVorus(Reader::create($logger), Writer::create($logger), FFProbe::create());
    }

    /**
     * @covers MediaVorus\MediaVorus::guess
     */
    public function testGuess()
    {
        $media = $this->object->guess(__DIR__ . '/../../files/ExifTool.jpg');
        $this->assertInstanceOf('\\MediaVorus\\Media\\MediaInterface', $media);
    }

    /**
     * @covers MediaVorus\MediaVorus::guessFromMimeType
     */
    public function testGuessFromMimeType()
    {
        $media = $this->object->guess(__DIR__ . '/../../files/ExifTool.jpg');
        $this->assertInstanceOf('\\MediaVorus\\Media\\Image', $media);
        $media = $this->object->guess(__DIR__ . '/../../files/CanonRaw.cr2');
        $this->assertInstanceOf('\\MediaVorus\\Media\\Image', $media);
        $media = $this->object->guess(__DIR__ . '/../../files/APE.ape');
        $this->assertInstanceOf('\\MediaVorus\\Media\\Audio', $media);

        $media = $this->object->guess(__DIR__ . '/../../files/PDF.pdf');
        $this->assertInstanceOf('\\MediaVorus\\Media\\Document', $media);
        $media = $this->object->guess(__DIR__ . '/../../files/ZIP.gz');
        $this->assertInstanceOf('\\MediaVorus\\Media\\DefaultMedia', $media);
        $media = $this->object->guess(__DIR__ . '/../../files/Flash.swf');
        $this->assertInstanceOf('\\MediaVorus\\Media\\Flash', $media);
        $media = $this->object->guess(__DIR__ . '/../../files/Test.ogv');
        $this->assertInstanceOf('\\MediaVorus\\Media\\Video', $media);
    }

    /**
     * @covers MediaVorus\MediaVorus::inspectDirectory
     */
    public function testInspectDirectory()
    {
        $medias = $this->object->inspectDirectory(__DIR__ . '/../../files');
        $this->assertInstanceOf('\\MediaVorus\\MediaCollection', $medias);
        $this->assertEquals(22, count($medias));

        foreach ($medias as $media) {
            if ($media->getFile()->getFilename() === 'KyoceraRaw.raw') {
                continue;
            }
            if (in_array($media->getFile()->getFilename(), array('XMP.svg', 'Font.dfont'))) {
                continue;
            }
            if ($media->getType() === MediaInterface::TYPE_IMAGE) {
                $this->assertInternalType('integer', $media->getWidth(), sprintf('Test width of %s', $media->getFile()->getFilename()));
            }
        }
    }

    /**
     * @covers MediaVorus\MediaVorus::create
     */
    public function testCreate()
    {
        $this->assertInstanceOf('MediaVorus\\MediaVorus', MediaVorus::create());
    }
}
