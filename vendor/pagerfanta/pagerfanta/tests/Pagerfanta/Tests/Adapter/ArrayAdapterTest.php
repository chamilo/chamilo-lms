<?php

namespace Pagerfanta\Tests\Adapter;

use Pagerfanta\Adapter\ArrayAdapter;

class ArrayAdapterTest extends \PHPUnit_Framework_TestCase
{
    private $array;
    private $adapter;

    protected function setUp()
    {
        $this->array = range(1, 100);
        $this->adapter = new ArrayAdapter($this->array);
    }

    public function testGetArray()
    {
        $this->assertSame($this->array, $this->adapter->getArray());
    }

    public function testGetNbResults()
    {
        $this->assertSame(100, $this->adapter->getNbResults());
    }

    /**
     * @dataProvider getResultsProvider
     */
    public function testGetResults($offset, $length)
    {
        $expected = array_slice($this->array, $offset, $length);

        $this->assertSame($expected, $this->adapter->getSlice($offset, $length));
    }

    public function getResultsProvider()
    {
        return array(
            array(2, 10),
            array(3, 2),
        );
    }
}
