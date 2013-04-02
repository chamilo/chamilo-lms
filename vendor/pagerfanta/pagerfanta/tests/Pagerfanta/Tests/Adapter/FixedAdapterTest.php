<?php

namespace Pagerfanta\Tests\Adapter;

use Pagerfanta\Adapter\FixedAdapter;

class FixedAdapterTest extends \PHPUnit_Framework_TestCase
{
    public function testgetNbResults()
    {
        $adapter = new FixedAdapter(5, array());
        $this->assertSame(5, $adapter->getNbResults());
    }

    /**
     * @dataProvider getSliceProvider
     */
    public function testGetSlice($results)
    {
        $adapter = new FixedAdapter(5, $results);
        $this->assertSame($results, $adapter->getSlice(0, 10));
        $this->assertSame($results, $adapter->getSlice(10, 20));
    }

    public function getSliceProvider()
    {
        return array(
            array(array('a', 'b')),
            array(new \stdClass()),
        );
    }
}
