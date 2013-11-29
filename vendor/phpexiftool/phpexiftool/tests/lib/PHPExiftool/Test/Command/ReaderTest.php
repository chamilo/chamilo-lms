<?php

namespace PHPExiftool\Test\Command;

require_once __DIR__ . '/../AbstractReaderTest.php';

use Monolog\Logger;
use Monolog\Handler\NullHandler;
use PHPExiftool\Test\AbstractReaderTest;
use PHPExiftool\Reader;

class ReaderTest extends AbstractReaderTest
{

    protected function getReader()
    {
        $logger = new Logger('Test');
        $logger->pushHandler(new NullHandler());

        return Reader::create($logger);
    }
}