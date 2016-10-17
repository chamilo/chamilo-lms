<?php
/**
 * This file is part of the PHPExiftool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Test\Command;

require_once __DIR__ . '/../AbstractWriterTest.php';

use Monolog\Logger;
use Monolog\Handler\NullHandler;
use PHPExiftool\Exiftool;
use PHPExiftool\Test\AbstractWriterTest;

class WriterTest extends AbstractWriterTest
{

    protected function getExiftool()
    {
        $logger = new Logger('Tests');
        $logger->pushHandler(new NullHandler());

        return new Exiftool($logger);
    }
}
