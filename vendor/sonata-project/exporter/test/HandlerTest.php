<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
