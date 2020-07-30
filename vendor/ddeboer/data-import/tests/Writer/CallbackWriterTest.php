<?php

namespace Ddeboer\DataImport\Tests\Writer;

use Ddeboer\DataImport\Writer\CallbackWriter;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class CallbackWriterTest extends \PHPUnit_Framework_TestCase
{
    public function testPrepare()
    {
        $callable = function(array $item) {
            return '';
        };

        $writer = new CallbackWriter($callable);
        $writer->prepare();
    }

    public function testWriteItem()
    {
        $string = '';
        $callable = function(array $item) use (&$string) {
            $string = implode(',', array_values($item));
        };

        $writer = new CallbackWriter($callable);
        $writer->writeItem(array('foo' => 'bar', 'bar' => 'foo'));

        $this->assertEquals('bar,foo', $string);
    }

    public function testFinish()
    {
        $callable = function(array $item) {
            return '';
        };

        $writer = new CallbackWriter($callable);
        $writer->finish();
    }
}
