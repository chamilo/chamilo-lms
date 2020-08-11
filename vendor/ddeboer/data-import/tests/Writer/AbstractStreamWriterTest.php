<?php

namespace Ddeboer\DataImport\Tests\Writer;

class AbstractStreamWriterTest extends StreamWriterTest
{
    protected function setUp()
    {
        $this->writer = $this->getMockForAbstractClass('Ddeboer\\DataImport\\Writer\\AbstractStreamWriter');
    }

    public function testItImplementsWriterInterface()
    {
        $this->assertInstanceOf('Ddeboer\\DataImport\\Writer', $this->writer);
    }

    public function testItThrowsInvalidArgumentExceptionOnInvalidStream()
    {
        $invalidStreams = array(0, 1, null, 'stream', new \stdClass());
        foreach ($invalidStreams as $invalidStream) {
            try {
                $this->writer->setStream($invalidStream);
                $this->fail('Above call should throw exception');
            } catch (\InvalidArgumentException $exception) {
                $this->assertContains('Expects argument to be a stream resource', $exception->getMessage());
            }
        }
    }

    public function testGetStreamReturnsAStreamResource()
    {
        $this->assertTrue('resource' == gettype($stream = $this->writer->getStream()), 'getStream should return a resource');
        $this->assertEquals('stream', get_resource_type($stream));
    }

    public function testSetStream()
    {
        $this->assertSame($this->writer, $this->writer->setStream($this->getStream()));
        $this->assertSame($this->getStream(), $this->writer->getStream());
    }

    public function testCloseOnFinishIsInhibitable()
    {
        $this->assertTrue($this->writer->getCloseStreamOnFinish());
        $this->assertSame($this->writer, $this->writer->setCloseStreamOnFinish(false));
        $this->assertFalse($this->writer->getCloseStreamOnFinish());
        $this->assertSame($this->writer, $this->writer->setCloseStreamOnFinish(true));
        $this->assertTrue($this->writer->getCloseStreamOnFinish());
    }

    public function testCloseOnFinishIsFalseForAutoOpenedStreams()
    {
        $this->writer->setCloseStreamOnFinish(true);
        $this->writer->getStream();
        $this->assertFalse($this->writer->getCloseStreamOnFinish());
    }

    public function testFinishCloseStreamAccordingToCloseOnFinishState()
    {
        $stream = $this->getStream();
        $this->writer->setStream($stream);
        $this->writer->prepare();

        $this->writer->setCloseStreamOnFinish(false);
        $this->writer->finish();
        $this->assertTrue(is_resource($stream));

        $this->writer->setCloseStreamOnFinish(true);
        $this->writer->finish();
        $this->assertFalse(is_resource($stream));
    }
}
