<?php

namespace Exporter\Test\Source;

use Exporter\Writer\JsonWriter;

class JsonWriterTest extends \PHPUnit_Framework_TestCase
{
    protected $filename;

    public function setUp()
    {
        $this->filename = 'foobar.json';

        if (is_file($this->filename)) {
            unlink($this->filename);
        }
    }

    public function testWrite()
    {
        $writer = new JsonWriter($this->filename, ',', '');
        $writer->open();

        $writer->write(array('john "2', 'doe', '1'));
        $writer->write(array('john 3', 'doe', '1'));

        $writer->close();

        $expected = '[["john \"2","doe","1"],["john 3","doe","1"]]';
        $content = file_get_contents($this->filename);

        $this->assertEquals($expected, $content);

        $expected = array(
            array('john "2', 'doe', '1'),
            array('john 3', 'doe', '1')
        );

        $this->assertEquals($expected, json_decode($content, false));
    }

    public function tearDown()
    {
        unlink($this->filename);
    }
}
