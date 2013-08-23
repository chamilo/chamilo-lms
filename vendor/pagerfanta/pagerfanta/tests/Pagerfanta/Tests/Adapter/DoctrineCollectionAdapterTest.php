<?php

namespace Pagerfanta\Tests\Adapter;

use Pagerfanta\Adapter\DoctrineCollectionAdapter;

class DoctrineCollectionTest extends \PHPUnit_Framework_TestCase
{
    private $collection;
    private $adapter;

    protected function setUp()
    {
        if ($this->collectionIsNotAvailable()) {
            $this->markTestSkipped('Doctrine Collection is not available');
        }

        $this->collection = $this->createCollectionMock();
        $this->adapter = new DoctrineCollectionAdapter($this->collection);
    }

    private function collectionIsNotAvailable()
    {
        return !interface_exists($this->getCollectionInterface());
    }

    private function createCollectionMock()
    {
        return $this
            ->getMockBuilder($this->getCollectionInterface())
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getCollectionInterface()
    {
        return 'Doctrine\Common\Collections\Collection';
    }

    public function testGetCollectionShouldReturnTheCollection()
    {
        $this->assertSame($this->collection, $this->adapter->getCollection());
    }

    public function testGetNbResultsShouldResultTheCollectionCount()
    {
        $this->collection
            ->expects($this->once())
            ->method('count')
            ->will($this->returnValue(120));

        $this->assertSame(120, $this->adapter->getNbResults());
    }

    public function testGetResultsShouldReturnTheCollectionSliceReturnValue()
    {
        $results = new \ArrayObject();
        $this->collection
            ->expects($this->once())
            ->method('slice')
            ->will($this->returnValue($results));

        $this->assertSame($results, $this->adapter->getSlice(1, 1));
    }

    public function testGetResultsShouldPassTheOffsetAndLengthToTheCollectionSlice()
    {
        $this->collection
            ->expects($this->once())
            ->method('slice')
            ->with(5, 12);

        $this->adapter->getSlice(5, 12);
    }
}
