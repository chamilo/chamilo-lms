<?php

namespace MediaAlchemyst\Tests;

use MediaAlchemyst\DriversContainer;
use MediaAlchemyst\Alchemyst;
use MediaAlchemyst\Specification\Audio;
use MediaAlchemyst\Specification\Video;
use MediaAlchemyst\Specification\Flash;
use MediaAlchemyst\Specification\Image;

class AlchemystNoBinaryTest extends AbstractAlchemystTester
{
    protected $specsAudio;
    protected $specsFlash;
    protected $specsImage;
    protected $specsVideo;

    protected function setUp()
    {
        $this->specsAudio = new Audio();
        $this->specsFlash = new Flash();
        $this->specsVideo = new Video();
        $this->specsVideo->setDimensions(320, 240);
        $this->specsImage = new Image();
        $this->specsImage->setDimensions(320, 240);
    }

    /**
     * @expectedException MediaAlchemyst\Exception\FileNotFoundException
     */
    public function testOpenUnknownFile()
    {
        $driversContainer = new DriversContainer();
        $object = new Alchemyst($driversContainer, $this->getFsManager());

        $object->turnInto(__DIR__ . '/../../files/invalid.file', 'dest.mpg', $this->getMock('MediaAlchemyst\Specification\SpecificationInterface'));
    }

    /**
     * @expectedException MediaAlchemyst\Exception\RuntimeException
     */
    public function testTurnIntoAudioAudio()
    {
        $driversContainer = new DriversContainer();
        $driversContainer['configuration'] = array(
            'ffmpeg.ffmpeg.binaries'       => 'nofile',
        );
        $object = new Alchemyst($driversContainer, $this->getFsManager());

        $dest = __DIR__ . '/../../files/output.flac';

        $object->turnInto(__DIR__ . '/../../files/Audio.mp3', $dest, $this->specsAudio);
    }

    /**
     * @expectedException MediaAlchemyst\Exception\RuntimeException
     */
    public function testTurnIntoAudioAudioWithFFProbe()
    {
        $driversContainer = new DriversContainer();
        $driversContainer['configuration'] = array(
            'ffmpeg.ffprobe.binaries'       => 'nofile',
        );
        $object = new Alchemyst($driversContainer, $this->getFsManager());

        $dest = __DIR__ . '/../../files/output.flac';

        $object->turnInto(__DIR__ . '/../../files/Audio.mp3', $dest, $this->specsAudio);
    }

    /**
     * @expectedException MediaAlchemyst\Exception\RuntimeException
     */
    public function testTurnIntoFlashImage()
    {
        $driversContainer = new DriversContainer();
        $driversContainer['configuration'] = array(
            'swftools.swfrender.binaries'  => 'nofile',
        );
        $object = new Alchemyst($driversContainer, $this->getFsManager());
        $dest = __DIR__ . '/../../files/output.png';

        $object->turnInto(__DIR__ . '/../../files/flashfile.swf', $dest, $this->specsImage);
    }

    /**
     * @expectedException MediaAlchemyst\Exception\RuntimeException
     */
    public function testTurnIntoDocumentImage()
    {
        $driversContainer = new DriversContainer();
        $driversContainer['configuration'] = array(
            'unoconv.binaries'                  => 'nofile',
        );
        $object = new Alchemyst($driversContainer, $this->getFsManager());
        $dest = __DIR__ . '/../../files/output.png';

        $object->turnInto(__DIR__ . '/../../files/Hello.odt', $dest, $this->specsImage);
    }

    /**
     * @expectedException MediaAlchemyst\Exception\RuntimeException
     */
    public function testTurnIntoDocumentFlash()
    {
        $driversContainer = new DriversContainer();
        $driversContainer['configuration'] = array(
            'swftools.pdf2swf.binaries'                  => 'nofile',
        );
        $object = new Alchemyst($driversContainer, $this->getFsManager());
        $dest = __DIR__ . '/../../files/output.swf';

        $object->turnInto(__DIR__ . '/../../files/Hello.odt', $dest, $this->specsFlash);
    }

    /**
     * @expectedException MediaAlchemyst\Exception\RuntimeException
     */
    public function testTurnIntoDocumentFlashWithUnoconv()
    {
        $driversContainer = new DriversContainer();
        $driversContainer['configuration'] = array(
            'unoconv.binaries'                  => 'nofile',
        );
        $object = new Alchemyst($driversContainer, $this->getFsManager());
        $dest = __DIR__ . '/../../files/output.swf';

        $object->turnInto(__DIR__ . '/../../files/Hello.odt', $dest, $this->specsFlash);
    }

    /**
     * @expectedException MediaAlchemyst\Exception\RuntimeException
     */
    public function testTurnIntoVideoImage()
    {
        $driversContainer = new DriversContainer();
        $driversContainer['configuration'] = array(
            'ffmpeg.ffmpeg.binaries'       => 'nofile',
        );
        $object = new Alchemyst($driversContainer, $this->getFsManager());
        $dest = __DIR__ . '/../../files/output.png';

        $object->turnInto(__DIR__ . '/../../files/Test.ogv', $dest, $this->specsImage);
    }

    /**
     * @expectedException MediaAlchemyst\Exception\RuntimeException
     */
    public function testTurnIntoVideoImageWithFFprobe()
    {
        $driversContainer = new DriversContainer();
        $driversContainer['configuration'] = array(
            'ffmpeg.ffprobe.binaries'       => 'nofile',
        );
        $object = new Alchemyst($driversContainer, $this->getFsManager());
        $dest = __DIR__ . '/../../files/output.png';

        $object->turnInto(__DIR__ . '/../../files/Test.ogv', $dest, $this->specsImage);
    }

    /**
     * @expectedException MediaAlchemyst\Exception\RuntimeException
     */
    public function testTurnIntoVideoVideo()
    {
        $driversContainer = new DriversContainer();
        $driversContainer['configuration'] = array(
            'ffmpeg.ffmpeg.binaries'       => 'nofile',
        );
        $object = new Alchemyst($driversContainer, $this->getFsManager());
        $dest = __DIR__ . '/../../files/output.webm';

        $object->turnInto(__DIR__ . '/../../files/Test.ogv', $dest, $this->specsVideo);
    }

    /**
     * @expectedException MediaAlchemyst\Exception\RuntimeException
     */
    public function testTurnIntoVideoVideoWithFFProbe()
    {
        $driversContainer = new DriversContainer();
        $driversContainer['configuration'] = array(
            'ffmpeg.ffprobe.binaries'       => 'nofile',
        );
        $object = new Alchemyst($driversContainer, $this->getFsManager());
        $dest = __DIR__ . '/../../files/output.webm';

        $object->turnInto(__DIR__ . '/../../files/Test.ogv', $dest, $this->specsVideo);
    }
}
