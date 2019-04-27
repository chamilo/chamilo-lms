<?php

namespace Ddeboer\DataImport\Tests\Writer;

use Ddeboer\DataImport\Writer\AbstractStreamWriter;

abstract class StreamWriterTest extends \PHPUnit_Framework_TestCase
{
    protected $stream;

    /** @var AbstractStreamWriter */
    protected $writer;

    protected function tearDown()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
            $this->stream = null;
        }
    }

    protected function getStream()
    {
        if (!is_resource($this->stream)) {
            $this->stream = fopen('php://temp', 'r+');
        }

        return $this->stream;
    }

    /**
     * @param string               $expected
     * @param AbstractStreamWriter $actual
     * @param string               $message
     */
    public static function assertContentsEquals($expected, $actual, $message = '')
    {
        $stream = $actual->getStream();
        rewind($stream);
        $actual = stream_get_contents($stream);

        self::assertEquals($expected, $actual, $message);
    }
}
