<?php

namespace MediaVorus;

use FFMpeg\FFProbe;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use PHPExiftool\Reader;
use PHPExiftool\Writer;

class FileTest extends TestCase
{

    /**
     * Instantiate mediavorus to register the mime types
     */
    public function setUp()
    {
        parent::setUp();
        $logger = new Logger('test');
        $logger->pushHandler(new NullHandler());

        $mediavorus = new MediaVorus(Reader::create($logger), Writer::create($logger), FFProbe::create());
    }

    /**
     * @covers MediaVorus\File::__construct
     * @covers MediaVorus\File::getMimeType
     */
    public function testGetMimeType()
    {
        $object = new File(__DIR__ . '/../../files/CanonRaw.cr2');
        $this->assertEquals('image/x-tika-canon', $object->getMimeType());
    }

    /**
     * @covers MediaVorus\File::__construct
     * @covers MediaVorus\File::getMimeType
     */
    public function testGetMimeTypeApe()
    {
        $object = new File(__DIR__ . '/../../files/APE.ape');
        $this->assertEquals('audio/x-monkeys-audio', $object->getMimeType());
    }

    /**
     * @covers MediaVorus\File::__construct
     * @covers MediaVorus\Exception\ExceptionInterface
     * @covers MediaVorus\Exception\FileNotFoundException
     * @expectedException MediaVorus\Exception\FileNotFoundException
     */
    public function testFileNotFound()
    {
        new File(__DIR__ . '/../../files/nonExistentFile');
    }
}
