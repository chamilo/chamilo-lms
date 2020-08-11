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

require_once __DIR__ . '/../AbstractPreviewExtractorTest.php';

use PHPExiftool\Test\AbstractPreviewExtractorTest;
use PHPExiftool\ExiftoolServer;

class PreviewExtractor extends AbstractPreviewExtractorTest
{
    protected $exiftool;

    public function setUp()
    {
        $this->markTestSkipped('Currently disable server support');
        $this->exiftool = new ExiftoolServer;
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
