<?php
/**
 * This file is part of the PHPExiftool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
