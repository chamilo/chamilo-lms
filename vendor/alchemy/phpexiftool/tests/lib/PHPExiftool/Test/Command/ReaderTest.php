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
