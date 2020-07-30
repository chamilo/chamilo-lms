<?php

namespace MediaAlchemyst\Tests\Transmuter;

use MediaAlchemyst\DriversContainer;
use MediaAlchemyst\Specification\Audio;
use MediaAlchemyst\Tests\AbstractAlchemystTester;
use MediaAlchemyst\Transmuter\Video2Audio;
use MediaAlchemyst\Tests\Specification\UnknownSpecs;

class Video2AudioTest extends AbstractAlchemystTester
{
    /**
     * @var Video2Audio
     */
    protected $object;

    /**
     * @var Audio
     */
    protected $specs;
    protected $source;
    protected $dest;

    protected function setUp()
    {
        $this->object = new Video2Audio(new DriversContainer(), $this->getFsManager());

        $this->specs = new Audio();
        $this->source = $this->getMediaVorus()->guess(__DIR__ . '/../../../files/Test.ogv');
        $this->dest = __DIR__ . '/../../../files/output_.mp3';
    }

    protected function tearDown()
    {
        if (file_exists($this->dest) && is_writable($this->dest)) {
            unlink($this->dest);
        }
    }

    /**
     * @covers MediaAlchemyst\Transmuter\Video2Audio::execute
     */
    public function testExecute()
    {
        $this->object->execute($this->specs, $this->source, $this->dest);

        $mediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals('audio/mpeg', $mediaDest->getFile()->getMimeType());
        $this->assertEquals(round($this->source->getDuration()), round($mediaDest->getDuration()));
    }

    /**
     * @covers MediaAlchemyst\Transmuter\Video2Audio::execute
     * @covers MediaAlchemyst\Transmuter\Video2Audio::getFormatFromFileType
     */
    public function testExecuteWithOptions()
    {
        $this->specs->setAudioCodec('libmp3lame');
        $this->specs->setAudioSampleRate(16000);
        $this->specs->setAudioKiloBitrate(256);

        $this->object->execute($this->specs, $this->source, $this->dest);

        $mediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals('audio/mpeg', $mediaDest->getFile()->getMimeType());
        $this->assertEquals(round($this->source->getDuration()), round($mediaDest->getDuration()));
    }

    /**
     * @covers MediaAlchemyst\Transmuter\Video2Audio::execute
     * @covers MediaAlchemyst\Exception\SpecNotSupportedException
     * @expectedException \MediaAlchemyst\Exception\SpecNotSupportedException
     */
    public function testWrongSpecs()
    {
        $this->object->execute(new UnknownSpecs(), $this->source, $this->dest);
    }

}