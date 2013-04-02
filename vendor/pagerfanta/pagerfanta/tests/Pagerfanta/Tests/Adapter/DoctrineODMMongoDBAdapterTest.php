<?php

namespace Pagerfanta\Tests\Adapter;

use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;

class DoctrineODMMongoDBAdapterTest extends \PHPUnit_Framework_TestCase
{
    private $queryBuilder;
    private $query;
    private $adapter;

    protected function setUp()
    {
        if ($this->isDoctrineMongoNotAvailable()) {
            $this->markTestSkipped('Doctrine MongoDB is not available');
        }

        $this->queryBuilder = $this->createQueryBuilderMock();
        $this->query = $this->createQueryMock();

        $this->adapter = new DoctrineODMMongoDBAdapter($this->queryBuilder);
    }

    private function isDoctrineMongoNotAvailable()
    {
        return !class_exists('Doctrine\ODM\MongoDB\Query\Builder');
    }

    private function createQueryBuilderMock()
    {
        return $this
            ->getMockBuilder('Doctrine\ODM\MongoDB\Query\Builder')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function createQueryMock()
    {
        return $this
            ->getMockBuilder('Doctrine\ODM\MongoDB\Query\Query')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetQueryBuilder()
    {
        $this->assertSame($this->queryBuilder, $this->adapter->getQueryBuilder());
    }

    public function testGetNbResultsShouldCreateTheQueryAndCount()
    {
        $this->queryBuilder
            ->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($this->query));
        $this->query
            ->expects($this->once())
            ->method('count')
            ->will($this->returnValue(110));

        $this->assertSame(110, $this->adapter->getNbResults());
    }

    public function testGetSlice()
    {
        $offset = 10;
        $length = 15;
        $slice = new \ArrayIterator();

        $this->queryBuilder
            ->expects($this->once())
            ->method('limit')
            ->with($length)
            ->will($this->returnValue($this->queryBuilder))
        ;
        $this->queryBuilder
            ->expects($this->once())
            ->method('skip')
            ->with($offset)
            ->will($this->returnValue($this->queryBuilder))
        ;
        $this->queryBuilder
            ->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($this->query))
        ;
        $this->query
            ->expects($this->once())
            ->method('execute')
            ->will($this->returnValue($slice))
        ;

        $this->assertSame($slice, $this->adapter->getSlice($offset, $length));
    }
}
