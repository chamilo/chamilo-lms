<?php

namespace MediaVorus;

use FFMpeg\FFProbe;
use MediaVorus\Media\MediaInterface;
use MediaVorus\Filter\MediaType;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use PHPExiftool\Writer;
use PHPExiftool\Reader;

class MediaCollectionTest extends TestCase
{

    /**
     * @covers MediaVorus\MediaCollection::match
     */
    public function testMatch()
    {
        $logger = new Logger('test');
        $logger->pushHandler(new NullHandler());

        $mediavorus = new MediaVorus(Reader::create($logger), Writer::create($logger), FFProbe::create());

        $collection = $mediavorus->inspectDirectory(__DIR__ . '/../../files/');
        $audio = $collection->match(new MediaType(MediaInterface::TYPE_AUDIO));

        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $audio);
        $this->assertGreaterThan(0, $audio->count());

        foreach ($audio as $audio) {
            $this->assertEquals(MediaInterface::TYPE_AUDIO, $audio->getType());
        }

        $notAudio = $collection->match(new MediaType(MediaInterface::TYPE_AUDIO), true);
        $this->assertGreaterThan(0, $notAudio->count());

        $this->assertInstanceOf('\\Doctrine\\Common\\Collections\\ArrayCollection', $notAudio);

        foreach ($notAudio as $audio) {
            $this->assertFalse(MediaInterface::TYPE_AUDIO === $audio->getType());
        }
    }
}
