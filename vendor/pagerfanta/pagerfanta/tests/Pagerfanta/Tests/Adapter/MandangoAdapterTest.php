<?php

namespace Pagerfanta\Tests\Adapter;

use Pagerfanta\Adapter\MandangoAdapter;

class MandangoAdapterTest extends \PHPUnit_Framework_TestCase
{
    private $query;
    private $adapter;

    protected function setUp()
    {
        if ($this->isMandangoNotAvailable()) {
            $this->markTestSkipped('Mandango is not available');
        }

        $this->query = $this->createQueryMock();
        $this->adapter = new MandangoAdapter($this->query);
    }

    private function isMandangoNotAvailable()
    {
        return !class_exists('Mandango\Query');
    }

    private function createQueryMock()
    {
        return $this
            ->getMockBuilder('Mandango\Query')
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
            ->method('count')
            ->will($this->returnValue(100))
        ;

        $this->assertSame(100, $this->adapter->getNbResults());
    }

    public function testGetResults()
    {
        $offset = 14;
        $length = 30;
        $slice = new \ArrayObject();

        $this->prepareQuerySkip($offset);
        $this->prepareQueryLimit($length);
        $this->prepareQueryAll($slice);

        $this->assertSame($slice, $this->adapter->getSlice($offset, $length));
    }

    private function prepareQueryLimit($limit)
    {
        $this->query
            ->expects($this->once())
            ->method('limit')
            ->with($limit)
            ->will($this->returnValue($this->query));
    }

    private function prepareQuerySkip($skip)
    {
        $this->query
            ->expects($this->once())
            ->method('skip')
            ->with($skip)
            ->will($this->returnValue($this->query));
    }

    private function prepareQueryAll($all)
    {
        $this->query
            ->expects($this->once())
            ->method('all')
            ->will($this->returnValue($all));
    }
}
