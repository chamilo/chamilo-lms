<?php

namespace PHPExiftool\Test\Server;

require_once __DIR__ . '/../AbstractReaderTest.php';

use PHPExiftool\Test\AbstractReaderTest;
use PHPExiftool\ExiftoolServer;
use PHPExiftool\Reader;
use PHPExiftool\RDFParser;

class ReaderTest extends AbstractReaderTest
{
    protected $exiftool;

    protected function setUp()
    {
        $this->exiftool = new ExiftoolServer();
        $this->exiftool->start();

        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();

        if ($this->exiftool) {
            $this->exiftool->stop();
        }
    }

    protected function getReader()
    {
        return new Reader($this->exiftool, new RDFParser());
    }
}