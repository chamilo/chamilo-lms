<?php

namespace MediaAlchemyst\Tests;

use FFMpeg\Exception\ExecutableNotFoundException as FFMpegExecutableNotFound;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use MediaVorus\MediaVorus;
use PHPExiftool\Reader;
use PHPExiftool\RDFParser;
use PHPExiftool\Writer;
use PHPExiftool\Exiftool;
use FFMpeg\FFProbe;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use MediaVorus\Utils\AudioMimeTypeGuesser;
use MediaVorus\Utils\PostScriptMimeTypeGuesser;
use MediaVorus\Utils\RawImageMimeTypeGuesser;
use MediaVorus\Utils\VideoMimeTypeGuesser;
use Neutron\TemporaryFilesystem\Manager;
use Neutron\TemporaryFilesystem\TemporaryFilesystem;
use Symfony\Component\Filesystem\Filesystem;

class AbstractAlchemystTester extends \PHPUnit_Framework_TestCase
{
    public function getMediaVorus()
    {
        static $initialized;

        if (null === $initialized) {
            $guesser = MimeTypeGuesser::getInstance();
            $guesser->register(new AudioMimeTypeGuesser());
            $guesser->register(new PostScriptMimeTypeGuesser());
            $guesser->register(new RawImageMimeTypeGuesser());
            $guesser->register(new VideoMimeTypeGuesser());
            $initialized = true;
        }

        return new MediaVorus($this->getReader(), $this->getWriter(), $this->getProbe());
    }

    public function getExiftool()
    {
        $logger = new Logger('test');
        $logger->pushHandler(new NullHandler());

        return new Exiftool($logger);
    }

    public function getReader()
    {
        return new Reader($this->getExiftool(), new RDFParser());
    }

    public function getWriter()
    {
        return new Writer($this->getExiftool());
    }

    public function getProbe()
    {
        try {
            return FFProbe::create();
        } catch (FFMpegExecutableNotFound $e) {

        }

        return null;
    }

    public function getFsManager()
    {
        $fs = new Filesystem();

        return new Manager(new TemporaryFilesystem($fs), $fs);
    }
}
