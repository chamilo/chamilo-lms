<?php

namespace Ddeboer\DataImport\Tests\Reader;

use Ddeboer\DataImport\Reader\CountableIteratorReader;

/**
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class CountableIteratorReaderTest extends \PHPUnit_Framework_TestCase
{
    public function testCount()
    {
        $iterator = new \ArrayIterator([
            [
                'id'       => 1,
                'username' => 'john.doe',
                'name'     => 'John Doe',
            ],
        ]);

        $reader = new CountableIteratorReader($iterator);

        // We need to rewind the iterator
        $reader->rewind();

        $this->assertEquals(1, $reader->count());
    }

    public function testIteratorCount()
    {
        $reader = new CountableIteratorReader(new CountableIterator);

        // We need to rewind the iterator
        $reader->rewind();

        $this->assertEquals(0, $reader->count());
    }
}

class CountableIterator extends \EmptyIterator
{

}
