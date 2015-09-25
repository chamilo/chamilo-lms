<?php

namespace Exporter\Test\Source;

use Exporter\Source\ArraySourceIterator;

class ArraySourceIteratorTest extends \PHPUnit_Framework_TestCase
{
    public function testHandler()
    {
        $data = array(
            array('john 1', 'doe', '1'),
            array('john 2', 'doe', '1'),
            array('john 3', 'doe', '1'),
            array('john 4', 'doe', '1'),
        );

        $iterator = new ArraySourceIterator($data);

        foreach ($iterator as $value) {
            $this->assertTrue(is_array($value));
            $this->assertEquals(3, count($value));
        }
    }
}
