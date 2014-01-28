<?php

namespace Pagerfanta\Tests\Adapter;

use Doctrine\Common\Collections\ArrayCollection;
use Pagerfanta\Adapter\DoctrineODMPhpcrAdapter;

class DoctrineODMPhpcrAdapterTest extends \PHPUnit_Framework_TestCase
{
    private $queryBuilder;
    private $query;
    private $adapter;

    protected function setUp()
    {
        if ($this->isDoctrinePhpcrNotAvailable()) {
            $this->markTestSkipped('Doctrine PHPCR-ODM is not available');
        }

        $this->queryBuilder = $this->createQueryBuilderMock();
        $this->query = $this->createQueryMock();

        $this->adapter = new DoctrineODMPhpcrAdapter($this->queryBuilder);
    }

    private function isDoctrinePhpcrNotAvailable()
    {
        return !class_exists('Doctrine\ODM\Phpcr\Query\QueryBuilder');
    }

    private function createQueryBuilderMock()
    {
        return $this
            ->getMockBuilder('Doctrine\ODM\Phpcr\Query\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function createQueryMock()
    {
        return $this
            ->getMockBuilder('Doctrine\ODM\Phpcr\Query\Query')
            ->disableOriginalConstructor()
            ->getMock()
        ;
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
            ->will($this->returnValue($this->query))
        ;
        $this->query
            ->expects($this->once())
            ->method('execute')
            ->will($this->returnValue(new ArrayCollection(array(1, 2, 3 , 4, 5, 6))))
        ;

        $this->assertSame(6, $this->adapter->getNbResults());
    }

    public function testGetSlice()
    {
        $offset = 10;
        $length = 15;
        $slice = new \ArrayIterator();

        $this->queryBuilder
            ->expects($this->once())
            ->method('setMaxResults')
            ->with($length)
            ->will($this->returnValue($this->queryBuilder))
        ;
        $this->queryBuilder
            ->expects($this->once())
            ->method('setFirstResult')
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
