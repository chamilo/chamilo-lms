<?php

namespace Ddeboer\DataImport\Tests\Writer;

use Ddeboer\DataImport\Writer\StreamMergeWriter;

class StreamMergeWriterTest extends AbstractStreamWriterTest
{
    /** @var StreamMergeWriter */
    protected $writer;

    protected function setUp()
    {
        parent::setUp();

        $this->writer = new StreamMergeWriter();
    }

    public function testItIsInstantiable()
    {
        $this->assertInstanceOf('Ddeboer\DataImport\Writer\StreamMergeWriter', $this->writer);
    }

    public function testItIsAStreamWriter()
    {
        $this->assertInstanceOf('Ddeboer\DataImport\Writer\AbstractStreamWriter', $this->writer);
    }

    public function testDiscriminantField()
    {
        $this->assertSame($this->writer, $this->writer->setDiscriminantField('foo'));
        $this->assertSame('foo', $this->writer->getDiscriminantField());
    }

    public function testDiscriminantFieldDefaultsToDiscr()
    {
        $this->assertSame('discr', $this->writer->getDiscriminantField());
    }

    public function testSetStreamWriterForSpecificDiscrValue()
    {
        $fooWriter = $this->getMockBuilder('Ddeboer\DataImport\Writer\AbstractStreamWriter')
            ->setMethods(array('setStream'))
            ->getMockForAbstractClass();

        $this->writer->setStream($stream = $this->getStream());
        $fooWriter
            ->expects($this->once())
            ->method('setStream')
            ->with($stream)
            ->will($this->returnSelf());

        $this->assertSame($this->writer, $this->writer->setStreamWriter('foo', $fooWriter));
        $this->assertSame($fooWriter, $this->writer->getStreamWriter('foo'));
    }

    public function testHasStreamWriter()
    {
        $fooWriter = $this->getMockBuilder('Ddeboer\DataImport\Writer\AbstractStreamWriter')
            ->getMockForAbstractClass();

        $this->assertFalse($this->writer->hasStreamWriter('foo'), 'no foo stream writer should be registered');
        $this->writer->setStreamWriter('foo', $fooWriter);
        $this->assertTrue($this->writer->hasStreamWriter('foo'), 'foo stream writer should be registered');
    }

    public function testStreamWriters()
    {
        $fooWriter = $this->getMockBuilder('Ddeboer\DataImport\Writer\AbstractStreamWriter')
            ->getMockForAbstractClass();
        $barWriter = $this->getMockBuilder('Ddeboer\DataImport\Writer\AbstractStreamWriter')
            ->getMockForAbstractClass();
        $writers = array(
            'foo' => $fooWriter,
            'bar' => $barWriter,
        );

        $this->assertSame($this->writer, $this->writer->setStreamWriters($writers));
        $this->assertSame($writers, $this->writer->getStreamWriters());
    }

    public function testWriteItem()
    {
        $fooWriter = $this->getMockBuilder('Ddeboer\DataImport\Writer\AbstractStreamWriter')
            ->getMockForAbstractClass();
        $barWriter = $this->getMockBuilder('Ddeboer\DataImport\Writer\AbstractStreamWriter')
            ->getMockForAbstractClass();
        $writers = array(
            'foo' => $fooWriter,
            'bar' => $barWriter,
        );
        $this->writer->setStreamWriters($writers);
        $this->writer->setDiscriminantField('foo');

        $barItem = array('foo' => 'bar');
        $fooWriter->expects($this->never())
            ->method('writeItem');
        $barWriter->expects($this->once())
            ->method('writeItem')
            ->with($barItem)
            ->will($this->returnSelf());

        $this->writer->writeItem($barItem);
    }

    public function testSetStream()
    {
        $fooWriter = $this->getMockBuilder('Ddeboer\DataImport\Writer\AbstractStreamWriter')
            ->getMockForAbstractClass();
        $barWriter = $this->getMockBuilder('Ddeboer\DataImport\Writer\AbstractStreamWriter')
            ->getMockForAbstractClass();
        $writers = array(
            'foo' => $fooWriter,
            'bar' => $barWriter,
        );
        $stream = $this->writer->getStream();
        $this->writer->setStreamWriters($writers);

        $this->assertSame($stream, $fooWriter->getStream());
        $this->assertSame($stream, $barWriter->getStream());

        $this->assertSame($this->writer, $this->writer->setStream($stream = $this->getStream()));

        $this->assertSame($stream, $fooWriter->getStream());
        $this->assertSame($stream, $barWriter->getStream());
    }

    public function testSetWriterShouldInhibitStreamClose()
    {
        $fooWriter = $this->getMockBuilder('Ddeboer\DataImport\Writer\AbstractStreamWriter')
            ->setMethods(array('setCloseStreamOnFinish'))
            ->getMockForAbstractClass();

        $fooWriter
            ->expects($this->once())
            ->method('setCloseStreamOnFinish')->with(false)
            ->will($this->returnArgument(0));

        $this->writer->setStreamWriter('foo', $fooWriter);
    }
}
