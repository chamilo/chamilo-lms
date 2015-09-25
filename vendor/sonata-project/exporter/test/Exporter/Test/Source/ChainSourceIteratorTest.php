<?php

namespace Exporter\Test\Source;

use Exporter\Source\ChainSourceIterator;

class ChainSourceIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function testIterator()
    {
        $source = $this->getMock('Exporter\Source\SourceIteratorInterface');

        $iterator = new ChainSourceIterator(array($source));

        foreach ($iterator as $data) {
        }
    }
}
