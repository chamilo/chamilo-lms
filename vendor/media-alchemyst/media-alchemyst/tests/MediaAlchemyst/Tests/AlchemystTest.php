<?php

namespace MediaAlchemyst\Tests;

use MediaAlchemyst\Alchemyst;
use MediaAlchemyst\Specification\Audio;
use MediaAlchemyst\Specification\Image;
use MediaAlchemyst\Specification\Flash;
use MediaAlchemyst\Specification\Video;
use MediaVorus\Media\MediaInterface;
use Symfony\Component\Process\ExecutableFinder;

class AlchemystTest extends AbstractAlchemystTester
{
    /**
     * @var Alchemyst
     */
    protected $object;
    protected $specsAudio;
    protected $specsFlash;
    protected $specsImage;
    protected $specsVideo;

    /**
     * @covers MediaAlchemyst\Alchemyst::__construct
     */
    protected function setUp()
    {
        $this->object = Alchemyst::create();

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
        $this->object->turnInto(__DIR__ . '/../../files/invalid.file', 'here.mpg', $this->getMock('MediaAlchemyst\Specification\SpecificationInterface'));
    }

    /**
     * @covers MediaAlchemyst\Alchemyst::turnInto
     * @covers MediaAlchemyst\Alchemyst::routeAction
     */
    public function testTurnIntoAudioAudio()
    {
        $dest = __DIR__ . '/../../files/output.flac';

        $this->object->turnInto(__DIR__ . '/../../files/Audio.mp3', $dest, $this->specsAudio);

        $media = $this->getMediaVorus()->guess($dest);
        $this->assertEquals(MediaInterface::TYPE_AUDIO, $media->getType());

        unlink($dest);
    }

    /**
     * @covers MediaAlchemyst\Alchemyst::turnInto
     * @covers MediaAlchemyst\Alchemyst::routeAction
     */
    public function testTurnIntoFlashImage()
    {
        $dest = __DIR__ . '/../../files/output.png';

        $this->object->turnInto(__DIR__ . '/../../files/flashfile.swf', $dest, $this->specsImage);

        $media = $this->getMediaVorus()->guess($dest);
        $this->assertEquals(MediaInterface::TYPE_IMAGE, $media->getType());

        unlink($dest);
    }

    /**
     * @covers MediaAlchemyst\Alchemyst::turnInto
     * @covers MediaAlchemyst\Alchemyst::routeAction
     */
    public function testTurnIntoDocumentImage()
    {
        $executableFinder = new ExecutableFinder();
        if ( ! $executableFinder->find('unoconv')) {
            $this->markTestSkipped('Unoconv is not installed');
        }

        $dest = __DIR__ . '/../../files/output.png';

        $this->object->turnInto(__DIR__ . '/../../files/Hello.odt', $dest, $this->specsImage);

        $media = $this->getMediaVorus()->guess($dest);
        $this->assertEquals(MediaInterface::TYPE_IMAGE, $media->getType());

        unlink($dest);
    }

    /**
     * @covers MediaAlchemyst\Alchemyst::turnInto
     * @covers MediaAlchemyst\Alchemyst::routeAction
     */
    public function testTurnIntoDocumentFlash()
    {
        $executableFinder = new ExecutableFinder();
        if ( ! $executableFinder->find('unoconv')) {
            $this->markTestSkipped('Unoconv is not installed');
        }

        $dest = __DIR__ . '/../../files/output.swf';

        $this->object->turnInto(__DIR__ . '/../../files/Hello.odt', $dest, $this->specsFlash);

        $media = $this->getMediaVorus()->guess($dest);
        $this->assertEquals(MediaInterface::TYPE_FLASH, $media->getType());

        unlink($dest);
    }

    /**
     * @covers MediaAlchemyst\Alchemyst::turnInto
     * @covers MediaAlchemyst\Alchemyst::routeAction
     */
    public function testTurnIntoImageImage()
    {
        $dest = __DIR__ . '/../../files/output.png';

        $this->object->turnInto(__DIR__ . '/../../files/photo03.JPG', $dest, $this->specsImage);

        $media = $this->getMediaVorus()->guess($dest);
        $this->assertEquals(MediaInterface::TYPE_IMAGE, $media->getType());

        unlink($dest);
    }

    /**
     * @covers MediaAlchemyst\Alchemyst::turnInto
     * @covers MediaAlchemyst\Alchemyst::routeAction
     */
    public function testTurnIntoVideoImage()
    {
        $dest = __DIR__ . '/../../files/output.png';

        $this->object->turnInto(__DIR__ . '/../../files/Test.ogv', $dest, $this->specsImage);

        $media = $this->getMediaVorus()->guess($dest);
        $this->assertEquals(MediaInterface::TYPE_IMAGE, $media->getType());

        unlink($dest);
    }

    /**
     * @covers MediaAlchemyst\Alchemyst::turnInto
     * @covers MediaAlchemyst\Alchemyst::routeAction
     */
    public function testTurnIntoVideoVideo()
    {
        $dest = __DIR__ . '/../../files/output.webm';

        $this->object->turnInto(__DIR__ . '/../../files/Test.ogv', $dest, $this->specsVideo);

        $media = $this->getMediaVorus()->guess($dest);
        $this->assertEquals(MediaInterface::TYPE_VIDEO, $media->getType());

        unlink($dest);
    }
}
