<?php

namespace PHPExiftool\Test\Server;

require_once __DIR__ . '/../AbstractWriterTest.php';

use PHPExiftool\ExiftoolServer;
use PHPExiftool\Test\AbstractWriterTest;

class WriterTest extends AbstractWriterTest
{
    protected $exiftool;

    public function setUp()
    {
        $this->markTestSkipped('Currently disable server support');
        $this->exiftool = new ExiftoolServer();
        $this->exiftool->start();

        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();

        if ($this->exiftool) {
            $this->exiftool->stop();
        }
    }

    protected function getExiftool()
    {
        return $this->exiftool;
    }
}
