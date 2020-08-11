<?php

namespace Ddeboer\DataImport\Tests\Writer;

use Ddeboer\DataImport\Writer\BatchWriter;

class BatchWriterTest extends \PHPUnit_Framework_TestCase
{
    public function testWriteItem()
    {
        $delegate = $this->getMock('Ddeboer\DataImport\Writer');
        $writer = new BatchWriter($delegate);

        $delegate->expects($this->once())
            ->method('prepare');

        $delegate->expects($this->never())
            ->method('writeItem');

        $writer->prepare();
        $writer->writeItem(['Test']);
    }

    public function testFlush()
    {
        $delegate = $this->getMock('Ddeboer\DataImport\Writer');
        $writer = new BatchWriter($delegate);

        $delegate->expects($this->exactly(20))
            ->method('writeItem');

        $writer->prepare();

        for ($i = 0; $i < 20; $i++) {
            $writer->writeItem(['Test']);
        }
    }
}
