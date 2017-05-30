<?php

namespace Ddeboer\DataImport\Tests\Reader;

use Ddeboer\DataImport\Reader\IteratorReader;

/**
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class IteratorReaderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetFields()
    {
        $iterator = new \ArrayIterator([
            [
                'id'       => 1,
                'username' => 'john.doe',
                'name'     => 'John Doe',
            ],
        ]);

        $reader = new IteratorReader($iterator);

        // We need to rewind the iterator
        $reader->rewind();

        $fields = $reader->getFields();

        $this->assertInternalType('array', $fields);
        $this->assertEquals(['id', 'username', 'name'], $fields);
    }
}
