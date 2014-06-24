<?php

namespace Exporter\Test;

use Exporter\Handler;

class HandlerTest extends \PHPUnit_Framework_TestCase
{

    public function testHandler()
    {
        $source = $this->getMock('Exporter\Source\SourceIteratorInterface');
        $writer = $this->getMock('Exporter\Writer\WriterInterface');
        $writer->expects($this->once())->method('open');
        $writer->expects($this->once())->method('close');

        $exporter = new Handler($source, $writer);
        $exporter->export();
    }
}
