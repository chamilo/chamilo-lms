<?php

namespace Pagerfanta\Tests\Adapter;

use Pagerfanta\Adapter\PropelAdapter;

/**
 * PropelAdapterTest
 *
 * @author William DURAND <william.durand1@gmail.com>
 */
class PropelAdapterTest extends \PHPUnit_Framework_TestCase
{
    private $query;
    private $adapter;

    protected function setUp()
    {
        if ($this->isPropelNotAvaiable()) {
            $this->markTestSkipped('Propel is not available');
        }

        $this->query = $this->createQueryMock();
        $this->adapter = new PropelAdapter($this->query);
    }

    private function isPropelNotAvaiable()
    {
        return !class_exists('ModelCriteria');
    }

    private function createQueryMock()
    {
        return $this
            ->getMockBuilder('ModelCriteria')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetQuery()
    {
        $this->assertSame($this->query, $this->adapter->getQuery());
    }

    public function testGetNbResults()
    {
        $this->query
            ->expects($this->once())
            ->method('limit')
            ->with(0);
        $this->query
            ->expects($this->once())
            ->method('offset')
            ->with(0);
        $this->query
            ->expects($this->once())
            ->method('count')
            ->will($this->returnValue(100));

        $this->assertSame(100, $this->adapter->getNbResults());
    }

    public function testGetSlice()
    {
        $offset = 14;
        $length = 20;
        $slice = new \ArrayObject();

        $this->query
            ->expects($this->once())
            ->method('limit')
            ->with($length);
        $this->query
            ->expects($this->once())
            ->method('offset')
            ->with($offset);
        $this->query
            ->expects($this->once())
            ->method('find')
            ->will($this->returnValue($slice));

        $this->assertSame($slice, $this->adapter->getSlice($offset, $length));
    }
}

