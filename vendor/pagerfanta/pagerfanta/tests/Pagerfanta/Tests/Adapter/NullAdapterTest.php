<?php

namespace Pagerfanta\Tests\Adapter;

use Pagerfanta\Adapter\NullAdapter;

class NullAdapterTest extends \PHPUnit_Framework_TestCase
{
    public function testGetNbResults()
    {
        $adapter = new NullAdapter(33);
        $this->assertSame(33, $adapter->getNbResults());
    }

    public function testGetSliceShouldReturnAnEmptyArrayIfTheOffsetIsEqualThanTheNbResults()
    {
        $adapter = new NullAdapter(10);
        $this->assertSame(array(), $adapter->getSlice(10, 5));
    }

    public function testGetSliceShouldReturnAnEmptyArrayIfTheOffsetIsGreaterThanTheNbResults()
    {
        $adapter = new NullAdapter(10);
        $this->assertSame(array(), $adapter->getSlice(11, 5));
    }

    public function testGetSliceShouldReturnANullArrayWithTheLengthPassed()
    {
        $adapter = new NullAdapter(100);
        $this->assertSame($this->createNullArray(10), $adapter->getSlice(20, 10));
    }

    public function testGetSliceShouldReturnANullArrayWithTheRemainCountWhenLengthIsGreaterThanTheRemain()
    {
        $adapter = new NullAdapter(33);
        $this->assertSame($this->createNullArray(3), $adapter->getSlice(30, 10));
    }

    private function createNullArray($length)
    {
        return array_fill(0, $length, null);
    }
}
